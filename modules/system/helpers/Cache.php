<?php namespace System\Helpers;

use App;
use File;
use Cache as CacheFacade;
use Config;

/**
 * Cache helper
 *
 * @method static Cache instance()
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class Cache
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * clear from the console command
     */
    public static function clear()
    {
        CacheFacade::flush();
        self::clearInternal();
    }

    /**
     * clearInternal
     */
    public static function clearInternal()
    {
        $instance = self::instance();
        $instance->clearCombiner();
        $instance->clearCache();

        if (Config::get('cms.enable_twig_cache', true)) {
            $instance->clearTwig();
        }

        $instance->clearMeta();
    }

    /**
     * clearCombiner
     */
    public function clearCombiner()
    {
        foreach (File::directories(storage_path().'/cms/combiner') as $directory) {
            File::deleteDirectory($directory);
        }
    }

    /**
     * clearCache
     */
    public function clearCache()
    {
        foreach (File::directories(storage_path().'/cms/cache') as $directory) {
            File::deleteDirectory($directory);
        }
    }

    /**
     * clearTwig
     */
    public function clearTwig()
    {
        foreach (File::directories(storage_path().'/cms/twig') as $directory) {
            File::deleteDirectory($directory);
        }
    }

    /**
     * clearMeta
     */
    public function clearMeta()
    {
        File::delete(storage_path().'/cms/disabled.json');

        File::delete(App::getCachedClassesPath());

        File::delete(App::getCachedCompilePath());

        File::delete(App::getCachedConfigPath());

        File::delete(App::getCachedServicesPath());

        File::delete(App::getCachedPackagesPath());

        File::delete(App::getCachedRoutesPath());
    }
}
