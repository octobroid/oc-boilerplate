<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * PutNode represents a "put" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class PutNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct(bool $capture, TwigNode $names, TwigNode $values, $endType, $lineno, $tag = 'put')
    {
        parent::__construct(['names' => $names, 'values' => $values], ['capture' => $capture, 'endType' => $endType], $lineno, $tag);
    }

    /**
     * compile the node to PHP.
     */
    public function compile(TwigCompiler $compiler)
    {
        $names = $this->getNode('names');
        $values = $this->getNode('values');
        $isCapture = $this->getAttribute('capture');
        if ($isCapture) {
            $blockName = $names->getNode(0);
            $compiler
                ->addDebugInfo($this)
                ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->startBlock(")
                ->raw("'".$blockName->getAttribute('name')."'")
                ->write(");\n")
            ;

            $isOverwrite = strtolower($this->getAttribute('endType')) == 'overwrite';

            $compiler->subcompile($this->getNode('values'));

            $compiler
                ->addDebugInfo($this)
                ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->endBlock(")
                ->raw($isOverwrite ? 'false' : 'true')
                ->write(");\n")
            ;
        }
        else {
            foreach ($names as $key => $name) {
                if (!$values->hasNode($key)) {
                    continue;
                }

                $value = $values->getNode($key);

                $compiler
                    ->addDebugInfo($this)
                    ->write("echo \$this->env->getExtension(\Cms\Twig\Extension::class)->setBlock(")
                    ->raw("'".$name->getAttribute('name')."'")
                    ->raw(', ')
                    ->subcompile($value)
                    ->write(");\n")
                ;
            }
        }
    }
}
