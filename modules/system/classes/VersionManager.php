<?php namespace System\Classes;

use Db;
use File;
use Yaml;
use Carbon\Carbon;
use October\Rain\Database\Updater;
use Exception;

/**
 * VersionManager manages the versions and database updates for plugins
 *
 * @method static VersionManager instance()
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class VersionManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * Value when no updates are found.
     */
    const NO_VERSION_VALUE = 0;

    /**
     * Morph types for history table.
     */
    const HISTORY_TYPE_COMMENT = 'comment';
    const HISTORY_TYPE_SCRIPT = 'script';

    /**
     * @var \Illuminate\Console\OutputStyle
     */
    protected $notesOutput;

    /**
     * @var array fileVersions cache of plugin versions as files.
     */
    protected $fileVersions;

    /**
     * @var array databaseVersions cache of database versions
     */
    protected $databaseVersions;

    /**
     * @var array databaseHistory cache of database history
     */
    protected $databaseHistory;

    /**
     * @var October\Rain\Database\Updater
     */
    protected $updater;

    /**
     * @var System\Classes\PluginManager
     */
    protected $pluginManager;

    protected function init()
    {
        $this->updater = new Updater;
        $this->pluginManager = PluginManager::instance();
    }

    /**
     * updatePlugin updates a single plugin by its code or object with it's latest changes
     * If the $toVersion parameter is specified, the process stops after
     * the specified version is applied.
     */
    public function updatePlugin($plugin, $toVersion = null)
    {
        $code = is_string($plugin) ? $plugin : $this->pluginManager->getIdentifier($plugin);

        if (!$this->hasVersionFile($code)) {
            return false;
        }

        $currentVersion = $this->getLatestFileVersion($code);
        $databaseVersion = $this->getDatabaseVersion($code);

        // No updates needed
        if ((string) $currentVersion === (string) $databaseVersion) {
            $this->note('- <info>Nothing to update.</info>');
            return;
        }

        $newUpdates = $this->getNewFileVersions($code, $databaseVersion);

        foreach ($newUpdates as $version => $details) {
            $this->applyPluginUpdate($code, $version, $details);

            if ($toVersion === $version) {
                return true;
            }
        }

        return true;
    }

    /**
     * listNewVersions returns a list of unapplied plugin versions
     */
    public function listNewVersions($plugin)
    {
        $code = is_string($plugin) ? $plugin : $this->pluginManager->getIdentifier($plugin);

        if (!$this->hasVersionFile($code)) {
            return [];
        }

        $databaseVersion = $this->getDatabaseVersion($code);

        return $this->getNewFileVersions($code, $databaseVersion);
    }

    /**
     * hasVersion will return true if a plugin has been registered at a supplied version
     */
    public function hasVersion($plugin, string $version): bool
    {
        $code = is_string($plugin) ? $plugin : $this->pluginManager->getIdentifier($plugin);

        foreach ($this->getDatabaseHistory($code) as $history) {
            if ($history->version === $version) {
                return true;
            }
        }

        return false;
    }

    /**
     * applyPluginUpdate applies a single version update to a plugin.
     */
    protected function applyPluginUpdate($code, $version, $details)
    {
        $version = $this->normalizeVersion($version);

        [$comments, $scripts] = $this->extractScriptsAndComments($details);

        /*
         * Apply scripts, if any
         */
        foreach ($scripts as $script) {
            if ($this->hasDatabaseHistory($code, $version, $script)) {
                continue;
            }

            $this->applyDatabaseScript($code, $version, $script);
        }

        /*
         * Register the comment and update the version
         */
        if (!$this->hasDatabaseHistory($code, $version)) {
            foreach ($comments as $comment) {
                $this->applyDatabaseComment($code, $version, $comment);

                $this->note(sprintf('- <info>v%s</info> %s', $version, $comment));
            }
        }

        $this->setDatabaseVersion($code, $version);
    }

    /**
     * removePlugin removes and packs down a plugin from the system. Files are left intact
     * If the $toVersion parameter is specified, the process stops after the specified
     * version is rolled back.
     */
    public function removePlugin($plugin, $toVersion = null): bool
    {
        // @todo this API is used as part of the builder plugin and could be replaced
        // with the removePluginToVersion method in a later deprecation review along
        // with creating a updatePluginToVersion API method -sg
        if ($toVersion) {
            return $this->removePluginToVersion($plugin, $toVersion, true);
        }

        $code = is_string($plugin) ? $plugin : $this->pluginManager->getIdentifier($plugin);

        if (!$this->hasVersionFile($code)) {
            return false;
        }

        $pluginHistory = $this->getDatabaseHistory($code);
        $pluginHistory = array_reverse($pluginHistory);

        foreach ($pluginHistory as $history) {
            if ($history->type === self::HISTORY_TYPE_COMMENT) {
                $this->removeDatabaseComment($code, $history->version);
            }
            elseif ($history->type === self::HISTORY_TYPE_SCRIPT) {
                $this->removeDatabaseScript($code, $history->version, $history->detail);
            }
        }

        $this->setDatabaseVersion($code);

        $this->resetCacheForCode($code);

        return true;
    }

    /**
     * removePluginToVersion will remove the plugin version up to a specified one,
     * you may also specify to include that version itself as part of the rollback.
     */
    public function removePluginToVersion($plugin, string $toVersion, bool $includeVersion = false): bool
    {
        $code = is_string($plugin) ? $plugin : $this->pluginManager->getIdentifier($plugin);

        if (!$this->hasVersionFile($code)) {
            return false;
        }

        $pluginHistory = $this->getDatabaseHistory($code);
        $pluginHistory = array_reverse($pluginHistory);

        $stopOnNextVersion = false;
        $latestVersion = null;

        foreach ($pluginHistory as $history) {
            // Stop if the $toVersion filter is met and we don't want to include
            // that version itself in the rollback.
            if (!$includeVersion && $history->version === $toVersion) {
                $latestVersion = $history->version;
                break;
            }

            // Stop if the $toVersion value was found and this is a new version.
            // The history could contain multiple items for a single version
            // (comments and scripts).
            if ($stopOnNextVersion && $history->version !== $toVersion) {
                $latestVersion = $history->version;
                break;
            }

            if ($history->type === self::HISTORY_TYPE_COMMENT) {
                $this->removeDatabaseComment($code, $history->version);
            }
            elseif ($history->type === self::HISTORY_TYPE_SCRIPT) {
                $this->removeDatabaseScript($code, $history->version, $history->detail);
            }

            if ($toVersion === $history->version) {
                $stopOnNextVersion = true;
            }
        }

        $this->setDatabaseVersion($code, $latestVersion);

        $this->resetCacheForCode($code);

        return true;
    }

    /**
     * resetCacheForCode will reset the cache for a specified plugin code
     */
    protected function resetCacheForCode(string $code): void
    {
        if (isset($this->fileVersions[$code])) {
            unset($this->fileVersions[$code]);
        }
        if (isset($this->databaseVersions[$code])) {
            unset($this->databaseVersions[$code]);
        }
        if (isset($this->databaseHistory[$code])) {
            unset($this->databaseHistory[$code]);
        }
    }

    /**
     * purgePlugin deletes all records from the version and history tables for a plugin
     * @param  string $pluginCode Plugin code
     * @return void
     */
    public function purgePlugin($pluginCode)
    {
        $versions = Db::table('system_plugin_versions')->where('code', $pluginCode);
        if ($countVersions = $versions->count()) {
            $versions->delete();
        }

        $history = Db::table('system_plugin_history')->where('code', $pluginCode);
        if ($countHistory = $history->count()) {
            $history->delete();
        }

        return ($countHistory + $countVersions) > 0;
    }

    //
    // File representation
    //

    /**
     * getLatestFileVersion returns the latest version of a plugin from its version file
     */
    protected function getLatestFileVersion($code)
    {
        $versionInfo = $this->getFileVersions($code);
        if (!$versionInfo) {
            return self::NO_VERSION_VALUE;
        }

        return trim(key(array_slice($versionInfo, -1, 1)));
    }

    /**
     * getNewFileVersions returns any new versions from a supplied version, ie. unapplied versions
     */
    protected function getNewFileVersions($code, $version = null)
    {
        if ($version === null) {
            $version = self::NO_VERSION_VALUE;
        }

        $versions = $this->getFileVersions($code);

        $position = array_search($version, array_keys($versions));

        return array_slice($versions, ++$position);
    }

    /**
     * getFileVersions returns all versions of a plugin from its version file
     */
    protected function getFileVersions($code)
    {
        if ($this->fileVersions !== null && array_key_exists($code, $this->fileVersions)) {
            return $this->fileVersions[$code];
        }

        $versionFile = $this->getVersionFile($code);
        $versionInfo = Yaml::parseFile($versionFile);

        if (!is_array($versionInfo)) {
            $versionInfo = [];
        }

        // Sort result
        uksort($versionInfo, function ($a, $b) {
            return version_compare($a, $b);
        });

        // Normalize result
        $result = [];

        foreach ($versionInfo as $version => $info) {
            $result[$this->normalizeVersion($version)] = $info;
        }

        return $this->fileVersions[$code] = $result;
    }

    /**
     * getVersionFile returns the absolute path to a version file for a plugin
     */
    protected function getVersionFile($code): string
    {
        $versionFile = $this->pluginManager->getPluginPath($code) . '/updates/version.yaml';

        return $versionFile;
    }

    /**
     * hasVersionFile checks if a plugin has a version file
     */
    protected function hasVersionFile($code): bool
    {
        $versionFile = $this->getVersionFile($code);

        return File::isFile($versionFile);
    }

    //
    // Database representation
    //

    /**
     * getDatabaseVersion returns the latest version of a plugin from the database
     */
    protected function getDatabaseVersion($code)
    {
        if ($this->databaseVersions === null) {
            $this->databaseVersions = Db::table('system_plugin_versions')->pluck('version', 'code')->all();
        }

        if (!isset($this->databaseVersions[$code])) {
            $this->databaseVersions[$code] = Db::table('system_plugin_versions')
                ->where('code', $code)
                ->value('version')
            ;
        }

        return $this->databaseVersions[$code] ?? self::NO_VERSION_VALUE;
    }

    /**
     * setDatabaseVersion updates a plugin version in the database, if the version
     * is not specified then the version is reset to empty.
     */
    protected function setDatabaseVersion($code, $version = null)
    {
        $currentVersion = $this->getDatabaseVersion($code);

        if ($version && !$currentVersion) {
            Db::table('system_plugin_versions')->insert([
                'code' => $code,
                'version' => $version,
                'created_at' => new Carbon
            ]);
        }
        elseif ($version && $currentVersion) {
            Db::table('system_plugin_versions')->where('code', $code)->update([
                'version' => $version,
                'created_at' => new Carbon
            ]);
        }
        elseif ($currentVersion) {
            Db::table('system_plugin_versions')->where('code', $code)->delete();
        }

        $this->databaseVersions[$code] = $version;
    }

    /**
     * applyDatabaseComment registers a database update comment in the history table
     */
    protected function applyDatabaseComment($code, $version, $comment)
    {
        Db::table('system_plugin_history')->insert([
            'code' => $code,
            'type' => self::HISTORY_TYPE_COMMENT,
            'version' => $version,
            'detail' => null,
            'created_at' => new Carbon
        ]);
    }

    /**
     * removeDatabaseComment removes a database update comment in the history table
     */
    protected function removeDatabaseComment($code, $version)
    {
        Db::table('system_plugin_history')
            ->where('code', $code)
            ->where('type', self::HISTORY_TYPE_COMMENT)
            ->where('version', $version)
            ->delete();
    }

    /**
     * applyDatabaseScript registers a database update script in the history table
     */
    protected function applyDatabaseScript($code, $version, $script)
    {
        // Execute the database PHP script
        $updateFile = $this->pluginManager->getPluginPath($code) . '/updates/' . $script;

        if (!File::isFile($updateFile)) {
            $this->note('- <error>v' . $version . ':  Migration file "' . $script . '" not found</error>');
            return;
        }

        try {
            $this->updater->setUp($updateFile);

            Db::table('system_plugin_history')->insert([
                'code' => $code,
                'type' => self::HISTORY_TYPE_SCRIPT,
                'version' => $version,
                'detail' => $script,
                'created_at' => new Carbon
            ]);
        }
        catch (Exception $ex) {
            try {
                $this->note('- <error>v' . $version . ':  Migration "' . $script . '" failed, attempting to rollback</error>');
                $this->updater->packDown($updateFile);
            }
            catch (Exception $ex) {
                $this->note('<error>Rollback failed! Reason: "' . $ex->getMessage() . '"</error>');
            }

            throw $ex;
        }
    }

    /**
     * removeDatabaseScript removes a database update script in the history table
     */
    protected function removeDatabaseScript($code, $version, $script)
    {
        // Execute the database PHP script
        $updateFile = $this->pluginManager->getPluginPath($code) . '/updates/' . $script;

        $this->updater->packDown($updateFile);

        Db::table('system_plugin_history')
            ->where('code', $code)
            ->where('type', self::HISTORY_TYPE_SCRIPT)
            ->where('version', $version)
            ->where('detail', $script)
            ->delete()
        ;
    }

    /**
     * getDatabaseHistory returns all the update history for a plugin
     */
    protected function getDatabaseHistory($code)
    {
        if ($this->databaseHistory !== null && array_key_exists($code, $this->databaseHistory)) {
            return $this->databaseHistory[$code];
        }

        $historyInfo = Db::table('system_plugin_history')
            ->where('code', $code)
            ->orderBy('id')
            ->get()
            ->all()
        ;

        return $this->databaseHistory[$code] = $historyInfo;
    }

    /**
     * hasDatabaseHistory checks if a plugin has an applied update version
     */
    protected function hasDatabaseHistory($code, $version, $script = null)
    {
        $historyInfo = $this->getDatabaseHistory($code);
        if (!$historyInfo) {
            return false;
        }

        foreach ($historyInfo as $history) {
            if ((string) $history->version !== (string) $version) {
                continue;
            }

            if ($history->type === self::HISTORY_TYPE_COMMENT && !$script) {
                return true;
            }

            if ($history->type === self::HISTORY_TYPE_SCRIPT && $history->detail === $script) {
                return true;
            }
        }

        return false;
    }

    /**
     * normalizeVersion checks some versions start with v and others not
     */
    protected function normalizeVersion($version): string
    {
        return rtrim(ltrim((string) $version, 'v'), '.');
    }

    /**
     * extractScriptsAndComments extracts script and comments from version details
     * @return array
     */
    protected function extractScriptsAndComments($details)
    {
        if (is_array($details)) {
            $fileNamePattern = "/^[a-z0-9\_\-\.\/\\\]+\.php$/i";

            $comments = array_values(array_filter($details, function ($detail) use ($fileNamePattern) {
                return !preg_match($fileNamePattern, $detail);
            }));

            $scripts = array_values(array_filter($details, function ($detail) use ($fileNamePattern) {
                return preg_match($fileNamePattern, $detail);
            }));
        }
        else {
            $comments = (array) $details;
            $scripts = [];
        }

        return [$comments, $scripts];
    }

    //
    // Notes
    //

    /**
     * note raises a note event for the migrator
     * @param  string  $message
     * @return void
     */
    protected function note($message)
    {
        if ($this->notesOutput !== null) {
            $this->notesOutput->writeln($message);
        }

        return $this;
    }

    /**
     * setNotesOutput sets an output stream for writing notes
     * @param  Illuminate\Console\Command $output
     * @return self
     */
    public function setNotesOutput($output)
    {
        $this->notesOutput = $output;

        return $this;
    }
}
