<?php namespace Backend\Elements;

use Backend;
use Backend\Classes\UiElement;

/**
 * FormToolbar
 *
 * @method FormToolbar cancelUrl(string $cancelUrl) cancelUrl
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class FormToolbar extends UiElement
{
    /**
     * render the element
     */
    public function render(): callable
    {
        return function() { ?>

            <div class="form-buttons">
                <div class="loading-indicator-container">
                    <?= $this->renderBody($this->body) ?>

                    <?php if ($this->cancelUrl): ?>
                        <span class="btn-text">
                            <?= e(trans('backend::lang.form.or')) ?>
                            <a href="<?= Backend::url($this->cancelUrl) ?>">
                                <?= e(trans('backend::lang.form.cancel')) ?>
                            </a>
                        </span>
                    <?php endif ?>
                </div>
            </div>

        <?php };
    }
}
