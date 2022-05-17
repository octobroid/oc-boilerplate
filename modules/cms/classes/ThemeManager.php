<?php namespace Cms\Classes;

use Db;
use Lang;
use Yaml;
use File;
use System;
use Cms\Classes\Theme as CmsTheme;
use October\Rain\Process\ComposerPhp;
use ApplicationException;
use Exception;

/**
 * ThemeManager
 *
 * @method static ThemeManager instance()
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array themes is for storing installed themes cache
     */
    protected $themes;

    /**
     * @var array themes is for storing installed themes cache
     */
    protected $themeDirs;

    /**
     * bootAllBackend will boot language messages for the active theme as `theme.acme::lang.*`
     */
    public function bootAllBackend()
    {
        $theme = CmsTheme::getActiveTheme();
        if (!$theme) {
            return;
        }

        $langPath = $theme->getPath() . '/lang';
        if (File::isDirectory($langPath)) {
            Lang::addNamespace("theme.{$theme->getId()}", $langPath);
        }

        if ($parent = $theme->getParentTheme()) {
            $langPath = $parent->getPath() . '/lang';
            if (File::isDirectory($langPath)) {
                Lang::addNamespace("theme.{$parent->getId()}", $langPath);
            }
        }
    }

    /**
     * getInstalled returns a collection of themes installed
     *
     * ['RainLab.Vanilla' => '1.0.0', ...]
     */
    public function getInstalled(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $result = [];

        foreach (CmsTheme::all() as $theme) {
            $dirName = $theme->getDirName();

            // Check composer file
            if (!$octoberCode = $this->getProductCode($dirName)) {
                continue;
            }

            // Check composer matches theme.yaml
            $publishedCode = $theme->getConfigValue('authorCode') . '.' . $theme->getConfigValue('code');
            if (strtolower($publishedCode) !== $octoberCode) {
                continue;
            }

            // Check version.yaml
            $result[$publishedCode] = $this->getLatestVersion($dirName);
        }

        return $this->themes = $result;
    }

    /**
     * getInstalled returns a collection of themes installed and their directories
     *
     * ['rainlab.vanilla' => 'vanilla', ...]
     */
    protected function getInstalledDirectories(): array
    {
        if ($this->themeDirs !== null) {
            return $this->themeDirs;
        }

        $result = [];

        foreach (CmsTheme::all() as $theme) {
            $dirName = $theme->getDirName();

            // Check composer file
            if (!$octoberCode = $this->getProductCode($dirName)) {
                continue;
            }

            $result[$octoberCode] = $dirName;
        }

        return $this->themeDirs = $result;
    }

    /**
     * isInstalled checks if a theme has ever been installed
     */
    public function isInstalled(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->getInstalledDirectories());
    }

    /**
     * findDirectoryName from a code
     */
    public function findDirectoryName($code): ?string
    {
        return $this->getInstalledDirectories()[strtolower($code)] ?? null;
    }

    /**
     * findInstalledCode returns an installed theme's code from it's dirname
     */
    public function findInstalledCode($dirName): ?string
    {
        foreach ($this->getInstalled() as $code => $name) {
            if ($dirName === $name) {
                return $code;
            }
        }

        return null;
    }

    /**
     * findByIdentifier returns a theme object from a directory name
     */
    public function findByIdentifier(string $dirName): ?CmsTheme
    {
        if (!CmsTheme::exists($dirName)) {
            return null;
        }

        return CmsTheme::load($dirName);
    }

    /**
     * getThemePath returns the disk path for the theme
     */
    public function getThemePath(string $dirName): string
    {
        if (!$theme = $this->findByIdentifier($dirName)) {
            return '';
        }

        return $theme->getPath();
    }

    /**
     * getProductCode finds the product code for a theme, it relies
     * on the composer file as the source of truth
     * author.sometheme
     */
    public function getProductCode(string $dirName): string
    {
        $name = $this->getComposerCode($dirName);

        $name = System::composerToOctoberCode($name);

        return $name;
    }

    /**
     * getComposerCode finds the composer code for a theme
     * author/sometheme-theme
     */
    public function getComposerCode(string $dirName): string
    {
        $path = $this->getThemePath($dirName);
        $file = $path . '/composer.json';

        if (!$path || !File::exists($file)) {
            return '';
        }

        $info = json_decode(File::get($file), true);

        return $info['name'] ?? '';
    }

    /**
     * getLatestVersion finds the latest version for a theme
     */
    public function getLatestVersion(string $dirName): string
    {
        $versionHistory = $this->getVersionHistory($dirName);

        $latestVersion = array_key_last($versionHistory);

        if ($latestVersion === null) {
            return '0.0.0';
        }

        return (string) $latestVersion;
    }

    /**
     * getVersionHistory returns the version history for a theme
     */
    public function getVersionHistory(string $dirName): array
    {
        $path = $this->getThemePath($dirName);

        if (!File::exists($file = $path . '/version.yaml')) {
            return [];
        }

        try {
            $updates = (array) Yaml::parseFile($file);
        }
        catch (Exception $ex) {
            return [];
        }

        uksort($updates, function ($a, $b) {
            return version_compare($b, $a);
        });

        return $updates;
    }

    /**
     * duplicateTheme duplicates a theme
     */
    public function duplicateTheme(string $dirName, string $newDirName = null): bool
    {
        if (!$dirName) {
            return false;
        }

        if (!$newDirName) {
            $newDirName = $dirName . '-copy';
        }

        $theme = CmsTheme::load($dirName);

        $sourcePath = $theme->getPath();
        $destinationPath = themes_path().'/'.$newDirName;

        if (File::isDirectory($destinationPath)) {
            return false;
        }

        // Duplicate theme
        File::copyDirectory($sourcePath, $destinationPath);

        // Unlock theme (if required)
        $this->performUnlockOnTheme($newDirName);

        $newTheme = CmsTheme::load($newDirName);
        $newName = $newTheme->getConfigValue('name') . ' - Copy';
        $newTheme->writeConfig(['name' => $newName]);

        return true;
    }

    /**
     * createChildTheme will create a child theme
     */
    public function createChildTheme(string $dirName, string $newDirName = null): bool
    {
        if (!$newDirName) {
            $newDirName = $dirName . '-child';
        }

        $themePath = themes_path($dirName);
        $childPath = themes_path($newDirName);
        $childYaml = $childPath . '/theme.yaml';

        // Child already exists
        if (file_exists($childPath)) {
            return false;
        }

        // Create child
        File::makeDirectory($childPath);
        File::copy($themePath . '/theme.yaml', $childYaml);

        $yaml = Yaml::parseFile($childYaml);
        $yaml['parent'] = $dirName;
        File::put($childYaml, Yaml::render($yaml));

        return true;
    }

    /**
     * importDatabaseTemplates
     */
    public function importDatabaseTemplates(string $dirName, string $srcDirName = null)
    {
        if (!$srcDirName) {
            $srcDirName = $dirName;
        }

        $theme = CmsTheme::load($dirName);
        $themePath = $theme->getPath();
        if (!$themePath) {
            return;
        }

        $templates = Db::table('cms_theme_templates')->where('source', $srcDirName)->get();

        foreach ($templates as $template) {
            $filePath = $themePath . '/' . $template->path;
            if ($template->deleted_at) {
                File::delete($filePath);
            }
            else {
                File::put($filePath, $template->content);
            }
        }
    }

    /**
     * purgeDatabaseTemplates
     */
    public function purgeDatabaseTemplates(string $dirName)
    {
        Db::table('cms_theme_templates')->where('source', $dirName)->delete();
    }

    /**
     * deleteTheme completely delete a theme from the system
     */
    public function deleteTheme(string $theme)
    {
        if (!$theme) {
            return false;
        }

        $theme = CmsTheme::load($theme);

        if ($theme->isActiveTheme()) {
            throw new ApplicationException(trans('cms::lang.theme.delete_active_theme_failed'));
        }

        $theme->removeCustomData();

        /*
         * Delete from file system
         */
        $themePath = $theme->getPath();
        if (File::isDirectory($themePath)) {
            File::deleteDirectory($themePath);
        }
    }

    /**
     * findMissingDependencies scans the system plugins to locate any dependencies that
     * are not currently installed. Returns an array of plugin codes that are needed.
     *
     *     ThemeManager::instance()->findMissingDependencies();
     *
     * @return array
     */
    public function findMissingDependencies(): array
    {
        $manager = \System\Classes\PluginManager::instance();

        $missing = [];

        foreach (CmsTheme::all() as $theme) {
            $required = $theme->getConfigValue('require', false);
            if (!$required || !is_array($required)) {
                continue;
            }

            foreach ($required as $require) {
                if ($manager->hasPlugin($require)) {
                    continue;
                }

                if (!in_array($require, $missing)) {
                    $missing[] = $require;
                }
            }
        }

        return $missing;
    }

    /**
     * findLockableThemes returns themes that are installed via composer
     */
    public function findLockableThemes(): array
    {
        $packages = (new ComposerPhp)->listAllPackages();

        $themes = [];

        $crossCheckPackage = function(string $composerCode, array $packages): bool {
            foreach ($packages as $package) {
                $name = $package['name'] ?? null;
                if ($name === $composerCode) {
                    return true;
                }
            }

            return false;
        };

        foreach (CmsTheme::all() as $theme) {
            $dirName = $theme->getDirName();
            $composerCode = $this->getComposerCode($dirName);
            if (!$composerCode || !$crossCheckPackage($composerCode, $packages)) {
                continue;
            }

            $themes[$composerCode] = $dirName;
        }

        return $themes;
    }

    /**
     * performLockOnTheme will add a lock file on a theme
     * Returns true if the process was successful
     */
    public function performLockOnTheme(string $dirName): bool
    {
        $themePath = themes_path($dirName);

        $lockFile = $themePath . '/.themelock';
        $noLockFile = $themePath . '/.themenolock';
        if (file_exists($lockFile) || file_exists($noLockFile)) {
            return false;
        }

        // Lock theme
        File::put($lockFile, 1);

        return true;
    }

    /**
     * performUnlockOnTheme will remove the lock file on a theme
     * Returns true if the process was successful
     */
    public function performUnlockOnTheme(string $dirName): bool
    {
        $themePath = themes_path($dirName);

        $lockFile = $themePath . '/.themelock';
        if (!file_exists($lockFile)) {
            return false;
        }

        // Unlock theme
        File::delete($lockFile);

        return true;
    }
}
