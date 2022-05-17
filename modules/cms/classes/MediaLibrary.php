<?php namespace Cms\Classes;

use Media\Classes\MediaLibrary as MediaMediaLibrary;

/**
 * Provides abstraction level for the Media Library operations.
 * Implements the library caching features and security checks.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated Use Media\Classes\MediaLibrary. Remove if year >= 2023.
 */
class MediaLibrary extends MediaMediaLibrary
{
    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        traceLog('Class ' . __CLASS__ . ' has been deprecated, use ' . MediaMediaLibrary::class . ' instead.');
        parent::init();
    }
}
