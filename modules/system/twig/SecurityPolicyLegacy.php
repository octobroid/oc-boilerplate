<?php namespace System\Twig;

use Twig\Markup;
use Twig\Template;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

/**
 * SecurityPolicyLegacy globally blocks accessibility of certain methods and properties
 *
 * @deprecated
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
final class SecurityPolicyLegacy implements SecurityPolicyInterface
{
    /**
     * @var array blockedClassMethods is a list of forbidden classes and methods
     */
    protected $blockedClassMethods = [
        \October\Rain\Database\Attach\File::class => ['fromPost', 'fromData', 'fromUrl', 'getDisk'],
    ];

    /**
     * @var array blockedClasses is a list of forbidden classes
     */
    protected $blockedClasses = [
        \Twig\Environment::class,
        \Illuminate\Filesystem\Filesystem::class,
        \Illuminate\Session\FileSessionHandler::class,
        \Illuminate\Contracts\Filesystem\Filesystem::class
    ];

    /**
     * @var array blockedProperties is a list of forbidden properties
     */
    protected $blockedProperties = [];

    /**
     * @var array blockedMethods is a list of forbidden methods
     */
    protected $blockedMethods = [
        // Prevent magic bypass
        '__call',

        // Prevent dynamic methods and props
        'addDynamicMethod',
        'addDynamicProperty',

        // Prevent binding event logic
        'bindEvent',
        'bindEventOnce',

        // General write bans
        'create',
        'insert',
        'update',
        'delete',
        'write',
    ];

    /**
     * __construct
     */
    public function __construct()
    {
        $this->setBlockedMethods();
    }

    /**
     * setBlockedMethods sets the defined blocked methods as lower case
     */
    public function setBlockedMethods(): void
    {
        foreach ($this->blockedMethods as $i => $m) {
            $this->blockedMethods[$i] = strtr($m, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
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

        $blockedMethod = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');

        // Check objects
        foreach ($this->blockedClassMethods as $blockedClass => $blockedMethods) {
            if (is_a($obj, $blockedClass) && in_array($blockedMethod, $blockedMethods)) {
                throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is blocked.', $method, $blockedClass), $blockedClass, $method);
            }
        }

        // Check general classes
        foreach ($this->blockedClasses as $blockedClass) {
            if (is_a($obj, $blockedClass)) {
                throw new SecurityNotAllowedMethodError(sprintf('Calling any method on a "%s" object is blocked.', $blockedClass), $blockedClass, $method);
            }
        }

        // Check general methods
        if (in_array($blockedMethod, $this->blockedMethods)) {
            $class = get_class($obj);
            throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is blocked.', $method, $class), $class, $method);
        }
    }

    /**
     * checkPropertyAllowed
     * @throws SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $property)
    {
        if (in_array($property, $this->blockedProperties)) {
            $class = get_class($obj);
            throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is blocked.', $property, $class), $class, $property);
        }
    }
}
