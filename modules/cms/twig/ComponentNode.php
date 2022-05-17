<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * ComponentNode represents a "component" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class ComponentNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct(TwigNode $nodes, $paramNames, $lineno, $tag = 'component')
    {
        parent::__construct(['nodes' => $nodes], ['names' => $paramNames], $lineno, $tag);
    }

    /**
     * compile the node to PHP.
     */
    public function compile(TwigCompiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$context['__cms_component_params'] = [];\n");

        for ($i = 1; $i < count($this->getNode('nodes')); $i++) {
            $compiler->write("\$context['__cms_component_params']['".$this->getAttribute('names')[$i-1]."'] = ");
            $compiler->subcompile($this->getNode('nodes')->getNode($i));
            $compiler->write(";\n");
        }

        $compiler
            ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->componentFunction(")
            ->subcompile($this->getNode('nodes')->getNode(0))
            ->write(", \$context['__cms_component_params']")
            ->write(");\n")
        ;

        $compiler->write("unset(\$context['__cms_component_params']);\n");
    }
}
