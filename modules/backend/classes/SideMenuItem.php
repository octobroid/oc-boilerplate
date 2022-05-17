<?php namespace Backend\Classes;

use October\Rain\Element\Navigation\ItemDefinition;

/**
 * SideMenuItem
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class SideMenuItem extends ItemDefinition
{
    /**
     * @var string owner
     */
    public $owner;

    /**
     * @var null|string iconSvg
     */
    public $iconSvg;

    /**
     * @var null|int|callable counter
     */
    public $counter;

    /**
     * @var null|string counterLabel
     */
    public $counterLabel;

    /**
     * @var array attributes
     */
    public $attributes = [];

    /**
     * @var array permissions
     */
    public $permissions = [];

    /**
     * @var string itemType
     */
    public $itemType;

    /**
     * @var string buttonActiveOn
     */
    public $buttonActiveOn;

    /**
     * evalConfig
     */
    protected function evalConfig($config): void
    {
        parent::evalConfig($config);

        $this->owner = $config['owner'] ?? $this->owner;
        $this->iconSvg = $config['iconSvg'] ?? $this->iconSvg;
        $this->counter = $config['counter'] ?? $this->counter;
        $this->counterLabel = $config['counterLabel'] ?? $this->counterLabel;
        $this->attributes = $config['attributes'] ?? $this->attributes;
        $this->permissions = $config['permissions'] ?? $this->permissions;
        $this->itemType = $config['itemType'] ?? $this->itemType;
        $this->buttonActiveOn = $config['buttonActiveOn'] ?? $this->buttonActiveOn;
    }

    /**
     * addAttribute
     * @param null|string|int $attribute
     * @param null|string|array $value
     */
    public function addAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    /**
     * removeAttribute
     */
    public function removeAttribute($attribute)
    {
        unset($this->attributes[$attribute]);
    }

    /**
     * addPermission
     */
    public function addPermission(string $permission, array $definition)
    {
        $this->permissions[$permission] = $definition;
    }

    /**
     * removePermission
     * @param string $permission
     * @return void
     */
    public function removePermission(string $permission)
    {
        unset($this->permissions[$permission]);
    }
}
