<?php namespace Backend\Classes;

use October\Rain\Element\Navigation\ItemDefinition;
use October\Rain\Exception\SystemException;

/**
 * MainMenuItem
 *
 * @method MainMenuItem owner(string $owner) owner
 * @method MainMenuItem iconSvg(null|string $iconSvg) iconSvg
 * @method MainMenuItem counter(mixed $counter) counter
 * @method MainMenuItem counterLabel(null|string $counterLabel) counterLabel
 * @method MainMenuItem permissions(array $permissions) permissions
 * @method MainMenuItem sideMenu(SideMenuItem[] $sideMenu) sideMenu
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class MainMenuItem extends ItemDefinition
{
    /**
     * initDefaultValues for this scope
     */
    protected function initDefaultValues()
    {
        parent::initDefaultValues();

        $this
            ->order(500)
            ->permissions([])
            ->sideMenu([])
        ;
    }

    /**
     * addPermission
     * @param string $permission
     * @param array $definition
     */
    public function addPermission(string $permission, array $definition)
    {
        $this->config['permissions'][$permission] = $definition;
    }

    /**
     * addSideMenuItem
     * @param SideMenuItem $sideMenu
     */
    public function addSideMenuItem(SideMenuItem $sideMenu)
    {
        $this->config['sideMenu'][$sideMenu->code] = $sideMenu;
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

        return $this->config['sideMenu'][$code];
    }

    /**
     * removeSideMenuItem
     * @param string $code
     */
    public function removeSideMenuItem(string $code)
    {
        unset($this->config['sideMenu'][$code]);
    }
}
