<?php namespace System\Twig;

use Twig\Markup;
use Twig\Template;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

/**
 * SecurityPolicy is a security policy using an allow-list
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    /**
     * @var array blockedClassMethods override list of known forbidden methods on class types
     * that are included in the allow-list.
     */
    protected $blockedClassMethods = [
        \October\Rain\Database\Attach\File::class => ['fromPost', 'fromData', 'fromUrl', 'getDisk'],
    ];

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
     * @var array allowMethods is a list of allowed methods
     */
    protected $allowMethods = [
        '__toString',
        'toArray'
    ];

    /**
     * __construct
     */
    public function __construct()
    {
        // Convert all methods to lower case
        foreach ($this->allowMethods as $i => $m) {
            $this->allowMethods[$i] = strtr($m, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
        }

        foreach ($this->blockMethods as $i => $m) {
            $this->blockMethods[$i] = strtr($m, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
        }

        foreach ($this->blockedClassMethods as $i => $methods) {
            foreach ($methods as $ii => $m) {
                $this->blockedClassMethods[$i][$ii] = strtr($m, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
            }
        }
    }

    /**
     * checkSecurity
     * @throws SecurityError
     */
    public function checkSecurity($tags, $filters, $functions): void
    {
    }

    /**
     * checkMethodAllowed
     * @throws SecurityNotAllowedMethodError
     */
    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        $this->checkMethodAllowedAllowlist($obj, $method);
        $this->checkMethodAllowedBlocklist($obj, $method);
    }

    /**
     * checkPropertyAllowed
     * @throws SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $property): void
    {
    }

    //
    // Allow-list
    //

    /**
     * checkMethodAllowedAllowlist
     */
    protected function checkMethodAllowedAllowlist($obj, $method)
    {
        // Common internals
        if (
            $obj instanceof \Carbon\Carbon ||
            $obj instanceof \Illuminate\View\View ||
            $obj instanceof \Illuminate\Support\HtmlString ||
            $obj instanceof \Illuminate\Support\Collection ||
            $obj instanceof \Illuminate\Database\Query\Builder ||
            $obj instanceof \Illuminate\Database\Eloquent\Model ||
            $obj instanceof \Illuminate\Database\Eloquent\Builder ||
            $obj instanceof \Illuminate\Pagination\AbstractPaginator
        ) {
            return;
        }

        // Contractual allow-list
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

        // Check general methods
        $allowMethod = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
        if (in_array($allowMethod, $this->allowMethods)) {
            return;
        }

        $className = get_class($obj);
        throw new SecurityNotAllowedMethodError(sprintf('Calling any method on a "%s" object is blocked.', $className), $className, $method);
    }

    //
    // Block-list
    //

    /**
     * checkMethodAllowedBlocklist
     */
    protected function checkMethodAllowedBlocklist($obj, $method)
    {
        $blockedMethod = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');

        // Check objects
        foreach ($this->blockedClassMethods as $blockedClass => $blockedMethods) {
            if (is_a($obj, $blockedClass) && in_array($blockedMethod, $blockedMethods)) {
                throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is blocked.', $method, $blockedClass), $blockedClass, $method);
            }
        }

        // Check general methods
        if (!in_array($blockedMethod, $this->blockMethods)) {
            return;
        }

        $className = get_class($obj);
        throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is blocked.', $method, $className), $className, $method);
    }
}
