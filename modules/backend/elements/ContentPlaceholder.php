<?php namespace Backend\Elements;

/**
 * ContentPlaceholder
 */
class ContentPlaceholder
{
    use \Backend\Traits\ElementRenderer;

    /**
     * @var array
     */
    protected $stack;

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
        $this->stack[] = [
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
        $this->stack[] = [
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
        $this->stack[] = [
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
        $this->stack[] = [
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
        $this->stack[] = [
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

        $this->stack[] = [
            'placeholder-text' => $lineDivs
        ];

        return $this;
    }
}
