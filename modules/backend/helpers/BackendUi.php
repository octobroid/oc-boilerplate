<?php namespace Backend\Helpers;

/**
 * BackendUi Helper
 *
 * @package october\backend
 * @see \Backend\Facades\Ui
 * @author Alexey Bobkov, Samuel Georges
 */
class BackendUi
{
    /**
     * button
     */
    public function button(...$args): \Backend\Elements\Button
    {
        return new \Backend\Elements\Button(...$args);
    }

    /**
     * ajaxButton
     */
    public function ajaxButton(...$args): \Backend\Elements\AjaxButton
    {
        return new \Backend\Elements\AjaxButton(...$args);
    }

    /**
     * formToolbar
     */
    public function formToolbar(...$args): \Backend\Elements\FormToolbar
    {
        return new \Backend\Elements\FormToolbar(...$args);
    }

    /**
     * callout
     */
    public function callout(...$args): \Backend\Elements\Callout
    {
        return new \Backend\Elements\Callout(...$args);
    }

    /**
     * contentPlaceholder
     */
    public function contentPlaceholder(...$args): \Backend\Elements\ContentPlaceholder
    {
        return new \Backend\Elements\ContentPlaceholder(...$args);
    }
}
