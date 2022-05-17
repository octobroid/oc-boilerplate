<?php namespace Cms\Twig;

use Twig\Node\Node as TwigNode;
use Twig\Token as TwigToken;
use Twig\TokenParser\AbstractTokenParser as TwigTokenParser;

/**
 * FlashTokenParser for the `{% flash %}` Twig tag.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class FlashTokenParser extends TwigTokenParser
{
    /**
     * parse a token and returns a node.
     * @return TwigNode A TwigNode instance
     */
    public function parse(TwigToken $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        if ($token = $stream->nextIf(TwigToken::NAME_TYPE)) {
            $name = $token->getValue();
        }
        else {
            $name = 'all';
        }
        $stream->expect(TwigToken::BLOCK_END_TYPE);

        $body = $this->parser->subparse([$this, 'decideIfEnd'], true);
        $stream->expect(TwigToken::BLOCK_END_TYPE);

        return new FlashNode($name, $body, $lineno, $this->getTag());
    }

    /**
     * decideIfEnd
     */
    public function decideIfEnd(TwigToken $token)
    {
        return $token->test(['endflash']);
    }

    /**
     * getTag name associated with this token parser.
     * @return string The tag name
     */
    public function getTag()
    {
        return 'flash';
    }
}
