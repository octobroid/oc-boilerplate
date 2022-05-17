<?php namespace Backend\Elements;

use Html;
use Backend;

/**
 * Button
 */
class Button
{
    use \Backend\Traits\ElementRenderer;

    /**
     * @var string label for the button
     */
    protected $label;

    /**
     * @var string linkUrl will use an anchor button
     */
    protected $linkUrl;

    /**
     * @var string cssClass for the button
     */
    protected $cssClass;

    /**
     * @var string replaceCssClass defaults for the button
     */
    protected $replaceCssClass;

    /**
     * @var array hotkey patterns
     */
    protected $hotkey;

    /**
     * @var string type of button
     */
    protected $type;

    /**
     * @var array attributes in HTML
     */
    protected $attributes;

    /**
     * @var bool isPrimary button
     */
    protected $isPrimary = false;

    /**
     * __construct
     */
    public function __construct(string $label)
    {
        $this->label = $label;
    }

    /**
     * render the element
     */
    public function render(): callable
    {
        return function() { ?>

            <?php if ($this->linkUrl): ?>

                <a href="<?= $this->linkUrl ?>"
                    <?= Html::attributes($this->buildAttributes()) ?>
                >
                    <?= $this->label ?>
                </a>

            <?php else: ?>

                <button <?= Html::attributes($this->buildAttributes()) ?>>
                    <?= $this->label ?>
                </button>

            <?php endif ?>

        <?php };
    }

    /**
     * setDefaults
     */
    protected function setDefaults(): void
    {
        if ($this->type === null) {
            $this->type = $this->isPrimary ? 'submit' : 'button';
        }
    }

    /**
     * buildAttributes
     */
    protected function buildAttributes(array $attr = []): array
    {
        $attr['type'] = $this->type;

        if ($this->hotkey) {
            $attr['data-hotkey'] = implode(',', $this->hotkey);
        }

        $attr['class'] = $this->buildCssClass();

        return $attr;
    }

    /**
     * buildCssClass
     */
    protected function buildCssClass(): string
    {
        if ($this->replaceCssClass !== null) {
            return $this->replaceCssClass;
        }

        $css = [];

        $css[] = 'btn';

        if ($this->isPrimary) {
            $css[] = 'btn-primary';
        }
        else {
            $css[] = 'btn-default';
        }

        $css[] = $this->cssClass;

        return implode(' ', $css);
    }

    /**
     * label
     */
    public function label(string $label): Button
    {
        $this->label = $label;

        return $this;
    }

    /**
     * linkTo
     */
    public function linkTo(string $linkUrl, bool $isRaw = false): Button
    {
        $this->linkUrl = $isRaw ? $linkUrl : Backend::url($linkUrl);

        return $this;
    }

    /**
     * cssClass
     */
    public function replaceCssClass(string $replaceCssClass): Button
    {
        $this->replaceCssClass = $replaceCssClass;

        return $this;
    }

    /**
     * cssClass
     */
    public function cssClass(string $cssClass): Button
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    /**
     * type
     */
    public function type(string $type): Button
    {
        $this->type = $type;

        return $this;
    }

    /**
     * hotkey
     */
    public function hotkey(...$hotkey): Button
    {
        $this->hotkey = $hotkey;

        return $this;
    }

    /**
     * attributes
     */
    public function attributes(array $attributes): Button
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * isPrimary
     */
    public function primary(): Button
    {
        $this->isPrimary = true;

        return $this;
    }
}
