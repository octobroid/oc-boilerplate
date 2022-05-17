<?php namespace Backend\Elements;

use Backend;

/**
 * FormToolbar
 */
class FormToolbar
{
    use \Backend\Traits\ElementRenderer;

    /**
     * @var callable|array|string body
     */
    protected $body;

    /**
     * @var string cancelUrl
     */
    protected $cancelUrl;

    /**
     * __construct
     */
    public function __construct(...$body)
    {
        $this->body = $body;
    }

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

    /**
     * cancelUrl
     */
    public function cancelUrl(string $cancelUrl): FormToolbar
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }
}
