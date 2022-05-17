<?php namespace Backend\Models;

use Backend\Classes\AuthManager;
use October\Rain\Auth\Models\Role as RoleBase;

/**
 * UserRole for an administrator
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class UserRole extends RoleBase
{
    const CODE_DEVELOPER = 'developer';
    const CODE_PUBLISHER = 'publisher';

    /**
     * @var string table associated with the model
     */
    protected $table = 'backend_user_roles';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|between:2,128|unique:backend_user_roles',
        'code' => 'unique:backend_user_roles',
    ];

    /**
     * @var array hasMany relationship
     */
    public $hasMany = [
        'users' => [User::class, 'key' => 'role_id']
    ];

    /**
     * filterFields used by the form controller
     */
    public function filterFields($fields)
    {
        if ($this->is_system) {
            $fields->code->disabled = true;
            $fields->permissions->disabled = true;
        }
    }

    /**
     * afterFetch event
     */
    public function afterFetch()
    {
        if ($this->is_system) {
            $this->permissions = $this->getDefaultPermissions();
        }
    }

    /**
     * beforeSave event
     */
    public function beforeSave()
    {
        if ($this->isSystemRole()) {
            $this->is_system = true;
            $this->permissions = [];
        }
    }

    /**
     * isSystemRole checks if a role is locked by the system
     */
    public function isSystemRole(): bool
    {
        if (!$this->code || !strlen(trim($this->code))) {
            return false;
        }

        if ($this->is_system || in_array($this->code, [
            self::CODE_DEVELOPER,
            self::CODE_PUBLISHER
        ])) {
            return true;
        }

        return AuthManager::instance()->hasPermissionsForRole($this->code);
    }

    /**
     * getDefaultPermissions returns default permissions for a role
     */
    public function getDefaultPermissions()
    {
        return AuthManager::instance()->listPermissionsForRole($this->code);
    }
}
