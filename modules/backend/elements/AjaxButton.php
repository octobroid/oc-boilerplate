<?php namespace Backend\Elements;

/**
 * AjaxButton
 *
 * @method AjaxButton ajaxHandler(string $ajaxHandler) ajaxHandler
 * @method AjaxButton ajaxData(array $ajaxData) ajaxData
 * @method AjaxButton confirmMessage(string $confirmMessage) confirmMessage
 * @method AjaxButton loadingMessage(string $loadingMessage) loadingMessage
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class AjaxButton extends Button
{
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
     * formDeleteButton
     */
    public function formDeleteButton(): static
    {
        $this->label('');

        $this->replaceCssClass('oc-icon-trash-o btn-icon danger pull-right');

        return $this;
    }
}
