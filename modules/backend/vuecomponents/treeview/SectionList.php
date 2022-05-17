<?php namespace Backend\VueComponents\TreeView;

use SystemException;

/**
 * Treeview section list. 
 * Encapsulates a list of Treeview sections.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class SectionList
{
    private $sections = [];

    private $childKeyPrefix;

    public function addSection(string $label, string $key)
    {
        $section = new SectionDefinition($label, $key);
        $section->setChildKeyPrefix($this->childKeyPrefix);

        return $this->sections[] = $section;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function setChildKeyPrefix($prefix)
    {
        $this->childKeyPrefix = $prefix;

        return $this;
    }

    public function toArray()
    {
        $result = [];
        foreach ($this->sections as $section)
        {
            $result[] = $section->toArray();
        }

        return $result;
    }
}