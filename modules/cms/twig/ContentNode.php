<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * ContentNode represents a "content" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class ContentNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct(TwigNode $nodes, $paramNames, $lineno, $tag = 'content')
    {
        parent::__construct(['nodes' => $nodes], ['names' => $paramNames], $lineno, $tag);
    }

    /**
     * compile the node to PHP.
     */
    public function compile(TwigCompiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$context['__cms_content_params'] = [];\n");

        for ($i = 1; $i < count($this->getNode('nodes')); $i++) {
            $compiler->write("\$context['__cms_content_params']['".$this->getAttribute('names')[$i-1]."'] = ");
            $compiler->write('twig_escape_filter($this->env, ');
            $compiler->subcompile($this->getNode('nodes')->getNode($i));
            $compiler->write(")");
            $compiler->write(";\n");
        }

        $compiler
            ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->contentFunction(")
            ->subcompile($this->getNode('nodes')->getNode(0))
            ->write(", \$context['__cms_content_params']")
            ->write(", true")
            ->write(");\n")
        ;

        $compiler->write("unset(\$context['__cms_content_params']);\n");
    }
}
