<?php namespace Cms\Traits;

use Cms\Classes\ComponentManager;
use Cms\Classes\ComponentHelpers;
use Cms\Classes\CmsCompoundObject;
use Cms\Components\ViewBag;
use Exception;

/**
 * EditorComponentListLoader loads components the CMS Editor Extension
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
trait EditorComponentListLoader
{
    /**
     * loadTemplateComponents
     */
    private function loadTemplateComponents(CmsCompoundObject $template)
    {
        $manager = ComponentManager::instance();

        $result = [];
        $viewBagFound = false;
        foreach ($template->settings['components'] as $name => $properties) {
            [$name, $alias] = strpos($name, ' ') ? explode(' ', $name) : [$name, $name];

            try {
                $templateComponent = $this->makeTemplateComponent($manager, $name, $properties, $alias);
                $result[] = $templateComponent;

                $viewBagFound = $viewBagFound || $templateComponent['className'] == ViewBag::class;
            }
            catch (Exception $ex) {
                $propertyValues = $this->makePropertiesForUnknownComponent($properties, $alias);
                $propertyValues = json_encode($propertyValues, JSON_UNESCAPED_SLASHES);

                $result[] = [
                    'title' => $name,
                    'alias' => $alias,
                    'icon' => 'icon-bug',
                    'description' => $ex->getMessage(),
                    'isUnknownComponent' => true,
                    'inspectorEnabled' => false,
                    'className' => '',
                    'propertyValues' => $propertyValues,
                    'name' => $name
                ];
            }
        }

        if (!$viewBagFound) {
            // Always inject a view bag so that custom template properties
            // defined using the CMS extensibility API can use it.
            // Empty view bags get automatically removed from templates
            // before they are saved.
            //
            $result[] = $this->makeTemplateComponent($manager, 'viewBag', [], 'viewBag');
        }

        return $result;
    }

    /**
     * Used to inject ViewBag to new CMS document templates
     */
    private function getViewBagComponent()
    {
        $manager = ComponentManager::instance();
        return $this->makeTemplateComponent($manager, 'viewBag', [], 'viewBag');;
    }

    /**
     * getComponentPluginIcon
     */
    private function getComponentPluginIcon($manager, $componentObj)
    {
        return $manager->findComponentOwnerDetails($componentObj)['icon'] ?? 'icon-puzzle-piece';
    }

    /**
     * makePropertiesForUnknownComponent
     */
    private function makePropertiesForUnknownComponent($properties, $alias)
    {
        $properties['oc.alias'] = $alias;

        return $properties;
    }

    /**
     * makeTemplateComponent
     */
    private function makeTemplateComponent($manager, $name, $properties, $alias)
    {
        $componentObj = $manager->makeComponent($name, null, $properties);
        if (!$componentObj) {
            throw new Exception('Component not found');
        }

        $componentObj->alias = $alias;

        $propertyConfig = ComponentHelpers::getComponentsPropertyConfig($componentObj, true, true);
        $propertyConfig = json_encode($propertyConfig, JSON_UNESCAPED_SLASHES);

        $propertyValues = ComponentHelpers::getComponentPropertyValues($componentObj, true);
        $propertyValues = json_encode($propertyValues, JSON_UNESCAPED_SLASHES);

        return [
            'title' => ComponentHelpers::getComponentName($componentObj),
            'alias' => $alias,
            'icon' => $this->getComponentPluginIcon($manager, $componentObj),
            'description' => ComponentHelpers::getComponentDescription($componentObj),
            'propertyConfig' => $propertyConfig,
            'propertyValues' => $propertyValues,
            'inspectorEnabled' => $componentObj->inspectorEnabled,
            'className' => get_class($componentObj),
            'isHidden' => $componentObj->isHidden,
            'name' => $componentObj->name
        ];
    }
}
