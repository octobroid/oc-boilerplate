<?php namespace Cms\Classes;

use Cms;
use Url;
use App;
use View;
use Lang;
use Flash;
use Config;
use System;
use Session;
use Request;
use Response;
use Exception;
use BackendAuth;
use Twig\Environment as TwigEnvironment;
use Twig\Cache\FilesystemCache as TwigCacheFilesystem;
use Twig\Extension\SandboxExtension;
use Cms\Twig\Loader as TwigLoader;
use Cms\Twig\DebugExtension;
use Cms\Twig\Extension as CmsTwigExtension;
use Cms\Models\MaintenanceSetting;
use System\Models\RequestLog;
use System\Helpers\View as ViewHelper;
use System\Twig\Extension as SystemTwigExtension;
use October\Rain\Exception\AjaxException;
use October\Rain\Exception\ValidationException;
use October\Rain\Parse\Bracket as TextParser;
use Illuminate\Http\RedirectResponse;

/**
 * The CMS controller class.
 * The controller finds and serves requested pages.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class Controller
{
    use \Cms\Traits\ThemeAssetMaker;
    use \Cms\Traits\ParsableController;
    use \System\Traits\EventEmitter;
    use \System\Traits\ResponseMaker;
    use \System\Traits\SecurityController;

    /**
     * @var \Cms\Classes\Theme A reference to the CMS theme processed by the controller.
     */
    protected $theme;

    /**
     * @var \Cms\Classes\Router A reference to the Router object.
     */
    protected $router;

    /**
     * @var \Cms\Twig\Loader A reference to the Twig template loader.
     */
    protected $loader;

    /**
     * @var \Cms\Classes\Page A reference to the CMS page template being processed.
     */
    protected $page;

    /**
     * @var \Cms\Classes\CodeBase A reference to the CMS page code section object.
     */
    protected $pageObj;

    /**
     * @var \Cms\Classes\Layout A reference to the CMS layout template used by the page.
     */
    protected $layout;

    /**
     * @var \Cms\Classes\CodeBase A reference to the CMS layout code section object.
     */
    protected $layoutObj;

    /**
     * @var TwigEnvironment Keeps the Twig environment object.
     */
    protected $twig;

    /**
     * @var string Contains the rendered page contents string.
     */
    protected $pageContents;

    /**
     * @var array A list of variables to pass to the page.
     */
    public $vars = [];

    /**
     * @var self Cache of self
     */
    protected static $instance;

    /**
     * @var \Cms\Classes\ComponentBase Object of the active component, used internally.
     */
    protected $componentContext;

    /**
     * @var \Cms\Classes\PartialStack Component partial stack, used internally.
     */
    protected $partialStack;

    /**
     * __construct the controller.
     * @param \Cms\Classes\Theme $theme Specifies the CMS theme.
     * If the theme is not specified, the current active theme used.
     */
    public function __construct($theme = null)
    {
        $this->theme = $theme ?: Theme::getActiveTheme();
        if (!$this->theme) {
            throw new CmsException(Lang::get('cms::lang.theme.active.not_found'));
        }

        $this->assetPath = $this->getThemeAssetPath();
        $this->assetLocalPath = $this->theme->getPath();
        $this->router = new Router($this->theme);
        $this->partialStack = new PartialStack;
        $this->initTwigEnvironment();

        self::$instance = $this;
    }

    /**
     * run finds and serves the requested page.
     * If the page cannot be found, returns the page with the URL /404.
     * If the /404 page doesn't exist, returns the system 404 page.
     * If the parameter is null, the current URL used. If it is not
     * provided then '/' is used
     *
     * @param string|null $url Specifies the requested page URL.
     * @return BaseResponse Returns the response to the provided URL
     */
    public function run($url = '/')
    {
        if ($url === null) {
            $url = Request::path();
        }

        if (trim($url) === '') {
            $url = '/';
        }

        /*
         * Hidden page
         */
        $page = $this->router->findByUrl($url);
        if ($page && $page->is_hidden && !BackendAuth::getUser()) {
            $page = null;
        }

        /*
         * Maintenance mode
         */
        if (MaintenanceSetting::isEnabled()) {
            if (!Request::ajax()) {
                $this->setStatusCode(503);
            }

            $page = Page::loadCached($this->theme, MaintenanceSetting::get('cms_page'));
        }

        /**
         * @event cms.page.beforeDisplay
         * Provides an opportunity to swap the page that gets displayed immediately after loading the page assigned to the URL.
         *
         * Example usage:
         *
         *     Event::listen('cms.page.beforeDisplay', function ((\Cms\Classes\Controller) $controller, (string) $url, (\Cms\Classes\Page) $page) {
         *         if ($url === '/tricked-you') {
         *             return \Cms\Classes\Page::loadCached('trick-theme-code', 'page-file-name');
         *         }
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.beforeDisplay', function ((string) $url, (\Cms\Classes\Page) $page) {
         *         if ($url === '/tricked-you') {
         *             return \Cms\Classes\Page::loadCached('trick-theme-code', 'page-file-name');
         *         }
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.beforeDisplay', [$url, $page])) {
            if ($event instanceof Page) {
                $page = $event;
            }
            else {
                return $event;
            }
        }

        /*
         * If the page was not found, render the 404 page - either provided by the theme or the built-in one.
         */
        if (!$page || $url === '404' || ($url === 'error' && !Config::get('app.debug', false))) {
            if (!Request::ajax()) {
                $this->setStatusCode(404);
            }

            // Log the 404 request
            if (!App::runningUnitTests()) {
                RequestLog::add();
            }

            if (!$page = $this->router->findByUrl('/404')) {
                return Response::make(View::make('cms::404'), $this->statusCode);
            }
        }

        /*
         * Run the page
         */
        $result = $this->runPage($page);

        /*
         * Post-processing
         */
        $result = $this->postProcessResult($page, $url, $result);

        /**
         * @event cms.page.display
         * Provides an opportunity to modify the response after the page for the URL has been processed. `$result` could be a string representing the HTML to be returned or it could be a Response instance.
         *
         * Example usage:
         *
         *     Event::listen('cms.page.display', function ((\Cms\Classes\Controller) $controller, (string) $url, (\Cms\Classes\Page) $page, (mixed) $result) {
         *         if ($url === '/tricked-you') {
         *             return Response::make('Boo!', 200);
         *         }
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.display', function ((string) $url, (\Cms\Classes\Page) $page, (mixed) $result) {
         *         if ($url === '/tricked-you') {
         *             return Response::make('Boo!', 200);
         *         }
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.display', [$url, $page, $result])) {
            $result = $event;
        }

        /*
         * Prepare and return response
         * @see \System\Traits\ResponseMaker
         */
        return $this->makeResponse($result);
    }

    /**
     * render a page in its entirety, including component initialization.
     * AJAX will be disabled for this process.
     * @param string $pageFile Specifies the CMS page file name to run.
     * @param array  $parameters  Routing parameters.
     * @param \Cms\Classes\Theme  $theme  Theme object
     */
    public static function render($pageFile, $parameters = [], $theme = null)
    {
        if (!$theme && (!$theme = Theme::getActiveTheme())) {
            throw new CmsException(Lang::get('cms::lang.theme.active.not_found'));
        }

        $controller = new static($theme);
        $controller->getRouter()->setParameters($parameters);

        if (($page = Page::load($theme, $pageFile)) === null) {
            throw new CmsException(Lang::get('cms::lang.page.not_found_name', ['name'=>$pageFile]));
        }

        return $controller->runPage($page, false);
    }

    /**
     * runPage runs a page directly from its object and supplied parameters.
     * @param \Cms\Classes\Page $page Specifies the CMS page to run.
     * @return string
     */
    public function runPage($page, $useAjax = true)
    {
        $this->page = $page;

        /*
         * If the page doesn't refer any layout, create the fallback layout.
         * Otherwise load the layout specified in the page.
         */
        if (!$page->layout) {
            $layout = Layout::initFallback($this->theme);
        }
        elseif (($layout = Layout::loadCached($this->theme, $page->layout)) === null) {
            throw new CmsException(Lang::get('cms::lang.layout.not_found_name', ['name'=>$page->layout]));
        }

        $this->layout = $layout;

        /*
         * The 'this' variable is reserved for default variables.
         */
        $this->vars['this'] = [
            'page' => $this->page,
            'layout' => $this->layout,
            'theme' => $this->theme,
            'param' => $this->router->getParameters(),
            'controller' => $this,
            'environment' => App::environment(),
            'session' => App::make('session')
        ];

        /*
         * Check for validation errors and old input in the session.
         */
        $this->vars['errors'] = (Config::get('session.driver') && Session::has('errors'))
            ? Session::get('errors')
            : new \Illuminate\Support\ViewErrorBag;

        $this->vars['oldInput'] = (Config::get('session.driver') && Session::hasOldInput())
            ? Session::getOldInput()
            : array_get($this->vars, 'oldInput', []);

        /*
         * Handle AJAX requests and execute the life cycle functions
         */
        $this->initCustomObjects();

        $this->initComponents();

        /*
         * Give the layout and page an opportunity to participate
         * after components are initialized and before AJAX is handled.
         */
        if ($this->layoutObj) {
            CmsException::mask($this->layout, 300);
            $this->layoutObj->onInit();
            CmsException::unmask();
        }

        CmsException::mask($this->page, 300);
        $this->pageObj->onInit();
        CmsException::unmask();

        /**
         * @event cms.page.init
         * Provides an opportunity to return a custom response from Controller->runPage() before AJAX handlers are executed
         *
         * Example usage:
         *
         *     Event::listen('cms.page.init', function ((\Cms\Classes\Controller) $controller, (\Cms\Classes\Page) $page) {
         *         return \Cms\Classes\Page::loadCached('trick-theme-code', 'page-file-name');
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.init', function ((\Cms\Classes\Page) $page) {
         *         return \Cms\Classes\Page::loadCached('trick-theme-code', 'page-file-name');
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.init', [$page])) {
            return $event;
        }

        /*
         * Execute AJAX event
         */
        if ($useAjax && $ajaxResponse = $this->execAjaxHandlers()) {
            return $ajaxResponse;
        }

        /*
         * Execute postback handler
         */
        if ($useAjax && $handlerResponse = $this->execPostbackHandler()) {
            return $handlerResponse;
        }

        /*
         * Execute page lifecycle
         */
        if ($cycleResponse = $this->execPageCycle()) {
            return $cycleResponse;
        }

        /*
         * Parse dynamic attributes on templates and components
         */
        $this->parseAllEnvironmentVars();

        /**
         * @event cms.page.beforeRenderPage
         * Fires after AJAX handlers are dealt with and provides an opportunity to modify the page contents
         *
         * Example usage:
         *
         *     Event::listen('cms.page.beforeRenderPage', function ((\Cms\Classes\Controller) $controller, (\Cms\Classes\Page) $page) {
         *         return 'Custom page contents';
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.beforeRenderPage', function ((\Cms\Classes\Page) $page) {
         *         return 'Custom page contents';
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.beforeRenderPage', [$this->page])) {
            $this->pageContents = $event;
        }
        else {
            /*
             * Render the page
             */
            CmsException::mask($this->page, 400);
            $this->loader->setObject($this->page);
            $template = $this->twig->loadTemplate($this->page->getFilePath());
            $this->pageContents = $template->render($this->vars);
            CmsException::unmask();
        }

        /*
         * Render the layout
         */
        CmsException::mask($this->layout, 400);
        $this->loader->setObject($this->layout);
        $template = $this->twig->loadTemplate($this->layout->getFilePath());
        $result = $template->render($this->vars);
        CmsException::unmask();

        return $result;
    }

    /**
     * Invokes the current page cycle without rendering the page,
     * used by AJAX handler that may rely on the logic inside the action.
     */
    public function pageCycle()
    {
        return $this->execPageCycle();
    }

    /**
     * Executes the page life cycle.
     * Creates an object from the PHP sections of the page and
     * it's layout, then executes their life cycle functions.
     */
    protected function execPageCycle()
    {
        /**
         * @event cms.page.start
         * Fires before all of the page & layout lifecycle handlers are run
         *
         * Example usage:
         *
         *     Event::listen('cms.page.start', function ((\Cms\Classes\Controller) $controller) {
         *         return Response::make('Taking over the lifecycle!', 200);
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.start', function () {
         *         return Response::make('Taking over the lifecycle!', 200);
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.start')) {
            return $event;
        }

        /*
         * Run layout functions
         */
        if ($this->layoutObj) {
            CmsException::mask($this->layout, 300);
            $response = (
                ($result = $this->layoutObj->onStart()) ||
                ($result = $this->layout->runComponents()) ||
                ($result = $this->layoutObj->onBeforePageStart())
            ) ? $result : null;
            CmsException::unmask();

            if ($response) {
                return $response;
            }
        }

        /*
         * Run page functions
         */
        CmsException::mask($this->page, 300);
        $response = (
            ($result = $this->pageObj->onStart()) ||
            ($result = $this->page->runComponents()) ||
            ($result = $this->pageObj->onEnd())
        ) ? $result : null;
        CmsException::unmask();

        if ($response) {
            return $response;
        }

        /*
         * Run remaining layout functions
         */
        if ($this->layoutObj) {
            CmsException::mask($this->layout, 300);
            $response = ($result = $this->layoutObj->onEnd()) ? $result : null;
            CmsException::unmask();
        }

        /**
         * @event cms.page.end
         * Fires after all of the page & layout lifecycle handlers are run
         *
         * Example usage:
         *
         *     Event::listen('cms.page.end', function ((\Cms\Classes\Controller) $controller) {
         *         return Response::make('Taking over the lifecycle!', 200);
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.end', function () {
         *         return Response::make('Taking over the lifecycle!', 200);
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.end')) {
            return $event;
        }

        return $response;
    }

    /**
     * Post-processes page HTML code before it's sent to the client.
     * Note for pre-processing see cms.template.processTwigContent event.
     * @param \Cms\Classes\Page $page Specifies the current CMS page.
     * @param string $url Specifies the current URL.
     * @param string $content The page markup to post-process.
     * @return string Returns the updated result string.
     */
    protected function postProcessResult($page, $url, $content)
    {
        $content = MediaViewHelper::instance()->processHtml($content);

        /**
         * @event cms.page.postprocess
         * Provides opportunity to hook into the post-processing of page HTML code before being sent to the client. `$dataHolder` = {content: $htmlContent}
         *
         * Example usage:
         *
         *     Event::listen('cms.page.postprocess', function ((\Cms\Classes\Controller) $controller, (string) $url, (\Cms\Classes\Page) $page, (object) $dataHolder) {
         *         $dataHolder->content = str_replace('<a href=', '<a rel="nofollow" href=', $dataHolder->content);
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.postprocess', function ((string) $url, (\Cms\Classes\Page) $page, (object) $dataHolder) {
         *         $dataHolder->content = 'My custom content';
         *     });
         *
         */
        $dataHolder = (object) ['content' => $content];
        $this->fireSystemEvent('cms.page.postprocess', [$url, $page, $dataHolder]);

        return $dataHolder->content;
    }

    //
    // Initialization
    //

    /**
     * Initializes the Twig environment and loader.
     * Registers the \Cms\Twig\Extension object with Twig.
     * @return void
     */
    protected function initTwigEnvironment()
    {
        $useCache = Config::get('cms.enable_twig_cache', true);
        $isDebugMode = System::checkDebugMode();
        $strictVariables = Config::get('cms.strict_variables', false);
        $forceBytecode = Config::get('cms.force_bytecode_invalidation', false);

        $options = [
            'auto_reload' => true,
            'debug' => $isDebugMode,
            'strict_variables' => $strictVariables,
        ];

        if ($useCache) {
            $options['cache'] = new TwigCacheFilesystem(
                storage_path().'/cms/twig',
                $forceBytecode ? TwigCacheFilesystem::FORCE_BYTECODE_INVALIDATION : 0
            );
        }

        $loader = new TwigLoader;
        $twig = new TwigEnvironment($loader, $options);
        $twig->addExtension(new CmsTwigExtension($this));
        $twig->addExtension(new SystemTwigExtension);

        // @deprecated use code below in v3
        if (System::checkSafeMode()) {
            if (env('CMS_SECURITY_POLICY_V2', false)) {
                $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicy, true));
            }
            else {
                $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicyLegacy, true));
            }
        }

        // @deprecated use the main policy in safe mode only
        // if (System::checkSafeMode()) {
        //     if (env('CMS_SECURITY_POLICY_V1', false)) {
        //         $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicyLegacy, true));
        //     }
        //     else {
        //         $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicy, true));
        //     }
        // }

        if ($isDebugMode) {
            $twig->addExtension(new DebugExtension($this));
        }

        $this->loader = $loader;
        $this->twig = $twig;
    }

    /**
     * Initializes the custom layout and page objects.
     * @return void
     */
    protected function initCustomObjects()
    {
        $this->layoutObj = null;

        if (!$this->layout->isFallBack()) {
            CmsException::mask($this->layout, 300);
            $parser = new CodeParser($this->layout);
            $this->layoutObj = $parser->source($this->page, $this->layout, $this);
            CmsException::unmask();
        }

        CmsException::mask($this->page, 300);
        $parser = new CodeParser($this->page);
        $this->pageObj = $parser->source($this->page, $this->layout, $this);
        CmsException::unmask();
    }

    /**
     * Initializes the components for the layout and page.
     * @return void
     */
    protected function initComponents()
    {
        if (!$this->layout->isFallBack()) {
            foreach ($this->layout->settings['components'] as $component => $properties) {
                [$name, $alias] = strpos($component, ' ')
                    ? explode(' ', $component)
                    : [$component, $component];

                $this->addComponent($name, $alias, $properties, true);
            }
        }

        foreach ($this->page->settings['components'] as $component => $properties) {
            [$name, $alias] = strpos($component, ' ')
                ? explode(' ', $component)
                : [$component, $component];

            $this->addComponent($name, $alias, $properties);
        }

        /**
         * @event cms.page.initComponents
         * Fires after the components for the given page have been initialized
         *
         * Example usage:
         *
         *     Event::listen('cms.page.initComponents', function ((\Cms\Classes\Controller) $controller, (\Cms\Classes\Page) $page, (\Cms\Classes\Layout) $layout) {
         *         \Log::info($page->title . ' components have been initialized');
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.initComponents', function ((\Cms\Classes\Page) $page, (\Cms\Classes\Layout) $layout) {
         *         \Log::info($page->title . ' components have been initialized');
         *     });
         *
         */
        $this->fireSystemEvent('cms.page.initComponents', [$this->page, $this->layout]);
    }

    //
    // AJAX
    //

    /**
     * Returns the AJAX handler for the current request, if available.
     * @return string
     */
    public function getAjaxHandler()
    {
        if (!Request::ajax() || Request::method() !== 'POST') {
            return null;
        }

        if ($handler = Request::header('X_OCTOBER_REQUEST_HANDLER')) {
            return trim($handler);
        }

        return null;
    }

    /**
     * execAjaxHandlers executes the page, layout, component and plugin AJAX handlers.
     * @return mixed Returns the AJAX Response object or null.
     */
    protected function execAjaxHandlers()
    {
        if ($handler = $this->getAjaxHandler()) {
            try {
                /*
                 * Validate the handler name
                 */
                if (!preg_match('/^(?:\w+\:{2})?on[A-Z]{1}[\w+]*$/', $handler)) {
                    throw new CmsException(Lang::get('cms::lang.ajax_handler.invalid_name', ['name'=>$handler]));
                }

                /*
                 * Validate the handler partial list
                 */
                if ($partialList = trim(Request::header('X_OCTOBER_REQUEST_PARTIALS'))) {
                    $partialList = explode('&', $partialList);

                    foreach ($partialList as $partial) {
                        if (!Partial::validateRequestName($partial)) {
                            throw new CmsException(Lang::get('cms::lang.partial.invalid_name', ['name'=>$partial]));
                        }
                    }
                }
                else {
                    $partialList = [];
                }

                $responseContents = [];

                /*
                 * Execute the handler
                 */
                if (!$result = $this->runAjaxHandler($handler)) {
                    throw new CmsException(Lang::get('cms::lang.ajax_handler.not_found', ['name'=>$handler]));
                }

                /*
                 * Render partials and return the response as array that will be converted to JSON automatically.
                 */
                foreach ($partialList as $partial) {
                    $responseContents[$partial] = $this->renderPartial($partial);
                }

                /*
                 * If the handler returned a redirect, process the URL and dispose of it so
                 * framework.js knows to redirect the browser and not the request!
                 */
                if ($result instanceof RedirectResponse) {
                    $responseContents['X_OCTOBER_REDIRECT'] = $result->getTargetUrl();
                    $result = null;
                }
                /*
                 * No redirect is used, look for any flash messages
                 */
                elseif (Request::header('X_OCTOBER_REQUEST_FLASH') && Flash::check()) {
                    $responseContents['X_OCTOBER_FLASH_MESSAGES'] = Flash::all();
                }

                /*
                 * If the handler returned an array, we should add it to output for rendering.
                 * If it is a string, add it to the array with the key "result".
                 * If an object, pass it to Laravel as a response object.
                 */
                if (is_array($result)) {
                    $responseContents = array_merge($responseContents, $result);
                }
                elseif (is_string($result)) {
                    $responseContents['result'] = $result;
                }
                elseif (is_object($result)) {
                    return $result;
                }

                return Response::make($responseContents, $this->statusCode);
            }
            catch (ValidationException $ex) {
                /*
                 * Handle validation errors
                 */
                $responseContents['X_OCTOBER_ERROR_FIELDS'] = $ex->getFields();
                $responseContents['X_OCTOBER_ERROR_MESSAGE'] = $ex->getMessage();
                throw new AjaxException($responseContents);
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }

        return null;
    }

    /**
     * execPostbackHandler is used internally to execute a postback version of an
     * AJAX handler.
     */
    protected function execPostbackHandler()
    {
        if (Request::method() !== 'POST') {
            return null;
        }

        $handler = post('_handler');
        if (!$handler) {
            return null;
        }

        if (!$this->verifyCsrfToken()) {
            return null;
        }

        $handlerResponse = $this->runAjaxHandler($handler);
        if ($handlerResponse && $handlerResponse !== true) {
            return $handlerResponse;
        }

        return null;
    }

    /**
     * runAjaxHandler Tries to find and run an AJAX handler in the page, layout, components and plugins.
     * The method stops as soon as the handler is found. It will return the response from the handler,
     * or true if the handler was found. Returns false otherwise.
     */
    protected function runAjaxHandler($handler)
    {
        /**
         * @event cms.ajax.beforeRunHandler
         * Provides an opportunity to modify an AJAX request
         *
         * The parameter provided is `$handler` (the requested AJAX handler to be run)
         *
         * Example usage (forwards AJAX handlers to a backend widget):
         *
         *     Event::listen('cms.ajax.beforeRunHandler', function ((\Cms\Classes\Controller) $controller, (string) $handler) {
         *         if (strpos($handler, '::')) {
         *             [$componentAlias, $handlerName] = explode('::', $handler);
         *             if ($componentAlias === $this->getBackendWidgetAlias()) {
         *                 return $this->backendControllerProxy->runAjaxHandler($handler);
         *             }
         *         }
         *     });
         *
         * Or
         *
         *     $this->controller->bindEvent('ajax.beforeRunHandler', function ((string) $handler) {
         *         if (strpos($handler, '::')) {
         *             [$componentAlias, $handlerName] = explode('::', $handler);
         *             if ($componentAlias === $this->getBackendWidgetAlias()) {
         *                 return $this->backendControllerProxy->runAjaxHandler($handler);
         *             }
         *         }
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.ajax.beforeRunHandler', [$handler])) {
            return $event;
        }

        /*
         * Process Component handler
         */
        if (strpos($handler, '::')) {
            [$componentName, $handlerName] = explode('::', $handler);
            $componentObj = $this->findComponentByName($componentName);

            if ($componentObj && $componentObj->methodExists($handlerName)) {
                $this->componentContext = $componentObj;
                $result = $componentObj->runAjaxHandler($handlerName);
                return $result ?: true;
            }
        }
        /*
         * Process code section handler
         */
        else {
            if (method_exists($this->pageObj, $handler)) {
                $result = $this->pageObj->$handler();
                return $result ?: true;
            }

            if (!$this->layout->isFallBack() && method_exists($this->layoutObj, $handler)) {
                $result = $this->layoutObj->$handler();
                return $result ?: true;
            }

            /*
             * Cycle each component to locate a usable handler
             */
            if (($componentObj = $this->findComponentByHandler($handler)) !== null) {
                $this->componentContext = $componentObj;
                $result = $componentObj->runAjaxHandler($handler);
                return $result ?: true;
            }
        }

        /*
         * Generic handler that does nothing
         */
        if ($handler === 'onAjax') {
            return true;
        }

        return false;
    }

    //
    // Rendering
    //

    /**
     * renderPage renders a requested page.
     * The framework uses this method internally.
     */
    public function renderPage()
    {
        $contents = $this->pageContents;

        /**
         * @event cms.page.render
         * Provides an opportunity to manipulate the page's rendered contents
         *
         * Example usage:
         *
         *     Event::listen('cms.page.render', function ((\Cms\Classes\Controller) $controller, (string) $pageContents) {
         *         return 'My custom contents';
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.render', function ((string) $pageContents) {
         *         return 'My custom contents';
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.render', [&$contents])) {
            return $event;
        }

        return $contents;
    }

    /**
     * loadPartialObject loads a partial for rendering.
     * @return Partial|false
     */
    public function loadPartialObject($name)
    {
        // Alias @ symbol for ::
        if (substr($name, 0, 1) === '@') {
            $name = '::' . substr($name, 1);
        }

        // Process Component partial
        if (strpos($name, '::') !== false) {
            [$componentAlias, $partialName] = explode('::', $name);

            // Component alias not supplied
            if (!strlen($componentAlias)) {
                if ($this->componentContext !== null) {
                    $componentObj = $this->componentContext;
                }
                elseif (($componentObj = $this->findComponentByPartial($partialName)) === null) {
                    return false;
                }
            }
            // Component alias is supplied
            elseif (($componentObj = $this->findComponentByName($componentAlias)) === null) {
                return false;
            }

            $this->componentContext = $componentObj;

            // Check if the theme has an override
            $partial = ComponentPartial::loadOverrideCached($this->theme, $componentObj, $partialName);

            // Check the component partial
            if ($partial === null) {
                $partial = ComponentPartial::loadCached($componentObj, $partialName);
            }

            if ($partial === null) {
                return false;
            }

            // Set context for self access
            $this->vars['__SELF__'] = $componentObj;
        }
        // Process theme partial
        elseif (($partial = Partial::loadCached($this->theme, $name)) === null) {
            return false;
        }

        return $partial;
    }

    /**
     * renderPartial renders a requested partial. The framework uses this method internally.
     * @param string $name The view to load.
     * @param array $parameters Parameter variables to pass to the view.
     * @param bool $throwException Throw an exception if the partial is not found.
     * @return mixed Partial contents or false if not throwing an exception.
     */
    public function renderPartial($name, $parameters = [], $throwException = true)
    {
        $vars = $this->vars;
        $this->vars = array_merge($this->vars, $parameters);

        /**
         * @event cms.page.beforeRenderPartial
         * Provides an opportunity to manipulate the name of the partial being rendered before it renders
         *
         * Example usage:
         *
         *     Event::listen('cms.page.beforeRenderPartial', function ((\Cms\Classes\Controller) $controller, (string) $partialName) {
         *         return Cms\Classes\Partial::loadCached($theme, 'custom-partial-name');
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.beforeRenderPartial', function ((string) $partialName) {
         *         return Cms\Classes\Partial::loadCached($theme, 'custom-partial-name');
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.beforeRenderPartial', [$name])) {
            $partial = $event;
        }
        else {
            $partial = $this->loadPartialObject($name, $throwException);
        }

        if ($partial === false) {
            if ($throwException) {
                throw new CmsException(Lang::get('cms::lang.partial.not_found_name', ['name'=>$name]));
            }
            else {
                return false;
            }
        }

        // Run functions for CMS partials only (Cms\Classes\Partial)
        if ($partial instanceof Partial) {
            $this->partialStack->stackPartial();

            $manager = ComponentManager::instance();

            foreach ($partial->settings['components'] as $component => $properties) {
                // Do not inject the viewBag component to the environment.
                // Not sure if they're needed there by the requirements,
                // but there were problems with array-typed properties used by Static Pages
                // snippets and parseRouteParamsOnComponent(). --ab
                if ($component === 'viewBag') {
                    continue;
                }

                [$name, $alias] = strpos($component, ' ')
                    ? explode(' ', $component)
                    : [$component, $component];

                $componentObj = $manager->makeComponent($name, $this->pageObj, $properties);

                if (!$componentObj) {
                    $strictMode = Config::get('cms.strict_components', false);
                    if ($strictMode) {
                        throw new CmsException(Lang::get('cms::lang.component.not_found', ['name' => $name]));
                    }
                    else {
                        $parameters[$alias] = null;
                    }
                }
                else {
                    $componentObj->alias = $alias;

                    $partial->components[$alias] = $componentObj;

                    $parameters[$alias] = $componentObj->makePrimaryAccessor();

                    $this->partialStack->addComponent($alias, $componentObj);

                    $this->parseRouteParamsOnComponent($componentObj, $this->router->getParameters());

                    $componentObj->init();

                    $this->parseEnvironmentVarsOnComponent($componentObj, $parameters + $this->vars);
                }
            }

            CmsException::mask($this->page, 300);
            $parser = new CodeParser($partial);
            $partialObj = $parser->source($this->page, $this->layout, $this);
            CmsException::unmask();

            CmsException::mask($partial, 300);
            $partialObj->onStart();
            $partial->runComponents();
            $partialObj->onEnd();
            CmsException::unmask();
        }

        // Render the partial
        CmsException::mask($partial, 400);
        $this->loader->setObject($partial);
        $template = $this->twig->loadTemplate($partial->getFilePath());
        $partialContent = $template->render(array_merge($this->vars, $parameters));
        CmsException::unmask();

        if ($partial instanceof Partial) {
            $this->partialStack->unstackPartial();
        }

        $this->vars = $vars;

        /**
         * @event cms.page.renderPartial
         * Provides an opportunity to manipulate the output of a partial after being rendered
         *
         * Example usage:
         *
         *     Event::listen('cms.page.renderPartial', function ((\Cms\Classes\Controller) $controller, (string) $partialName, (string) &$partialContent) {
         *         return "Overriding content";
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.renderPartial', function ((string) $partialName, (string) &$partialContent) {
         *         return "Overriding content";
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.renderPartial', [$name, &$partialContent])) {
            return $event;
        }

        return $partialContent;
    }

    /**
     * loadContentObject loads content for rendering.
     * @return Content|false
     */
    public function loadContentObject($name)
    {
        // Load content from theme
        if (($content = Content::loadCached($this->theme, $name)) === null) {
            return false;
        }

        return $content;
    }

    /**
     * renderContent renders a requested content file. The framework uses this method internally.
     * @param string $name The content view to load.
     * @param array $parameters Parameter variables to pass to the view.
     * @return mixed Contents or false if now throwing an exception.
     */
    public function renderContent($name, $parameters = [], $throwException = true)
    {
        /**
         * @event cms.page.beforeRenderContent
         * Provides an opportunity to manipulate the name of the content file being rendered before it renders
         *
         * Example usage:
         *
         *     Event::listen('cms.page.beforeRenderContent', function ((\Cms\Classes\Controller) $controller, (string) $contentName) {
         *         return Cms\Classes\Content::loadCached($theme, 'custom-content-name');
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.beforeRenderContent', function ((string) $contentName) {
         *         return Cms\Classes\Content::loadCached($theme, 'custom-content-name');
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.beforeRenderContent', [$name])) {
            $content = $event;
        }
        else {
            $content = $this->loadContentObject($name);
        }

        if ($content === false) {
            if ($throwException) {
                throw new CmsException(Lang::get('cms::lang.content.not_found_name', ['name'=>$name]));
            }
            else {
                return false;
            }
        }

        $fileContent = $content->parsedMarkup;

        /*
         * Inject global view variables
         */
        $globalVars = ViewHelper::getGlobalVars();
        if (!empty($globalVars)) {
            $parameters = (array) $parameters + $globalVars;
        }

        /*
         * Parse basic template variables
         */
        if (!empty($parameters)) {
            $fileContent = TextParser::parse($fileContent, $parameters);
        }

        /**
         * @event cms.page.renderContent
         * Provides an opportunity to manipulate the output of a content file after being rendered
         *
         * Example usage:
         *
         *     Event::listen('cms.page.renderContent', function ((\Cms\Classes\Controller) $controller, (string) $contentName, (string) &$fileContent) {
         *         return "Overriding content";
         *     });
         *
         * Or
         *
         *     $controller->bindEvent('page.renderContent', function ((string) $contentName, (string) &$fileContent) {
         *         return "Overriding content";
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('cms.page.renderContent', [$name, &$fileContent])) {
            return $event;
        }

        return $fileContent;
    }

    /**
     * Renders a component's default content, preserves the previous component context.
     * @param $name
     * @param array $parameters
     * @return string Returns the component default contents.
     */
    public function renderComponent($name, $parameters = [])
    {
        $result = null;
        $previousContext = $this->componentContext;

        if ($componentObj = $this->findComponentByName($name)) {
            $componentObj->id = uniqid($name);
            $componentObj->setProperties(array_merge($componentObj->getProperties(), $parameters));
            $this->componentContext = $componentObj;
            $result = $componentObj->onRender();
        }

        if (!$result) {
            $result = $this->renderPartial($name.'::default', [], false);
        }

        $this->componentContext = $previousContext;
        return $result;
    }

    //
    // Getters
    //

    /**
     * Returns an existing instance of the controller.
     * If the controller doesn't exists, returns null.
     * @return mixed Returns the controller object or null.
     */
    public static function getController()
    {
        return self::$instance;
    }

    /**
     * Returns the current CMS theme.
     * @return \Cms\Classes\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Returns the Twig environment.
     * @return TwigEnvironment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * Returns the Twig loader.
     * @return \Cms\Twig\Loader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Returns the routing object.
     * @return \Cms\Classes\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Intended to be called from the layout, returns the page code base object.
     * @return \Cms\Classes\CodeBase
     */
    public function getPageObject()
    {
        return $this->pageObj;
    }

    /**
     * Returns the CMS page object being processed by the controller.
     * The object is not available on the early stages of the controller
     * initialization.
     * @return \Cms\Classes\Page Returns the Page object or null.
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Intended to be called from the page, returns the layout code base object.
     * @return \Cms\Classes\CodeBase
     */
    public function getLayoutObject()
    {
        return $this->layoutObj;
    }

    /**
     * Returns the CMS layout object being processed by the controller.
     * The object is not available on the early stages of the controller
     * initialization.
     * @return \Cms\Classes\Layout Returns the Layout object or null.
     */
    public function getLayout()
    {
        return $this->layout;
    }

    //
    // Page helpers
    //

    /**
     * pageNotFound returns a 404 page response
     */
    public static function pageNotFound()
    {
        try {
            $controller = (self::getController() ?: new self);

            $router = $controller->getRouter();

            if (!$router->findByUrl('/404')) {
                return View::make('cms::404');
            }

            return $controller->run('/404');
        }
        catch (Exception $ex) {
            return View::make('cms::404');
        }
    }

    /**
     * pageError returns a 500 page response
     */
    public static function pageError()
    {
        try {
            $controller = (self::getController() ?: new self);

            $router = $controller->getRouter();

            if (!$router->findByUrl('/error')) {
                return View::make('cms::error');
            }

            return $controller->run('/error');
        }
        catch (Exception $ex) {
            return View::make('cms::error');
        }
    }

    /**
     * pageUrl looks up the URL for a supplied page and returns it relative to the website root.
     *
     * @param mixed $name Specifies the Cms Page file name.
     * @param array $parameters Route parameters to consider in the URL.
     * @param bool $routePersistence By default the existing routing parameters will be included
     * @return string
     */
    public function pageUrl($name, $parameters = [], $routePersistence = true)
    {
        if (!$name) {
            return $this->currentPageUrl($parameters, $routePersistence);
        }

        /*
         * Second parameter can act as third
         */
        if (is_bool($parameters)) {
            $routePersistence = $parameters;
        }

        if (!is_array($parameters)) {
            $parameters = [];
        }

        if ($routePersistence) {
            $parameters = array_merge($this->router->getParameters(), $parameters);
        }

        if (!$url = $this->router->findByFile($name, $parameters)) {
            return null;
        }

        return Cms::url($url);
    }

    /**
     * currentPageUrl looks up the current page URL with supplied parameters and route persistence.
     * @param array $parameters
     * @param bool $routePersistence
     * @return null|string
     */
    public function currentPageUrl($parameters = [], $routePersistence = true)
    {
        if (!$currentFile = $this->page->getFileName()) {
            return null;
        }

        return $this->pageUrl($currentFile, $parameters, $routePersistence);
    }

    /**
     * themeUrl converts supplied URL to a theme URL relative to the website root, if the URL
     * provided is an array then the files will be combined
     */
    public function themeUrl($url = null): string
    {
        if (is_array($url)) {
            return $this->combineAssets($url);
        }

        return Url::asset($this->getThemeAssetPath($url));
    }

    /**
     * Returns a routing parameter.
     * @param string $name Routing parameter name.
     * @param string $default Default to use if none is found.
     * @return string
     */
    public function param($name, $default = null)
    {
        return $this->router->getParameter($name, $default);
    }

    //
    // Component helpers
    //

    /**
     * addComponent class or short name to the page or layout object, assigning
     * it an alias with configuration as properties.
     * @param mixed  $name
     * @param string $alias
     * @param array  $properties
     * @param bool   $addToLayout
     * @return ComponentBase|null
     */
    public function addComponent($name, $alias, $properties, $addToLayout = false)
    {
        $manager = ComponentManager::instance();

        if ($addToLayout) {
            $componentObj = $manager->makeComponent($name, $this->layoutObj, $properties);
        }
        else {
            $componentObj = $manager->makeComponent($name, $this->pageObj, $properties);
        }

        if (!$componentObj) {
            $strictMode = Config::get('cms.strict_components', false);
            if ($strictMode) {
                throw new CmsException(Lang::get('cms::lang.component.not_found', ['name' => $name]));
            }
            else {
                return $this->vars[$alias] = null;
            }
        }

        $componentObj->alias = $alias;

        if ($addToLayout) {
            $this->layout->components[$alias] = $componentObj;
        }
        else {
            $this->page->components[$alias] = $componentObj;
        }

        $this->vars[$alias] = $componentObj->makePrimaryAccessor();

        $this->parseRouteParamsOnComponent($componentObj, $this->router->getParameters());

        $componentObj->init();

        return $componentObj;
    }

    /**
     * findComponentByName searches the layout and page components by an alias
     * and returns the component object if found.
     * @param $name
     * @return ComponentBase|null
     */
    public function findComponentByName($name)
    {
        if (isset($this->page->components[$name])) {
            return $this->page->components[$name];
        }

        if (isset($this->layout->components[$name])) {
            return $this->layout->components[$name];
        }

        $partialComponent = $this->partialStack->getComponent($name);
        if ($partialComponent !== null) {
            return $partialComponent;
        }

        return null;
    }

    /**
     * findComponentByHandler searches the layout and page components by an AJAX handler
     * and returns the component object if found.
     * @param string $handler
     * @return ComponentBase|null
     */
    public function findComponentByHandler($handler)
    {
        foreach ($this->page->components as $component) {
            if ($component->methodExists($handler)) {
                return $component;
            }
        }

        foreach ($this->layout->components as $component) {
            if ($component->methodExists($handler)) {
                return $component;
            }
        }

        return null;
    }

    /**
     * findComponentByPartial searches the layout and page components by a partial file
     * and returns the component object if found.
     * @param string $partial
     * @return ComponentBase|null
     */
    public function findComponentByPartial($partial)
    {
        foreach ($this->page->components as $component) {
            if (ComponentPartial::check($component, $partial)) {
                return $component;
            }
        }

        foreach ($this->layout->components as $component) {
            if (ComponentPartial::check($component, $partial)) {
                return $component;
            }
        }

        return null;
    }

    /**
     * setComponentContext manually, used by Components when calling renderPartial.
     */
    public function setComponentContext(ComponentBase $component = null)
    {
        $this->componentContext = $component;
    }
}
