<?php namespace Backend\VueComponents\TreeView;

use SystemException;
use Backend\VueComponents\DropdownMenu\ItemDefinition;

/**
 * Treeview section definition. 
 * Encapsulates Treeview section information.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class SectionDefinition
{
    private $key;

    private $menuItems = null;

    private $createMenuItems = null;

    private $label;

    private $nodes = [];

    private $childKeyPrefix;

    private $hasApiMenuItems = false;

    private $userData = null;

    public function __construct(string $label, string $key)
    {
        $this->label = $label;
        $this->key = $key;
    }

    public function addNode(string $label, string $key)
    {
        if (strlen($this->childKeyPrefix)) {
            $key = $this->childKeyPrefix.$key;
        }

        $node = new NodeDefinition($label, $key);

        $node->setChildKeyPrefix($this->childKeyPrefix);

        return $this->nodes[] = $node;
    }

    public function setChildKeyPrefix($prefix)
    {
        $this->childKeyPrefix = $prefix;

        return $this;
    }

    public function addMenuItem($type, string $label = null, string $command = null)
    {
        if (!$this->menuItems) {
            $this->menuItems = new ItemDefinition(ItemDefinition::TYPE_TEXT, 'root', 'none');
        }

        return $this->menuItems->addItem($type, $label, $command);
    }

    public function addMenuItemObject(ItemDefinition $item)
    {
        if (!$this->menuItems) {
            $this->menuItems = new ItemDefinition(ItemDefinition::TYPE_TEXT, 'root', 'none');
        }

        return $this->menuItems->addItemObject($item);
    }

    public function addCreateMenuItem($type, string $label = null, string $command = null)
    {
        if (!$this->createMenuItems) {
            $this->createMenuItems = new ItemDefinition(ItemDefinition::TYPE_TEXT, 'root', 'none');
        }

        return $this->createMenuItems->addItem($type, $label, $command);
    }

    /**
     * Indicates that the section supports API-generated menu items.
     */
    public function setHasApiMenuItems(bool $hasApiMenuItems)
    {
        $this->hasApiMenuItems = $hasApiMenuItems;

        return $this;
    }

    /**
     * Sets optional user data object.
     */
    public function setUserData(array $userData)
    {
        $this->userData = $userData;

        return $this;
    }

    public function setUserDataElement(string $key, $value)
    {
        if (!is_array($this->userData)) {
            $this->userData = [];
        }

        $this->userData[$key] = $value;
        return $this;
    }

    public function getNodes()
    {
        return $this->nodes;
    }

    public function toArray()
    {
        $result = [
            'label' => $this->label,
            'uniqueKey' => $this->key
        ];

        if ($this->hasApiMenuItems) {
            $result['hasApiMenuItems'] = $this->hasApiMenuItems;
        }
        else {
            $result['hasApiMenuItems'] = false;
        }

        if ($this->userData) {
            $result['userData'] = $this->userData;
        }

        $result['nodes'] = [];

        foreach ($this->nodes as $node) {
            $result['nodes'][] = $node->toArray();
        }

        if ($this->menuItems) {
            $menuItems = $this->menuItems->toArray();
            $result['menuItems'] = $menuItems['items'];
        }

        if ($this->createMenuItems) {
            $createMenuItems = $this->createMenuItems->toArray();
            $result['createMenuItems'] = $createMenuItems['items'];
        }

        return $result;
    }
}