<?php namespace Backend\Elements;

use Html;
use Backend;
use Backend\Classes\UiElement;

/**
 * Button
 *
 * @method Button label(string $label) label for the button
 * @method Button linkUrl(string $linkUrl) linkUrl will use an anchor button
 * @method Button cssClass(string $cssClass) cssClass for the button
 * @method Button replaceCssClass(string $replaceCssClass) replaceCssClass defaults for the button
 * @method Button hotkey(...$hotkey) hotkey patterns
 * @method Button type(string $type) type of button
 * @method Button attributes(array $attributes) attributes in HTML
 * @method Button isPrimary(string $isPrimary) isPrimary button
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Button extends UiElement
{
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
     * linkTo
     */
    public function linkTo(string $linkUrl, bool $isRaw = false): static
    {
        $this->linkUrl = $isRaw ? $linkUrl : Backend::url($linkUrl);

        return $this;
    }
}
