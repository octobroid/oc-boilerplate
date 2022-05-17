<?php namespace System\Controllers;

use Lang;
use Flash;
use Backend;
use BackendMenu;
use Backend\Classes\Controller;
use System\Models\PluginVersion;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use System\Classes\SettingsManager;
use System\Widgets\Changelog;
use System\Widgets\Updater;

/**
 * Updates controller
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class Updates extends Controller
{
    /**
     * @var array Extensions implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\ListController::class
    ];

    /**
     * @var array `ListController` configuration.
     */
    public $listConfig = [
        'list' => 'config_list.yaml',
        'manage' => 'config_manage_list.yaml'
    ];

    /**
     * @var array requiredPermissions to view this page.
     */
    public $requiredPermissions = ['system.manage_updates'];

    /**
     * @var System\Widgets\Changelog
     */
    protected $changelogWidget;

    /**
     * @var System\Widgets\Updater
     */
    protected $updaterWidget;

    /**
     * __construct the class
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'updates');
        SettingsManager::setContext('October.System', 'updates');

        $this->changelogWidget = new Changelog($this);
        $this->changelogWidget->bindToController();

        $this->updaterWidget = new Updater($this);
        $this->updaterWidget->bindToController();
    }

    /**
     * composer endpoint used by updaterWidget
     */
    public function composer()
    {
        return $this->updaterWidget->handleComposerAction();
    }

    /**
     * index controller
     */
    public function index()
    {
        $this->addJs('/modules/system/assets/js/updates/updates.js', 'core');

        $this->vars['currentVersion'] = UpdateManager::instance()->getCurrentVersion();
        $this->vars['projectDetails'] = UpdateManager::instance()->getProjectDetails();
        $this->vars['pluginsActiveCount'] = PluginVersion::applyEnabled()->count();
        $this->vars['pluginsCount'] = PluginVersion::count();
        return $this->asExtension('ListController')->index();
    }

    /**
     * index_onCompareVersions
     */
    public function index_onCompareVersions()
    {
        $force = (bool) post('force', false);

        return UpdateManager::instance()->checkVersions($force);
    }

    /**
     * manage controller for plugins
     */
    public function manage()
    {
        $this->pageTitle = 'system::lang.plugins.manage';
        PluginManager::instance()->clearDisabledCache();
        return $this->asExtension('ListController')->index();
    }

    /**
     * manage_onBulkAction performs a bulk action on the provided plugins
     */
    public function manage_onBulkAction()
    {
        if (
            ($bulkAction = post('action')) &&
            ($checkedIds = post('checked')) &&
            is_array($checkedIds) &&
            count($checkedIds)
        ) {
            $manager = PluginManager::instance();

            foreach ($checkedIds as $pluginId) {
                if (!$plugin = PluginVersion::find($pluginId)) {
                    continue;
                }

                $savePlugin = true;
                switch ($bulkAction) {
                    // Disables plugin on the system
                    case 'disable':
                        $plugin->is_disabled = 1;
                        $manager->disablePlugin($plugin->code, true);
                        break;

                    // Enables plugin on the system
                    case 'enable':
                        $plugin->is_disabled = 0;
                        $manager->enablePlugin($plugin->code, true);
                        break;

                    // Rebuilds plugin database migrations
                    case 'refresh':
                        $savePlugin = false;
                        if ($plugin->orphaned) {
                            UpdateManager::instance()->rollbackPlugin($plugin->code);
                        }
                        else {
                            $manager->refreshPlugin($plugin->code);
                        }
                        break;

                    // Rollback and remove plugins from the system
                    case 'remove':
                        $savePlugin = false;
                        $manager->deletePlugin($plugin->code);
                        break;
                }

                if ($savePlugin) {
                    $plugin->save();
                }
            }
        }

        Flash::success(Lang::get("system::lang.plugins.{$bulkAction}_success"));
        return $this->listRefresh('manage');
    }

    /**
     * listInjectRowClass is an override for the ListController behavior
     * Modifies the CSS class for each row in the list to
     *
     * - hidden - Disabled by configuration
     * - safe disabled - Orphaned or disabled
     * - negative - Disabled by system
     * - frozen - Frozen by the user
     * - positive - Default CSS class
     *
     * @see Backend\Behaviors\ListController
     * @return string
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if ($record->disabledByConfig) {
            return 'hidden';
        }

        if ($record->orphaned || $record->is_disabled) {
            return 'safe disabled';
        }

        if ($definition != 'manage') {
            return;
        }

        if ($record->disabledBySystem) {
            return 'negative';
        }

        return 'positive';
    }
}
