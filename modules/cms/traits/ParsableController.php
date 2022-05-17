<?php namespace Cms\Traits;

use Cms\Classes\CmsObject;
use Cms\Classes\ComponentBase;

/**
 * ParsableController adds property and attribute parsing logic to the CMS controller
 */
trait ParsableController
{
    /**
     * parseAllEnvironmentVars parses vars for all relevant objects.
     */
    protected function parseAllEnvironmentVars()
    {
        $this->parseEnvironmentVarsOnTemplate($this->page, $this->vars);
        $this->parseEnvironmentVarsOnTemplate($this->layout, $this->vars);

        foreach ($this->layout->components as $component) {
            $this->parseEnvironmentVarsOnComponent($component, $this->vars);
        }

        foreach ($this->page->components as $component) {
            $this->parseEnvironmentVarsOnComponent($component, $this->vars);
        }
    }

    /**
     * parseRouteParamsOnComponent where property values should be defined as {{ :param }}.
     */
    protected function parseRouteParamsOnComponent(ComponentBase $component, array $params = [], array $properties = null, string $propPrefix = '')
    {
        $properties = $properties !== null ? $properties : $component->getProperties();

        foreach ($properties as $propName => $propValue) {
            if (is_array($propValue)) {
                $this->parseRouteParamsOnComponent($component, $params, $propValue, $propName.'.');
                continue;
            }

            if ($override = $this->makeRouterPropertyReplacement($propValue, $params)) {
                [$paramName, $newValue] = $override;
                $component->setProperty($propPrefix.$propName, $newValue);
                $component->setExternalPropertyName($propPrefix.$propName, $paramName);
            }
        }
    }

    /**
     * parseEnvironmentVarsOnComponent where property values should be defined as {{ param }}.
     */
    protected function parseEnvironmentVarsOnComponent(ComponentBase $component, array $vars = [], array $properties = null, string $propPrefix = '')
    {
        $properties = $properties !== null ? $properties : $component->getProperties();

        foreach ($properties as $propName => $propValue) {
            if (is_array($propValue)) {
                $this->parseEnvironmentVarsOnComponent($component, $vars, $propValue, $propName.'.');
                continue;
            }

            if ($override = $this->makeDynamicAttributeReplacement($propValue, $vars)) {
                [$paramName, $newValue] = $override;
                $component->setProperty($propPrefix.$propName, $newValue);
                $component->setExternalPropertyName($propPrefix.$propName, $paramName);
            }
        }
    }

    /**
     * parseEnvironmentVarsOnTemplate where property values should be defined as {{ param }}.
     */
    protected function parseEnvironmentVarsOnTemplate(CmsObject $template, array $vars = [], array $attributes = null, string $attrPrefix = '')
    {
        $attributes = $attributes !== null ? $attributes : $template->getParsableAttributeValues();

        foreach ($attributes as $attrName => $attrValue) {
            if (is_array($attrValue)) {
                $this->parseEnvironmentVarsOnTemplate($template, $vars, $attrValue, $attrName.'.');
                continue;
            }

            if ($override = $this->makeDynamicAttributeReplacement($attrValue, $vars)) {
                [$paramName, $newValue] = $override;
                $template->setParsableAttribute($attrPrefix.$attrName, $newValue);
            }
        }
    }

    /**
     * makeRouterPropertyReplacement will look inside property values to replace any
     * Twig-like variables with values from the route parameters.
     *
     *     {{ :post }}
     */
    protected function makeRouterPropertyReplacement($propertyValue, array $routerParameters = []): ?array
    {
        if (!is_string($propertyValue)) {
            return null;
        }

        $matches = [];
        if (preg_match('/^\{\{(\s*:[^\}]+)\}\}$/', $propertyValue, $matches)) {
            $paramName = trim($matches[1]);
            $routeParamName = substr($paramName, 1);
            $newPropertyValue = $routerParameters[$routeParamName] ?? null;
            return [$paramName, $newPropertyValue];
        }

        return null;
    }

    /**
     * makeDynamicAttributeReplacement will look inside attribute values to replace any
     * Twig-like variables with the values inside the parameters.
     *
     *     {{ post.title }}
     */
    protected function makeDynamicAttributeReplacement($attrValue, array $parameters = []): ?array
    {
        if (!is_string($attrValue)) {
            return null;
        }

        $matches = [];
        if (preg_match_all('/\{\{([^:\}]+)\}\}/', $attrValue, $matches)) {
            $newAttrValue = $attrValue;
            $lastParamName = null;

            foreach ($matches[1] as $key => $paramName) {
                $paramName = $lastParamName = trim($paramName);
                $replaceWith = array_get($parameters, $paramName);
                $toReplace = $matches[0][$key];
                $newAttrValue = str_replace($toReplace, $replaceWith, $newAttrValue);
            }

            return [$lastParamName, $newAttrValue];
        }

        return null;
    }
}
