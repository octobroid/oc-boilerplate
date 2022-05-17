<?php namespace Cms\FormWidgets;

use Media\FormWidgets\MediaFinder as MediaMediaFinder;

/**
 * Media Finder
 * Renders a record finder field.
 *
 *    image:
 *        label: Some image
 *        type: media
 *        prompt: Click the %s button to find a user
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated Use Media\FormWidgets\MediaFinder. Remove if year >= 2023.
 */
class MediaFinder extends MediaMediaFinder
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        traceLog('FormWidget Cms\FormWidgets\MediaFinder has been deprecated, use ' . MediaMediaFinder::class . ' instead.');

        $this->assetPath = '/modules/media/formwidgets/mediafinder/assets';
        $this->viewPath = base_path('/modules/media/formwidgets/mediafinder/partials');

        parent::__construct(...func_get_args());
    }
}
