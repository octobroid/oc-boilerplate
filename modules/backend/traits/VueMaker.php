<?php namespace Backend\Traits;

use Str;
use Backend;
use SystemException;
use Backend\Classes\VueComponentBase;

/**
 * VueMaker Trait
 * Adds exception based methods to a class, goes well with `System\Traits\ViewMaker`.
 *
 * To add a component call `registerVueComponent()` in a controller
 * action:
 *
 *     $this->registerVueComponent('Plugin/VueComponents/MyComponent');
 *
 * This will automatically load the component's JavaScript definition,
 * component template, and CSS file.
 *
 * @see Backend\Classes\VueComponentBase
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
trait VueMaker
{
    /**
     * @var array A list of registered Vue component classes
     */
    protected $vueComponents = [];

    /**
     * Registers a Vue component to be loaded when the action view renders.
     * @param String $componentClassName
     * @return void
     */
    public function registerVueComponent($componentClassName)
    {
        $component = $this->makeVueComponent($componentClassName);
        $this->vueComponents[] = $component;

        $requiredComponents = $component->getDependencies();
        if (!is_array($requiredComponents)) {
            throw new SystemException(sprintf('getDependencies() must return an array: %s', $componentClassName));
        }

        foreach ($requiredComponents as $className) {
            if (!$this->isVueComponentRegistered($className)) {
                $this->registerVueComponent($className);
            }
        }
    }

    public function outputVueComponentTemplates()
    {
        $result = [];

        foreach ($this->vueComponents as $component) {
            $templateId = Str::getClassId($component);
            $result[] = sprintf('<script type="text/template" id="%s">', $templateId);
            $result[] = $component->render();
            $result[] = '</script>';

            foreach ($component->getSubcomponents() as $subcomponent) {
                $templateId = Str::getClassId($component).'_'.$subcomponent;
                $templateId = str_replace('.', '_', $templateId);
                $result[] = sprintf('<script type="text/template" id="%s">', $templateId);
                $result[] = $component->renderSubcomponent($subcomponent);
                $result[] = '</script>';
            }
        }

        return implode(PHP_EOL, $result);
    }

    protected function makeVueComponent($className)
    {
        if (!class_exists($className)) {
            throw new SystemException(sprintf('Vue component class not found: %s', $className));
        }

        $component = new $className($this);
        if (!$component instanceof VueComponentBase) {
            throw new SystemException(
                sprintf('Vue component class must be a descendant of Backend\Classes\VueComponentBase: %s', $className)
            );
        }

        return $component;
    }

    protected function isVueComponentRegistered($componentClassName)
    {
        foreach ($this->vueComponents as $component) {
            if ($componentClassName == get_class($component)) {
                return true;
            }
        }

        return false;
    }
}
