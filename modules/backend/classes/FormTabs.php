<?php namespace Backend\Classes;

use October\Rain\Element\Form\FieldsetDefinition;

/**
 * FormTabs is a fieldset definition for backend tabs
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class FormTabs extends FieldsetDefinition
{
    const SECTION_OUTSIDE = 'outside';
    const SECTION_PRIMARY = 'primary';
    const SECTION_SECONDARY = 'secondary';

    //
    // Configurable properties
    //

    /**
     * @var string section specifies the form section these tabs belong to
     */
    public $section = 'outside';

    /**
     * @var array lazy is the names of tabs to lazy load
     */
    public $lazy = [];

    /**
     * @var array adaptive is the names of tabs that use the entire screen space
     */
    public $adaptive = [];

    /**
     * @var string defaultTab is default tab label to use when none is specified
     */
    public $defaultTab = 'backend::lang.form.undefined_tab';

    /**
     * @var string activeTab is the selected tab when the form first loads, name or index.
     */
    public $activeTab;

    /**
     * @var array icons lists of icons for their corresponding tabs
     */
    public $icons = [];

    /**
     * @var bool stretch should these tabs stretch to the bottom of the page layout
     */
    public $stretch;

    /**
     * @var string cssClass cpecifies a CSS class to attach to the tab container
     */
    public $cssClass;

    /**
     * @var array paneCssClass specifies a CSS class to an individual tab pane
     */
    public $paneCssClass;

    /**
     * @var bool linkable means tab gets url fragment to be linkable
     */
    public $linkable = true;

    /**
     * __construct specifies a tabs rendering section. Supported sections are:
     * - outside - stores a section of "tabless" fields.
     * - primary - tabs section for primary fields.
     * - secondary - tabs section for secondary fields.
     * @param string $section Specifies a section as described above.
     * @param array $config A list of render mode specific config.
     */
    public function __construct($section, $config = [])
    {
        $this->section = strtolower($section) ?: $this->section;

        if ($config && is_array($config)) {
            $this->useConfig($config);
        }

        if ($this->section === self::SECTION_OUTSIDE) {
            $this->suppressTabs = true;
        }
    }

    /**
     * evalConfig process options and apply them to this object
     */
    protected function evalConfig(array $config): void
    {
        parent::evalConfig($config);

        if (array_key_exists('activeTab', $config)) {
            $this->activeTab = $config['activeTab'];
        }

        if (array_key_exists('icons', $config)) {
            $this->icons = $config['icons'];
        }

        if (array_key_exists('stretch', $config)) {
            $this->stretch = $config['stretch'];
        }

        if (array_key_exists('cssClass', $config)) {
            $this->cssClass = $config['cssClass'];
        }

        if (array_key_exists('paneCssClass', $config)) {
            $this->paneCssClass = $config['paneCssClass'];
        }

        if (array_key_exists('lazy', $config)) {
            $this->lazy = $config['lazy'];
        }

        if (array_key_exists('adaptive', $config)) {
            $this->adaptive = $config['adaptive'];
        }

        if (array_key_exists('linkable', $config)) {
            $this->linkable = (bool) $config['linkable'];
        }
    }

    /**
     * isLazy checks if a tab should be lazy loaded
     */
    public function isLazy($tabName): bool
    {
        return in_array($tabName, $this->lazy);
    }

    /**
     * addLazy flags a tab to be lazy loaded
     */
    public function addLazy($tabName)
    {
        $this->lazy = array_merge((array) $this->lazy, (array) $tabName);
    }

    /**
     * isAdaptive checks if a tab uses adaptive sizing
     */
    public function isAdaptive($tabName): bool
    {
        return in_array($tabName, $this->adaptive);
    }

    /**
     * addAdaptive flags a tab to use adaptive sizing
     */
    public function addAdaptive($tabName)
    {
        $this->adaptive = array_merge((array) $this->adaptive, (array) $tabName);
    }

    /**
     * getIcon returns an icon for the tab based on the tab's name
     * @param string $name
     * @return string
     */
    public function getIcon($name)
    {
        if (!empty($this->icons[$name])) {
            return $this->icons[$name];
        }
    }

    /**
     * getPaneCssClass returns a tab pane CSS class
     * @param string $index
     * @param string $label
     * @return string
     */
    public function getPaneCssClass($index = null, $label = null)
    {
        if (is_string($this->paneCssClass)) {
            return $this->paneCssClass;
        }

        if ($index !== null && isset($this->paneCssClass[$index])) {
            return $this->paneCssClass[$index];
        }

        if ($label !== null && isset($this->paneCssClass[$label])) {
            return $this->paneCssClass[$label];
        }

        return $this->paneCssClass['*'] ?? '';
    }

    /**
     * setPaneCssClass appends a CSS class to the tab pane
     */
    public function setPaneCssClass($tabNameOrIndex, string $cssClass, bool $overwrite = false)
    {
        if (is_string($this->paneCssClass)) {
            $this->paneCssClass = ['*' => $this->paneCssClass];
        }

        if ($overwrite) {
            $this->paneCssClass[$tabNameOrIndex] = $cssClass;
        }
        else {
            $currentValue = $this->paneCssClass[$tabNameOrIndex] ?? '';
            $this->paneCssClass[$tabNameOrIndex] = trim($currentValue . ' ' . $cssClass);
        }
    }

    /**
     * isPaneActive returns a tab pane CSS class
     */
    public function isPaneActive($index = null, $label = null): bool
    {
        if ($this->activeTab === null) {
            return $index === 1;
        }

        if ($index !== null && $this->activeTab === $index) {
            return true;
        }

        if ($label !== null && $this->activeTab === $label) {
            return true;
        }

        return false;
    }
}
