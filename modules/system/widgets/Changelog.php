<?php namespace System\Widgets;

use Yaml;
use Lang;
use File;
use Exception;
use SystemException;
use ApplicationException;
use Backend\Classes\WidgetBase;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;

/**
 * Changelog widget
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class Changelog extends WidgetBase
{
    /**
     * @var string alias used for this widget
     */
    public $alias = 'changelog';

    /**
     * loadAssets adds widget specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     */
    protected function loadAssets()
    {
        $this->addCss('css/changelog.css', 'core');
        // $this->addJs('js/changelog.js', 'core');
    }

    /**
     * render renders the widget
     */
    public function render(): string
    {
        return '';
    }

    /**
     * onLoadChangelog displays system changelog information
     */
    public function onLoadChangelog()
    {
        try {
            $fetchedContent = UpdateManager::instance()->requestChangelog();
            $changelog = array_get($fetchedContent, 'history');

            if (!$changelog || !is_array($changelog)) {
                throw new ApplicationException(
                    // Empty response from the server.
                    Lang::get('system::lang.server.response_empty')
                );
            }

            $this->vars['changelog'] = $changelog;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('system_list');
    }

    /**
     * onLoadPluginChangelog displays plugin changelog information
     */
    public function onLoadPluginChangelog()
    {
        try {
            if (!$code = post('code')) {
                throw new SystemException('Missing code');
            }

            $manager = PluginManager::instance();
            $plugin = $manager->findByIdentifier($code);
            $path = $manager->getPluginPath($plugin);

            $changelog = $this->getPluginVersionFile($path, 'updates/version.yaml');
            if (!is_array($changelog)) {
                $changelog = null;
            }

            $this->vars['changelog'] = $changelog;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        return $this->makePartial('plugin_list');
    }

    /**
     * getPluginVersionFile returns the version file contents for a plugin
     */
    protected function getPluginVersionFile(string $path, string $filename): array
    {
        $contents = [];

        try {
            $updates = (array) Yaml::parseFile($path.'/'.$filename);

            foreach ($updates as $version => $details) {
                if (!is_array($details)) {
                    $details = (array) $details;
                }

                // Filter out update scripts
                $details = array_filter($details, function ($string) use ($path) {
                    return !preg_match('/^[a-z_\-0-9]*\.php$/i', $string) || !File::exists($path . '/updates/' . $string);
                });

                $contents[$version] = $details;
            }
        }
        catch (Exception $ex) {
        }

        uksort($contents, function ($a, $b) {
            return version_compare($b, $a);
        });

        return $contents;
    }
}
