<?php namespace Cms\Classes;

use Media\Classes\MediaLibraryItem as MediaMediaLibrary;

/**
 * Represents a file or folder in the Media Library.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated Use Media\Classes\MediaLibraryItem. Remove if year >= 2023.
 */
class MediaLibraryItem extends MediaMediaLibrary
{
    public function __construct()
    {
        traceLog('Class Cms\Classes\MediaLibraryItem has been deprecated, use ' . MediaMediaLibrary::class . ' instead.');
        parent::__construct(...func_get_args());
    }
}
