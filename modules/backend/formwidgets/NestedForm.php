<?php namespace Backend\FormWidgets;

use Backend\Widgets\Form;
use Backend\Classes\FormWidgetBase;
use October\Rain\Database\Model;

/**
 * NestedForm widget
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class NestedForm extends FormWidgetBase
{
    use \Backend\Traits\FormModelWidget;

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'nestedform';

    /**
     * @var array form configuration
     */
    public $form;

    /**
     * @var bool showPanel defines if the nested form is styled like a panel
     */
    public $showPanel = true;

    /**
     * @var bool useRelation will instruct the widget to look for a relationship
     */
    protected $useRelation = false;

    /**
     * @var Form formWidget reference
     */
    protected $formWidget;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'form',
            'showPanel'
        ]);

        if ($this->formField->disabled) {
            $this->previewMode = true;
        }

        $this->processRelationMode();

        $this->makeNestedFormWidget();
    }

    /**
     * makeNestedFormWidget creates a form widget
     */
    protected function makeNestedFormWidget()
    {
        $config = $this->makeConfig($this->form);

        if ($this->useRelation) {
            $config->model = $this->getLoadValueFromRelation();
        }
        else {
            $config->model = $this->model;
            $config->data = $this->getLoadValue();
            $config->isNested = true;
        }

        $config->alias = $this->alias . $this->defaultAlias;
        $config->arrayName = $this->getFieldName();

        $widget = $this->makeWidget(Form::class, $config);
        $widget->previewMode = $this->previewMode;
        $widget->bindToController();

        $this->formWidget = $widget;
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        $this->formWidget->previewMode = $this->previewMode;
    }

    /**
     * resetFormValue from the form field
     */
    public function resetFormValue()
    {
        $this->formWidget->setFormValues($this->formField->value);
    }

    /**
     * getLoadValueFromRelation
     */
    protected function getLoadValueFromRelation()
    {
        [$model, $attribute] = $this->resolveModelAttribute($this->valueFrom);

        return $model->{$attribute} ?? $this->getRelationModel();
    }

    /**
     * loadAssets
     */
    protected function loadAssets()
    {
        $this->addCss('css/nestedform.css', 'core');
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('nestedform');
    }

    /**
     * processRelationMode
     */
    protected function processRelationMode()
    {
        [$model, $attribute] = $this->nearestModelAttribute($this->valueFrom);

        if ($model instanceof Model && $model->hasRelation($attribute)) {
            $this->useRelation = true;
        }
    }
}
