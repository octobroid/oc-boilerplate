<?php namespace Media\FormWidgets;

use BackendAuth;
use Media\Classes\MediaLibrary;
use Backend\Classes\FormWidgetBase;
use October\Rain\Support\Collection;
use October\Rain\Database\Model;
use Exception;

/**
 * Media Finder
 * Renders a record finder field.
 *
 *    image:
 *        label: Some image
 *        type: media
 *
 * @package october\media
 * @author Alexey Bobkov, Samuel Georges
 */
class MediaFinder extends FormWidgetBase
{
    //
    // Configurable properties
    //

    /**
     * @var string Display mode for the selection. Values: file, image.
     */
    public $mode = 'file';

    /**
     * @var int Preview image width
     */
    public $imageWidth = 190;

    /**
     * @var int Preview image height
     */
    public $imageHeight = 190;

    /**
     * @var int|null maxItems allowed
     */
    public $maxItems = null;

    /**
     * @var string Defines a mount point for the editor toolbar.
     * Must include a module name that exports the Vue application and a state element name.
     * Format: module.name::stateElementName
     * Only works in Vue applications and form document layouts.
     */
    public $externalToolbarAppState = null;

    /**
     * @var string Defines an event bus for an external toolbar.
     * Must include a module name that exports the Vue application and a state element name.
     * Format: module.name::eventBus
     * Only works in Vue applications and form document layouts.
     */
    public $externalToolbarEventBus = null;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'media';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'mode',
            'imageWidth',
            'imageHeight',
            'maxItems',
            'externalToolbarAppState',
            'externalToolbarEventBus'
        ]);

        if ($this->formField->disabled || $this->formField->readOnly) {
            $this->previewMode = true;
        }

        if (!BackendAuth::userHasAccess('media.manage_media')) {
            $this->previewMode = true;
        }

        $this->processMaxItems();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('mediafinder');
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        $this->vars['size'] = $this->formField->size;
        $this->vars['fileList'] = $fileList = $this->getFileList();
        $this->vars['singleFile'] = $fileList->first();
        $this->vars['displayMode'] = $this->getDisplayMode();
        $this->vars['field'] = $this->formField;
        $this->vars['maxItems'] = $this->maxItems;
        $this->vars['imageWidth'] = $this->imageWidth;
        $this->vars['imageHeight'] = $this->imageHeight;
        $this->vars['externalToolbarAppState'] = $this->externalToolbarAppState;
        $this->vars['externalToolbarEventBus'] = $this->externalToolbarEventBus;
    }

    /**
     * getFileList returns a list of associated files
     */
    protected function getFileList()
    {
        $value = $this->getLoadValue() ?: [];

        if (!is_array($value)) {
            $value = [$value];
        }

        // Lookup files
        $mediaLib = MediaLibrary::instance();

        $list = [];
        foreach ($value as $val) {
            try {
                if ($file = $mediaLib->findFile($val)) {
                    $list[] = $file;
                }
            }
            catch (Exception $ex) {}
        }

        // Promote to Collection
        $list = new Collection($list);

        return $list;
    }

    /**
     * processMaxItems
     */
    protected function processMaxItems()
    {
        if ($this->maxItems === null) {
            if ($this->model instanceof Model) {
                $this->maxItems = $this->model->isJsonable($this->valueFrom) ? 0 : 1;
            }
            else {
                $this->maxItems = 1;
            }
        }

        $this->maxItems = (int) $this->maxItems;
    }

    /**
     * getDisplayMode for the file upload. Eg: file-multi, image-single, etc
     * @return string
     */
    protected function getDisplayMode()
    {
        $mode = $this->getConfig('mode', 'file');

        if (str_contains($mode, '-')) {
            return $mode;
        }

        $mode .= $this->maxItems === 1 ? '-single' : '-multi';

        return $mode;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addJs('js/mediafinder.js', 'core');
        $this->addCss('css/mediafinder.css', 'core');
    }
}
