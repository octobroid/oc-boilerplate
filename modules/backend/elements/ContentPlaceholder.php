<?php namespace Backend\Elements;

use Backend\Classes\UiElement;

/**
 * ContentPlaceholder
 *
 * @method ContentPlaceholder stack(array $stack) stack
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ContentPlaceholder extends UiElement
{
    /**
     * initDefaultValues override method
     */
    protected function initDefaultValues()
    {
        $this->stack([]);
    }

    /**
     * render the element
     */
    public function render(): callable
    {
        return function() { ?>

            <div class="element-content-placeholder is-animated is-rounded">
                <?php foreach ($this->stack as $item): ?>
                    <?= $this->renderStackItems($item) ?>
                <?php endforeach ?>
            </div>

        <?php };
    }

    /**
     * renderStackItems converts a PHP array to an array of HTML divs
     */
    protected function renderStackItems(array $item): string
    {
        $result = '';

        foreach ($item as $class => $stack) {
            if (is_array($stack)) {
                $result .= '<div class="'.$class.'">'.$this->renderStackItems($stack).'</div>';
            }
            else {
                $result .= '<div class="'.$stack.'"></div>';
            }
        }

        return $result;
    }

    /**
     * addHeader
     */
    public function addHeader(): ContentPlaceholder
    {
        $this->config['stack'][] = [
            'placeholder-heading' => [
                'heading-content' => [
                    'heading-title'
                ]
            ]
        ];

        return $this;
    }

    /**
     * addHeaderImage
     */
    public function addHeaderImage(): ContentPlaceholder
    {
        $this->config['stack'][] = [
            'placeholder-heading' => [
                'heading-img',
                'heading-content' => [
                    'heading-title'
                ]
            ]
        ];

        return $this;
    }

    /**
     * addHeaderSubtitle
     */
    public function addHeaderSubtitle(): ContentPlaceholder
    {
        $this->config['stack'][] = [
            'placeholder-heading' => [
                'heading-content' => [
                    'heading-title',
                    'heading-subtitle'
                ]
            ]
        ];

        return $this;
    }

    /**
     * addHeaderSubtitleImage
     */
    public function addHeaderSubtitleImage(): ContentPlaceholder
    {
        $this->config['stack'][] = [
            'placeholder-heading' => [
                'heading-img',
                'heading-content' => [
                    'heading-title',
                    'heading-subtitle'
                ]
            ]
        ];

        return $this;
    }

    /**
     * addImage
     */
    public function addImage(): ContentPlaceholder
    {
        $this->config['stack'][] = [
            'placeholder-img'
        ];

        return $this;
    }

    /**
     * addText
     */
    public function addText(int $lines): ContentPlaceholder
    {
        $lineDivs = [];
        foreach (range(1, $lines) as $index) {
            $lineDivs[] = 'text-line';
        }

        $this->config['stack'][] = [
            'placeholder-text' => $lineDivs
        ];

        return $this;
    }
}
