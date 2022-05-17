<?php namespace Backend\VueComponents\TreeView;

use SystemException;
use Backend\VueComponents\DropdownMenu\ItemDefinition;

/**
 * Treeview node definition. 
 * Encapsulates Treeview node information.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class NodeDefinition
{
    const DISPLAY_MODE_TREE = 'tree';
    const DISPLAY_MODE_LIST = 'list';

    const DND_SORT = 'sort';
    const DND_MOVE = 'move';
    const DND_CUSTOM = 'custom';
    const DND_CUSTOM_EXTERNAL = 'custom-external';

    const GROUP_BY_MODE_FOLDERS = 'folders';
    const GROUP_BY_MODE_NESTING = 'nesting';

    private $label;

    private $key;

    private $childKeyPrefix;

    private $displayMode = NodeDefinition::DISPLAY_MODE_LIST;

    private $draggable = false;

    private $selectable = true;

    private $icon;

    private $nodes = [];

    private $userData = null;

    private $sortBy = null;

    private $groupBy = null;

    private $groupByMode = NodeDefinition::GROUP_BY_MODE_FOLDERS;

    private $dragAndDropMode = null;

    private $rootMenuItems = null;

    private $description = null;

    private $hasApiMenuItems = false;

    private $displayProperty;

    private $noMoveDrop = null;

    private $multiSelect = null;

    private $hideInQuickAccess = false;

    public function __construct(string $label, string $key)
    {
        $this->label = $label;
        $this->key = $key;
    }

    /**
     * Sets tree branch display mode - tree or list.
     * Only the root node display mode is considered.
     */
    public function setDisplayMode(string $value)
    {
        if (!in_array($value, [self::DISPLAY_MODE_TREE, self::DISPLAY_MODE_LIST])) {
            throw new SystemException('Invalid tree display mode: '.$value);
        }

        if ($this->groupBy) {
            throw new SystemException('Treeview branch grouping is only supported for the LIST display mode');
        }

        $this->displayMode = $value;

        return $this;
    }

    /**
     * Determines whether multiple nodes can be selected.
     * Only the root node property value is considered.
     */
    public function setMultiSelect(bool $value)
    {
        $this->multiSelect = $value;

        return $this;
    }

    /**
     * Determines whether the node is draggable.
     * Only the root node draggable value is considered.
     */
    public function setDraggable(bool $value)
    {
        $this->draggable = $value;

        return $this;
    }

    /**
     * Determines whether the node must be hidden in the Treeview Quick Access user interface.
     * This affects only leaf nodes.
     */
    public function setHideInQuickAccess($value)
    {
        $this->hideInQuickAccess = $value;

        return $this;
    }

    /**
     * Determines whether other nodes can be dropped to the node.
     */
    public function setNoMoveDrop(bool $value)
    {
        $this->noMoveDrop = $value;

        return $this;
    }

    /**
     * Sets node description. 
     * Node descriptions are rendered by the treeview component
     * but their styling must be done by a parent component.
     */
    public function setDescription(string $value)
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Determines if the node is selectable.
     * The value is ignored for root nodes - they cannot
     * be selected.
     */
    public function setSelectable(bool $value)
    {
        $this->selectable = $value;

        return $this;
    }

    public function setIcon(string $backgroundColor, string $iconClassName)
    {
        $this->icon = [
            'backgroundColor' => $backgroundColor,
            'cssClass' => $iconClassName
        ];

        return $this;
    }

    public function setFolderIcon()
    {
        $this->icon = 'folder';
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
        $this->childKeyPrefix = $this->childKeyPrefix.$prefix;
        return $this;
    }

    /**
     * Sets optional sorting for a tree branch.
     * Applies to root nodes only. Allows to sort
     * child nodes using userData object properties.
     * Syntax: propertyName1,propertyName2:desc.
     */
    public function setSortBy(string $sortBy)
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * Sets tree branch drag and drop mode - sort, move (or both), or custom.
     * Only the root node drag and drop mode is considered.
     */
    public function setDragAndDropMode(array $mode)
    {
        foreach ($mode as $option) {
            if (!in_array($option, [self::DND_MOVE, self::DND_SORT, self::DND_CUSTOM, self::DND_CUSTOM_EXTERNAL])) {
                throw new SystemException('Invalid treeview branch drag and drop mode: '.$option);
            }

            if ($option === self::DND_CUSTOM && count($mode) > 1) {
                throw new SystemException('Treeview branch drag and drop Custom mode cannot be combined with other modes');
            }
        }

        if ($this->groupBy) {
            throw new SystemException('Drag and drop is not supported for treeview branches with enabled Group By feature');
        }

        $this->dragAndDropMode = $mode;

        return $this;
    }

    /**
     * Sets optional grouping for a tree branch.
     * Applies to root nodes only. Allows to group
     * child nodes using a userData object property.
     * Supported for the list display mode only.
     */
    public function setGroupBy(string $property)
    {
        if ($this->displayMode != NodeDefinition::DISPLAY_MODE_LIST) {
            throw new SystemException('Treeview branch grouping is only supported for the LIST display mode');
        }

        if ($this->dragAndDropMode) {
            throw new SystemException('Drag and drop is not supported for treeview branches with enabled Group By feature');
        }

        $this->groupBy = $property;

        return $this;
    }

    public function setGroupByMode(string $mode) {
        if (!in_array($mode, [self::GROUP_BY_MODE_NESTING, self::GROUP_BY_MODE_FOLDERS])) {
            throw new SystemException('Invalid treeview branch group by mode: '.$mode);
        }

        $this->groupByMode = $mode;

        return $this;
    }

    public function addRootMenuItem($type, string $label = null, string $command = null)
    {
        if (!$this->rootMenuItems) {
            $this->rootMenuItems = new ItemDefinition(ItemDefinition::TYPE_TEXT, 'root', 'none');
        }

        return $this->rootMenuItems->addItem($type, $label, $command);
    }

    /**
     * Allows to set userData property name to use as the node label.
     * Applies to root nodes only.
     */
    public function setDisplayProperty(string $displayProperty)
    {
        $this->displayProperty = $displayProperty;

        return $this;
    }

    /**
     * Indicates that the menu item supports API-generated menu items.
     */
    public function setHasApiMenuItems(bool $hasApiMenuItems)
    {
        $this->hasApiMenuItems = $hasApiMenuItems;

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
            'uniqueKey' => $this->key,
            'displayMode' => $this->displayMode,
            'draggable' => $this->draggable,
            'selectable' => $this->selectable
        ];

        if ($this->icon) {
            $result['icon'] = $this->icon;
        }

        if ($this->description) {
            $result['description'] = $this->description;
        }

        if ($this->groupBy) {
            $result['groupBy'] = $this->groupBy;
            $result['groupByMode'] = $this->groupByMode;
        }

        if ($this->dragAndDropMode) {
            $result['dragAndDropMode'] = $this->dragAndDropMode;
        }

        if ($this->displayProperty) {
            $result['displayProperty'] = $this->displayProperty;
        }

        if ($this->sortBy) {
            $result['sortBy'] = $this->sortBy;
        }

        if ($this->hasApiMenuItems) {
            $result['hasApiMenuItems'] = $this->hasApiMenuItems;
        }

        if ($this->noMoveDrop !== null) {
            $result['noMoveDrop'] = $this->noMoveDrop;
        }

        if ($this->multiSelect !== null) {
            $result['multiSelect'] = $this->multiSelect;
        }

        if ($this->hideInQuickAccess) {
            $result['hideInQuickAccess'] = true;
        }

        $result['nodes'] = [];

        if ($this->userData) {
            $result['userData'] = $this->userData;
        }

        foreach ($this->nodes as $node) {
            $result['nodes'][] = $node->toArray();
        }

        if ($this->rootMenuItems) {
            $menuItems = $this->rootMenuItems->toArray();
            $result['topLevelMenuitems'] = $menuItems['items'];
        }

        return $result;
    }
}