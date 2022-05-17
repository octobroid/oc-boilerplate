<?php namespace Backend\Classes;

use System\Classes\PluginManager;
use October\Rain\Auth\Manager as RainAuthManager;
use October\Rain\Exception\SystemException;

/**
 * AuthManager is backend authentication manager.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class AuthManager extends RainAuthManager
{
    /**
     * {@inheritdoc}
     */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    protected $sessionKey = 'admin_auth';

    /**
     * {@inheritdoc}
     */
    protected $userModel = \Backend\Models\User::class;

    /**
     * @var string roleModel class
     */
    protected $roleModel = \Backend\Models\UserRole::class;

    /**
     * {@inheritdoc}
     */
    protected $groupModel = \Backend\Models\UserGroup::class;

    /**
     * {@inheritdoc}
     */
    protected $throttleModel = \Backend\Models\UserThrottle::class;

    /**
     * {@inheritdoc}
     */
    protected $requireActivation = false;

    //
    // Permission management
    //

    /**
     * permissionDefaults
     */
    protected static $permissionDefaults = [
        'code' => null,
        'label' => null,
        'comment' => null,
        'roles' => null,
        'order' => 500
    ];

    /**
     * @var array callbacks for registration.
     */
    protected $callbacks = [];

    /**
     * @var array permissions registered.
     */
    protected $permissions = [];

    /**
     * @var array permissionRoles is a list of registered permission roles.
     */
    protected $permissionRoles = false;

    /**
     * @var array permissionCache of registered permissions.
     */
    protected $permissionCache = false;

    /**
     * userHasAccess is identical to User::hasAccess
     */
    public function userHasAccess($permissions, $all = true)
    {
        if ($user = $this->getUser()) {
            return $user->hasAccess($permissions, $all);
        }

        return false;
    }

    /**
     * registerCallback registers a callback function that defines authentication permissions.
     * The callback function should register permissions by calling the manager's
     * registerPermissions() function. The manager instance is passed to the
     * callback function as an argument. Usage:
     *
     *     BackendAuth::registerCallback(function ($manager) {
     *         $manager->registerPermissions([...]);
     *     });
     *
     * @param callable $callback A callable function.
     */
    public function registerCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * registerPermissions registers the back-end permission items.
     * The argument is an array of the permissions. The array keys represent the
     * permission codes, specific for the plugin/module. Each element in the
     * array should be an associative array with the following keys:
     * - label - specifies the menu label localization string key, required.
     * - order - a position of the item in the menu, optional.
     * - comment - a brief comment that describes the permission, optional.
     * - tab - assign this permission to a tabbed group, optional.
     * @param string $owner Specifies the permissions' owner plugin or module in the format Author.Plugin
     * @param array $definitions An array of the menu item definitions.
     */
    public function registerPermissions($owner, array $definitions)
    {
        foreach ($definitions as $code => $definition) {
            $permission = (object)array_merge(self::$permissionDefaults, array_merge($definition, [
                'code' => $code,
                'owner' => $owner
            ]));

            $this->permissions[] = $permission;
        }
    }

    /**
     * removePermission removes a single back-end permission. Where owner specifies the
     * permissions' owner plugin or module in the format Author.Plugin. Where code is
     * the permission to remove.
     */
    public function removePermission(string $owner, string $code)
    {
        if (!$this->permissions) {
            throw new SystemException('Unable to remove permissions before they are loaded.');
        }

        $ownerPermissions = array_filter($this->permissions, function ($permission) use ($owner) {
            return $permission->owner === $owner;
        });

        foreach ($ownerPermissions as $key => $permission) {
            if ($permission->code === $code) {
                unset($this->permissions[$key]);
            }
        }
    }

    /**
     * listPermissions returns a list of the registered permissions items.
     */
    public function listPermissions(): array
    {
        if ($this->permissionCache !== false) {
            return $this->permissionCache;
        }

        /*
         * Load module items
         */
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        /*
         * Load plugin items
         */
        $plugins = PluginManager::instance()->getPlugins();

        foreach ($plugins as $id => $plugin) {
            $items = $plugin->registerPermissions();
            if (!is_array($items)) {
                continue;
            }

            $this->registerPermissions($id, $items);
        }

        /*
         * Sort permission items
         */
        usort($this->permissions, function ($a, $b) {
            if ($a->order === $b->order) {
                return 0;
            }

            return $a->order > $b->order ? 1 : -1;
        });

        return $this->permissionCache = $this->permissions;
    }

    /**
     * listTabbedPermissions returns an array of registered permissions, grouped by tabs.
     */
    public function listTabbedPermissions(): array
    {
        $tabs = [];

        foreach ($this->listPermissions() as $permission) {
            $tab = $permission->tab ?? 'backend::lang.form.undefined_tab';

            if (!array_key_exists($tab, $tabs)) {
                $tabs[$tab] = [];
            }

            $tabs[$tab][] = $permission;
        }

        return $tabs;
    }

    /**
     * {@inheritdoc}
     */
    protected function createUserModelQuery()
    {
        return parent::createUserModelQuery()->withTrashed();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateUserModel($user)
    {
        if (!$user instanceof $this->userModel) {
            return false;
        }

        if ($user->deleted_at !== null) {
            return false;
        }

        return $user;
    }

    /**
     * listPermissionsForRole returns an array of registered permissions belonging to a
     * given role code.
     * @param string $role
     * @param bool $includeOrphans
     * @return array
     */
    public function listPermissionsForRole($role, $includeOrphans = true): array
    {
        if ($this->permissionRoles === false) {
            $this->permissionRoles = [];

            foreach ($this->listPermissions() as $permission) {
                if ($permission->roles) {
                    foreach ((array) $permission->roles as $_role) {
                        $this->permissionRoles[$_role][$permission->code] = 1;
                    }
                }
                else {
                    $this->permissionRoles['*'][$permission->code] = 1;
                }
            }
        }

        $result = $this->permissionRoles[$role] ?? [];

        if ($includeOrphans) {
            $result += $this->permissionRoles['*'] ?? [];
        }

        return $result;
    }

    /**
     * hasPermissionsForRole checks if the user has the permissions for a role.
     */
    public function hasPermissionsForRole($role): bool
    {
        return !!$this->listPermissionsForRole($role, false);
    }
}
