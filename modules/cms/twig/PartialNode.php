<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * PartialNode represents a "partial" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class PartialNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct(TwigNode $nodes, $paramNames, $body, $lineno, $tag = 'partial')
    {
        $nodes = ['nodes' => $nodes];

        if ($body) {
            $nodes['body'] = $body;
        }

        parent::__construct($nodes, ['names' => $paramNames], $lineno, $tag);
    }

    /**
     * compile the node to PHP.
     */
    public function compile(TwigCompiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$context['__cms_partial_params'] = [];\n");

        if ($this->hasNode('body')) {
            $compiler
                ->addDebugInfo($this)
                ->write('ob_start();')
                ->subcompile($this->getNode('body'))
                ->write("\$context['__cms_partial_params']['body'] = ob_get_clean();");
        }

        for ($i = 1; $i < count($this->getNode('nodes')); $i++) {
            $compiler->write("\$context['__cms_partial_params']['".$this->getAttribute('names')[$i-1]."'] = ");
            $compiler->subcompile($this->getNode('nodes')->getNode($i));
            $compiler->write(";\n");
        }

        $compiler
            ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->partialFunction(")
            ->subcompile($this->getNode('nodes')->getNode(0))
            ->write(", \$context['__cms_partial_params']")
            ->write(", true")
            ->write(");\n")
        ;

        $compiler->write("unset(\$context['__cms_partial_params']);\n");
    }
}
