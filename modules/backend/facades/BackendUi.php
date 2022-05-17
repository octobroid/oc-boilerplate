<?php namespace Backend\Facades;

use October\Rain\Support\Facade;

/**
 * BackendUi facade
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 *
 * @method static \Backend\Elements\Button button(string $label)
 * @method static \Backend\Elements\AjaxButton ajaxButton(string $label, string $ajaxHandler)
 * @method static \Backend\Elements\FormToolbar formToolbar(callable $body)
 * @method static \Backend\Elements\Callout callout(callable $body = null)
 * @method static \Backend\Elements\ContentPlaceholder contentPlaceholder()
 */
class BackendUi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Backend\Helpers\BackendUi
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'backend.ui';
    }
}
