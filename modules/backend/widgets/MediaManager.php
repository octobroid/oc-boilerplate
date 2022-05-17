<?php namespace Backend\Widgets;

use Media\Widgets\MediaManager as MediaMediaManager;

/**
 * Media Manager widget.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated Use Media\Widgets\MediaManager. Remove if year >= 2023.
 */
class MediaManager extends MediaMediaManager
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        traceLog('Widget Backend\Widgets\MediaManager has been deprecated, use ' . MediaMediaManager::class . ' instead.');

        $this->assetPath = '/modules/media/widgets/mediamanager/assets';
        $this->viewPath = base_path('/modules/media/widgets/mediamanager/partials');

        parent::__construct(...func_get_args());
    }
}
