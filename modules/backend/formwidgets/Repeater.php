<?php namespace Backend\FormWidgets;

use Lang;
use ApplicationException;
use Backend\Classes\FormWidgetBase;

/**
 * Repeater Form Widget
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Repeater extends FormWidgetBase
{
    use \Backend\Traits\FormModelSaver;
    use \Backend\Traits\FormModelWidget;
    use \Backend\FormWidgets\Repeater\HasJsonStore;
    use \Backend\FormWidgets\Repeater\HasRelationStore;

    const MODE_ACCORDION = 'accordion';
    const MODE_BUILDER = 'builder';

    //
    // Configurable properties
    //

    /**
     * @var array form field configuration
     */
    public $form;

    /**
     * @var string prompt text for adding new items
     */
    public $prompt = 'backend::lang.repeater.add_new_item';

    /**
     * @var bool showReorder allows the user to reorder the items
     */
    public $showReorder = true;

    /**
     * @var bool showDuplicate allow the user to duplicate an item
     */
    public $showDuplicate = true;

    /**
     * @var string titleFrom field name to use for the title of collapsed items
     */
    public $titleFrom = false;

    /**
     * @var string groupKeyFrom attribute stored along with the saved data
     */
    public $groupKeyFrom = '_group';

    /**
     * @var int minItems required. Pre-displays those items when not using groups
     */
    public $minItems;

    /**
     * @var int maxItems permitted
     */
    public $maxItems;

    /**
     * @var string displayMode constant
     */
    public $displayMode;

    /**
     * @var bool itemsExpanded will expand the repeater item by default, otherwise
     * they will be collapsed and only select one at a time when clicking the header.
     */
    public $itemsExpanded = true;

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
    protected $defaultAlias = 'repeater';

    /**
     * @var array indexMeta data associated to each field, organised by index
     */
    protected $indexMeta = [];

    /**
     * @var array formWidgets collection
     */
    protected $formWidgets = [];

    /**
     * @var bool onAddItemCalled stops nested repeaters populating from previous sibling.
     */
    protected static $onAddItemCalled = false;

    /**
     * @var bool useGroups
     */
    protected $useGroups = false;

    /**
     * @var bool useRelation
     */
    protected $useRelation = false;

    /**
     * @var array relatedRecords when using a relation
     */
    protected $relatedRecords;

    /**
     * @var array groupDefinitions
     */
    protected $groupDefinitions = [];

    /**
     * @var boolean isLoaded is true when the request is made via postback
     */
    protected $isLoaded = false;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'form',
            'prompt',
            'displayMode',
            'itemsExpanded',
            'showReorder',
            'showDuplicate',
            'titleFrom',
            'groupKeyFrom',
            'minItems',
            'maxItems',
            'externalToolbarAppState',
            'externalToolbarEventBus'
        ]);

        if ($this->formField->disabled) {
            $this->previewMode = true;
        }

        $this->processGroupMode();

        $this->processRelationMode();

        $this->processLoadedState();

        $this->processLegacyConfig();

        // First pass will contain postback, then raw attributes
        // This occurs to bind widgets to the controller early
        if (!self::$onAddItemCalled) {
            $this->processItems();
        }
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('repeater');
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        // Second pass will contain filtered attributes, then postback
        // This occurs to apply filtered values to the widget data
        // @todo Replace with resetFormValue method
        if (!self::$onAddItemCalled) {
            $this->processItems();
        }

        if ($this->previewMode) {
            foreach ($this->formWidgets as $widget) {
                $widget->previewMode = true;
            }
        }

        $this->vars['name'] = $this->getFieldName();
        $this->vars['displayMode'] = $this->getDisplayMode();
        $this->vars['itemsExpanded'] = $this->itemsExpanded;
        $this->vars['prompt'] = $this->prompt;
        $this->vars['formWidgets'] = $this->formWidgets;
        $this->vars['titleFrom'] = $this->titleFrom;
        $this->vars['groupKeyFrom'] = $this->groupKeyFrom;
        $this->vars['minItems'] = $this->minItems;
        $this->vars['maxItems'] = $this->maxItems;
        $this->vars['useRelation'] = $this->useRelation;
        $this->vars['useGroups'] = $this->useGroups;
        $this->vars['groupDefinitions'] = $this->groupDefinitions;
        $this->vars['showReorder'] = $this->showReorder;
        $this->vars['showDuplicate'] = $this->showDuplicate;
        $this->vars['externalToolbarAppState'] = $this->externalToolbarAppState;
        $this->vars['externalToolbarEventBus'] = $this->externalToolbarEventBus;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss('css/repeater.css', 'core');
        $this->addJs('js/repeater-min.js', 'core');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return $this->processSaveValue($value);
    }

    /**
     * processLoadedState is special logic that occurs during a postback,
     * the form field value is set directly from the postback data, this occurs
     * during initialization so that nested form widgets can be bound to the controller.
     */
    protected function processLoadedState()
    {
        if (!post($this->alias . '_loaded')) {
            return;
        }

        $this->formField->value = $this->getLoadedValueFromPost();
        $this->isLoaded = true;
    }

    /**
     * getLoadedValueFromPost returns the loaded value from postback with indexes intact
     */
    protected function getLoadedValueFromPost()
    {
        return post($this->formField->getName());
    }

    /**
     * processLegacyConfig converts deprecated options to latest
     */
    protected function processLegacyConfig()
    {
        if ($style = $this->getConfig('style')) {
            if ($style === 'accordion' || $style === 'collapsed') {
                $this->itemsExpanded = false;
            }
        }
    }

    /**
     * processSaveValue splices in some meta data (group and index values) to the dataset
     * @param array $value
     * @return array|null
     */
    protected function processSaveValue($value)
    {
        if (!is_array($value) || !$value) {
            return null;
        }

        if ($this->minItems && count($value) < $this->minItems) {
            throw new ApplicationException(Lang::get('backend::lang.repeater.min_items_failed', [
                'name' => $this->fieldName,
                'min' => $this->minItems,
                'items' => count($value)
            ]));
        }

        if ($this->maxItems && count($value) > $this->maxItems) {
            throw new ApplicationException(Lang::get('backend::lang.repeater.max_items_failed', [
                'name' => $this->fieldName,
                'max' => $this->maxItems,
                'items' => count($value)
            ]));
        }

        return $this->useRelation
            ? $this->processSaveForRelation($value)
            : $this->processSaveForJson($value);
    }

    /**
     * processItems processes data and applies it to the form widgets
     */
    protected function processItems()
    {
        $currentValue = $this->useRelation
            ? $this->getLoadValueFromRelation()
            : $this->getLoadValue();

        // This lets record finder work inside a repeater with some hacks
        // since record finder spawns outside the form and its AJAX calls
        // don't reinitialize this repeater's items. We a need better way
        // remove if year >= 2023 -sg
        $handler = $this->controller->getAjaxHandler();
        if (!$this->isLoaded && starts_with($handler, $this->alias . 'Form')) {
            $handler = str_after($handler, $this->alias . 'Form');
            preg_match("~^(\d+)~", $handler, $matches);

            if (isset($matches[1])) {
                $index = $matches[1];
                $this->makeItemFormWidget($index);
            }
        }

        // Pad current value with minimum items and disable for groups,
        // which cannot predict their item types
        if (!$this->useGroups && $this->minItems > 0) {
            if (!is_array($currentValue)) {
                $currentValue = [];
            }

            if (count($currentValue) < $this->minItems) {
                $currentValue = array_pad($currentValue, $this->minItems, []);
            }
        }

        // Repeater value is empty or invalid
        if ($currentValue === null || !is_array($currentValue)) {
            $this->formWidgets = [];
            return;
        }

        // Load up the necessary form widgets
        foreach ($currentValue as $index => $value) {
            $groupType = $this->useRelation
                ? $this->getGroupCodeFromRelation($value)
                : $this->getGroupCodeFromJson($value);

            $this->makeItemFormWidget($index, $groupType);
        }
    }

    /**
     * makeItemFormWidget creates a form widget based on a field index and optional group code
     * @param int $index
     * @param string $groupCode
     * @param int $fromIndex
     * @return \Backend\Widgets\Form
     */
    protected function makeItemFormWidget($index = 0, $groupCode = null, $fromIndex = null)
    {
        $configDefinition = $this->useGroups
            ? $this->getGroupFormFieldConfig($groupCode)
            : $this->form;

        $config = $this->makeConfig($configDefinition);

        // Duplicate
        $dataIndex = $fromIndex !== null ? $fromIndex : $index;

        if ($this->useRelation) {
            $config->model = $this->getModelFromIndex($index);
        }
        else {
            $config->model = $this->model;
            $config->data = $this->getValueFromIndex($dataIndex);
            $config->isNested = true;
        }

        $config->alias = $this->alias . 'Form' . $index;
        $config->arrayName = $this->getFieldName().'['.$index.']';
        $config->sessionKey = $this->sessionKey;
        $config->sessionKeySuffix = $this->sessionKeySuffix . '-' . $index;

        $widget = $this->makeWidget(\Backend\Widgets\Form::class, $config);
        $widget->previewMode = $this->previewMode;
        $widget->bindToController();

        $this->indexMeta[$index] = [
            'groupCode' => $groupCode
        ];

        return $this->formWidgets[$index] = $widget;
    }

    /**
     * getValueFromIndex returns the data at a given index
     */
    protected function getValueFromIndex($index)
    {
        $data = $this->isLoaded
            ? $this->getLoadedValueFromPost()
            : $this->getLoadValue();

        return $data[$index] ?? [];
    }

    /**
     * getDisplayMode for the repeater
     */
    protected function getDisplayMode(): string
    {
        return $this->displayMode ?: static::MODE_ACCORDION;
    }

    //
    // AJAX handlers
    //

    /**
     * onAddItem handler
     */
    public function onAddItem()
    {
        self::$onAddItemCalled = true;

        $groupCode = post('_repeater_group');
        $index = $this->getNextIndex();

        if ($this->useRelation) {
            $this->createRelationAtIndex($index, $groupCode);
        }

        $this->prepareVars();
        $this->vars['widget'] = $this->makeItemFormWidget($index, $groupCode);
        $this->vars['indexValue'] = $index;

        $itemContainer = '@#' . $this->getId('items');

        return [
            $itemContainer => $this->makePartial('repeater_item')
        ];
    }

    /**
     * onDuplicateItem
     */
    public function onDuplicateItem()
    {
        $fromIndex = post('_repeater_index');
        $groupCode = post('_repeater_group');
        $toIndex = $this->getNextIndex();

        $this->prepareVars();
        $this->vars['widget'] = $this->makeItemFormWidget($toIndex, $groupCode, $fromIndex);
        $this->vars['indexValue'] = $toIndex;

        $itemContainer = '@#' . $this->getId('items');

        return [
            'result' => ['duplicateIndex' => $toIndex],
            $itemContainer => $this->makePartial('repeater_item')
        ];
    }

    /**
     * onRemoveItem
     */
    public function onRemoveItem()
    {
        if (!$this->useRelation) {
            return;
        }

        // Delete related records
        $deletedItems = (array) post('_repeater_items');
        foreach ($deletedItems as $item) {
            $index = $item['repeater_index'] ?? null;
            if ($index !== null) {
                $this->deleteRelationAtIndex($index);
            }
        }
    }

    /**
     * onRefresh
     */
    public function onRefresh()
    {
        $index = post('_repeater_index');
        $group = post('_repeater_group');

        $widget = $this->makeItemFormWidget($index, $group);

        return $widget->onRefresh();
    }

    /**
     * getNextIndex determines the next available index number for assigning to a
     * new repeater item
     */
    protected function getNextIndex(): int
    {
        $data = $this->isLoaded
            ? $this->getLoadedValueFromPost()
            : $this->getLoadValue();

        if (is_array($data) && count($data)) {
            return max(array_keys($data)) + 1;
        }

        return 0;
    }

    //
    // Group Mode
    //

    /**
     * getGroupFormFieldConfig returns the form field configuration for a group, identified by code
     * @param string $code
     * @return array|null
     */
    protected function getGroupFormFieldConfig($code)
    {
        if (!$code) {
            return null;
        }

        $fields = array_get($this->groupDefinitions, $code.'.fields');

        if (!$fields) {
            return null;
        }

        return ['fields' => $fields];
    }

    /**
     * processGroupMode processes features related to group mode
     */
    protected function processGroupMode()
    {
        $palette = [];
        $group = $this->getConfig('groups', []);
        $this->useGroups = (bool) $group;

        if ($this->useGroups) {
            if (is_string($group)) {
                $group = $this->makeConfig($group);
            }

            foreach ($group as $code => $config) {
                $palette[$code] = [
                    'code' => $code,
                    'name' => $config['name'] ?? '',
                    'icon' => $config['icon'] ?? 'icon-square-o',
                    'description' => $config['description'] ?? '',
                    'fields' => $config['fields'] ?? ''
                ];
            }

            $this->groupDefinitions = $palette;
        }
    }

    /**
     * getGroupCodeFromIndex returns a field group code from its index
     * @param $index int
     */
    public function getGroupCodeFromIndex($index): string
    {
        return (string) array_get($this->indexMeta, $index.'.groupCode');
    }

    /**
     * getGroupItemConfig returns the group config from its unique code
     */
    public function getGroupItemConfig(string $groupCode, string $name = null, $default = null)
    {
        return array_get($this->groupDefinitions, $groupCode.'.'.$name, $default);
    }
}
