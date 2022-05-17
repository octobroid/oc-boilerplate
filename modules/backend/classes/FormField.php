<?php namespace Backend\Classes;

use Str;
use Html;
use Lang;
use October\Rain\Database\Model;
use October\Rain\Html\Helper as HtmlHelper;
use October\Rain\Element\Form\FieldDefinition;
use Illuminate\Support\Collection;
use SystemException;
use Exception;

/**
 * FormField definition is a translation of the form field configuration
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class FormField extends FieldDefinition
{
    /**
     * @var int Value returned when the form field should not contribute any save data.
     */
    const NO_SAVE_DATA = -1;

    /**
     * @var string A special character in yaml config files to indicate a field higher in hierarchy
     */
    const HIERARCHY_UP = '^';

    /**
     * @var string If the field element names should be contained in an array. Eg:
     *
     *     <input name="nameArray[fieldName]" />
     */
    public $arrayName;

    /**
     * @var string A prefix to the field identifier so it can be totally unique.
     */
    public $idPrefix;

    /**
     * @var string Form field value.
     */
    public $value;

    /**
     * @var string Model attribute to use for the display value.
     */
    public $valueFrom;

    /**
     * @var string Specifies a default value for supported fields.
     */
    public $defaults;

    /**
     * @var string Model attribute to use for the default value.
     */
    public $defaultFrom;

    /**
     * @var string Specifies contextual visibility of this form field.
     */
    public $context;

    /**
     * @var bool Specifies if this field is mandatory.
     */
    public $required;

    /**
     * @var bool autoFocus flags the field to be focused on load.
     */
    public $autoFocus = false;

    /**
     * @var bool Specify if the field is read-only or not.
     */
    public $readOnly = false;

    /**
     * @var bool Specify if the field is disabled or not.
     */
    public $disabled = false;

    /**
     * @var bool Specifies if this field stretch to fit the page height.
     */
    public $stretch = false;

    /**
     * @var array Contains a list of attributes specified in the field configuration.
     */
    public $attributes;

    /**
     * @var string Specifies a CSS class to attach to the field container.
     */
    public $cssClass;

    /**
     * @var string Specifies a path for partial-type fields.
     */
    public $path;

    /**
     * @var array Other field names this field depends on, when the other fields are modified, this field will update.
     */
    public $dependsOn;

    /**
     * @var array Other field names this field can be triggered by, see the Trigger API documentation.
     */
    public $trigger;

    /**
     * @var array Other field names text is converted in to a URL, slug or file name value in this field.
     */
    public $preset;

    /**
     * @var array permissions needed to view this field
     */
    public $permissions;

    /**
     * @var string changeHandler AJAX handler for the change event
     */
    public $changeHandler;

    /**
     * __construct
     * @todo remove this method if year >= 2023
     */
    public function __construct($fieldName, $label)
    {
        parent::__construct((string) $fieldName);

        $this->label((string) $label);
    }

    /**
     * Process options and apply them to this object.
     * @param array $config
     * @return array
     */
    protected function evalConfig($config): void
    {
        parent::evalConfig($config);

        /*
         * Standard config:property values
         */
        $applyConfigValues = [
            'changeHandler',
            'commentHtml',
            'dependsOn',
            'required',
            'autoFocus',
            'readOnly',
            'disabled',
            'cssClass',
            'stretch',
            'context',
            'trigger',
            'preset',
            'path',
        ];

        foreach ($applyConfigValues as $value) {
            if (array_key_exists($value, $config)) {
                $this->{$value} = $config[$value];
            }
        }

        /*
         * Custom applicators
         */
        if (isset($config['default'])) {
            $this->defaults = $config['default'];
        }
        if (isset($config['defaultFrom'])) {
            $this->defaultFrom = $config['defaultFrom'];
        }
        if (isset($config['attributes'])) {
            $this->attributes($config['attributes']);
        }
        if (isset($config['containerAttributes'])) {
            $this->attributes($config['containerAttributes'], 'container');
        }
        if (isset($config['permissions'])) {
            $this->permissions = (array) $config['permissions'];
        }

        if (isset($config['valueFrom'])) {
            $this->valueFrom = $config['valueFrom'];
        }
        else {
            $this->valueFrom = $this->fieldName;
        }
    }

    /**
     * Determine if the provided value matches this field's value.
     * @param string $value
     * @return bool
     */
    public function isSelected($value = true)
    {
        if ($this->value === null) {
            return false;
        }

        return (string) $value === (string) $this->value;
    }

    /**
     * Sets the attributes for this field in a given position.
     * - field: Attributes are added to the form field element (input, select, textarea, etc)
     * - container: Attributes are added to the form field container (div.form-group)
     * @param  array $items
     * @param  string $position
     * @return void
     */
    public function attributes($items, $position = 'field'): FormField
    {
        if (!is_array($items)) {
            return $this;
        }

        $multiArray = array_filter($items, 'is_array');
        if (!$multiArray) {
            $this->attributes[$position] = $items;
            return $this;
        }

        foreach ($items as $_position => $_items) {
            $this->attributes($_items, $_position);
        }

        return $this;
    }

    /**
     * Checks if the field has the supplied [unfiltered] attribute.
     * @param  string $name
     * @param  string $position
     * @return bool
     */
    public function hasAttribute($name, $position = 'field')
    {
        if (!isset($this->attributes[$position])) {
            return false;
        }

        return array_key_exists($name, $this->attributes[$position]);
    }

    /**
     * Returns the attributes for this field at a given position.
     * @param  string $position
     * @return array
     */
    public function getAttributes($position = 'field', $htmlBuild = true)
    {
        $result = array_get($this->attributes, $position, []);
        $result = $this->filterAttributes($result, $position);

        return $htmlBuild ? Html::attributes($result) : $result;
    }

    /**
     * Adds any circumstantial attributes to the field based on other
     * settings, such as the 'disabled' option.
     * @param  array $attributes
     * @param  string $position
     * @return array
     */
    protected function filterAttributes($attributes, $position = 'field')
    {
        $position = strtolower($position);

        $attributes = $this->filterTriggerAttributes($attributes, $position);
        $attributes = $this->filterPresetAttributes($attributes, $position);

        if ($position === 'field' && $this->disabled) {
            $attributes = $attributes + ['disabled' => 'disabled'];
        }

        if ($position === 'field' && $this->autoFocus) {
            $attributes = $attributes + ['autofocus' => 'autofocus'];
        }

        if ($position === 'field' && $this->readOnly) {
            $attributes = $attributes + ['readonly' => 'readonly'];

            if ($this->type === 'checkbox' || $this->type === 'switch') {
                $attributes = $attributes + ['onclick' => 'return false;'];
            }
        }

        return $attributes;
    }

    /**
     * Adds attributes used specifically by the Trigger API
     * @param  array $attributes
     * @param  string $position
     * @return array
     */
    protected function filterTriggerAttributes($attributes, $position = 'field')
    {
        if (!$this->trigger || !is_array($this->trigger)) {
            return $attributes;
        }

        $triggerAction = array_get($this->trigger, 'action');
        $triggerField = array_get($this->trigger, 'field');
        $triggerCondition = array_get($this->trigger, 'condition');
        $triggerForm = $this->arrayName;
        $triggerMulti = '';

        // Apply these to container
        if (in_array($triggerAction, ['hide', 'show']) && $position !== 'container') {
            return $attributes;
        }

        // Apply these to field/input
        if (in_array($triggerAction, ['enable', 'disable', 'empty']) && $position !== 'field') {
            return $attributes;
        }

        // Reduce the field reference for the trigger condition field
        $triggerFieldParentLevel = Str::getPrecedingSymbols($triggerField, self::HIERARCHY_UP);
        if ($triggerFieldParentLevel > 0) {
            // Remove the preceding symbols from the trigger field name
            $triggerField = substr($triggerField, $triggerFieldParentLevel);
            $triggerForm = HtmlHelper::reduceNameHierarchy($triggerForm, $triggerFieldParentLevel);
        }

        // Preserve multi field types
        if (Str::endsWith($triggerField, '[]')) {
            $triggerField = substr($triggerField, 0, -2);
            $triggerMulti = '[]';
        }

        // Final compilation
        if ($this->arrayName) {
            $fullTriggerField = $triggerForm.'['.implode('][', HtmlHelper::nameToArray($triggerField)).']'.$triggerMulti;
        }
        else {
            $fullTriggerField = $triggerField.$triggerMulti;
        }

        $newAttributes = [
            'data-trigger' => '[name="'.$fullTriggerField.'"]',
            'data-trigger-action' => $triggerAction,
            'data-trigger-condition' => $triggerCondition,
            'data-trigger-closest-parent' => 'form, div[data-control="formwidget"]'
        ];

        return $attributes + $newAttributes;
    }

    /**
     * Adds attributes used specifically by the Input Preset API
     * @param  array $attributes
     * @param  string $position
     * @return array
     */
    protected function filterPresetAttributes($attributes, $position = 'field')
    {
        if (!$this->preset || $position !== 'field') {
            return $attributes;
        }

        if (!is_array($this->preset)) {
            $this->preset = ['field' => $this->preset, 'type' => 'slug'];
        }

        $presetField = array_get($this->preset, 'field');
        $presetType = array_get($this->preset, 'type');

        if ($this->arrayName) {
            $fullPresetField = $this->arrayName.'['.implode('][', HtmlHelper::nameToArray($presetField)).']';
        }
        else {
            $fullPresetField = $presetField;
        }

        $newAttributes = [
            'data-input-preset' => '[name="'.$fullPresetField.'"]',
            'data-input-preset-type' => $presetType,
            'data-input-preset-closest-parent' => 'form'
        ];

        if ($prefixInput = array_get($this->preset, 'prefixInput')) {
            $newAttributes['data-input-preset-prefix-input'] = $prefixInput;
        }

        return $attributes + $newAttributes;
    }

    /**
     * Returns a value suitable for the field name property.
     * @param  string $arrayName Specify a custom array name
     * @return string
     */
    public function getName($arrayName = null)
    {
        if ($arrayName === null) {
            $arrayName = $this->arrayName;
        }

        if ($arrayName) {
            return $arrayName.'['.implode('][', HtmlHelper::nameToArray($this->fieldName)).']';
        }

        return $this->fieldName;
    }

    /**
     * Returns a value suitable for the field id property.
     * @param  string $suffix Specify a suffix string
     * @return string
     */
    public function getId($suffix = null)
    {
        $id = 'field';
        if ($this->arrayName) {
            $id .= '-'.$this->arrayName;
        }

        $id .= '-'.$this->fieldName;

        if ($suffix) {
            $id .= '-'.$suffix;
        }

        if ($this->idPrefix) {
            $id = $this->idPrefix . '-' . $id;
        }

        return HtmlHelper::nameToId($id);
    }

    /**
     * Returns a raw config item value.
     * @param  string $value
     * @param  string $default
     * @return mixed
     */
    public function getConfig($value, $default = null)
    {
        return array_get($this->config, $value, $default);
    }

    /**
     * Returns this fields value from a supplied data set, which can be
     * an array or a model or another generic collection.
     * @param mixed $data
     * @param mixed $default
     * @return mixed
     */
    public function getValueFromData($data, $default = null)
    {
        $fieldName = $this->valueFrom ?: $this->fieldName;
        return $this->getFieldNameFromData($fieldName, $data, $default);
    }

    /**
     * Returns the default value for this field, the supplied data is used
     * to source data when defaultFrom is specified.
     * @param mixed $data
     * @return mixed
     */
    public function getDefaultFromData($data)
    {
        if ($this->defaultFrom) {
            return $this->getFieldNameFromData($this->defaultFrom, $data);
        }

        if ($this->defaults !== '') {
            return $this->defaults;
        }

        return null;
    }

    /**
     * resolveModelAttribute returns the final model and attribute name of a nested attribute. Eg:
     *
     *     [$model, $attribute] = $this->resolveAttribute('person[phone]');
     *
     * @param  string $attribute.
     * @return array
     */
    public function resolveModelAttribute($model, $attribute = null)
    {
        return $this->resolveModelAttributeInternal($model, $attribute);
    }

    /**
     * nearestModelAttribute returns the nearest model and attribute name of a nested attribute,
     * which is useful for checking if an attribute is jsonable or a relation.
     */
    public function nearestModelAttribute($model, $attribute = null)
    {
        return $this->resolveModelAttributeInternal($model, $attribute, [
            'nearMatch' => true,
            'objectOnly' => true
        ]);
    }

    /**
     * resolveModelAttributeInternal is an internal method resolver for resolveModelAttribute
     */
    protected function resolveModelAttributeInternal($model, $attribute = null, $options = [])
    {
        extract(array_merge([
            'objectOnly' => false,
            'nearMatch' => false
        ], $options));

        if ($attribute === null) {
            $attribute = $this->valueFrom ?: $this->fieldName;
        }

        $parts = is_array($attribute) ? $attribute : HtmlHelper::nameToArray($attribute);
        $last = array_pop($parts);

        foreach ($parts as $part) {
            if ($objectOnly && !is_object($model->{$part})) {
                if ($nearMatch) {
                    return [$model, $part];
                }

                continue;
            }

            $model = $model->{$part};
        }

        return [$model, $last];
    }

    /**
     * Internal method to extract the value of a field name from a data set.
     * @param string $fieldName
     * @param mixed $data
     * @param mixed $default
     * @return mixed
     */
    protected function getFieldNameFromData($fieldName, $data, $default = null)
    {
        /*
         * Array field name, eg: field[key][key2][key3]
         */
        $keyParts = HtmlHelper::nameToArray($fieldName);
        $lastField = end($keyParts);
        $result = $data;

        /*
         * Loop the field key parts and build a value.
         * To support relations only the last field should return the
         * relation value, all others will look up the relation object as normal.
         */
        foreach ($keyParts as $key) {
            if ($result instanceof Model && $result->hasRelation($key)) {
                if ($key === $lastField) {
                    $result = $result->getRelationValue($key) ?: $default;
                }
                else {
                    $result = $result->{$key};
                }
            }
            elseif (is_array($result)) {
                if (!array_key_exists($key, $result)) {
                    return $default;
                }
                $result = $result[$key];
            }
            else {
                if (!isset($result->{$key})) {
                    return $default;
                }
                $result = $result->{$key};
            }
        }

        return $result;
    }

    /**
     * getOptionsFromModel looks at the model for defined options.
     */
    public function getOptionsFromModel($model, $fieldOptions, $data)
    {
        // Method name
        if (is_string($fieldOptions)) {
            $fieldOptions = $this->getOptionsFromModelAsString($model, $fieldOptions, $data);
        }
        // Default collection
        elseif ($fieldOptions === null || $fieldOptions === true) {
            $fieldOptions = $this->getOptionsFromModelAsDefault($model, $data);
        }

        // Cast collections to array
        if ($fieldOptions instanceof Collection) {
            $fieldOptions = $fieldOptions->all();
        }

        return $fieldOptions;
    }

    /**
     * getOptionsFromModelAsString where options are an explicit method reference
     */
    protected function getOptionsFromModelAsString($model, string $methodName, $data)
    {
        // Calling via ClassName::method
        if (
            strpos($methodName, '::') !== false &&
            ($staticMethod = explode('::', $methodName)) &&
            count($staticMethod) === 2 &&
            is_callable($staticMethod)
        ) {
            $fieldOptions = $staticMethod($model, $this);

            if (!is_array($fieldOptions)) {
                throw new SystemException(Lang::get('backend::lang.field.options_static_method_invalid_value', [
                    'class' => $staticMethod[0],
                    'method' => $staticMethod[1]
                ]));
            }
        }
        // Calling via $model->method
        else {
            if (!$this->objectMethodExists($model, $methodName)) {
                throw new SystemException(Lang::get('backend::lang.field.options_method_not_exists', [
                    'model' => get_class($model),
                    'method' => $methodName,
                    'field' => $this->fieldName
                ]));
            }

            $fieldOptions = $model->$methodName($this->value, $this->fieldName, $data);
        }

        return $fieldOptions;
    }

    /**
     * getOptionsFromModelAsDefault refers to the model method or any of its behaviors
     */
    protected function getOptionsFromModelAsDefault($model, $data)
    {
        try {
            [$model, $attribute] = $this->resolveModelAttributeInternal($model, $this->fieldName, ['objectOnly' => true]);
        }
        catch (Exception $ex) {
            throw new SystemException(Lang::get('backend::lang.field.options_method_invalid_model', [
                'model' => get_class($model),
                'field' => $this->fieldName
            ]));
        }

        $methodName = 'get'.studly_case($attribute).'Options';
        if (
            !$this->objectMethodExists($model, $methodName) &&
            !$this->objectMethodExists($model, 'getDropdownOptions')
        ) {
            throw new SystemException(Lang::get('backend::lang.field.options_method_not_exists', [
                'model' => get_class($model),
                'method' => $methodName,
                'field' => $this->fieldName
            ]));
        }

        if ($this->objectMethodExists($model, $methodName)) {
            $fieldOptions = $model->$methodName($this->value, $data);
        }
        else {
            $fieldOptions = $model->getDropdownOptions($attribute, $this->value, $data);
        }

        return $fieldOptions;
    }

    /**
     * Internal helper for method existence checks.
     *
     * @param  object $object
     * @param  string $method
     * @return boolean
     */
    protected function objectMethodExists($object, $method)
    {
        if (method_exists($object, 'methodExists')) {
            return $object->methodExists($method);
        }

        return method_exists($object, $method);
    }
}
