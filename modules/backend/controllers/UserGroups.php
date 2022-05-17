<?php namespace Backend\Controllers;

use Backend\Classes\SettingsController;

/**
 * UserGroups controller
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class UserGroups extends SettingsController
{
    /**
     * @var array Extensions implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class
    ];

    /**
     * @var array formConfig for `FormController` configuration.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var array listConfig for `ListController` configuration.
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array relationConfig for `RelationController` configuration.
     */
    public $relationConfig = 'config_relation.yaml';

    /**
     * @var array Permissions required to view this page.
     */
    public $requiredPermissions = ['backend.manage_users'];

    /**
     * @var string settingsItemCode determines the settings code
     */
    public $settingsItemCode = 'admingroups';
}
