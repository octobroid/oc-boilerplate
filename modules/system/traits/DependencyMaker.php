<?php namespace System\Traits;

use System\Classes\DependencyResolver;

/**
 * DependencyMaker is used for DI injection in method calls.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait DependencyMaker
{
    /**
     * @var DependencyResolver dependencyResolver for AJAX handlers and controller actions.
     */
    protected $dependencyResolver;

    /**
     * makeCallMethod will prepare method args with DI and call the method
     */
    protected function makeCallMethod($instance, string $method, array $parameters = [])
    {
        if ($this->dependencyResolver === null) {
            $this->dependencyResolver = new DependencyResolver;
        }

        $resolvedParams = $this->dependencyResolver->resolve($instance, $method, $parameters);

        return $instance->$method(...$resolvedParams);
    }
}
