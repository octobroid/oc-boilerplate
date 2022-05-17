<?php namespace System\Twig;

use Url;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFilter as TwigSimpleFilter;
use System\Classes\MarkupManager;
use System\Classes\ResizeImages;

/**
 * The System Twig extension class implements common Twig functions and filters.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class Extension extends TwigExtension
{
    /**
     * @var \System\Classes\MarkupManager A reference to the markup manager instance.
     */
    protected $markupManager;

    /**
     * Creates the extension instance.
     */
    public function __construct()
    {
        $this->markupManager = MarkupManager::instance();
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        $functions = [];

        /*
         * Include extensions provided by plugins
         */
        $functions = $this->markupManager->makeTwigFunctions($functions);

        return $functions;
    }

    /**
     * Returns a list of filters this extensions provides.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        $filters = [
            new TwigSimpleFilter('app', [$this, 'appFilter'], ['is_safe' => ['html']]),
            new TwigSimpleFilter('resize', [$this, 'resizeFilter'], ['is_safe' => ['html']]),
        ];

        /*
         * Include extensions provided by plugins
         */
        $filters = $this->markupManager->makeTwigFilters($filters);

        return $filters;
    }

    /**
     * Returns a list of token parsers this extensions provides.
     *
     * @return array An array of token parsers
     */
    public function getTokenParsers()
    {
        $parsers = [];

        /*
         * Include extensions provided by plugins
         */
        $parsers = $this->markupManager->makeTwigTokenParsers($parsers);

        return $parsers;
    }

    /**
     * appFilter converts supplied URL to one relative to the website root.
     * @param mixed $url Specifies the application-relative URL
     * @return string
     */
    public function appFilter($url)
    {
        return Url::to($url);
    }

    /**
     * resizeFilter converts supplied input into a URL that will return the desired resized image.
     * The image can be either a file model, absolute path, or URL.
     */
    public function resizeFilter($image, $width = null, $height = null, $options = [])
    {
        return Url::to(ResizeImages::resize($image, $width, $height, $options));
    }
}
