<?php namespace System\Controllers;

use Lang;
use Flash;
use System;
use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use System\Models\EventLog;

/**
 * EventLogs controller
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class EventLogs extends Controller
{
    /**
     * @var array implement extensions in this controller
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    /**
     * @var array formConfig `FormController` configuration.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var array listConfig `ListController` configuration.
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array requiredPermissions to view this page
     */
    public $requiredPermissions = ['system.access_logs'];

    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('October.System', 'event_logs');
    }

    /**
     * index_onRefresh
     */
    public function index_onRefresh()
    {
        return $this->listRefresh();
    }

    /**
     * index_onEmptyLog
     */
    public function index_onEmptyLog()
    {
        EventLog::truncate();
        Flash::success(Lang::get('system::lang.event_log.empty_success'));
        return $this->listRefresh();
    }

    /**
     * index_onDelete
     */
    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = EventLog::find($recordId)) {
                    continue;
                }
                $record->delete();
            }

            Flash::success(Lang::get('backend::lang.list.delete_selected_success'));
        }
        else {
            Flash::error(Lang::get('backend::lang.list.delete_selected_empty'));
        }

        return $this->listRefresh();
    }

    /**
     * preview page action
     */
    public function preview($id)
    {
        $this->addCss('/modules/system/assets/css/eventlogs/exception-beautifier.css', 'core');
        $this->addJs('/modules/system/assets/js/eventlogs/exception-beautifier.js', 'core');

        if (System::checkDebugMode()) {
            $this->addJs('/modules/system/assets/js/eventlogs/exception-beautifier.links.js', 'core');
        }

        return $this->asExtension('FormController')->preview($id);
    }
}
