<?php namespace Backend\Classes;

use October\Rain\Html\Helper as HtmlHelper;

/**
 * FormWidgetBase class contains widgets used specifically for forms
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class FormWidgetBase extends WidgetBase
{
    //
    // Configurable properties
    //

    /**
     * @var \October\Rain\Database\Model model object for the form.
     */
    public $model;

    /**
     * @var array data containing field values, if none supplied model should be used.
     */
    public $data;

    /**
     * @var string sessionKey for the active session, used for editing forms and deferred bindings.
     */
    public $sessionKey;

    /**
     * @var string sessionKeySuffix adds some extra uniqueness to the session key.
     */
    public $sessionKeySuffix;

    /**
     * @var bool previewMode renders this form with uneditable preview data.
     */
    public $previewMode = false;

    /**
     * @var bool showLabels determines if this form field should display comments and labels.
     */
    public $showLabels = true;

    //
    // Object properties
    //

    /**
     * @var FormField formField object containing general form field information.
     */
    protected $formField;

    /**
     * @var Backend\Widgets\Form The parent form that contains this field
     */
    protected $parentForm = null;

    /**
     * @var string Form field name.
     */
    protected $fieldName;

    /**
     * @var string Model attribute to get/set value from.
     */
    protected $valueFrom;

    /**
     * __construct
     * @param $controller Controller Active controller object.
     * @param $formField FormField Object containing general form field information.
     * @param $configuration array Configuration the relates to this widget.
     */
    public function __construct($controller, $formField, $configuration = [])
    {
        $this->formField = $formField;
        $this->fieldName = $formField->fieldName;
        $this->valueFrom = $formField->valueFrom;

        $this->config = $this->makeConfig($configuration);

        $this->fillFromConfig([
            'model',
            'data',
            'sessionKey',
            'sessionKeySuffix',
            'previewMode',
            'showLabels',
            'parentForm',
        ]);

        parent::__construct($controller, $configuration);
    }

    /**
     * getParentForm retrieves the parent form for this formwidget
     * @return Backend\Widgets\Form|null
     */
    public function getParentForm()
    {
        return $this->parentForm;
    }

    /**
     * getFieldName returns the HTML element field name for this widget, used for
     * capturing user input, passed back to the getSaveValue method when saving.
     * @return string HTML element name
     */
    public function getFieldName()
    {
        return $this->formField->getName();
    }

    /**
     * getId returns a unique ID for this widget. Useful in creating HTML markup.
     */
    public function getId($suffix = null)
    {
        $id = parent::getId($suffix);
        $id .= '-' . $this->fieldName;
        return HtmlHelper::nameToId($id);
    }

    /**
     * getSaveValue processes the postback value for this widget. If the value is omitted from
     * postback data, the form widget will be skipped.
     * @param mixed $value The existing value for this widget.
     * @return string The new value for this widget.
     */
    public function getSaveValue($value)
    {
        return $value;
    }

    /**
     * getLoadValue returns the value for this form field,
     * supports nesting via HTML array.
     * @return string
     */
    public function getLoadValue()
    {
        if ($this->formField->value !== null) {
            return $this->formField->value;
        }

        $defaultValue = $this->model && !$this->model->exists
            ? $this->formField->getDefaultFromData($this->data ?: $this->model)
            : null;

        return $this->formField->getValueFromData($this->data ?: $this->model, $defaultValue);
    }

    /**
     * resetFormValue from the form field, triggered by the parent form calling `setFormValues`
     * and the new value is in the formField object `value` property.
     */
    public function resetFormValue()
    {
    }

    /**
     * getSessionKey returns the active session key, including suffix.
     * @return string
     */
    public function getSessionKey()
    {
        return $this->sessionKey . $this->sessionKeySuffix;
    }
}
