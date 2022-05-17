<?php namespace Cms\Twig;

use Twig\Token as TwigToken;
use Twig\TokenParser\AbstractTokenParser as TwigTokenParser;

/**
 * FrameworkTokenParser for the `{% framework %}` Twig tag.
 *
 *     {% framework %}
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class FrameworkTokenParser extends TwigTokenParser
{
    /**
     * parse a token and returns a node.
     * @return Twig\Node\Node A Twig\Node\Node instance
     */
    public function parse(TwigToken $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $name = null;
        if ($token = $stream->nextIf(TwigToken::NAME_TYPE)) {
            $name = $token->getValue();
        }

        $stream->expect(TwigToken::BLOCK_END_TYPE);
        return new FrameworkNode($name, $lineno, $this->getTag());
    }

    /**
     * getTag name associated with this token parser.
     * @return string The tag name
     */
    public function getTag()
    {
        return 'framework';
    }
}
