<?php namespace Backend\Widgets;

use Lang;
use Form as FormHelper;
use Backend\Classes\FormTabs;
use Backend\Classes\FormField;
use Backend\Classes\WidgetBase;
use October\Rain\Element\Form\FieldDefinition;
use October\Rain\Element\Form\FieldsetDefinition;
use October\Rain\Database\Model;
use October\Rain\Html\Helper as HtmlHelper;
use October\Contracts\Element\FormElement;
use SystemException;

/**
 * Form Widget is used for building back end forms and renders a form
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Form extends WidgetBase implements FormElement
{
    use \Backend\Widgets\Form\FieldProcessor;
    use \Backend\Widgets\Form\HasFormWidgets;
    use \Backend\Traits\FormModelSaver;

    //
    // Configurable properties
    //

    /**
     * @var array Form field configuration.
     */
    public $fields;

    /**
     * @var array Primary tab configuration.
     */
    public $tabs;

    /**
     * @var array Secondary tab configuration.
     */
    public $secondaryTabs;

    /**
     * @var Model Form model object.
     */
    public $model;

    /**
     * @var array Dataset containing field values, if none supplied, model is used.
     */
    public $data;

    /**
     * @var string The context of this form, fields that do not belong
     * to this context will not be shown.
     */
    public $context;

    /**
     * @var string If the field element names should be contained in an array.
     * Eg: <input name="nameArray[fieldName]" />
     */
    public $arrayName;

    /**
     * @var bool Used to flag that this form is being rendered as part of another form,
     * a good indicator to expect that the form model and dataset values will differ.
     */
    public $isNested = false;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'form';

    /**
     * @var boolean Determines if field definitions have been created.
     */
    protected $fieldsDefined = false;

    /**
     * @var array Collection of all fields used in this form.
     * @see \Backend\Classes\FormField
     */
    protected $allFields = [];

    /**
     * @var object Collection of tab sections used in this form.
     * @see \Backend\Classes\FormTabs
     */
    protected $allTabs = [
        'outside' => null,
        'primary' => null,
        'secondary' => null,
    ];

    /**
     * @var FieldsetDefinition|null activeTabSection where fields are currently being added
     */
    protected $activeTabSection = null;

    /**
     * @var string sessionKey for the active session, used for editing forms and deferred bindings.
     */
    public $sessionKey;

    /**
     * @var string sessionKeySuffix adds some extra uniqueness to the session key.
     */
    public $sessionKeySuffix;

    /**
     * @var bool Render this form with uneditable preview data.
     */
    public $previewMode = false;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'fields',
            'tabs',
            'secondaryTabs',
            'model',
            'data',
            'context',
            'arrayName',
            'isNested',
            'sessionKeySuffix',
        ]);

        $this->initFormWidgetsConcern();
        $this->allTabs = (object) $this->allTabs;
        $this->validateModel();
    }

    /**
     * Ensure fields are defined and form widgets are registered so they can
     * also be bound to the controller this allows their AJAX features to
     * operate.
     *
     * @return void
     */
    public function bindToController()
    {
        $this->defineFormFields();
        parent::bindToController();
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addJs('js/october.form.js', 'core');
    }

    /**
     * Renders the widget.
     *
     * Options:
     *  - preview: Render this form as an uneditable preview. Default: false
     *  - useContainer: Wrap the result in a container, used by AJAX. Default: true
     *  - section: Which form section to render. Default: null
     *     - outside: Renders the Outside Fields section.
     *     - primary: Renders the Primary Tabs section.
     *     - secondary: Renders the Secondary Tabs section.
     *     - null: Renders all sections
     *
     * @param array $options
     * @return string|bool The rendered partial contents, or false if suppressing an exception
     */
    public function render($options = [])
    {
        $this->defineFormFields();
        $this->applyFiltersFromModel();
        $this->prepareVars();

        /*
         * Custom options
         */
        if (isset($options['preview'])) {
            $this->previewMode = $options['preview'];
        }

        if (!isset($options['useContainer'])) {
            $options['useContainer'] = true;
        }

        if (!isset($options['section'])) {
            $options['section'] = null;
        }

        $extraVars = [];
        $targetPartial = 'form';

        /*
         * Determine the partial to use based on the supplied section option
         */
        if ($section = $options['section']) {
            $section = strtolower($section);

            if (isset($this->allTabs->{$section})) {
                $extraVars['tabs'] = $this->allTabs->{$section};
            }

            $targetPartial = 'section';
            $extraVars['renderSection'] = $section;
        }

        /*
         * Apply a container to the element
         */
        if ($options['useContainer']) {
            $targetPartial = $section ? 'section-container' : 'form-container';
        }

        /*
         * Force preview mode on all widgets
         */
        if ($this->previewMode) {
            foreach ($this->formWidgets as $widget) {
                $widget->previewMode = $this->previewMode;
            }
        }

        return $this->makePartial($targetPartial, $extraVars);
    }

    /**
     * renderFields renders the specified fields.
     */
    public function renderFields(array $fields): string
    {
        return $this->makePartial('form_fields', ['fields' => $fields]);
    }

    /**
     * renderField renders a single form field
     *
     * Options:
     *  - useContainer: Wrap the result in a container, used by AJAX. Default: true
     *
     * @param \Backend\Classes\FormField|string $field The field name or definition
     * @param array $options
     * @return string|bool The rendered partial contents, or false if suppressing an exception
     */
    public function renderField($field, $options = [])
    {
        $this->defineFormFields();
        $this->prepareVars();

        if (is_string($field)) {
            if (!isset($this->allFields[$field])) {
                throw new SystemException(Lang::get(
                    'backend::lang.form.missing_definition',
                    compact('field')
                ));
            }

            $field = $this->allFields[$field];
        }

        if (!isset($options['useContainer'])) {
            $options['useContainer'] = true;
        }

        $targetPartial = $options['useContainer'] ? 'field-container' : 'field';

        return $this->makePartial($targetPartial, ['field' => $field]);
    }

    /**
     * renderFieldElement renders the HTML element for a field
     * @param \Backend\Classes\FormField|string $field
     * @return string|bool The rendered partial contents, or false if suppressing an exception
     */
    public function renderFieldElement($field)
    {
        if (is_string($field)) {
            if (!isset($this->allFields[$field])) {
                throw new SystemException(Lang::get(
                    'backend::lang.form.missing_definition',
                    compact('field')
                ));
            }

            $field = $this->allFields[$field];
        }

        return $this->makePartial('field_' . $field->type, [
            'field' => $field,
            'formModel' => $this->model
        ]);
    }

    /**
     * validateModel validates the supplied form model.
     * @return mixed
     */
    protected function validateModel()
    {
        if (!$this->model) {
            throw new SystemException(Lang::get(
                'backend::lang.form.missing_model',
                ['class'=>get_class($this->controller)]
            ));
        }

        $this->data = isset($this->data)
            ? (object) $this->data
            : $this->model;

        return $this->model;
    }

    /**
     * Prepares the form data
     *
     * @return void
     */
    protected function prepareVars()
    {
        $this->vars['sessionKey'] = $this->getSessionKey();
        $this->vars['outsideTabs'] = $this->allTabs->outside;
        $this->vars['primaryTabs'] = $this->allTabs->primary;
        $this->vars['secondaryTabs'] = $this->allTabs->secondary;
    }

    /**
     * setFormValues sets or resets form field values.
     * @param array $data
     * @return array
     */
    public function setFormValues($data = null)
    {
        if ($data === null) {
            $data = $this->getSaveData();
        }

        // Fill the model as if it were to be saved
        $this->prepareModelsToSave($this->model, $data);

        // Data set differs from model
        if ($this->data !== $this->model) {
            $this->data = (object) array_merge((array) $this->data, (array) $data);
        }

        // Set field values from data source
        foreach ($this->allFields as $field) {
            $field->value = $this->getFieldValue($field);
        }

        // Notify form widgets of the change
        foreach ($this->formWidgets as $widget) {
            $widget->resetFormValue();
        }

        return $data;
    }

    /**
     * Event handler for refreshing the form.
     *
     * @return array
     */
    public function onRefresh()
    {
        $result = [];
        $saveData = $this->getSaveDataInternal();

        /**
         * @event backend.form.beforeRefresh
         * Called before the form is refreshed, modify the $dataHolder->data property in place
         *
         * Example usage:
         *
         *     Event::listen('backend.form.beforeRefresh', function ((\Backend\Widgets\Form) $formWidget, (stdClass) $dataHolder) {
         *         $dataHolder->data = $arrayOfSaveDataToReplaceExistingDataWith;
         *     });
         *
         * Or
         *
         *     $formWidget->bindEvent('form.beforeRefresh', function ((stdClass) $dataHolder) {
         *         $dataHolder->data = $arrayOfSaveDataToReplaceExistingDataWith;
         *     });
         *
         */
        $dataHolder = (object) ['data' => $saveData];
        $this->fireSystemEvent('backend.form.beforeRefresh', [$dataHolder]);
        $saveData = $dataHolder->data;

        /*
         * Set the form variables and prepare the widget
         */
        $this->setFormValues($saveData);
        $this->applyFiltersFromModel();
        $this->prepareVars();

        /**
         * @event backend.form.refreshFields
         * Called when the form is refreshed, giving the opportunity to modify the form fields
         *
         * Example usage:
         *
         *     Event::listen('backend.form.refreshFields', function ((\Backend\Widgets\Form) $formWidget, (array) $allFields) {
         *         $allFields['name']->required = false;
         *     });
         *
         * Or
         *
         *     $formWidget->bindEvent('form.refreshFields', function ((array) $allFields) {
         *         $allFields['name']->required = false;
         *     });
         *
         */
        $this->fireSystemEvent('backend.form.refreshFields', [$this->allFields]);

        /*
         * If an array of fields is supplied, update specified fields individually.
         */
        if (($updateFields = post('fields')) && is_array($updateFields)) {
            foreach ($updateFields as $field) {
                if (!isset($this->allFields[$field])) {
                    continue;
                }

                $fieldObject = $this->allFields[$field];
                $result['#' . $fieldObject->getId('group')] = $this->makePartial('field', ['field' => $fieldObject]);
            }
        }

        /*
         * Update the whole form
         */
        if (empty($result)) {
            $result = ['#'.$this->getId() => $this->makePartial('form')];
        }

        /**
         * @event backend.form.refresh
         * Called after the form is refreshed, should return an array of additional result parameters.
         *
         * Example usage:
         *
         *     Event::listen('backend.form.refresh', function ((\Backend\Widgets\Form) $formWidget, (array) $result) {
         *         $result['#my-partial-id' => $formWidget->makePartial('$/path/to/custom/backend/partial.htm')];
         *         return $result;
         *     });
         *
         * Or
         *
         *     $formWidget->bindEvent('form.refresh', function ((array) $result) use ((\Backend\Widgets\Form $formWidget)) {
         *         $result['#my-partial-id' => $formWidget->makePartial('$/path/to/custom/backend/partial.htm')];
         *         return $result;
         *     });
         *
         */
        $eventResults = $this->fireSystemEvent('backend.form.refresh', [$result], false);

        foreach ($eventResults as $eventResult) {
            if (!is_array($eventResult)) {
                continue;
            }

            $result = $eventResult + $result;
        }

        return $result;
    }

    /**
     * onLazyLoadTab renders all fields of a tab in the target tab pane
     */
    public function onLazyLoadTab()
    {
        $target  = post('target');

        if (!$tabName = post('name')) {
            throw new SystemException(Lang::get('backend::lang.form.missing_tab'));
        }

        $tab = $this->getTab(post('section', 'primary'));

        $fields = $tab !== null ? array_get($tab->getFields(), $tabName) : [];

        return [
            $target => $this->makePartial('form_fields', ['fields' => $fields]),
        ];
    }

    /**
     * nameToId is a helper method to convert a field name to a valid ID attribute
     * @param $input
     * @return string
     */
    public function nameToId($input)
    {
        return HtmlHelper::nameToId($input);
    }

    /**
     * addFormField adds a field to the fieldset
     */
    public function addFormField(string $fieldName = null, string $label = null): FieldDefinition
    {
        $fieldObj = new FormField($fieldName, $label);

        $fieldObj->arrayName = $this->arrayName;

        $fieldObj->idPrefix = $this->getId();

        $this->allFields[$fieldName] = $fieldObj;

        $this->activeTabSection->addField($fieldName, $fieldObj);

        return $fieldObj;
    }

    /**
     * getFormFieldset returns the current fieldset definition
     */
    public function getFormFieldset(): FieldsetDefinition
    {
        return $this->activeTabSection;
    }

    /**
     * defineFormFields creates a flat array of form fields from the configuration
     * and slots fields in to their respective tabs
     */
    protected function defineFormFields()
    {
        if ($this->fieldsDefined) {
            return;
        }

        /**
         * @event backend.form.extendFieldsBefore
         * Called before the form fields are defined
         *
         * Example usage:
         *
         *     Event::listen('backend.form.extendFieldsBefore', function ((\Backend\Widgets\Form) $widget) {
         *         // You should always check to see if you're extending correct model/controller
         *         if (!$widget->model instanceof \Foo\Example\Models\Bar) {
         *             return;
         *         }
         *
         *         // Here you can't use addFields() because it will throw you an exception because form is not yet created
         *         // and it does not have tabs and fields
         *         // For this example we will pretend that we want to add a new field named example_field
         *         $widget->fields['example_field'] = [
         *             'label' => 'Example field',
         *             'comment' => 'Your example field',
         *             'type' => 'text',
         *         ];
         *     });
         *
         * Or
         *
         *     $formWidget->bindEvent('form.extendFieldsBefore', function () use ((\Backend\Widgets\Form $formWidget)) {
         *         // You should always check to see if you're extending correct model/controller
         *         if (!$widget->model instanceof \Foo\Example\Models\Bar) {
         *             return;
         *         }
         *
         *         // Here you can't use addFields() because it will throw you an exception because form is not yet created
         *         // and it does not have tabs and fields
         *         // For this example we will pretend that we want to add a new field named example_field
         *         $widget->fields['example_field'] = [
         *             'label' => 'Example field',
         *             'comment' => 'Your example field',
         *             'type' => 'text',
         *         ];
         *     });
         *
         */
        $this->fireSystemEvent('backend.form.extendFieldsBefore');

        /*
         * Init tabs
         */
        $this->allTabs->outside = new FormTabs(FormTabs::SECTION_OUTSIDE, (array) $this->config);
        $this->allTabs->primary = new FormTabs(FormTabs::SECTION_PRIMARY, $this->tabs);
        $this->allTabs->secondary = new FormTabs(FormTabs::SECTION_SECONDARY, $this->secondaryTabs);

        /*
         * Outside fields
         */
        if (!isset($this->fields) || !is_array($this->fields)) {
            $this->fields = [];
        }

        $this->addFields($this->fields);
        $this->addFieldsFromModel();

        /*
         * Primary Tabs + Fields
         */
        if (!isset($this->tabs['fields']) || !is_array($this->tabs['fields'])) {
            $this->tabs['fields'] = [];
        }

        $this->addFields($this->tabs['fields'], FormTabs::SECTION_PRIMARY);
        $this->addFieldsFromModel(FormTabs::SECTION_PRIMARY);

        /*
         * Secondary Tabs + Fields
         */
        if (!isset($this->secondaryTabs['fields']) || !is_array($this->secondaryTabs['fields'])) {
            $this->secondaryTabs['fields'] = [];
        }

        $this->addFields($this->secondaryTabs['fields'], FormTabs::SECTION_SECONDARY);
        $this->addFieldsFromModel(FormTabs::SECTION_SECONDARY);

        /**
         * @event backend.form.extendFields
         * Called after the form fields are defined
         *
         * Example usage:
         *
         *     Event::listen('backend.form.extendFields', function ((\Backend\Widgets\Form) $widget) {
         *         // Only for the User controller
         *         if (!$widget->getController() instanceof \RainLab\User\Controllers\Users) {
         *             return;
         *         }
         *
         *         // Only for the User model
         *         if (!$widget->model instanceof \RainLab\User\Models\User) {
         *             return;
         *         }
         *
         *         // Add an extra birthday field
         *         $widget->addFields([
         *             'birthday' => [
         *                 'label'   => 'Birthday',
         *                 'comment' => 'Select the users birthday',
         *                 'type'    => 'datepicker'
         *             ]
         *         ]);
         *
         *         // Remove a Surname field
         *         $widget->removeField('surname');
         *     });
         *
         * Or
         *
         *     $formWidget->bindEvent('form.extendFields', function () use ((\Backend\Widgets\Form $formWidget)) {
         *         // Only for the User controller
         *         if (!$widget->getController() instanceof \RainLab\User\Controllers\Users) {
         *             return;
         *         }
         *
         *         // Only for the User model
         *         if (!$widget->model instanceof \RainLab\User\Models\User) {
         *             return;
         *         }
         *
         *         // Add an extra birthday field
         *         $widget->addFields([
         *             'birthday' => [
         *                 'label'   => 'Birthday',
         *                 'comment' => 'Select the users birthday',
         *                 'type'    => 'datepicker'
         *             ]
         *         ]);
         *
         *         // Remove a Surname field
         *         $widget->removeField('surname');
         *     });
         *
         */
        $this->fireSystemEvent('backend.form.extendFields', [$this->allFields]);

        /*
         * Apply post processing
         */
        $this->processPermissionCheck($this->allFields);
        $this->processFormWidgetFields($this->allFields);
        $this->processValidationAttributes($this->allFields);
        $this->processFieldOptionValues($this->allFields);
        $this->processRequiredAttributes($this->allFields);

        /*
         * Set field values from data source
         */
        foreach ($this->allFields as $field) {
            $field->value = $this->getFieldValue($field);
        }

        /*
         * Convert automatic spanned fields
         */
        $this->processAutoSpan($this->allTabs->outside);
        $this->processAutoSpan($this->allTabs->primary);
        $this->processAutoSpan($this->allTabs->secondary);

        /*
         * At least one tab section should stretch
         */
        if (
            $this->allTabs->secondary->stretch === null
            && $this->allTabs->primary->stretch === null
            && $this->allTabs->outside->stretch === null
        ) {
            if ($this->allTabs->secondary->hasFields()) {
                $this->allTabs->secondary->stretch = true;
            }
            elseif ($this->allTabs->primary->hasFields()) {
                $this->allTabs->primary->stretch = true;
            }
            else {
                $this->allTabs->outside->stretch = true;
            }
        }

        $this->fieldsDefined = true;
    }

    /**
     * addFields programatically, used internally and for extensibility
     * @param array $fields
     * @param string $addToArea
     */
    public function addFields(array $fields, $addToArea = null)
    {
        foreach ($fields as $name => $config) {
            $fieldObj = $this->makeFormField($name, $config);

            // Check form field matches the active context
            if ($fieldObj->context !== null) {
                $context = is_array($fieldObj->context) ? $fieldObj->context : [$fieldObj->context];
                if (!in_array($this->getContext(), $context)) {
                    continue;
                }
            }

            // Field name without @context suffix
            $fieldName = $fieldObj->fieldName;

            $this->allFields[$fieldName] = $fieldObj;

            switch (strtolower($addToArea)) {
                case FormTabs::SECTION_PRIMARY:
                    $this->allTabs->primary->addField($fieldName, $fieldObj);
                    break;
                case FormTabs::SECTION_SECONDARY:
                    $this->allTabs->secondary->addField($fieldName, $fieldObj);
                    break;
                default:
                    $this->allTabs->outside->addField($fieldName, $fieldObj);
                    break;
            }
        }
    }

    /**
     * addFieldsFromModel from the model
     */
    protected function addFieldsFromModel(string $addToArea = null): void
    {
        if ($this->isNested || !$this->model) {
            return;
        }

        switch (strtolower($addToArea)) {
            case FormTabs::SECTION_PRIMARY:
                $this->activeTabSection = $this->allTabs->primary;
                $modelMethod = 'definePrimaryFormFields';
                break;
            case FormTabs::SECTION_SECONDARY:
                $this->activeTabSection = $this->allTabs->secondary;
                $modelMethod = 'defineSecondaryFormFields';
                break;
            default:
                $this->activeTabSection = $this->allTabs->outside;
                $modelMethod = 'defineFormFields';
                break;
        }

        if (method_exists($this->model, $modelMethod)) {
            $this->model->$modelMethod($this);
        }
    }

    /**
     * addTabFields
     */
    public function addTabFields(array $fields)
    {
        $this->addFields($fields, 'primary');
    }

    /**
     * addSecondaryTabFields
     */
    public function addSecondaryTabFields(array $fields)
    {
        $this->addFields($fields, 'secondary');
    }

    /**
     * removeField programatically
     */
    public function removeField($name): bool
    {
        if (!isset($this->allFields[$name])) {
            return false;
        }

        /*
         * Remove from tabs
         */
        $this->allTabs->primary->removeField($name);
        $this->allTabs->secondary->removeField($name);
        $this->allTabs->outside->removeField($name);

        /*
         * Remove from main collection
         */
        unset($this->allFields[$name]);

        return true;
    }

    /**
     * removeTab programatically remove all fields belonging to a tab
     * @param string $name
     */
    public function removeTab($name)
    {
        foreach ($this->allFields as $fieldName => $field) {
            if ($field->tab == $name) {
                $this->removeField($fieldName);
            }
        }
    }

    /**
     * makeFormField creates a form field object from name and configuration
     */
    protected function makeFormField(string $name, $config = []): FormField
    {
        $label = $config['label'] ?? null;
        [$fieldName, $fieldContext] = $this->evalFieldName($name);

        $field = new FormField($fieldName, $label);

        if ($fieldContext) {
            $field->context = $fieldContext;
        }

        $field->arrayName = $this->arrayName;
        $field->idPrefix = $this->getId();

        /*
         * Simple field type
         */
        if (is_string($config)) {
            $field->displayAs($config);
        }
        /*
         * Defined field type
         */
        else {
            $fieldType = $config['type'] ?? null;
            if (!is_string($fieldType) && $fieldType !== null) {
                throw new SystemException(Lang::get(
                    'backend::lang.field.invalid_type',
                    ['type' => gettype($fieldType)]
                ));
            }

            if ($config) {
                $field->useConfig($config);
            }

            if ($fieldType) {
                $field->displayAs($fieldType);
            }
        }

        return $field;
    }

    /**
     * getFields for the instance
     */
    public function getFields(): array
    {
        return $this->allFields;
    }

    /**
     * getField object specified
     */
    public function getField(string $field)
    {
        if (isset($this->allFields[$field])) {
            return $this->allFields[$field];
        }

        return null;
    }

    /**
     * getTabs for the instance
     * @return object[FormTabs]
     */
    public function getTabs()
    {
        return $this->allTabs;
    }

    /**
     * Get a specified tab object.
     * Options: outside, primary, secondary.
     *
     * @param string $field
     * @return mixed
     */
    public function getTab($tab)
    {
        if (isset($this->allTabs->$tab)) {
            return $this->allTabs->$tab;
        }

        return null;
    }

    /**
     * evalFieldName parses a field's name for embedded context
     * with a result of fieldName@context to [fieldName, context]
     */
    protected function evalFieldName(string $field): array
    {
        if (strpos($field, '@') === false) {
            return [$field, null];
        }

        return explode('@', $field);
    }

    /**
     * hasFieldValue determines if the field value is found in the data.
     */
    protected function hasFieldValue($field, $data = null): bool
    {
        return $field->getValueFromData($data, FormField::NO_SAVE_DATA) !== FormField::NO_SAVE_DATA;
    }

    /**
     * Looks up the field value.
     * @param mixed $field
     * @param mixed $data
     * @return string
     */
    protected function getFieldValue($field, $data = null)
    {
        if ($data === null) {
            $data = $this->data;
        }

        if (is_string($field)) {
            if (!isset($this->allFields[$field])) {
                throw new SystemException(Lang::get(
                    'backend::lang.form.missing_definition',
                    compact('field')
                ));
            }

            $field = $this->allFields[$field];
        }

        $defaultValue = $this->useDefaultValues()
            ? $field->getDefaultFromData($data)
            : null;

        // No translation on complex arrays (i.e repeater defaults)
        $defaultValue = is_string($defaultValue)
            ? Lang::get($defaultValue)
            : $defaultValue;

        return $field->getValueFromData($data, $defaultValue);
    }

    /**
     * useDefaultValues determines when to apply default data
     */
    protected function useDefaultValues(): bool
    {
        return $this->isNested || !$this->model->exists;
    }

    /**
     * getFieldDepends returns a HTML encoded value containing the other fields
     * this field depends on
     * @param  \Backend\Classes\FormField $field
     */
    protected function getFieldDepends($field): string
    {
        if (!$field->dependsOn) {
            return '';
        }

        $dependsOn = is_array($field->dependsOn) ? $field->dependsOn : [$field->dependsOn];

        $dependsOn = htmlspecialchars(json_encode($dependsOn), ENT_QUOTES, 'UTF-8');

        return $dependsOn;
    }

    /**
     * showFieldLabels is a helper method to determine if field should be rendered
     * with label and comments.
     */
    protected function showFieldLabels(FormField $field): bool
    {
        if (in_array($field->type, ['checkbox', 'switch', 'section', 'hint'])) {
            return false;
        }

        if ($field->type === 'widget') {
            return (bool) ($this->makeFormFieldWidget($field)->showLabels ?? true);
        }

        return true;
    }

    /**
     * Returns post data from a submitted form.
     *
     * @return array
     */
    public function getSaveData()
    {
        $this->defineFormFields();

        $saveData = $this->getSaveDataInternal();

        $this->applyFiltersFromModel($saveData);

        return $this->cleanSaveDataInternal($saveData);
    }

    /**
     * getSaveDataInternal will return all possible data to save
     */
    protected function getSaveDataInternal(): array
    {
        $this->defineFormFields();

        $result = [];

        /*
         * Source data
         */
        $data = $this->arrayName ? post($this->arrayName) : post();
        if (!$data) {
            $data = [];
        }

        /*
         * Spin over each field and extract the postback value
         */
        foreach ($this->allFields as $name => $field) {
            /*
             * Handle HTML array, eg: item[key][another]
             */
            $parts = HtmlHelper::nameToArray($name);
            if (($value = $this->dataArrayGet($data, $parts)) !== null) {
                // Convert number to float
                if ($field->type === 'number') {
                    $value = !strlen(trim($value)) ? null : (float) $value;
                }

                $this->dataArraySet($result, $parts, $value);
            }
        }

        /*
         * Give widgets an opportunity to process the data.
         */
        foreach ($this->formWidgets as $field => $widget) {
            /*
             * Handle HTML array, eg: item[key][another]
             */
            $parts = HtmlHelper::nameToArray($field);
            if (($value = $this->dataArrayGet($data, $parts)) !== null) {
                $widgetValue = $widget->getSaveValue($value);
                $this->dataArraySet($result, $parts, $widgetValue);
            }
        }

        return $result;
    }

    /**
     * cleanSaveDataInternal will purge disabled and hidden fields from the dataset
     */
    protected function cleanSaveDataInternal(array $data): array
    {
        foreach ($this->allFields as $name => $field) {
            if ($field->disabled || $field->hidden) {
                $parts = HtmlHelper::nameToArray($name);
                $this->dataArrayForget($data, $parts);
            }
        }

        return $data;
    }

    /**
     * applyFiltersFromModel allows the model to filter fields
     */
    protected function applyFiltersFromModel($applyData = null)
    {
        $targetModel = clone $this->model;

        /*
         * Apply specified data before filtering
         */
        if ($applyData) {
            if (method_exists($targetModel, 'fill')) {
                $this->prepareModelsToSave($targetModel, $applyData);
            }

            foreach ($this->allFields as $field) {
                if ($this->hasFieldValue($field, $applyData)) {
                    $field->value = $this->getFieldValue($field, $applyData);
                }
            }
        }

        /*
         * Standard usage
         */
        if (method_exists($targetModel, 'filterFields')) {
            $targetModel->filterFields((object) $this->allFields, $this->getContext());
        }

        /*
         * Advanced usage
         */
        if (method_exists($targetModel, 'fireEvent')) {
            /**
             * @event model.form.filterFields
             * Called after the form is initialized
             *
             * Example usage:
             *
             *     $model->bindEvent('model.form.filterFields', function ((\Backend\Widgets\Form) $formWidget, (stdClass) $fields, (string) $context) use (\October\Rain\Database\Model $model) {
             *         if ($model->source_type == 'http') {
             *             $fields->source_url->hidden = false;
             *             $fields->git_branch->hidden = true;
             *         }
             *         elseif ($model->source_type == 'git') {
             *             $fields->source_url->hidden = false;
             *             $fields->git_branch->hidden = false;
             *         }
             *         else {
             *             $fields->source_url->hidden = true;
             *             $fields->git_branch->hidden = true;
             *         }
             *     });
             *
             */
            $targetModel->fireEvent('model.form.filterFields', [$this, (object) $this->allFields, $this->getContext()]);
        }
    }

    /**
     * getSessionKey returns the active session key.
     * @return string
     */
    public function getSessionKey()
    {
        if ($this->sessionKey) {
            return $this->sessionKey;
        }

        $sessionKey = post('_session_key');

        if (!$sessionKey) {
            $sessionKey = FormHelper::getSessionKey();
        }

        return $this->sessionKey = $sessionKey;
    }

    /**
     * getSessionKeyWithPrefix
     * @return string
     */
    public function getSessionKeyWithSuffix()
    {
        return $this->getSessionKey() . $this->sessionKeySuffix;
    }

    /**
     * getContext returns the active context for displaying the form.
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Variant to array_get() but preserves dots in key names.
     *
     * @param array $array
     * @param array $parts
     * @param null $default
     * @return mixed
     */
    protected function dataArrayGet(array $array, array $parts, $default = null)
    {
        if (count($parts) === 1) {
            $key = array_shift($parts);
            if (isset($array[$key])) {
                return $array[$key];
            }

            return $default;
        }

        foreach ($parts as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Variant to array_set() but preserves dots in key names.
     *
     * @param array $array
     * @param array $parts
     * @param string $value
     * @return array
     */
    protected function dataArraySet(array &$array, array $parts, $value)
    {
        while (count($parts) > 1) {
            $key = array_shift($parts);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array =& $array[$key];
        }

        $array[array_shift($parts)] = $value;

        return $array;
    }

    /**
     * Variant to array_forget() but preserves dots in key names.
     *
     * @param array $array
     * @param array $parts
     * @return void
     */
    protected function dataArrayForget(array &$array, array $parts)
    {
        while (count($parts) > 1) {
            $part = array_shift($parts);

            if (isset($array[$part]) && is_array($array[$part])) {
                $array = &$array[$part];
            }
            else {
                continue;
            }
        }

        unset($array[array_shift($parts)]);
    }
}
