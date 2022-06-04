<?php namespace Backend\Elements;

use Backend\Classes\UiElement;

/**
 * Callout
 *
 * @method Callout type(string $type) type
 * @method Callout icon(string $icon) icon
 * @method Callout label(string $label) label
 * @method Callout comment(string $comment) comment
 * @method Callout cssClass(string $cssClass) cssClass
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Callout extends UiElement
{
    /**
     * initDefaultValues override method
     */
    protected function initDefaultValues()
    {
        $this->type('info');
    }

    /**
     * render the element
     */
    public function render(): callable
    {
        return function() { ?>

            <div class="<?= $this->buildCssClass() ?>">
                <?php if ($this->hasHeader()): ?>
                    <div class="header">
                        <?php if ($this->icon): ?>
                            <i class="<?= $this->icon ?>"></i>
                        <?php endif ?>
                        <?php if ($this->label): ?>
                            <h3><?= $this->label ?></h3>
                        <?php endif ?>
                        <?php if ($this->comment): ?>
                            <?= $this->comment ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>
                <?php if ($this->body): ?>
                    <div class="content">
                        <?= $this->renderBody($this->body) ?>
                    </div>
                <?php endif ?>
            </div>

        <?php };
    }

    /**
     * buildCssClass
     */
    protected function buildCssClass(): string
    {
        $css = [];
        $css[] = 'callout fade show';
        $css[] = 'callout-'.$this->type;

        if (!$this->icon || !$this->hasHeader()) {
            $css[] = 'no-icon';
        }

        if (!$this->label) {
            $css[] = 'no-title';
        }

        if (!$this->comment) {
            $css[] = 'no-subheader';
        }

        $css[] = $this->cssClass;

        return implode(' ', $css);
    }

    /**
     * hasHeader
     */
    protected function hasHeader(): bool
    {
        return $this->label || $this->comment;
    }

    /**
     * success
     */
    public function success(): static
    {
        $this->type('success');
        $this->icon('icon-check');

        return $this;
    }

    /**
     * danger
     */
    public function danger(): static
    {
        $this->type('danger');
        $this->icon('icon-exclamation');

        return $this;
    }

    /**
     * warning
     */
    public function warning(): static
    {
        $this->type('warning');
        $this->icon('icon-flag');

        return $this;
    }

    /**
     * tip
     */
    public function tip(): static
    {
        $this->type('info');
        $this->icon('icon-info');

        return $this;
    }
}
