<?php namespace Backend\Elements;

/**
 * AjaxButton
 */
class AjaxButton extends Button
{
    /**
     * @var string ajaxHandler
     */
    protected $ajaxHandler;

    /**
     * @var array ajaxData
     */
    protected $ajaxData;

    /**
     * @var string confirmMessage
     */
    protected $confirmMessage;

    /**
     * @var string loadingMessage
     */
    protected $loadingMessage;

    /**
     * __construct
     */
    public function __construct(string $label, string $ajaxHandler)
    {
        $this->label = $label;
        $this->ajaxHandler = $ajaxHandler;
    }

    /**
     * buildAttributes
     */
    protected function buildAttributes(array $attr = []): array
    {
        $attr = parent::buildAttributes($attr);

        $attr['data-request'] = $this->ajaxHandler;

        if ($this->ajaxData !== null) {
            $attr['data-request-data'] = $this->ajaxData;
        }

        if ($this->confirmMessage !== null) {
            $attr['data-request-confirm'] = $this->confirmMessage;
        }

        if ($this->loadingMessage !== null) {
            $attr['data-load-indicator'] = $this->loadingMessage;
        }

        return $attr;
    }

    /**
     * ajaxHandler
     */
    public function handler(string $ajaxHandler): AjaxButton
    {
        $this->ajaxHandler = $ajaxHandler;

        return $this;
    }

    /**
     * data
     */
    public function data(array $ajaxData): AjaxButton
    {
        $this->ajaxData = $ajaxData;

        return $this;
    }

    /**
     * confirmMessage
     */
    public function confirmMessage(string $confirmMessage): AjaxButton
    {
        $this->confirmMessage = $confirmMessage;

        return $this;
    }

    /**
     * loadingMessage
     */
    public function loadingMessage(string $loadingMessage): AjaxButton
    {
        $this->loadingMessage = $loadingMessage;

        return $this;
    }

    /**
     * formDeleteButton
     */
    public function formDeleteButton()
    {
        $this->label = '';

        $this->replaceCssClass('oc-icon-trash-o btn-icon danger pull-right');

        return $this;
    }
}
