<?php namespace Backend\FormWidgets;

use October\Rain\Database\Model;
use Backend\Classes\FormWidgetBase;

/**
 * Tag List Form Widget
 */
class TagList extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;
    use \Backend\FormWidgets\TagList\HasStringStore;
    use \Backend\FormWidgets\TagList\HasRelationStore;

    const MODE_ARRAY = 'array';
    const MODE_STRING = 'string';
    const MODE_RELATION = 'relation';

    //
    // Configurable properties
    //

    /**
     * @var string separator for tags: space, comma.
     */
    public $separator = 'comma';

    /**
     * @var bool customTags allowed to be entered manually by the user.
     */
    public $customTags = true;

    /**
     * @var mixed options settings. Set to true to get from model.
     */
    public $options;

    /**
     * @var string mode for the return value. Values: string, array, relation.
     */
    public $mode;

    /**
     * @var string nameFrom if mode is relation, model column to use for the name reference.
     */
    public $nameFrom = 'name';

    /**
     * @var bool useKey instead of value for saving and reading data.
     */
    public $useKey = false;

    /**
     * @var string placeholder for empty TagList widget
     */
    public $placeholder = '';

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'taglist';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'separator',
            'customTags',
            'options',
            'mode',
            'nameFrom',
            'useKey',
            'placeholder'
        ]);

        $this->processMode();
    }

    /**
     * processMode
     */
    protected function processMode()
    {
        // Set by config
        if ($this->mode !== null) {
            return;
        }

        [$model, $attribute] = $this->nearestModelAttribute($this->valueFrom);

        if ($model instanceof Model && $model->hasRelation($attribute)) {
            $this->mode = static::MODE_RELATION;
            return;
        }

        if ($model instanceof Model && $model->isJsonable($attribute)) {
            $this->mode = static::MODE_ARRAY;
            return;
        }

        $this->mode = static::MODE_STRING;
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('taglist');
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        $this->vars['placeholder'] = $this->placeholder;
        $this->vars['useKey'] = $this->useKey;
        $this->vars['field'] = $this->formField;
        $this->vars['fieldOptions'] = $this->getFieldOptions();
        $this->vars['selectedValues'] = $this->getLoadValue();
        $this->vars['customSeparators'] = $this->getCustomSeparators();
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        if ($this->mode === static::MODE_RELATION) {
            return $this->processSaveForRelation($value);
        }

        if ($this->mode === static::MODE_STRING) {
            return $this->processSaveForString($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getLoadValue()
    {
        $value = parent::getLoadValue();

        if ($this->mode === static::MODE_RELATION) {
            return $this->getLoadValueFromRelation($value);
        }

        if ($this->mode === static::MODE_STRING) {
            return $this->getLoadValueFromString($value);
        }

        return $value;
    }

    /**
     * Returns defined field options, or from the relation if available.
     * @return array
     */
    public function getFieldOptions()
    {
        $options = [];

        if ($this->formField->hasOptions()) {
            $options = $this->formField->options();
        }
        elseif ($this->mode === static::MODE_RELATION) {
            $options = $this->getFieldOptionsForRelation();
        }

        return $options;
    }

    /**
     * getPreviewOptions generates options for display in read only modes
     */
    public function getPreviewOptions(array $selectedValues, array $availableOptions): array
    {
        $displayOptions = [];
        foreach ($availableOptions as $key => $option) {
            if (!strlen($option)) {
                continue;
            }
            if (
                ($this->useKey && in_array($key, $selectedValues)) ||
                (!$this->useKey && in_array($option, $selectedValues))
            ) {
                $displayOptions[] = $option;
            }
        }

        return $displayOptions;
    }
}
