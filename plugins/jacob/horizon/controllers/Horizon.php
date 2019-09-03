<?php namespace Jacob\Horizon\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Illuminate\Support\Facades\Response;

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

    public function icon()
    {
        return Response::make(file_get_contents(plugins_path('jacob/horizon/icon/horizon.svg')), 200, [
            'Content-Type' => 'image/svg+xml'
        ]);
    }
}
