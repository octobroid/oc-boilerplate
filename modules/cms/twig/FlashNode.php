<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Compiler as TwigCompiler;

/**
 * FlashNode represents a "flash" node
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class FlashNode extends TwigNode
{
    /**
     * __construct
     */
    public function __construct($name, TwigNode $body, $lineno, $tag = 'flash')
    {
        parent::__construct(['body' => $body], ['name' => $name], $lineno, $tag);
    }

    /**
     * compile the node to PHP.
     */
    public function compile(TwigCompiler $compiler)
    {
        $attrib = $this->getAttribute('name');

        $compiler
            ->write('$_type = isset($context["type"]) ? $context["type"] : null;')
            ->write('$_message = isset($context["message"]) ? $context["message"] : null;')
        ;

        if ($attrib == 'all') {
            $compiler
                ->addDebugInfo($this)
                ->write('foreach (Flash::getMessages() as $type => $messages) {'.PHP_EOL)
                ->indent()
                    ->write('foreach ($messages as $message) {'.PHP_EOL)
                    ->indent()
                        ->write('$context["type"] = $type;')
                        ->write('$context["message"] = $message;')
                        ->subcompile($this->getNode('body'))
                    ->outdent()
                    ->write('}'.PHP_EOL)
                ->outdent()
                ->write('}'.PHP_EOL)
            ;
        }
        else {
            $compiler
                ->addDebugInfo($this)
                ->write('$context["type"] = ')
                ->string($attrib)
                ->write(';')
                ->write('foreach (Flash::')
                ->raw($attrib)
                ->write('() as $message) {'.PHP_EOL)
                ->indent()
                    ->write('$context["message"] = $message;')
                    ->subcompile($this->getNode('body'))
                ->outdent()
                ->write('}'.PHP_EOL)
            ;
        }

        $compiler
            ->write('$context["type"] = $_type;')
            ->write('$context["message"] = $_message;')
        ;
    }
}
