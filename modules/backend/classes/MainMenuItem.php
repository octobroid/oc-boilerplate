<?php namespace Backend\Classes;

use October\Rain\Element\Navigation\ItemDefinition;
use October\Rain\Exception\SystemException;

/**
 * MainMenuItem
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class MainMenuItem extends ItemDefinition
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
     * @var mixed counter
     */
    public $counter;

    /**
     * @var null|string counterLabel
     */
    public $counterLabel;

    /**
     * @var array permissions
     */
    public $permissions = [];

    /**
     * @var SideMenuItem[] sideMenu
     */
    public $sideMenu = [];

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
        $this->permissions = $config['permissions'] ?? $this->permissions;
        $this->order = $config['order'] ?? 500;
    }

    /**
     * addPermission
     * @param string $permission
     * @param array $definition
     */
    public function addPermission(string $permission, array $definition)
    {
        $this->permissions[$permission] = $definition;
    }

    /**
     * addSideMenuItem
     * @param SideMenuItem $sideMenu
     */
    public function addSideMenuItem(SideMenuItem $sideMenu)
    {
        $this->sideMenu[$sideMenu->code] = $sideMenu;
    }

    /**
     * getSideMenuItem
     * @param string $code
     * @return SideMenuItem
     * @throws SystemException
     */
    public function getSideMenuItem(string $code)
    {
        if (!array_key_exists($code, $this->sideMenu)) {
            throw new SystemException('No sidenavigation item available with code ' . $code);
        }

        return $this->sideMenu[$code];
    }

    /**
     * removeSideMenuItem
     * @param string $code
     */
    public function removeSideMenuItem(string $code)
    {
        unset($this->sideMenu[$code]);
    }
}
