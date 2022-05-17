<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * DefaultNode represents a "default" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class DefaultNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct($lineno, $tag = 'default')
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
            ->write("echo '<!-- X_OCTOBER_DEFAULT_BLOCK_CONTENT -->';\n")
        ;
    }
}
