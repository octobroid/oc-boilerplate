<?php namespace Cms\Classes;

use Lang;
use File;
use Cache;
use Event;
use Config;
use October\Rain\Router\Router as RainRouter;
use October\Rain\Router\Helper as RouterHelper;
use Carbon\Carbon;

/**
 * Router parses page URL patterns and finds pages by URLs.
 *
 * The page URL format is explained below.
 *
 *     /blog/post/:post_id
 *
 * Name of parameters should be compatible with PHP variable names. To make a parameter optional
 * add the question mark after its name:
 *
 *     /blog/post/:post_id?
 *
 * By default parameters in the middle of the URL are required, for example:
 *
 *     /blog/:post_id?/comments - although the :post_id parameter is marked as optional,
 *
 * it will be processed as required.
 *
 * Optional parameters can have default values which are used as fallback values in case if the real
 * parameter value is not presented in the URL. Default values cannot contain the pipe symbols and question marks.
 *
 * Specify the default value after the question mark:
 *
 *     /blog/category/:category_id?10 - The category_id parameter would be 10 for this URL: /blog/category
 *
 * You can also add regular expression validation to parameters. To add a validation expression
 * add the pipe symbol after the parameter name (or the question mark) and specify the expression.
 * The forward slash symbol is not allowed in the expressions. Examples:
 *
 *     /blog/:post_id|^[0-9]+$/comments - this will match /blog/post/10/comments
 *     /blog/:post_id|^[0-9]+$ - this will match /blog/post/3
 *     /blog/:post_name?|^[a-z0-9\-]+$ - this will match /blog/my-blog-post
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class Router
{
    /**
     * @var \Cms\Classes\Theme A reference to the CMS theme containing the object.
     */
    protected $theme;

    /**
     * @var string The last URL to be looked up using findByUrl().
     */
    protected $url;

    /**
     * @var array A list of parameters names and values extracted from the URL pattern and URL string.
     */
    protected $parameters = [];

    /**
     * @var array Contains the URL map - the list of page file names and corresponding URL patterns.
     */
    protected $urlMap = [];

    /**
     * October\Rain\Router\Router Router object with routes preloaded.
     */
    protected $routerObj;

    /**
     * __construct the router instance.
     * @param \Cms\Classes\Theme $theme Specifies the theme being processed.
     */
    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * findByUrl finds a page by its URL. Returns the page object and sets the $parameters property.
     * @param string $url The requested URL string.
     * @return \Cms\Classes\Page Returns \Cms\Classes\Page object or null if the page cannot be found.
     */
    public function findByUrl($url)
    {
        $this->url = $url;
        $url = RouterHelper::normalizeUrl($url);

        /**
         * @event cms.router.beforeRoute
         * Fires before the CMS Router handles a route
         *
         * Example usage:
         *
         *     Event::listen('cms.router.beforeRoute', function ((string) $url, (\Cms\Classes\Router) $thisRouterInstance) {
         *         return \Cms\Classes\Page::loadCached('trick-theme-code', 'page-file-name');
         *     });
         *
         */
        $apiResult = Event::fire('cms.router.beforeRoute', [$url, $this], true);
        if ($apiResult !== null) {
            return $apiResult;
        }

        for ($pass = 1; $pass <= 2; $pass++) {
            $fileName = null;
            $urlList = [];

            $cacheable = Config::get('cms.enable_route_cache');
            if ($cacheable) {
                $fileName = $this->getCachedUrlFileName($url, $urlList);
                if (is_array($fileName)) {
                    [$fileName, $this->parameters] = $fileName;
                }
            }

            // Find the page by URL and cache the route
            //
            if (!$fileName) {
                $router = $this->getRouterObject();
                if ($router->match($url)) {
                    $this->parameters = $router->getParameters();

                    $fileName = $router->matchedRoute();

                    if ($cacheable) {
                        if (!$urlList || !is_array($urlList)) {
                            $urlList = [];
                        }

                        $urlList[$url] = !empty($this->parameters)
                            ? [$fileName, $this->parameters]
                            : $fileName;

                        // Store in cache
                        Cache::put(
                            $this->getUrlListCacheKey(),
                            base64_encode(serialize($urlList)),
                            Carbon::now()->addMinutes(Config::get('cms.url_cache_ttl', 10))
                        );
                    }
                }
            }

            // Return the page
            //
            if ($fileName) {
                if (($page = Page::loadCached($this->theme, $fileName)) === null) {
                    // If the page was not found on the disk, clear the URL cache
                    // and repeat the routing process.
                    if ($pass === 1) {
                        $this->clearCache();
                        continue;
                    }

                    return null;
                }

                return $page;
            }

            return null;
        }
    }

    /**
     * findByFile finds a URL by it's page. Returns the URL route for linking to the page and
     * uses the supplied parameters in it's address.
     * @param string $fileName Page file name.
     * @param array $parameters Route parameters to consider in the URL.
     * @return string A built URL matching the page route.
     */
    public function findByFile($fileName, $parameters = [])
    {
        if (!strlen(File::extension($fileName))) {
            $fileName .= '.htm';
        }

        $router = $this->getRouterObject();

        return $router->url($fileName, $parameters);
    }

    /**
     * getRouterObject autoloads the URL map only allowing a single execution.
     * @return October\Rain\Router\Router Returns the URL map.
     */
    protected function getRouterObject()
    {
        if ($this->routerObj !== null) {
            return $this->routerObj;
        }

        // Load up each route rule
        $router = new RainRouter;
        foreach ($this->getUrlMap() as $pageInfo) {
            $router->route($pageInfo['file'], $pageInfo['pattern']);
        }

        // Sort all the rules
        $router->sortRules();

        return $this->routerObj = $router;
    }

    /**
     * getUrlMap autoloads the URL map only allowing a single execution.
     * @return array Returns the URL map.
     */
    protected function getUrlMap()
    {
        if (!count($this->urlMap)) {
            $this->loadUrlMap();
        }

        return $this->urlMap;
    }

    /**
     * loadUrlMap - a list of page file names and corresponding URL patterns.
     * The URL map can is cached. The clearUrlMap() method resets the cache. By default
     * the map is updated every time when a page is saved in the back-end, or
     * when the interval defined with the cms.url_cache_ttl expires.
     * @return boolean Returns true if the URL map was loaded from the cache. Otherwise returns false.
     */
    protected function loadUrlMap()
    {
        // Cache preferences
        $cacheKey = $this->getCacheKey('page-url-map');
        $cacheable = Config::get('cms.enable_route_cache');

        // Read from cache
        if (
            $cacheable &&
            ($cached = Cache::get($cacheKey, false)) &&
            (($unserialized = @unserialize(@base64_decode($cached))) !== false)
        ) {
            $this->urlMap = $unserialized;
            return true;
        }

        // The item doesn't exist in the cache, create the map
        $urlMap = [];
        $pages = $this->theme->listPages();
        foreach ($pages as $page) {
            if (!$page->url) {
                continue;
            }

            $urlMap[] = ['file' => $page->getFileName(), 'pattern' => $page->url];
        }

        // Store in cache
        if ($cacheable) {
            Cache::put(
                $cacheKey,
                base64_encode(serialize($urlMap)),
                Carbon::now()->addMinutes(Config::get('cms.url_cache_ttl', 10))
            );
        }

        $this->urlMap = $urlMap;
        return false;
    }

    /**
     * clearCache clears the router cache.
     */
    public function clearCache()
    {
        Cache::forget($this->getCacheKey('page-url-map'));
        Cache::forget($this->getCacheKey('cms-url-list'));
    }

    /**
     * setParameters sets the current routing parameters.
     * @param  array $parameters
     * @return array
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * getParameters returns the current routing parameters.
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * getUrl returns the last URL to be looked up.
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * getParameter returns a routing parameter.
     * @return array
     */
    public function getParameter($name, $default = null)
    {
        if (isset($this->parameters[$name]) && ($this->parameters[$name] === '0' || !empty($this->parameters[$name]))) {
            return $this->parameters[$name];
        }

        return $default;
    }

    /**
     * getCacheKey returns the caching URL key depending on the theme.
     * @param string $keyName Specifies the base key name.
     * @return string Returns the theme-specific key name.
     */
    protected function getCacheKey($keyName)
    {
        return md5($this->theme->getPath()).$keyName.Lang::getLocale();
    }

    /**
     * getUrlListCacheKey returns the cache key name for the URL list.
     * @return string
     */
    protected function getUrlListCacheKey()
    {
        return $this->getCacheKey('cms-url-list');
    }

    /**
     * getCachedUrlFileName tries to load a page file name corresponding to a specified URL
     * from the cache. Working with the URL list loaded from the cache. Returns the page
     * file name if the URL exists in the cache. Otherwise returns null.
     * @param string $url
     * @param array &$urlList
     * @return mixed
     */
    protected function getCachedUrlFileName($url, &$urlList)
    {
        $key = $this->getUrlListCacheKey();
        $urlList = Cache::get($key, false);

        if (!$urlList) {
            return null;
        }

        $urlList = @unserialize(@base64_decode($urlList));
        if (!is_array($urlList)) {
            return null;
        }

        return $urlList[$url] ?? null;
    }
}
