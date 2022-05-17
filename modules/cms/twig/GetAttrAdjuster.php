<?php namespace Cms\Twig;

use Twig\Node\Node;
use Twig\Environment;
use Twig\Node\Expression\GetAttrExpression;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * GetAttrAdjuster tweaks the get attribute functionality.
 */
class GetAttrAdjuster implements NodeVisitorInterface
{
    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env)
    {
        if (get_class($node) !== GetAttrExpression::class) {
            return $node;
        }

        $nodes = [
            'node' => $node->getNode('node'),
            'attribute' => $node->getNode('attribute')
        ];

        if ($node->hasNode('arguments')) {
            $nodes['arguments'] = $node->getNode('arguments');
        }

        $attributes = [
            'type' => $node->getAttribute('type'),
            'is_defined_test' => $node->getAttribute('is_defined_test'),
            'ignore_strict_check' => $node->getAttribute('ignore_strict_check'),
            'optimizable' => $node->getAttribute('optimizable'),
        ];

        return new GetAttrNode($nodes, $attributes, $node->getTemplateLine(), $node->getNodeTag());
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(Node $node, Environment $env)
    {
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return 0;
    }
}