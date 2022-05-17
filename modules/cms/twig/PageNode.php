<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * PageNode represents a "page" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class PageNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct($lineno, $tag = 'page')
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
            ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->pageFunction();\n")
        ;
    }
}
