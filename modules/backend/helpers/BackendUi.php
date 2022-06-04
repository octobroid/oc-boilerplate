<?php namespace Backend\Helpers;

use Backend\Elements\Button;
use Backend\Elements\AjaxButton;
use Backend\Elements\FormToolbar;
use Backend\Elements\Callout;
use Backend\Elements\ContentPlaceholder;

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
    public function button(...$args): Button
    {
        return new Button(...$args);
    }

    /**
     * ajaxButton
     */
    public function ajaxButton(...$args): AjaxButton
    {
        return new AjaxButton(...$args);
    }

    /**
     * formToolbar
     */
    public function formToolbar(...$args): FormToolbar
    {
        return new FormToolbar(...$args);
    }

    /**
     * callout
     */
    public function callout(...$args): Callout
    {
        return new Callout(...$args);
    }

    /**
     * contentPlaceholder
     */
    public function contentPlaceholder(...$args): ContentPlaceholder
    {
        return new ContentPlaceholder(...$args);
    }
}
