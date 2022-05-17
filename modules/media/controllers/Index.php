<?php namespace Media\Controllers;

use BackendMenu;
use Media\Widgets\MediaManager;
use Backend\Classes\Controller;

/**
 * Index route for the Media Manager
 *
 * @package october\media
 * @author Alexey Bobkov, Samuel Georges
 */
class Index extends Controller
{
    /**
     * @var array requiredPermissions to view this page.
     */
    public $requiredPermissions = ['media.*'];

    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.Media', 'media', true);

        $this->pageTitle = 'backend::lang.media.menu_label';
    }

    /**
     * beforeDisplay
     */
    public function beforeDisplay()
    {
        $manager = new MediaManager($this, 'manager');
        $manager->bindToController();
    }

    /**
     * index
     */
    public function index()
    {
        $this->bodyClass = 'compact-container';
    }
}
