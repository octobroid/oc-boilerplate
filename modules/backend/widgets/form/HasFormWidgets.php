<?php namespace Backend\Widgets\Form;

use Lang;
use SystemException;
use Backend\Classes\FormField;
use Backend\Classes\WidgetManager;
use Backend\Classes\FormWidgetBase;

/**
 * HasFormWidgets concern
 */
trait HasFormWidgets
{
    /**
     * @var array Collection of all form widgets used in this form.
     */
    protected $formWidgets = [];

    /**
     * @var \Backend\Classes\WidgetManager widgetManager
     */
    protected $widgetManager;

    /**
     * initFormWidgetsConcern
     */
    protected function initFormWidgetsConcern()
    {
        $this->widgetManager = WidgetManager::instance();
    }

    /**
     * makeFormFieldWidget object from a form field object
     */
    protected function makeFormFieldWidget(FormField $field): ?FormWidgetBase
    {
        if ($field->type !== 'widget') {
            return null;
        }

        if (isset($this->formWidgets[$field->fieldName])) {
            return $this->formWidgets[$field->fieldName];
        }

        $widgetConfig = $this->makeConfig($field->config);
        $widgetConfig->alias = $this->alias . studly_case($this->nameToId($field->fieldName));
        $widgetConfig->sessionKey = $this->getSessionKey();
        $widgetConfig->sessionKeySuffix = $this->sessionKeySuffix;
        $widgetConfig->previewMode = $this->previewMode;
        $widgetConfig->model = $this->model;
        $widgetConfig->data = $this->data;
        $widgetConfig->parentForm = $this;

        $widgetName = $widgetConfig->widget;
        $widgetClass = $this->widgetManager->resolveFormWidget($widgetName);

        if (!class_exists($widgetClass)) {
            throw new SystemException(Lang::get(
                'backend::lang.widget.not_registered',
                ['name' => $widgetClass]
            ));
        }

        $widget = $this->makeFormWidget($widgetClass, $field, $widgetConfig);

        // If options config is defined, request options from the model.
        if (isset($field->config['options'])) {
            $field->options(function () use ($field) {
                $fieldOptions = $field->config['options'];
                return $field->getOptionsFromModel($this->model, $fieldOptions, $this->data);
            });
        }

        return $this->formWidgets[$field->fieldName] = $widget;
    }

    /**
     * isFormWidget checks if a field type is a widget or not
     */
    protected function isFormWidget(string $fieldType): bool
    {
        if (!$fieldType) {
            return false;
        }

        if (strpos($fieldType, '\\')) {
            return true;
        }

        $widgetClass = $this->widgetManager->resolveFormWidget($fieldType);

        if (!class_exists($widgetClass)) {
            return false;
        }

        if (is_subclass_of($widgetClass, 'Backend\Classes\FormWidgetBase')) {
            return true;
        }

        return false;
    }

    /**
     * getFormWidgets for the instance
     */
    public function getFormWidgets(): array
    {
        return $this->formWidgets;
    }

    /**
     * getFormWidget returns a specified form widget
     * @param string $field
     */
    public function getFormWidget($field)
    {
        if (isset($this->formWidgets[$field])) {
            return $this->formWidgets[$field];
        }

        return null;
    }
}
