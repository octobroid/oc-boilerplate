<?php

namespace Renatio\BackupManager\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Renatio\BackupManager\Behaviors\BackupController;
use Renatio\BackupManager\Classes\SystemRequirements;

/**
 * Class Backups
 * @package Renatio\BackupManager\Controllers
 */
class Backups extends Controller
{

    /**
     * @var array
     */
    public $requiredPermissions = ['renatio.backupmanager.access_backups'];

    /**
     * @var array
     */
    public $implement = [
        'Backend.Behaviors.ListController',
        BackupController::class,
    ];

    /**
     * @var string
     */
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Renatio.BackupManager', 'backupmanager', 'backups');
    }

    /**
     * @return void
     */
    public function index()
    {
        $this->checkSystemRequirements();

        $this->asExtension('ListController')->index();
    }

    /**
     * Show hints for system requirements issues
     *
     * @return void
     */
    protected function checkSystemRequirements()
    {
        $this->vars['issues'] = (new SystemRequirements)->check();
    }

}