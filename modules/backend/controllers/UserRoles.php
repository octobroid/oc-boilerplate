<?php namespace Backend\Controllers;

use Backend;
use BackendAuth;
use Backend\Classes\SettingsController;
use ForbiddenException;

/**
 * UserRoles controller
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class UserRoles extends SettingsController
{
    /**
     * @var array Extensions implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    /**
     * @var array `FormController` configuration.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var array `ListController` configuration.
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array Permissions required to view this page.
     */
    public $requiredPermissions = ['admins.manage.roles'];

    /**
     * @var string settingsItemCode determines the settings code
     */
    public $settingsItemCode = 'adminroles';

    /**
     * onImpersonateRole
     */
    public function onImpersonateRole($roleId = null)
    {
        if ($role = $this->formFindModelObject($roleId)) {
            BackendAuth::impersonateRole($role);
        }

        return Backend::redirect('');
    }

    /**
     * listExtendQuery
     */
    public function listExtendQuery($query)
    {
        $this->applyRankPermissionsToQuery($query);
    }

    /**
     * formExtendQuery
     */
    public function formExtendQuery($query)
    {
        $this->applyRankPermissionsToQuery($query);
    }

    /**
     * applyRankPermissionsToQuery
     */
    protected function applyRankPermissionsToQuery($query)
    {
        // Super users have no restrictions
        if ($this->user->isSuperUser()) {
            return;
        }

        // User has no role and therefore cannot manage roles
        if (!$this->user->role || !$this->user->role->sort_order) {
            $query->whereRaw('1 = 2');
            return;
        }

        $query->where('sort_order', '>', $this->user->role->sort_order);
    }
}
