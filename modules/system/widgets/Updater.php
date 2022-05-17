<?php namespace System\Widgets;

use Lang;
use Flash;
use Backend;
use Redirect;
use Exception;
use ApplicationException;
use Cms\Classes\ThemeManager;
use Backend\Classes\WidgetBase;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use System\Models\PluginVersion;
use October\Rain\Process\Composer as ComposerProcess;

/**
 * Updater widget
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class Updater extends WidgetBase
{
    /**
     * @var string Defined alias used for this widget.
     */
    public $alias = 'updater';

    /**
     * loadAssets adds widget specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     */
    protected function loadAssets()
    {
        $this->addCss('css/updater.css', 'core');
        $this->addJs('js/updater.js', 'core');
    }

    /**
     * render renders the widget
     */
    public function render(): string
    {
        return '';
    }

    public function composerActionUrl()
    {
        return $this->controller->actionUrl('composer');
    }

    /**
     * handleComposerAction is the logic for the composer iframe route
     * This logic should be added to the controller:
     *
     *     public function composer()
     *     {
     *         return $this->updaterWidget->handleComposerAction();
     *     }
     */
    public function handleComposerAction()
    {
        $this->controller->suppressLayout = true;

        ini_set('max_input_time', 0);
        ini_set('max_execution_time', 0);

        while (@ob_end_flush());

        $composer = new ComposerProcess;

        $composer->setCallback(function($msg) {
            if ($nMsg = $this->processOutput($msg)) {
                if ($this->checkIgnoreOutput($nMsg)) {
                    return;
                }

                echo '<line>'.trim($nMsg).'</line>' . PHP_EOL;
                flush();
            }
        });

        flush();

        $actionCode = get('code');

        switch ($actionCode) {
            case 'updateCore':
                $composer->update();
                break;

            case 'installTheme':
            case 'installPlugin':
                $composer->require(get('packages'));
                break;

            case 'removeTheme':
            case 'removePlugin':
                $composer->remove(get('packages'));
                break;

            default:
                echo "<exit>Unknown code {$actionCode}</exit>";
                return;
        }

        echo "<exit>{$composer->lastExitCode()}</exit>";
    }

    /**
     * checkIgnoreOutput checks for any unrelated composer messages
     * and is used to ignore them
     */
    protected function checkIgnoreOutput($message): bool
    {
        if (strlen(trim($message)) === 0) {
            return true;
        }

        if (starts_with($message, [
            'Warning from',
            'Warning: Accessing',
            'Loading from cache',
            'Downloading',
            'Reading composer.json of'
        ])) {
            return true;
        }

        if (ends_with($message, [
            'No replacement was suggested.'
        ])) {
            return true;
        }

        return false;
    }

    /**
     * onLoadUpdates spawns the update checker popup
     */
    public function onLoadUpdates()
    {
        return $this->makePartial('update_form');
    }

    /**
     * onCheckForUpdates contacts the update server for a list of necessary updates.
     */
    public function onCheckForUpdates()
    {
        try {
            $result = UpdateManager::instance()->requestUpdateList();
            $result = $this->processUpdateLists($result);
            $result = $this->processImportantUpdates($result);

            $this->vars['core'] = $result['core'] ?? false;
            $this->vars['hasUpdates'] = $result['hasUpdates'] ?? false;
            $this->vars['hasImportantUpdates'] = $result['hasImportantUpdates'] ?? false;
            $this->vars['pluginList'] = $result['plugins'] ?? [];
            $this->vars['themeList'] = $result['themes'] ?? [];
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return ['#updateContainer' => $this->makePartial('update_list')];
    }

    /**
     * onSyncProject synchronizes plugin packages with local packages
     */
    public function onSyncProject()
    {
        try {
            $installPackages = UpdateManager::instance()->syncProjectPackages();

            $updateSteps = [];

            foreach ($installPackages as $composerCode) {
                $updateSteps[] = [
                    'type'  => 'composer',
                    'code'  => 'installPlugin',
                    'label' => Lang::get('system::lang.updates.plugin_downloading', ['name' => $composerCode]),
                    'name'  => $composerCode
                ];
            }

            $updateSteps[] = [
                'type'  => 'final',
                'code' => 'completeUpdate',
                'label' => Lang::get('system::lang.updates.update_completing'),
            ];

            $this->vars['updateSteps'] = $updateSteps;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('execute');
    }

    /**
     * onApplyUpdates runs the composer update process
     */
    public function onApplyUpdates()
    {
        try {
            $updateSteps = [
                [
                    'type'  => 'composer',
                    'code'  => 'updateCore',
                    'label' => Lang::get('system::lang.updates.core_downloading')
                ],
                [
                    'type'  => 'final',
                    'code' => 'completeUpdate',
                    'label' => Lang::get('system::lang.updates.update_completing'),
                ]
            ];

            $this->vars['updateSteps'] = $updateSteps;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('execute');
    }

    /**
     * onInstallPlugin validates the plugin code and execute the plugin installation
     */
    public function onInstallPlugin()
    {
        try {
            if (!$code = trim(post('code'))) {
                throw new ApplicationException(Lang::get('system::lang.install.missing_plugin_name'));
            }

            if (!$composerCode = $this->findPluginComposerCode($code)) {
                throw new ApplicationException(Lang::get('system::lang.updates.plugin_not_found'));
            }

            $updateSteps = [
                [
                    'type'  => 'composer',
                    'code'  => 'installPlugin',
                    'label' => Lang::get('system::lang.updates.plugin_downloading', ['name' => $composerCode]),
                    'name'  => $composerCode
                ],
                [
                    'type'  => 'final',
                    'code'  => 'completeInstall',
                    'label' => Lang::get('system::lang.install.install_completing'),
                ]
            ];

            $this->vars['updateSteps'] = $updateSteps;

            return $this->makePartial('execute');
        }
        catch (Exception $ex) {
            $this->handleError($ex);
            return $this->makePartial('plugin_form');
        }
    }

    /**
     * onRemovePlugin removes an existing plugin
     */
    public function onRemovePlugin()
    {
        try {
            if (!$code = trim(post('code'))) {
                throw new ApplicationException(Lang::get('system::lang.install.missing_plugin_name'));
            }

            if (!$composerCode = $this->findPluginComposerCode($code)) {
                throw new ApplicationException(Lang::get('system::lang.updates.plugin_not_found'));
            }

            $updateSteps = [
                [
                    'type'  => 'composer',
                    'code'  => 'removePlugin',
                    'label' => Lang::get('system::lang.updates.plugin_removing', ['name' => $composerCode]),
                    'name'  => $composerCode
                ],
                [
                    'type'  => 'final',
                    'code'  => 'completeInstall',
                    'label' => Lang::get('system::lang.install.install_completing'),
                ]
            ];

            $this->vars['updateSteps'] = $updateSteps;

            return $this->makePartial('execute');
        }
        catch (Exception $ex) {
            $this->handleError($ex);
            return $this->makePartial('plugin_form');
        }
    }

    /**
     * onInstallTheme validates the theme code and execute the theme installation
     */
    public function onInstallTheme()
    {
        try {
            if (!$code = trim(post('code'))) {
                throw new ApplicationException(Lang::get('system::lang.install.missing_theme_name'));
            }

            if (!$composerCode = $this->findThemeComposerCode($code)) {
                throw new ApplicationException(Lang::get('system::lang.updates.theme_not_found'));
            }

            $updateSteps = [
                [
                    'type'  => 'composer',
                    'code'  => 'installTheme',
                    'label' => Lang::get('system::lang.updates.theme_downloading', ['name' => $composerCode]),
                    'name'  => $composerCode
                ],
                [
                    'type'  => 'final',
                    'code'  => 'completeUpdate',
                    'label' => Lang::get('system::lang.install.install_completing'),
                    'name'  => $code
                ]
            ];

            $this->vars['updateSteps'] = $updateSteps;

            return $this->makePartial('execute');
        }
        catch (Exception $ex) {
            $this->handleError($ex);
            return $this->makePartial('theme_form');
        }
    }

    /**
     * onRemoveTheme removes an existing theme
     */
    public function onRemoveTheme()
    {
        try {
            if (!$code = trim(post('code'))) {
                throw new ApplicationException(Lang::get('system::lang.install.missing_theme_name'));
            }

            if (!$composerCode = $this->findThemeComposerCode($code)) {
                throw new ApplicationException(Lang::get('system::lang.updates.theme_not_found'));
            }

            $updateSteps = [
                [
                    'type'  => 'composer',
                    'code'  => 'removeTheme',
                    'label' => Lang::get('system::lang.updates.theme_removing', ['name' => $composerCode]),
                    'name'  => $composerCode
                ],
                [
                    'type'  => 'final',
                    'code'  => 'completeUpdate',
                    'label' => Lang::get('system::lang.install.install_completing'),
                    'name'  => $code
                ]
            ];

            $this->vars['updateSteps'] = $updateSteps;

            return $this->makePartial('execute');
        }
        catch (Exception $ex) {
            $this->handleError($ex);
            return $this->makePartial('theme_form');
        }
    }

    /**
     * onExecuteStep runs a specific update step
     */
    public function onExecuteStep()
    {
        // Address timeout limits
        @set_time_limit(3600);

        $manager = UpdateManager::instance();
        $stepCode = post('code');

        switch ($stepCode) {
            case 'completeUpdate':
                $manager->update();
                Flash::success(Lang::get('system::lang.updates.update_success'));
                return Redirect::refresh();

            case 'completeInstall':
                $manager->update();
                Flash::success(Lang::get('system::lang.install.install_success'));
                return Redirect::refresh();
        }
    }

    /**
     * findPluginComposerCode locates a composer code for a plugin
     */
    protected function findPluginComposerCode(string $code): string
    {
        // Local
        $manager = ThemeManager::instance();
        if ($manager->findByIdentifier($code)) {
            return $manager->getComposerCode($code);
        }

        // Remote
        $details = UpdateManager::instance()->requestPluginDetails($code);
        return $details['composer_code'] ?? null;
    }

    /**
     * findThemeComposerCode locates a composer code for a plugin
     */
    protected function findThemeComposerCode(string $code): string
    {
        // Local
        $manager = PluginManager::instance();
        if ($plugin = $manager->findByIdentifier($code)) {
            return $manager->getComposerCode($plugin);
        }

        // Remote
        $details = UpdateManager::instance()->requestThemeDetails($code);
        return $details['composer_code'] ?? null;
    }

    /**
     * processOutput cleans up console output for display
     */
    protected function processOutput($output)
    {
        if ($output === null) {
            return $output;
        }

        // Split backspaced lines
        while (true) {
            $oldOutput = $output;
            $output = str_replace(chr(8).chr(8), chr(8), $output);
            if ($output === $oldOutput) {
                break;
            }
        }
        $output = array_last(explode(chr(8), $output));

        // Remove terminal colors
        $output = preg_replace('/\\e\[[0-9]+m/', '', $output);

        // Remove trailing newline
        $output = preg_replace('/\\n$/', '', $output);

        return $output;
    }

    /**
     * processImportantUpdates loops the update list and checks for actionable updates
     */
    protected function processImportantUpdates(array $result): array
    {
        $hasImportantUpdates = false;

        /*
         * Core
         */
        if (isset($result['core'])) {
            $coreImportant = false;

            foreach (array_get($result, 'core.updates', []) as $build => $description) {
                if (strpos($description, '!!!') === false) {
                    continue;
                }

                $detailsUrl = '//octobercms.com/support/articles/release-notes';
                $description = str_replace('!!!', '', $description);
                $result['core']['updates'][$build] = [$description, $detailsUrl];
                $coreImportant = $hasImportantUpdates = true;
            }

            $result['core']['isImportant'] = $coreImportant ? '1' : '0';
        }

        /*
         * Plugins
         */
        foreach (array_get($result, 'plugins', []) as $code => $plugin) {
            $isImportant = false;

            foreach (array_get($plugin, 'updates', []) as $version => $description) {
                if (strpos($description, '!!!') === false) {
                    continue;
                }

                $isImportant = $hasImportantUpdates = true;
                $detailsUrl = Backend::url('system/market/plugin/'.PluginVersion::makeSlug($code).'/upgrades').'?fetch=1';
                $description = str_replace('!!!', '', $description);
                $result['plugins'][$code]['updates'][$version] = [$description, $detailsUrl];
            }

            $result['plugins'][$code]['isImportant'] = $isImportant ? '1' : '0';
        }

        $result['hasImportantUpdates'] = $hasImportantUpdates;

        return $result;
    }

    /**
     * processUpdateLists reverses the update lists for the core and all plugins.
     */
    protected function processUpdateLists(array $result): array
    {
        if ($core = array_get($result, 'core')) {
            $result['core']['updates'] = array_reverse(array_get($core, 'updates', []), true);
        }

        foreach (array_get($result, 'plugins', []) as $code => $plugin) {
            $result['plugins'][$code]['updates'] = array_reverse(array_get($plugin, 'updates', []), true);
        }

        return $result;
    }
}
