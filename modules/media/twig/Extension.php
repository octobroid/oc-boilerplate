<?php namespace Media\Twig;

use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFilter as TwigSimpleFilter;
use Media\Classes\MediaLibrary;

/**
 * Extension for Twig used by the Media module
 *
 * @package october\media
 * @author Alexey Bobkov, Samuel Georges
 */
class Extension extends TwigExtension
{
    /**
     * getFunctions returns a list of functions to add to the existing list.
     * @return array
     */
    public function getFunctions()
    {
        return [];
    }

    /**
     * getFilters returns a list of filters this extensions provides.
     * @return array
     */
    public function getFilters()
    {
        $filters = [
            new TwigSimpleFilter('media', [$this, 'mediaFilter'], ['is_safe' => ['html']]),
        ];

        return $filters;
    }

    /**
     * getTokenParsers returns a list of token parsers this extensions provides.
     * @return array
     */
    public function getTokenParsers()
    {
        return [];
    }

    /**
     * mediaFilter converts supplied file to a URL relative to the media library.
     * @param mixed $file
     * @return string
     */
    public function mediaFilter($file)
    {
        return MediaLibrary::url($file);
    }
}
