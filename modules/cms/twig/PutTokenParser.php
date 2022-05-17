<?php namespace Cms\Twig;

use Twig\Token as TwigToken;
use Twig\TokenParser\AbstractTokenParser as TwigTokenParser;
use Twig\Error\SyntaxError;

/**
 * PutTokenParser for the `{% put %}` Twig tag.
 *
 *     {% put head %}
 *         <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet"/>
 *     {% endput %}
 *
 * or
 *
 *     {% put head %}
 *         <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css" rel="stylesheet"/>
 *         {% default %}
 *     {% endput %}
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class PutTokenParser extends TwigTokenParser
{
    /**
     * parse a token and returns a node.
     * @return \Twig\Node\Node A Twig\Node\Node instance
     */
    public function parse(TwigToken $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();

        $capture = false;
        $endType = null;
        if ($stream->nextIf(TwigToken::OPERATOR_TYPE, '=')) {
            $values = $this->parser->getExpressionParser()->parseMultitargetExpression();

            $stream->expect(TwigToken::BLOCK_END_TYPE);

            if (count($names) !== count($values)) {
                throw new SyntaxError('When using put, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        else {
            $capture = true;

            if (count($names) > 1) {
                throw new SyntaxError('When using put with a block, you cannot have multiple targets.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }

            $stream->expect(TwigToken::BLOCK_END_TYPE);
            $values = $this->parser->subparse([$this, 'decidePutEnd'], true);

            if ($token = $stream->nextIf(TwigToken::NAME_TYPE)) {
                $endType = $token->getValue();
            }

            $stream->expect(TwigToken::BLOCK_END_TYPE);
        }

        return new PutNode($capture, $names, $values, $endType, $lineno, $this->getTag());
    }

    /**
     * decidePutEnd
     */
    public function decidePutEnd(TwigToken $token)
    {
        return $token->test('endput');
    }

    /**
     * getTag name associated with this token parser.
     * @return string The tag name
     */
    public function getTag()
    {
        return 'put';
    }
}
