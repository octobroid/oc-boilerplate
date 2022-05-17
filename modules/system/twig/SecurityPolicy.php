<?php namespace System\Twig;

use Twig\Markup;
use Twig\Template;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

/**
 * SecurityPolicy is a more strict policy using a whitelist
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    /**
     * @var array blockMethods is a list of forbidden methods
     */
    protected $blockMethods = [
        // Block PHP
        '__call',
        '__callStatic',

        // Block October\Rain\Extension\ExtensionTrait
        'extend',
        'extensionExtendCallback',

        // Block October\Rain\Extension\ExtendableTrait
        'extendableCall',
        'extendableCallStatic',
        'extendClassWith',
        'implementClassWith',
        'addDynamicMethod',
        'addDynamicProperty',

        // Block October\Rain\Support\Traits\Emitter
        'bindEvent',
        'bindEventOnce',

        // Block Illuminate\Support\Traits\Macroable
        'macro',
        'mixin',
    ];

    /**
     * __construct
     */
    public function __construct()
    {
        // Convert all methods to lower case
        foreach ($this->blockMethods as $i => $m) {
            $this->blockMethods[$i] = strtr($m, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
        }
    }

    /**
     * checkSecurity
     * @throws SecurityError
     */
    public function checkSecurity($tags, $filters, $functions)
    {
    }

    /**
     * checkMethodAllowed
     * @throws SecurityNotAllowedMethodError
     */
    public function checkMethodAllowed($obj, $method)
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        $this->checkMethodAllowedWhitelist($obj, $method);
        $this->checkMethodAllowedBlacklist($obj, $method);

    }

    /**
     * checkPropertyAllowed
     * @throws SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $property)
    {
    }

    //
    // Whitelist
    //

    /**
     * checkMethodAllowedWhitelist
     */
    protected function checkMethodAllowedWhitelist($obj, $method)
    {
        // Common internals
        if (
            $obj instanceof \Carbon\Carbon ||
            $obj instanceof \Illuminate\View\View ||
            $obj instanceof \Illuminate\Support\Collection ||
            $obj instanceof \Illuminate\Database\Query\Builder ||
            $obj instanceof \Illuminate\Database\Eloquent\Builder ||
            $obj instanceof \Illuminate\Pagination\AbstractPaginator
        ) {
            return;
        }

        // Contractual whitelist
        if ($obj instanceof \October\Contracts\Twig\CallsMethods) {
            $methodNames = $obj->getTwigMethodNames();
            if (in_array($method, $methodNames)) {
                return;
            }
        }

        // Contractual wildcard
        if ($obj instanceof \October\Contracts\Twig\CallsAnyMethod) {
            return;
        }

        $className = get_class($obj);
        throw new SecurityNotAllowedMethodError(sprintf('Calling any method on a "%s" object is blocked.', $className), $className, $method);
    }

    //
    // Blacklist
    //

    /**
     * checkMethodAllowedBlacklist
     */
    protected function checkMethodAllowedBlacklist($obj, $method)
    {
        $blockedMethod = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');

        if (!in_array($blockedMethod, $this->blockMethods)) {
            return;
        }

        $className = get_class($obj);
        throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is blocked.', $method, $className), $className, $method);
    }
}
