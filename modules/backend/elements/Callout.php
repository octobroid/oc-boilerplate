<?php namespace Backend\Elements;

/**
 * Callout
 */
class Callout
{
    use \Backend\Traits\ElementRenderer;

    /**
     * @var callable|array|string body
     */
    protected $body;

    /**
     * @var string type
     */
    protected $type = 'info';

    /**
     * @var string icon
     */
    protected $icon;

    /**
     * @var string label
     */
    protected $label;

    /**
     * @var string comment
     */
    protected $comment;

    /**
     * @var string cssClass
     */
    protected $cssClass;

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
                            <p><?= $this->comment ?></p>
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
        $css[] = 'callout fade in';
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
    public function success(): Callout
    {
        $this->type('success');
        $this->icon('icon-check');

        return $this;
    }

    /**
     * danger
     */
    public function danger(): Callout
    {
        $this->type('danger');
        $this->icon('icon-exclamation');

        return $this;
    }

    /**
     * warning
     */
    public function warning(): Callout
    {
        $this->type('warning');
        $this->icon('icon-flag');

        return $this;
    }

    /**
     * tip
     */
    public function tip(): Callout
    {
        $this->type('info');
        $this->icon('icon-info');

        return $this;
    }

    /**
     * type
     */
    public function type(string $type): Callout
    {
        $this->type = $type;

        return $this;
    }

    /**
     * icon
     */
    public function icon(string $icon): Callout
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * label
     */
    public function label(string $label): Callout
    {
        $this->label = $label;

        return $this;
    }

    /**
     * comment
     */
    public function comment(string $comment): Callout
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * cssClass
     */
    public function cssClass(string $cssClass): Callout
    {
        $this->cssClass = $cssClass;

        return $this;
    }
}
