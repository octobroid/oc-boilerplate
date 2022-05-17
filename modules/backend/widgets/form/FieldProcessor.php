<?php namespace Backend\Widgets\Form;

use BackendAuth;
use Backend\Classes\FormTabs;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * FieldProcessor concern
 */
trait FieldProcessor
{
    /**
     * processAutoSpan converts fields with a span set to 'auto' as either
     * 'left' or 'right' depending on the previous field.
     */
    protected function processAutoSpan(FormTabs $tabs)
    {
        $prevSpan = null;

        foreach ($tabs->getFields() as $fields) {
            foreach ($fields as $field) {
                // Auto sizing
                if (strtolower($field->span) === 'auto') {
                    if ($prevSpan === 'left') {
                        $field->span = 'right';
                    }
                    else {
                        $field->span = 'left';
                    }
                }

                $prevSpan = $field->span;

                // Adaptive sizing
                if (strtolower($field->span) === 'adaptive') {
                    $field->size = 'adaptive';
                    $field->stretch = true;
                    $tabs->stretch = true;
                    $tabs->addAdaptive($field->tab);
                }
            }
        }
    }

    /**
     * processPermissionCheck check if user has permissions to show the field
     * and removes it if permission is denied
     */
    protected function processPermissionCheck(array $fields): void
    {
        foreach ($fields as $fieldName => $field) {
            if (
                $field->permissions &&
                !BackendAuth::getUser()->hasAccess($field->permissions, false)
            ) {
                $this->removeField($fieldName);
            }
        }
    }

    /**
     * processFormWidgetFields will mutate fields types that are registered as widgets,
     * convert their type to 'widget' and internally allocate the widget object
     */
    protected function processFormWidgetFields(array $fields): void
    {
        foreach ($fields as $field) {
            if (!$this->isFormWidget((string) $field->type)) {
                continue;
            }

            $newConfig = ['widget' => $field->type];

            if (is_array($field->config)) {
                $newConfig += $field->config;
            }

            $field->useConfig($newConfig)->displayAs('widget');

            /*
             * Create form widget instance and bind to controller
             */
            $this->makeFormFieldWidget($field)->bindToController();
        }
    }

    /**
     * processValidationAttributes applies the field name to the validation engine
     */
    protected function processValidationAttributes(array $fields): void
    {
        if (!$this->model || !method_exists($this->model, 'setValidationAttributeName')) {
            return;
        }

        foreach ($fields as $field) {
            $attrName = implode('.', HtmlHelper::nameToArray($field->fieldName));
            $this->model->setValidationAttributeName($attrName, $field->label);
        }
    }

    /**
     * processFieldOptionValues sets the callback for retrieving options
     */
    protected function processFieldOptionValues(array $fields): void
    {
        $optionModelTypes = ['dropdown', 'radio', 'checkboxlist', 'balloon-selector'];

        foreach ($fields as $field) {
            if (!in_array($field->type, $optionModelTypes, false)) {
                continue;
            }

            // Specified explicitly on the object already
            if ($field->hasOptions() && is_array($field->options)) {
                continue;
            }

            // Look at config value in case it was missed
            $fieldOptions = $field->options ?: ($field->config['options'] ?? null);

            // Defer the execution of option data collection
            $field->options(function () use ($field, $fieldOptions) {
                return $field->getOptionsFromModel($this->model, $fieldOptions, $this->data);
            });
        }
    }

    /**
     * processRequiredAttributes will set the required flag based on the model preference
     */
    protected function processRequiredAttributes(array $fields): void
    {
        if (!$this->model || !method_exists($this->model, 'setValidationAttributeName')) {
            return;
        }

        foreach ($fields as $field) {
            if ($field->required !== null) {
                continue;
            }

            $attrName = implode('.', HtmlHelper::nameToArray($field->fieldName));
            $field->required = $this->model->isAttributeRequired($attrName);
        }
    }
}
