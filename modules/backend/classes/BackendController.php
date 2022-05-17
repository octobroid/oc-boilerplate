<?php namespace Backend\Classes;

use Str;
use App;
use File;
use View;
use Event;
use System;
use Response;
use Illuminate\Routing\Controller as ControllerBase;
use October\Rain\Router\Helper as RouterHelper;
use System\Classes\PluginManager;
use Closure;

/**
 * BackendController is the master controller for all back-end pages.
 * All requests that are prefixed with the backend URI pattern are sent here,
 * then the next URI segments are analysed and the request is routed to the
 * relevant back-end controller.
 *
 * For example, a request with the URL `/backend/acme/blog/posts` will look
 * for the `Posts` controller inside the `Acme.Blog` plugin.
 *
 * @see Backend\Classes\Controller Base class for back-end controllers
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class BackendController extends ControllerBase
{
    use \October\Rain\Extension\ExtendableTrait;

    /**
     * @var array Behaviors implemented by this controller.
     */
    public $implement;

    /**
     * @var string Allows early access to page action.
     */
    public static $action;

    /**
     * @var array Allows early access to page parameters.
     */
    public static $params;

    /**
     * __construct a new BackendController instance
     */
    public function __construct()
    {
        $this->extendableConstruct();
    }

    /**
     * extend this object properties upon construction
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * runCmsPage passses unhandled URLs to the CMS Controller, if it exists
     */
    protected function runCmsPage($url)
    {
        if (System::hasModule('Cms')) {
            return App::make(\Cms\Classes\Controller::class)->run($url);
        }
        else {
            return Response::make(View::make('backend::404'), 404);
        }
    }

    /**
     * run finds and serves the requested backend controller
     * If the controller cannot be found, returns the Cms page with the URL /404.
     * If the /404 page doesn't exist, returns the system 404 page.
     * @param string $url Specifies the requested page URL.
     * If the parameter is omitted, the current URL used.
     * @return string Returns the processed page content.
     */
    public function run($url = null)
    {
        $params = RouterHelper::segmentizeUrl($url);

        // Handle NotFoundHttpExceptions in the backend (usually triggered by abort(404))
        Event::listen('exception.beforeRender', function ($exception, $httpCode, $request) {
            if (!System::hasModule('Cms') && $exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return View::make('backend::404');
            }
        }, 1);

        /*
         * Database check
         */
        if (!App::hasDatabase()) {
            return System::checkDebugMode()
                ? Response::make(View::make('backend::no_database'), 200)
                : $this->runCmsPage($url);
        }

        /*
         * Look for a Module controller
         */
        $module = $params[0] ?? 'backend';
        $controller = $params[1] ?? 'index';
        self::$action = $action = isset($params[2]) ? $this->parseAction($params[2]) : 'index';
        self::$params = $controllerParams = array_slice($params, 3);
        $controllerClass = '\\'.$module.'\Controllers\\'.$controller;
        if ($controllerObj = $this->findController(
            $controllerClass,
            $action,
            base_path().'/modules'
        )) {
            return $controllerObj->run($action, $controllerParams);
        }

        /*
         * Look for a Plugin controller
         */
        if (count($params) >= 2) {
            [$author, $plugin] = $params;

            $pluginCode = ucfirst($author) . '.' . ucfirst($plugin);
            if (PluginManager::instance()->isDisabled($pluginCode)) {
                return Response::make(View::make('backend::404'), 404);
            }

            $controller = $params[2] ?? 'index';
            self::$action = $action = isset($params[3]) ? $this->parseAction($params[3]) : 'index';
            self::$params = $controllerParams = array_slice($params, 4);
            $controllerClass = '\\'.$author.'\\'.$plugin.'\Controllers\\'.$controller;
            if ($controllerObj = $this->findController(
                $controllerClass,
                $action,
                plugins_path()
            )) {
                return $controllerObj->run($action, $controllerParams);
            }
        }

        /*
         * Fall back to CMS controller
         */
        return $this->runCmsPage($url);
    }

    /**
     * findController is used internally to find a backend controller with a
     * callable action method
     * @param string $controller Specifies a method name to execute.
     * @param string $action Specifies a method name to execute.
     * @param string $inPath Base path for class file location.
     * @return ControllerBase Returns the backend controller object
     */
    protected function findController($controller, $action, $inPath)
    {
        /*
         * Workaround: Composer does not support case insensitivity.
         */
        if (!class_exists($controller)) {
            $controller = Str::normalizeClassName($controller);
            $controllerFile = $inPath.strtolower(str_replace('\\', '/', $controller)) . '.php';
            if (
                strpos($controllerFile, '..') !== false ||
                strpos($controllerFile, './') !== false ||
                strpos($controllerFile, '//') !== false
            ) {
                return false;
            }

            if ($controllerFile = File::existsInsensitive($controllerFile)) {
                include_once $controllerFile;
            }
        }

        if (!class_exists($controller)) {
            return false;
        }

        $controllerObj = App::make($controller);

        if ($controllerObj->actionExists($action)) {
            return $controllerObj;
        }

        return false;
    }

    /**
     * parseAction processes the action name, since dashes are not supported in PHP methods
     */
    protected function parseAction(string $actionName): string
    {
        if (strpos($actionName, '-') !== false) {
            return snake_case(camel_case($actionName));
        }

        return $actionName;
    }
}
