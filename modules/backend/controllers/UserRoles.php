<?php namespace Backend\Controllers;

use View;
use Backend;
use Response;
use BackendAuth;
use Backend\Classes\SettingsController;

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
    public $requiredPermissions = ['backend.manage_users'];

    /**
     * @var string settingsItemCode determines the settings code
     */
    public $settingsItemCode = 'adminroles';

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        /*
         * Only super users can access
         */
        $this->bindEvent('page.beforeDisplay', function () {
            if (!$this->user->isSuperUser()) {
                return Response::make(View::make('backend::access_denied'), 403);
            }
        });
    }

    /**
     * formExtendFields adds available permission fields to the Role form.
     */
    public function formExtendFields($form)
    {
        /*
         * Add permissions tab
         */
        $form->addTabFields($this->generatePermissionsField());
    }

    /**
     * generatePermissionsField adds the permissions editor widget to the form.
     */
    protected function generatePermissionsField(): array
    {
        return [
            'permissions' => [
                'tab' => 'backend::lang.user.permissions',
                'type' => \Backend\FormWidgets\PermissionEditor::class,
                'mode' => 'checkbox'
            ]
        ];
    }

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
}
