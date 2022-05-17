<?php namespace Backend\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * Sensitive widget renders a password field that can be optionally made visible
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Sensitive extends FormWidgetBase
{
    /**
     * @var bool readOnly if the sensitive field cannot be edited, but can be toggled
     */
    public $readOnly = false;

    /**
     * @var bool disabled if the sensitive field is disabled
     */
    public $disabled = false;

    /**
     * @var bool allowCopy if a button will be available to copy the value
     */
    public $allowCopy = false;

    /**
     * @var string displayMode determines how the widget is displayed. Modes: text, textarea
     */
    public $displayMode = 'text';

    /**
     * @var string hiddenPlaceholder string used as a placeholder for an unrevealed sensitive value
     */
    public $hiddenPlaceholder = '__hidden__';

    /**
     * @var bool hideOnTabChange if the sensitive input will be hidden if the user changes to another tab
     */
    public $hideOnTabChange = true;

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'sensitive';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'readOnly',
            'disabled',
            'allowCopy',
            'hiddenPlaceholder',
            'hideOnTabChange',
        ]);

        $this->displayMode = isset($this->config->mode) && in_array($this->config->mode, ['text', 'textarea'])
            ? $this->config->mode
            : 'text';

        if ($this->formField->disabled || $this->formField->readOnly) {
            $this->previewMode = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('sensitive');
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        $this->vars['readOnly'] = $this->readOnly;
        $this->vars['disabled'] = $this->disabled;
        $this->vars['hasValue'] = !empty($this->getLoadValue());
        $this->vars['allowCopy'] = $this->allowCopy;
        $this->vars['hiddenPlaceholder'] = $this->hiddenPlaceholder;
        $this->vars['hideOnTabChange'] = $this->hideOnTabChange;
        $this->vars['displayMode'] = $this->displayMode;
    }

    /**
     * onShowValue reveals the value of a hidden, unmodified sensitive field
     */
    public function onShowValue()
    {
        return [
            'value' => $this->getLoadValue()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        if ($value === $this->hiddenPlaceholder) {
            $value = $this->getLoadValue();
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss('css/sensitive.css', 'core');
        $this->addJs('js/sensitive.js', 'core');
    }
}
