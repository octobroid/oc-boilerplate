<?php namespace Backend\Controllers;

use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use Backend\Widgets\ReportContainer;

/**
 * Index controller for the dashboard
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class Index extends Controller
{
    use \Backend\Traits\InspectableContainer;

    /**
     * @var array requiredPermissions to view this page.
     * @see checkPermissionRedirect()
     */
    public $requiredPermissions = [];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContextOwner('October.Backend');

        $this->addCss('/modules/backend/assets/css/dashboard/dashboard.css', 'core');
    }

    /**
     * index
     */
    public function index()
    {
        if ($redirect = $this->checkPermissionRedirect()) {
            return $redirect;
        }

        $this->initReportContainer();

        $this->pageTitle = 'backend::lang.dashboard.menu_label';

        BackendMenu::setContextMainMenu('dashboard');
    }

    /**
     * index_onInitReportContainer
     */
    public function index_onInitReportContainer()
    {
        $this->initReportContainer();

        return ['#dashReportContainer' => $this->widget->reportContainer->render()];
    }

    /**
     * initReportContainer prepares the report widget used by the dashboard
     * @param Model $model
     * @return void
     */
    protected function initReportContainer()
    {
        new ReportContainer($this, 'config_dashboard.yaml');
    }

    /**
     * checkPermissionRedirect custom permissions check that will redirect to the next
     * available menu item, if permission to this page is denied.
     */
    protected function checkPermissionRedirect()
    {
        if ($this->user->hasAccess('backend.access_dashboard')) {
            return;
        }

        if ($first = array_first(BackendMenu::listMainMenuItems())) {
            return Redirect::intended($first->url);
        }
    }
}
