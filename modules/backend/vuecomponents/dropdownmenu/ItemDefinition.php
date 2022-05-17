<?php namespace Backend\VueComponents\DropdownMenu;

use SystemException;
use Backend\Classes\VueComponentBase;

/**
 * Dropdown menu item definition.
 * Encapsulates Dropdown menu item information.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ItemDefinition
{
    const TYPE_TEXT = 'text';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIOBUTTON = 'radiobutton';
    const TYPE_SEPARATOR = 'separator';

    private $disabled = false;

    private $type = ItemDefinition::TYPE_TEXT;

    private $label;

    private $linkHref;

    private $linkTarget;

    private $icon;

    private $command;

    private $items = [];

    private $checked = false;

    private $userData = null;

    private $key = null;

    private $group;

    public function __construct($type, string $label = null, string $command = null)
    {
        $this->type = $type;

        if ($type != ItemDefinition::TYPE_SEPARATOR && !strlen($label)) {
            throw new SystemException('Dropdown menu item label is not provided for item type '.$type);
        }

        if ($type != ItemDefinition::TYPE_SEPARATOR && !strlen($command)) {
            throw new SystemException('Dropdown menu item command is not provided for item type '.$type);
        }

        $this->label = $label;
        $this->command = $command;
    }

    /**
     * Sets optional item key attribute
     */
    public function setKey(string $value)
    {
        $this->key = $value;
    }

    public function setLinkHref(string $value)
    {
        $this->linkHref = $value;

        return $this; 
    }

    public function setLinkTarget(string $value)
    {
        $this->linkTarget = $value;

        return $this; 
    }

    public function setDisabled(bool $value)
    {
        $this->disabled = $value;

        return $this; 
    }

    public function setIcon(string $value)
    {
        if (in_array($this->type, [self::TYPE_CHECKBOX, self::TYPE_RADIOBUTTON])) {
            throw new SystemException('Checkbox and radiobutton dropdown menu items cannot have icons');
        }

        $this->icon = $value;

        return $this;
    }

    public function setChecked(bool $value)
    {
        $this->checked = $value;
    }

    public function addItem($type, string $label = null, string $command = null)
    {
        return $this->items[] = new ItemDefinition($type, $label, $command);
    }

    public function addItemObject(ItemDefinition $item)
    {
        return $this->items[] = $item;
    }

    public function hasItems()
    {
        return count($this->items) > 0;
    }

    /**
     * Sets optional user data object.
     */
    public function setUserData(array $userData)
    {
        $this->userData = $userData;

        return $this;
    }

    public function setGroup(string $group)
    {
        $this->group = $group;

        return $this;
    }

    public function toArray()
    {
        $result = [
            'type' => $this->type,
            'icon' => $this->icon,
            'command' => $this->command,
            'label' => $this->label,
            'href' => $this->linkHref,
            'target' => $this->linkTarget,
            'disabled' => $this->disabled,
            'checked' => $this->checked
        ];

        if ($this->group) {
            $result['group'] = $this->group;
        }

        if ($this->userData) {
            $result['userData'] = $this->userData;
        }

        if ($this->key) {
            $result['key'] = $this->key;
        }

        $result['items'] = [];

        if ($this->items) {
            $subItems = [];

            foreach ($this->items as $item) {
                $subItems[] = $item->toArray();
            }

            $result['items'] = $subItems;
        }

        return $result;
    }
}