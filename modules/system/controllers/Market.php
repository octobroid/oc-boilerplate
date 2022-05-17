<?php namespace System\Controllers;

use Lang;
use Backend;
use Response;
use BackendMenu;
use Cms\Classes\ThemeManager;
use Backend\Classes\Controller;
use System\Classes\ProductDetail;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use System\Classes\SettingsManager;
use System\Widgets\Changelog;
use System\Widgets\Updater;
use ApplicationException;
use Exception;

/**
 * Marketplace controller
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class Market extends Controller
{
    /**
     * @var array Permissions required to view this page.
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
     * __construct
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

    public function composer()
    {
        return $this->updaterWidget->handleComposerAction();
    }

    /**
     * index shows marketplace information
     */
    public function index($tab = null)
    {
        if (get('search')) {
            return Response::make($this->onSearchProducts());
        }

        try {
            // $this->bodyClass = 'compact-container';
            $this->pageTitle = 'system::lang.market.menu_label';

            $this->addJs('/modules/system/assets/js/market/market.js', 'core');
            $this->addCss('/modules/system/assets/css/market/market.css', 'core');

            $projectDetails = UpdateManager::instance()->getProjectDetails();
            $defaultTab = $projectDetails ? 'project' : 'plugins';

            $this->vars['projectDetails'] = $projectDetails;
            $this->vars['activeTab'] = $tab ?: $defaultTab;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function plugin($urlCode = null, $tab = null)
    {
        try {
            $this->pageTitle = 'system::lang.updates.details_title_plugin';
            $this->addJs('/modules/system/assets/js/market/details.js', 'core');
            $this->addCss('/modules/system/assets/css/market/details.css', 'core');

            $code = $this->slugToCode($urlCode);
            $product = new ProductDetail($code);

            if (!$product->exists()) {
                throw new ApplicationException(Lang::get('system::lang.updates.plugin_not_found'));
            }

            // Fetch from server
            // if (get('fetch')) {
            //     $fetchedContent = UpdateManager::instance()->requestPluginContent($code);
            //     $product->upgradeHtml = array_get($fetchedContent, 'upgrade_guide_html');
            // }

            $this->vars['projectDetails'] = UpdateManager::instance()->getProjectDetails();
            $this->vars['activeTab'] = $tab ?: 'readme';
            $this->vars['urlCode'] = $urlCode;
            $this->vars['product'] = $product;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function theme($urlCode = null, $tab = null)
    {
        try {
            $this->pageTitle = 'system::lang.updates.details_title_theme';
            $this->addJs('/modules/system/assets/js/market/details.js', 'core');
            $this->addCss('/modules/system/assets/css/market/details.css', 'core');

            $code = $this->slugToCode($urlCode);
            $product = new ProductDetail($code, true);

            if (!$product->exists()) {
                throw new ApplicationException(Lang::get('system::lang.updates.theme_not_found'));
            }

            $this->vars['projectDetails'] = UpdateManager::instance()->getProjectDetails();
            $this->vars['activeTab'] = $tab ?: 'readme';
            $this->vars['urlCode'] = $urlCode;
            $this->vars['product'] = $product;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function onBrowseProject()
    {
        $project = UpdateManager::instance()->requestBrowseProject();
        $pluginManager = PluginManager::instance();
        $themeManager = ThemeManager::instance();

        $products = collect();

        foreach (($project['plugins'] ?? []) as $plugin) {
            $installed = $pluginManager->hasPlugin($plugin['code'] ?? null);
            $slug = $this->codeToSlug($plugin['code'] ?? null);

            $plugin['type'] = 'plugin';
            $plugin['slug'] = $slug;
            $plugin['detailUrl'] = $this->actionUrl('plugin') . '/' . $slug;
            $plugin['installed'] = $installed;
            $plugin['handler'] = $installed
                ? $this->updaterWidget->getEventHandler('onRemovePlugin')
                : $this->updaterWidget->getEventHandler('onInstallPlugin');

            $products->add($plugin);
        }

        foreach (($project['themes'] ?? []) as $theme) {
            $installed = $themeManager->isInstalled($theme['code'] ?? null);
            $slug = $this->codeToSlug($theme['code'] ?? null);

            $theme['type'] = 'theme';
            $theme['slug'] = $slug;
            $theme['detailUrl'] = $this->actionUrl('theme') . '/' . $slug;
            $theme['installed'] = $installed;
            $theme['handler'] = $installed
                ? $this->updaterWidget->getEventHandler('onRemoveTheme')
                : $this->updaterWidget->getEventHandler('onInstallTheme');

            $products->add($theme);
        }

        $products->sortBy('updated_at');

        return $project + [
            'products' => $products
        ];
    }

    public function onSearchProducts()
    {
        $searchType = get('search', 'plugin');
        $serverUri = $searchType == 'plugin' ? 'plugin/search' : 'theme/search';

        $manager = UpdateManager::instance();
        return $manager->requestServerData($serverUri, ['query' => get('query')]);
    }

    public function onSelectProduct()
    {
        $slug = $this->codeToSlug(post('code'));
        $type = post('type') === 'theme' ? 'theme' : 'plugin';
        return Backend::redirect('system/market/'.$type.'/'.$slug);
    }

    public function onBrowsePackages()
    {
        $type = post('type', 'plugin');
        $page = get($type.'_page');

        $packages = UpdateManager::instance()->requestBrowseProducts($type, $page);

        // Inject slug attribute for URLs
        foreach (array_get($packages, 'data', []) as $key => $package) {
            $packages['data'][$key]['slug'] = $this->codeToSlug($package['code']);
        }

        return ['result' => $packages];
    }

    /**
     * slugToCode converts a slug to a product code
     * rainlab-blog -> rainlab.blog
     */
    protected function slugToCode(string $code): string
    {
        $parts = explode('-', $code, 2);

        if (!isset($parts[1])) {
            return strtolower($code);
        }
        else {
            return strtolower($parts[0].'.'.$parts[1]);
        }
    }

    /**
     * codeToSlug converts a product code to a slug
     * RainLab.Blog -> rainlab-blog
     */
    protected function codeToSlug(string $code): string
    {
        return strtolower(str_replace('.', '-', $code));
    }
}
