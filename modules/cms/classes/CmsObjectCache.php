<?php namespace Cms\Classes;

use App;
use Config;

/**
 * CmsObjectCache provides a simple request-level cache for CMS objects
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class CmsObjectCache
{
    /**
     * @var array cache collection
     */
    protected static $cache = [];

    /**
     * lookup
     */
    public static function lookup($instance, $fileName)
    {
        $cacheKey = self::makeCacheKey($instance, $fileName);

        if (self::has($cacheKey)) {
            return self::get($cacheKey);
        }

        $result = $instance
            ->remember(Config::get('cms.template_cache_ttl', 1440))
            ->cacheDriver(Config::get('cms.template_cache_driver', 'file'))
            ->find($fileName)
        ;

        self::put($cacheKey, $result);

        return $result;
    }

    /**
     * has
     */
    protected static function has($cacheKey): bool
    {
        if (App::runningInConsole() || App::runningUnitTests()) {
            return false;
        }

        return array_key_exists($cacheKey, static::$cache);
    }

    /**
     * get
     */
    protected static function get($cacheKey): ?CmsObject
    {
        return static::$cache[$cacheKey];
    }

    /**
     * put
     */
    protected static function put($cacheKey, ?CmsObject $obj)
    {
        static::$cache[$cacheKey] = $obj;
    }

    /**
     * makeCacheKey makes a unique key for this object
     */
    protected static function makeCacheKey($instance, string $fileName): string
    {
        $instance->fileName = $fileName;

        return 'cms_object_'.$instance->getTwigCacheKey();
    }

    /**
     * flush
     */
    public static function flush()
    {
        static::$cache = [];
    }
}
