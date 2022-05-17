<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * StylesNode represents a "styles" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class StylesNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct($lineno, $tag = 'styles')
    {
        parent::__construct([], [], $lineno, $tag);
    }

    /**
     * compile the node to PHP.
     */
    public function compile(TwigCompiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->assetsFunction('css');\n")
            ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->displayBlock('styles');\n")
        ;
    }
}
