<?php namespace Cms\Twig;

use Block;
use Event;
use Redirect;
use Twig\TwigFilter as TwigSimpleFilter;
use Twig\TwigFunction as TwigSimpleFunction;
use Twig\Extension\AbstractExtension as TwigExtension;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Cms\Classes\Controller;

/**
 * Extension implements the basic CMS Twig functions and filters.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class Extension extends TwigExtension
{
    /**
     * @var \Cms\Classes\Controller controller reference
     */
    protected $controller;

    /**
     * __construct the extension instance.
     */
    public function __construct(Controller $controller = null)
    {
        $this->controller = $controller;
    }

    /**
     * getFunctions returns a list of functions to add to the existing list.
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigSimpleFunction('page', [$this, 'pageFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('partial', [$this, 'partialFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('hasPartial', [$this, 'hasPartialFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('content', [$this, 'contentFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('hasContent', [$this, 'hasContentFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('component', [$this, 'componentFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('placeholder', [$this, 'placeholderFunction'], ['is_safe' => ['html']]),
            new TwigSimpleFunction('redirect', [$this, 'redirectFunction'], []),
            new TwigSimpleFunction('abort', [$this, 'abortFunction'], []),
        ];
    }

    /**
     * getFilters returns a list of filters this extension provides.
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigSimpleFilter('page', [$this, 'pageFilter'], ['is_safe' => ['html']]),
            new TwigSimpleFilter('theme', [$this, 'themeFilter'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * getTokenParsers returns a list of token parsers this extension provides.
     * @return array
     */
    public function getTokenParsers()
    {
        return [
            new PageTokenParser,
            new PartialTokenParser,
            new ContentTokenParser,
            new PutTokenParser,
            new PlaceholderTokenParser,
            new DefaultTokenParser,
            new FrameworkTokenParser,
            new ComponentTokenParser,
            new FlashTokenParser,
            new ScriptsTokenParser,
            new StylesTokenParser,
        ];
    }

    /**
     * getNodeVisitors returns a list of node visitors this extension provides.
     * @return array
     */
    public function getNodeVisitors()
    {
        return [
            new GetAttrAdjuster
        ];
    }

    /**
     * pageFunction renders a page.
     * This function should be used in the layout code to output the requested page.
     * @return string
     */
    public function pageFunction()
    {
        return $this->controller->renderPage();
    }

    /**
     * partialFunction renders a partial based on the partial name. The parameters
     * are an optional list of view variables. An exception can be thrown if
     * nothing is found.
     * @return string
     */
    public function partialFunction($name, $parameters = [], $throwException = false)
    {
        return $this->controller->renderPartial($name, $parameters, $throwException);
    }

    /**
     * hasPartialFunction checks the partials existence without rendering it.
     * @return bool
     */
    public function hasPartialFunction($name)
    {
        return (bool) $this->controller->loadPartialObject($name);
    }

    /**
     * contentFunction renders a partial based on the file name. The parameters
     * are an optional list of view variables, otherwise pass false to render nothing
     * and check the existence. An exception can be thrown if nothing is found.
     * @return string
     */
    public function contentFunction($name, $parameters = [], $throwException = false)
    {
        return $this->controller->renderContent($name, $parameters, $throwException);
    }

    /**
     * hasContentFunction checks the content existence without rendering it.
     * @return bool
     */
    public function hasContentFunction($name)
    {
        return (bool) $this->controller->loadContentObject($name);
    }

    /**
     * componentFunction renders a component's default content.
     * @param string $name Specifies the component name.
     * @param array $parameters A optional list of parameters to pass to the component.
     * @return string
     */
    public function componentFunction($name, $parameters = [])
    {
        return $this->controller->renderComponent($name, $parameters);
    }

    /**
     * assetsFunction renders registered assets of a given type
     * @return string
     */
    public function assetsFunction($type = null)
    {
        $result = $this->controller->makeAssets($type);

        Event::fire('cms.assets.render', [$type, &$result]);

        return $result;
    }

    /**
     * placeholderFunction renders a placeholder content, without removing the block,
     * must be called before the placeholder tag itself
     * @return string
     */
    public function placeholderFunction($name, $default = null)
    {
        if (($result = Block::get($name)) === null) {
            return null;
        }

        $result = str_replace('<!-- X_OCTOBER_DEFAULT_BLOCK_CONTENT -->', trim($default), $result);

        return $result;
    }

    /**
     * redirectFunction will redirect the response to a theme page or URL
     * @param string $to
     * @param int $code
     */
    public function redirectFunction($to, $parameters = [], $code = 302)
    {
        if (is_int($parameters)) {
            $code = $parameters;
            $parameters = [];
        }

        $url = $this->controller->pageUrl($to, $parameters) ?: $to;

        $this->controller->setResponse(Redirect::to($url, $code));
    }

    /**
     * abortFunction will abort the successful page cycle
     * @param int $code
     * @param string|false $message
     */
    public function abortFunction($code, $message = '')
    {
        if ($message === false) {
            $this->controller->setStatusCode($code);
            return;
        }

        if ($code == 404) {
            throw new NotFoundHttpException($message);
        }

        throw new HttpException($code, $message);
    }

    /**
     * pageFilter looks up the URL for a supplied page and returns it relative to the website root.
     * @param mixed $name Specifies the Cms Page file name.
     * @param array $parameters Route parameters to consider in the URL.
     * @param bool $routePersistence By default the existing routing parameters will be included
     * when creating the URL, set to false to disable this feature.
     * @return string
     */
    public function pageFilter($name, $parameters = [], $routePersistence = true)
    {
        return $this->controller->pageUrl($name, $parameters, $routePersistence);
    }

    /**
     * themeFilter converts supplied URL to a theme URL relative to the website root. If the URL provided is an
     * array then the files will be combined.
     * @param mixed $url Specifies the theme-relative URL
     * @return string
     */
    public function themeFilter($url)
    {
        return $this->controller->themeUrl($url);
    }

    /**
     * startBlock opens a layout block.
     * @param string $name Specifies the block name
     */
    public function startBlock($name)
    {
        Block::startBlock($name);
    }

    /**
     * setBlock sets a block value as a variable.
     */
    public function setBlock(string $name, $value)
    {
        Block::set($name, $value);
    }

    /**
     * displayBlock returns a layout block contents and removes the block.
     * @param string $name Specifies the block name
     * @param string $default The default placeholder contents.
     * @return string|null
     */
    public function displayBlock($name, $default = null)
    {
        if (($result = Block::placeholder($name)) === null) {
            return $default;
        }

        /**
         * @event cms.block.render
         * Provides an opportunity to modify the rendered block content
         *
         * Example usage:
         *
         *     Event::listen('cms.block.render', function ((string) $name, (string) $result) {
         *         if ($name === 'myBlockName') {
         *             return 'my custom content';
         *         }
         *     });
         *
         */
        if ($event = Event::fire('cms.block.render', [$name, $result], true)) {
            $result = $event;
        }

        $result = str_replace('<!-- X_OCTOBER_DEFAULT_BLOCK_CONTENT -->', trim($default), $result);

        return $result;
    }

    /**
     * endBlock closes a layout block.
     */
    public function endBlock($append = true)
    {
        Block::endBlock($append);
    }
}
