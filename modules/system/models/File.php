<?php namespace System\Models;

use Url;
use Config;
use Storage;
use Backend\Controllers\Files;
use October\Rain\Database\Attach\File as FileBase;

/**
 * File attachment model
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class File extends FileBase
{
    /**
     * @var string table in database used by the model
     */
    protected $table = 'system_files';

    /**
     * {@inheritDoc}
     */
    public function getThumb($width, $height, $options = [])
    {
        if (!$this->isPublic() && class_exists(Files::class)) {

            $options = $this->getDefaultThumbOptions($options);

            // Ensure that the thumb exists first
            parent::getThumb($width, $height, $options);

            // Return the Files controller handler for the URL
            return Files::getThumbUrl($this, $width, $height, $options);
        }

        return parent::getThumb($width, $height, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getPath($fileName = null)
    {
        if (!$this->isPublic() && class_exists(Files::class)) {
            return Files::getDownloadUrl($this);
        }

        return parent::getPath($fileName);
    }

    /**
     * getLocalRootPath will, if working with local storage, determine the absolute local path
     */
    protected function getLocalRootPath()
    {
        return Config::get('filesystems.disks.local.root', storage_path('app'));
    }

    /**
     * getPublicPath returns the public address for the storage path
     */
    public function getPublicPath()
    {
        $uploadsPath = Config::get('system.storage.uploads.path', '/storage/app/uploads');

        if ($this->isPublic()) {
            $uploadsPath .= '/public';
        }
        else {
            $uploadsPath .= '/protected';
        }

        // Relative links
        if (
            $this->isLocalStorage() &&
            Config::get('system.relative_links') === true
        ) {
            return $uploadsPath . '/';
        }

        return Url::asset($uploadsPath) . '/';
    }

    /**
     * getStorageDirectory returns the internal storage path
     */
    public function getStorageDirectory()
    {
        $uploadsFolder = Config::get('system.storage.uploads.folder');

        if ($this->isPublic()) {
            return $uploadsFolder . '/public/';
        }

        return $uploadsFolder . '/protected/';
    }

    /**
     * isLocalStorage returns true if storage.uploads.disk in config/system.php is "local"
     * @return bool
     */
    protected function isLocalStorage()
    {
        return Config::get('system.storage.uploads.disk') == 'local';
    }

    /**
     * getDisk returns the storage disk the file is stored on
     * @return FilesystemAdapter
     */
    public function getDisk()
    {
        return Storage::disk(Config::get('system.storage.uploads.disk'));
    }
}
