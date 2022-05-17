<?php namespace System\Classes;

use Url;
use App;
use File;
use Lang;
use Route;
use Event;
use Cache;
use Config;
use Storage;
use Redirect;
use October\Rain\Database\Attach\File as FileModel;
use ApplicationException;
use Resizer;

/**
 * ResizeImages is used for resizing image files
 *
 * @method static ResizeImages instance()
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ResizeImages
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array availableSources to get image paths
     */
    protected $availableSources = [];

    /**
     * init is a singleton constructor
     */
    public function init()
    {
    }

    /**
     * resize
     */
    public static function resize($image, $width = null, $height = null, $options = [])
    {
        return self::instance()->prepareRequest($image, $width, $height, $options);
    }

    /**
     * getContents
     */
    public function getContents($cacheKey)
    {
        $cacheInfo = $this->getCache($cacheKey);
        if (!$cacheInfo || !isset($cacheInfo['path'])) {
            throw new ApplicationException(Lang::get('system::lang.resizer.not_found', ['name'=>$cacheKey]));
        }

        // Calculate properties
        $width = $cacheInfo['width'] ?? 0;
        $height = $cacheInfo['height'] ?? 0;
        $options = (array) @json_decode(@base64_decode($cacheInfo['options']), true);
        $filename = $this->getResizeFilename($cacheKey, $width, $height, $options);

        // Get raw cached file path
        $rawPath = $cacheInfo['path'];
        $tempRawPath = null;

        // If path is external, copy to local filesystem
        $isExternal = strpos($rawPath, 'http') === 0;
        if ($isExternal) {
            $tempRawPath = $this->getTempPath() . '/raw_' . $filename;
            $contents = file_get_contents($rawPath);
            file_put_contents($tempRawPath, $contents);
        }

        // Create temp path for resized file in local filesystem
        $tempPath = $this->getTempPath() . '/' . $filename;

        // Perform resize
        Resizer::open($isExternal ? $tempRawPath : $rawPath)
            ->resize($width, $height, $options)
            ->save($tempPath);

        // Save resized file to disk
        $disk = Storage::disk(Config::get('system.storage.resources.disk'));
        $resourcesFolder = Config::get('system.storage.resources.folder') . '/resize';
        $disk->put($resourcesFolder . '/' . $filename, file_get_contents($tempPath));

        // Cleanup
        File::delete($tempPath);

        if ($tempRawPath && file_exists($tempRawPath)) {
            File::delete($tempRawPath);
        }

        return Redirect::to($this->getPublicPath() . '/' . $filename);
    }

    /**
     * prepareRequest for resizing
     */
    protected function prepareRequest($image, $width = null, $height = null, $options = [])
    {
        $imageInfo = $this->processImage($image);
        $options = $this->getDefaultResizeOptions($options);

        // Use the same extension as source image
        if ($options['extension'] === 'auto') {
            $options['extension'] = $imageInfo['extension'];
        }

        $width = (int) $width;
        $height = (int) $height;

        // Check is resized
        $cacheKey = $this->getCacheKey([$imageInfo, $width, $height, $options]);
        $filename = $this->getResizeFilename($cacheKey, $width, $height, $options);
        $disk = Storage::disk(Config::get('system.storage.resources.disk'));
        $resourcesFolder = Config::get('system.storage.resources.folder');
        $resourcesFolder .= '/resize';

        if ($disk->exists($resourcesFolder . '/' . $filename)) {
            return $this->getPublicPath() . '/' . $filename;
        }

        // Cache and process
        $cacheInfo = $this->getCache($cacheKey);

        if (!$cacheInfo) {
            $cacheInfo = [
                'version' => $cacheKey . '-1',
                'source' => $imageInfo['source'],
                'path' => $imageInfo['path'],
                'extension' => $imageInfo['extension'],
                'width' => $width,
                'height' => $height,
                'options' => base64_encode(json_encode($options)),
            ];

            $this->putCache($cacheKey, $cacheInfo);
        }

        $outputFilename = $cacheInfo['version'];

        return $this->getResizedUrl($outputFilename);
    }

    /**
     * getResizedUrl
     */
    protected function getResizedUrl($outputFilename = 'undefined.css')
    {
        $combineAction = \System\Classes\SystemController::class.'@resize';
        $actionExists = Route::getRoutes()->getByAction($combineAction) !== null;

        if ($actionExists) {
            return Url::action($combineAction, [$outputFilename], false);
        }

        return '/resize/'.$outputFilename;
    }

    /**
     * processImage
     */
    protected function processImage($image)
    {
        $result = [
            'path' => null,
            'extension' => null,
            'source' => null
        ];

        // File model
        if ($image instanceof FileModel) {
            $disk = $image->getDisk();
            $path = $image->getDiskPath();

            if (File::extension($path) && $disk->exists($path)) {
                $result['path'] = $image->getLocalPath();
                $result['extension'] = $image->getExtension();
                $result['source'] = 'model';
            }
        }
        elseif (is_string($image)) {
            $path = $this->parseFileName($image);

            // Local path
            if ($path !== null) {
                $result['path'] = $path;
                $result['extension'] = File::extension($path);
                $result['source'] = 'local';
            }
            // URL
            elseif (strpos($image, '://') !== false) {
                $result['path'] = $image;
                $result['extension'] = explode('?', File::extension($image))[0];
                $result['source'] = 'url';
            }
        }

        return $result;
    }

    /**
     * Parse the file name to get a relative path for the file
     * @return string
     */
    protected function parseFileName($filePath): ?string
    {
        // Local disk path
        if (file_exists($filePath)) {
            return $filePath;
        }

        // Pop off URI from URL
        $path = urldecode(parse_url($filePath, PHP_URL_PATH));

        foreach ($this->getAvailableSources() as $source) {
            if ($source['disk'] !== 'local')  {
                continue;
            }

            $folder = $source['path'];
            if (strpos($path, $folder) !== false) {
                $pathParts = explode($folder, $path, 2);
                $finalPath = base_path($folder . end($pathParts));
                if (file_exists($finalPath)) {
                    return $finalPath;
                }
            }
        }

        return null;
    }

    /**
     * getAvailableSources returns available sources
     */
    protected function getAvailableSources(): array
    {
        if ($this->availableSources) {
            return $this->availableSources;
        }

        $config = App::make('config');

        $sources = [
            'resources' => [
                'disk' => $config->get('system.storage.resources.disk', 'local'),
                'folder' => $config->get('system.storage.resources.folder', 'resized'),
                'path' => $config->get('system.storage.resources.path', '/storage/app/resources'),
            ],
            'media' => [
                'disk' => $config->get('system.storage.media.disk', 'local'),
                'folder' => $config->get('system.storage.media.folder', 'media'),
                'path' => $config->get('system.storage.media.path', '/storage/app/media'),
            ],
            'uploads' => [
                'disk' => $config->get('system.storage.uploads.disk', 'local'),
                'folder' => $config->get('system.storage.uploads.folder', 'uploads'),
                'path' => $config->get('system.storage.uploads.path', '/storage/app/uploads'),
            ],
            'modules' => [
                'disk' => 'local',
                'folder' => base_path('modules'),
                'path' => '/modules',
            ],
            'plugins' => [
                'disk' => 'local',
                'folder' => base_path('plugins'),
                'path' => '/plugins',
            ],
            'themes' => [
                'disk' => 'local',
                'folder' => base_path('themes'),
                'path' => '/themes',
            ],
        ];

        /**
         * @event system.resizer.getAvailableSources
         * Provides an opportunity to modify the available sources
         *
         * Example usage:
         *
         *     Event::listen('system.resizer.getAvailableSources', function ((array) &$sources)) {
         *         $sources['custom'] = [
         *              'disk' => 'custom',
         *              'folder' => 'relative/path/on/disk',
         *              'path' => 'publicly/accessible/path',
         *         ];
         *     });
         *
         */
        Event::fire('system.resizer.getAvailableSources', [&$sources]);

        return $this->availableSources = $sources;
    }

    /**
     * getResizeFilename generates a thumbnail filename
     */
    protected function getResizeFilename($id, $width, $height, $options): string
    {
        $options = $this->getDefaultResizeOptions($options);
        $offsetA = $options['offset'][0];
        $offsetB = $options['offset'][1];
        $mode = $options['mode'];
        $extension = $options['extension'];

        return "img_${id}_${width}_${height}_${offsetA}_${offsetB}_${mode}.${extension}";
    }

    /**
     * getDefaultResizeOptions returns the default thumbnail options
     */
    protected function getDefaultResizeOptions($overrideOptions = []): array
    {
        $defaultOptions = [
            'mode' => 'auto',
            'offset' => [0, 0],
            'quality' => 90,
            'sharpen' => 0,
            'interlace' => false,
            'extension' => 'auto',
        ];

        if (!is_array($overrideOptions)) {
            $overrideOptions = ['mode' => $overrideOptions];
        }

        $options = array_merge($defaultOptions, $overrideOptions);

        $options['mode'] = strtolower($options['mode']);

        return $options;
    }

    //
    // Paths
    //

    /**
     * getPublicPath returns the public address for the resources path
     */
    public function getPublicPath()
    {
        $disk = Storage::disk(Config::get('system.storage.resources.disk'));
        $resourcesFolder = Config::get('system.storage.resources.folder');
        $resourcesFolder .= '/resize';

        if (
            Config::get('system.storage.resources.disk') === 'local' &&
            Config::get('system.relative_links') === true
        ) {
            return $resourcesFolder;
        }

        return $disk->url($resourcesFolder);
    }

    /**
     * getOutputPath returns the final resource path
     */
    public function getOutputPath()
    {
        $path = rtrim(Config::get('system.storage.resources.path', '/storage/app/resources'), '/');
        $path .= '/resize';

        $path = base_path($path);

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * getTempPath returns an internal working path
     */
    public function getTempPath()
    {
        $path = temp_path() . '/resize';

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    //
    // Cache
    //

    /**
     * Stores information about a asset collection against
     * a cache identifier.
     * @param string $cacheKey Cache identifier.
     * @param array $cacheInfo List of asset files.
     * @return bool Successful
     */
    protected function putCache($cacheKey, array $cacheInfo)
    {
        $cacheKey = 'resizer.'.$cacheKey;

        if (Cache::has($cacheKey)) {
            return false;
        }

        $this->putCacheIndex($cacheKey);

        Cache::forever($cacheKey, base64_encode(serialize($cacheInfo)));

        return true;
    }

    /**
     * Look up information about a cache identifier.
     * @param string $cacheKey Cache identifier
     * @return array Cache information
     */
    protected function getCache($cacheKey)
    {
        $cacheKey = 'resizer.'.$cacheKey;

        if (!Cache::has($cacheKey)) {
            return false;
        }

        return @unserialize(@base64_decode(Cache::get($cacheKey)));
    }

    /**
     * getCacheKey builds a unique string based on assets
     */
    protected function getCacheKey(array $payload): string
    {
        $cacheKey = json_encode($payload);

        /**
         * @event cms.resizer.getCacheKey
         * Provides an opportunity to modify the asset resizer's cache key
         *
         * Example usage:
         *
         *     Event::listen('cms.resizer.getCacheKey', function ((\System\Classes\ResizeImages) $assetCombiner, (stdClass) $dataHolder) {
         *         $dataHolder->key = rand();
         *     });
         *
         */
        $dataHolder = (object) ['key' => $cacheKey];
        Event::fire('cms.resizer.getCacheKey', [$this, $dataHolder]);
        $cacheKey = $dataHolder->key;

        return md5($cacheKey);
    }

    /**
     * Resets the resizer cache
     * @return void
     */
    public static function resetCache()
    {
        if (Cache::has('resizer.index')) {
            $index = (array) @unserialize(@base64_decode(Cache::get('resizer.index'))) ?: [];

            foreach ($index as $cacheKey) {
                Cache::forget($cacheKey);
            }

            Cache::forget('resizer.index');
        }

        // CacheHelper::instance()->clearCombiner();
    }

    /**
     * Adds a cache identifier to the index store used for
     * performing a reset of the cache.
     * @param string $cacheKey Cache identifier
     * @return bool Returns false if identifier is already in store
     */
    protected function putCacheIndex($cacheKey)
    {
        $index = [];

        if (Cache::has('resizer.index')) {
            $index = (array) @unserialize(@base64_decode(Cache::get('resizer.index'))) ?: [];
        }

        if (in_array($cacheKey, $index)) {
            return false;
        }

        $index[] = $cacheKey;

        Cache::forever('resizer.index', base64_encode(serialize($index)));

        return true;
    }
}
