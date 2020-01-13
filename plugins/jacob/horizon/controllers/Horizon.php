<?php namespace Jacob\Horizon\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Horizon Back-end Controller
 */
class Horizon extends Controller
{
    public $requiredPermissions = ['jacob.horizon.access'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Jacob.Horizon', 'horizon', 'horizon');
    }

    public function index()
    {
        $this->pageTitle = 'Horizon dashboard';
    }
}
