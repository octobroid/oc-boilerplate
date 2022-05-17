<?php namespace System\Classes;

use App;
use Url;
use File;
use Lang;
use Http;
use Cache;
use Event;
use Schema;
use Config;
use Request;
use System as SystemHelper;
use Carbon\Carbon;
use Cms\Classes\ThemeManager;
use System\Models\Parameter;
use System\Models\PluginVersion;
use October\Rain\Process\ComposerPhp;
use ApplicationException;
use SystemException;
use Exception;

/**
 * UpdateManager handles the CMS install and update process.
 *
 * @method static UpdateManager instance()
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class UpdateManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var \Illuminate\Console\OutputStyle
     */
    protected $notesOutput;

    /**
     * @var string Application base path.
     */
    protected $baseDirectory;

    /**
     * @var string A temporary working directory.
     */
    protected $tempDirectory;

    /**
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var string Secure API Key
     */
    protected $key;

    /**
     * @var string Secure API Secret
     */
    protected $secret;

    /**
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @var \Illuminate\Database\Migrations\DatabaseMigrationRepository
     */
    protected $repository;

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->pluginManager = PluginManager::instance();
        $this->themeManager = class_exists(ThemeManager::class) ? ThemeManager::instance() : null;
        $this->versionManager = VersionManager::instance();
        $this->tempDirectory = temp_path();
        $this->baseDirectory = base_path();
        $this->bindContainerObjects();

        /*
         * Ensure temp directory exists
         */
        if (!File::isDirectory($this->tempDirectory)) {
            File::makeDirectory($this->tempDirectory, 0777, true);
        }
    }

    /**
     * These objects are "soft singletons" and may be lost when
     * the IoC container reboots. This provides a way to rebuild
     * for the purposes of unit testing.
     */
    public function bindContainerObjects()
    {
        $this->migrator = App::make('migrator');
        $this->repository = App::make('migration.repository');
    }

    /**
     * update creates the migration table and updates.
     */
    public function update(): UpdateManager
    {
        $firstUp = !Schema::hasTable($this->getMigrationTableName());
        if ($firstUp) {
            $this->repository->createRepository();
            $this->note('Migration table created');
        }

        // Update modules
        foreach (SystemHelper::listModules() as $module) {
            $this->migrateModule($module);
        }

        // Update plugins
        $plugins = $this->pluginManager->getPlugins();
        foreach ($plugins as $code => $plugin) {
            $this->updatePlugin($code);
        }

        // Reset update count
        Parameter::set('system::update.count', 0);

        /**
         * @event system.updater.migrate
         * Provides an opportunity to add migration logic to updater
         *
         * Example usage:
         *
         *     Event::listen('system.updater.migrate', function ((\System\Classes\UpdateManager) $updateManager) {
         *         $updateManager->note('Done');
         *     });
         *
         */
        Event::fire('system.updater.migrate', [$this]);

        // Seed modules
        if ($firstUp) {
            foreach (SystemHelper::listModules() as $module) {
                $this->seedModule($module);
            }
        }

        return $this;
    }

    /**
     * check for new updates and returns the amount of unapplied updates
     */
    public function check(bool $force = false): int
    {
        $versions = $this->checkVersions($force);

        return (int) array_get($versions, 'count', 0);
    }

    /**
     * checkVersions checks for available versions
     */
    public function checkVersions(bool $force = false): array
    {
        /*
         * No key is set, return a skeleton schema
         */
        if (!Parameter::get('system::project.key')) {
            return [
                'count' => 0,
                'core' => null,
                'plugins' => []
            ];
        }

        /*
         * Retry period not passed, skipping.
         */
        if (!$force
            && ($retryTimestamp = Parameter::get('system::update.retry'))
            && Carbon::createFromTimeStamp($retryTimestamp)->isFuture()
        ) {
            return (array) Parameter::get('system::update.versions');
        }

        /*
         * Ask again
         */
        try {
            $result = $this->requestUpdateList();
            $versions['count'] = array_get($result, 'update', 0);
            $versions['core'] = array_get($result, 'core.version', null);
            $versions['plugins'] = [];
            foreach (array_get($result, 'plugins') as $code => $plugin) {
                $versions['plugins'][$code] = array_get($plugin, 'version');
            }
        }
        catch (Exception $ex) {
            $versions = [
                'count' => 0,
                'core' => null,
                'plugins' => []
            ];
        }

        /*
         * Remember update count, set retry date
         */
        Parameter::set('system::update.versions', $versions);
        Parameter::set('system::update.retry', Carbon::now()->addHours(24)->timestamp);

        return $versions;
    }

    /**
     * Requests an update list used for checking for new updates.
     * @param  boolean $force Request application and plugins hash list regardless of version.
     * @return array
     */
    public function requestUpdateList()
    {
        $installed = PluginVersion::all();
        $versions = $installed->pluck('version', 'code')->all();
        $names = $installed->pluck('name', 'code')->all();
        $icons = $installed->pluck('icon', 'code')->all();
        $build = Parameter::get('system::core.build');
        $themes = [];

        if ($this->themeManager) {
            $themes = array_keys($this->themeManager->getInstalled());
        }

        $params = [
            'plugins' => base64_encode(json_encode($versions)),
            'themes' => base64_encode(json_encode($themes)),
            'version' => SystemHelper::VERSION,
            'build' => $build
        ];

        $result = [];
        $serverData = $this->requestServerData('project/check', $params);
        $updateCount = (int) array_get($serverData, 'update', 0);

        /*
         * Inject known core build
         */
        if ($core = array_get($serverData, 'core')) {
            $core['old_build'] = Parameter::get('system::core.build');
            $result['core'] = $core;
        }

        /*
         * Inject the application's known plugin name and version
         */
        $plugins = [];
        foreach (array_get($serverData, 'plugins', []) as $code => $info) {
            $info['name'] = $names[$code] ?? $code;
            $info['old_version'] = $versions[$code] ?? false;
            $info['icon'] = $icons[$code] ?? false;
            $plugins[$code] = $info;
            $updateCount++;
        }
        $result['plugins'] = $plugins;

        /*
         * Strip out themes that have been installed before
         */
        // if ($this->themeManager) {
        //     $themes = [];
        //     foreach (array_get($serverData, 'themes', []) as $code => $info) {
        //         if (!$this->themeManager->isInstalled($code)) {
        //             $themes[$code] = $info;
        //         }
        //     }
        //     $result['themes'] = $themes;
        //     $updateCount++;
        // }

        /*
         * Recalculate the update counter
         */
        $result['hasUpdates'] = $updateCount > 0;
        $result['update'] = $updateCount;
        Parameter::set('system::update.count', $updateCount);

        return $result;
    }

    /**
     * getProjectKey locates the project key from the file system and seeds the parameter
     */
    public function getProjectKey()
    {
        if (
            File::exists($seedFile = storage_path('cms/project.json')) &&
            ($contents = json_decode(File::get($seedFile), true)) &&
            isset($contents['project'])
        ) {
            Parameter::set('system::project.key', $contents['project']);
            File::delete($seedFile);
        }

        return Parameter::get('system::project.key');
    }

    /**
     * getProjectDetails returns the active project details
     */
    public function getProjectDetails(): ?object
    {
        if (!$projectKey = $this->getProjectKey()) {
            return null;
        }

        $projectId = Parameter::get('system::project.id');

        if (!$projectId) {
            $details = $this->requestProjectDetails($projectKey);
            if (!isset($details['id']))  {
                return null;
            }

            Parameter::set([
                'system::project.id' => $details['id'],
                'system::project.name' => $details['name'],
                'system::project.owner' => $details['owner'],
                'system::project.is_active' => $details['is_active']
            ]);
        }

        return (object) [
            'id' => $projectId,
            'key' => $projectKey,
            'name' => Parameter::get('system::project.name'),
            'owner' => Parameter::get('system::project.owner'),
            'is_active' => Parameter::get('system::project.is_active'),
        ];
    }

    /**
     * syncProjectPackages compares installed packages to project packages
     */
    public function syncProjectPackages(): array
    {
        $crossCheckPackage = function(string $composerCode, array $packages): bool {
            foreach ($packages as $package) {
                $name = $package['name'] ?? null;
                if ($name === $composerCode) {
                    return true;
                }
            }

            return false;
        };

        $plugins = $themes = [];
        $packages = (new ComposerPhp)->listAllPackages();
        $project = $this->requestProjectDetails();

        foreach (($project['plugins'] ?? []) as $plugin) {
            $cCode = $plugin['composer_code'] ?? null;

            if ($cCode === null || $crossCheckPackage($cCode, $packages)) {
                continue;
            }

            $plugins[] = $cCode;
        }

        foreach (($project['themes'] ?? []) as $theme) {
            $cCode = $theme['composer_code'] ?? null;

            if ($cCode === null || $crossCheckPackage($cCode, $packages)) {
                continue;
            }

            $themes[] = $cCode;
        }

        return array_merge($plugins, $themes);
    }

    /**
     * requestProjectDetails requests details about a project based on its identifier
     */
    public function requestProjectDetails(string $projectKey = null): array
    {
        if ($projectKey === null) {
            $projectKey = $this->getProjectKey();
        }

        return $this->requestServerData('project/detail', ['id' => $projectKey]);
    }

    /**
     * getComposerUrl returns the endpoint for composer
     */
    public function getComposerUrl(bool $withProtocol = true): string
    {
        $gateway = env('APP_COMPOSER_GATEWAY', Config::get('system.composer_gateway', 'gateway.octobercms.com'));

        return $withProtocol ? 'https://'.$gateway : $gateway;
    }

    /**
     * uninstall rolls back all modules and plugins.
     */
    public function uninstall(): UpdateManager
    {
        /*
         * Rollback plugins
         */
        $plugins = array_reverse($this->pluginManager->getPlugins());
        foreach ($plugins as $name => $plugin) {
            $this->rollbackPlugin($name);
        }

        /*
         * Register module migration files
         */
        $paths = [];
        foreach (SystemHelper::listModules() as $module) {
            $paths[] = base_path() . '/modules/'.strtolower($module).'/database/migrations';
        }

        /*
         * Rollback modules
         */
        if (isset($this->notesOutput)) {
            $this->migrator->setOutput($this->notesOutput);
        }

        while (true) {
            $rolledBack = $this->migrator->rollback($paths, ['pretend' => false]);

            if (count($rolledBack) === 0) {
                break;
            }
        }

        Schema::dropIfExists($this->getMigrationTableName());

        return $this;
    }

    //
    // Modules
    //

    /**
     * migrateModule runs migrations on a single module
     */
    public function migrateModule(string $module): UpdateManager
    {
        if (isset($this->notesOutput)) {
            $this->migrator->setOutput($this->notesOutput);
        }

        $this->note($module);

        $this->migrator->run(base_path() . '/modules/'.strtolower($module).'/database/migrations');

        return $this;
    }

    /**
     * seedModule runs seeds on a module
     */
    public function seedModule(string $module): UpdateManager
    {
        $className = '\\'.$module.'\Database\Seeds\DatabaseSeeder';
        if (!class_exists($className)) {
            return $this;
        }

        $seeder = App::make($className);
        $seeder->run();

        $this->note(sprintf('<info>Seeded %s</info> ', $module));

        return $this;
    }

    /**
     * getCurrentVersion returns the current version, with or without build
     */
    public function getCurrentVersion(): string
    {
        $version = SystemHelper::VERSION;

        $build = $this->getCurrentBuildNumber();
        if ($build !== null) {
            $version .= '.' . $build;
        }

        return $version;
    }

    /**
     * getCurrentBuildNumber return the current build number
     */
    public function getCurrentBuildNumber(): ?string
    {
        return Parameter::get('system::core.build');
    }

    /**
     * setBuild sets the build number and hash
     */
    public function setBuild(string $build): void
    {
        Parameter::set('system::core.build', $build);
        Parameter::set('system::update.retry', null);
    }

    /**
     * setBuildNumberManually asks the gateway for the lastest build number and stores it.
     */
    public function setBuildNumberManually()
    {
        $version = null;

        try {
            // List packages to find version string from october/rain
            $packages = (new ComposerPhp)->listAllPackages();
            foreach ($packages as $package) {
                $packageName = $package['name'] ?? null;
                if (mb_strtolower($packageName) === 'october/system') {
                    $version = $package['version'] ?? null;
                }
            }

            if ($version === null) {
                throw new SystemException('Package october/system not found in composer');
            }
        }
        catch (Exception $ex) {
            $version = '0.0.0';
        }

        $build = $this->getBuildFromVersion($version);

        $this->setBuild((int) $build);

        return $build;
    }

    //
    // Plugins
    //

    /**
     * requestPluginDetails looks up a plugin from the update server
     */
    public function requestPluginDetails(string $name): array
    {
        return $this->requestServerData('package/detail', ['name' => $name, 'type' => 'plugin']);
    }

    /**
     * requestPluginContent looks up content for a plugin from the update server
     */
    public function requestPluginContent(string $name): array
    {
        return $this->requestServerData('package/content', ['name' => $name, 'type' => 'plugin']);
    }

    /**
     * updatePlugin runs update on a single plugin
     */
    public function updatePlugin(string $name): UpdateManager
    {
        /*
         * Update the plugin database and version
         */
        $plugin = $this->pluginManager->findByIdentifier($name);

        if (!$plugin) {
            $this->note('<error>Unable to find</error> ' . $name);
            return $this;
        }

        $this->note($name);

        $this->versionManager->setNotesOutput($this->notesOutput);

        $this->versionManager->updatePlugin($plugin);

        return $this;
    }

    /**
     * rollbackPlugin removes an existing plugin database and version record
     */
    public function rollbackPlugin(string $name): UpdateManager
    {
        $plugin = $this->pluginManager->findByIdentifier($name);

        if (!$plugin && $this->versionManager->purgePlugin($name)) {
            $this->note('<info>Purged from database</info> ' . $name);
            return $this;
        }

        if ($this->versionManager->removePlugin($plugin)) {
            $this->note('<info>Rolled back</info> ' . $name);
            return $this;
        }

        $this->note('<error>Unable to find</error> ' . $name);
        return $this;
    }

    /**
     * rollbackPlugin removes an existing plugin database and version record
     */
    public function rollbackPluginToVersion(string $name, string $toVersion): UpdateManager
    {
        $toVersion = ltrim($toVersion, 'v');

        $plugin = $this->pluginManager->findByIdentifier($name);

        if (!$plugin && $this->versionManager->purgePlugin($name)) {
            $this->note('<info>Purged from database</info> ' . $name);
            return $this;
        }

        if (!$this->versionManager->hasVersion($plugin, $toVersion)) {
            throw new ApplicationException(Lang::get('system::lang.updates.plugin_version_not_found'));
        }

        if ($this->versionManager->removePluginToVersion($plugin, $toVersion)) {
            $this->note("<info>Rolled back</info> ${name} <info>to version</info> {$toVersion}");
            return $this;
        }

        $this->note('<error>Unable to find</error> ' . $name);
        return $this;
    }

    //
    // Themes
    //

    /**
     * requestThemeDetails looks up a theme from the update server
     */
    public function requestThemeDetails(string $name): array
    {
        return $this->requestServerData('package/detail', ['name' => $name, 'type' => 'theme']);
    }

    /**
     * requestThemeContent looks up content for a theme from the update server
     */
    public function requestThemeContent(string $name): array
    {
        return $this->requestServerData('package/content', ['name' => $name, 'type' => 'theme']);
    }

    //
    // Products
    //

    /**
     * requestBrowseProject will list project details and cache it
     */
    public function requestBrowseProject()
    {
        $cacheKey = 'system-market-project';

        if (Cache::has($cacheKey)) {
            return @json_decode(@base64_decode(Cache::get($cacheKey)), true) ?: [];
        }

        $data = $this->requestProjectDetails();

        // 5 minutes
        $expiresAt = now()->addMinutes(5);
        Cache::put($cacheKey, base64_encode(json_encode($data)), $expiresAt);

        return $data;
    }

    /**
     * requestBrowseProducts will list available products
     */
    public function requestBrowseProducts($type = null, $page = null)
    {
        if ($type !== 'plugin' && $type !== 'theme') {
            $type = 'plugin';
        }

        $cacheKey = "system-market-browse-${type}-${page}";

        if (Cache::has($cacheKey)) {
            return @json_decode(@base64_decode(Cache::get($cacheKey)), true) ?: [];
        }

        $data = $this->requestServerData('package/browse', [
            'type' => $type,
            'page' => $page
        ]);

        // 60 minutes
        $expiresAt = now()->addMinutes(60);
        Cache::put($cacheKey, base64_encode(json_encode($data)), $expiresAt);

        return $data;
    }

    //
    // Changelog
    //

    /**
     * requestChangelog returns the latest changelog information.
     */
    public function requestChangelog()
    {
        $result = Http::get('https://octobercms.com/changelog?json='.SystemHelper::VERSION);

        if ($result->code === 404) {
            throw new ApplicationException(Lang::get('system::lang.server.response_empty'));
        }

        if ($result->code !== 200) {
            throw new ApplicationException(
                strlen($result->body)
                ? $result->body
                : Lang::get('system::lang.server.response_empty')
            );
        }

        try {
            $resultData = json_decode($result->body, true);
        }
        catch (Exception $ex) {
            throw new ApplicationException(Lang::get('system::lang.server.response_invalid'));
        }

        return $resultData;
    }

    //
    // Notes
    //

    /**
     * note writes a note event for the migrator.
     * @param  string  $message
     * @return self
     */
    protected function note($message): UpdateManager
    {
        if ($this->notesOutput !== null) {
            $this->notesOutput->writeln($message);
        }

        return $this;
    }

    /**
     * setNotesOutput sets an output stream for writing notes.
     * @param  Illuminate\Console\Command $output
     * @return self
     */
    public function setNotesOutput($output): UpdateManager
    {
        $this->notesOutput = $output;

        return $this;
    }

    /**
     * getNotesOutput returns the note output, used by command line.
     */
    public function getNotesOutput()
    {
        return $this->notesOutput;
    }

    //
    // Gateway access
    //

    /**
     * Contacts the update server for a response.
     * @param  string $uri      Gateway API URI
     * @param  array  $postData Extra post data
     * @return array
     */
    public function requestServerData($uri, $postData = [])
    {
        $result = Http::post($this->createServerUrl($uri), function ($http) use ($postData) {
            $this->applyHttpAttributes($http, $postData);
        });

        if ($result->code === 404) {
            throw new ApplicationException(Lang::get('system::lang.server.response_not_found'));
        }

        if ($result->code !== 200) {
            throw new ApplicationException(
                strlen($result->body)
                ? $result->body
                : Lang::get('system::lang.server.response_empty')
            );
        }

        $resultData = false;

        try {
            $resultData = @json_decode($result->body, true);
        }
        catch (Exception $ex) {
            throw new ApplicationException(Lang::get('system::lang.server.response_invalid'));
        }

        if ($resultData === false || (is_string($resultData) && !strlen($resultData))) {
            throw new ApplicationException(Lang::get('system::lang.server.response_invalid'));
        }

        return $resultData;
    }

    /**
     * requestServerFile downloads a file from the update server.
     * @param  string $uri          Gateway API URI
     * @param  string $fileCode     A unique code for saving the file.
     * @param  string $expectedHash The expected file hash of the file.
     * @param  array  $postData     Extra post data
     * @return void
     */
    public function requestServerFile($uri, $fileCode, $expectedHash, $postData = [])
    {
        $filePath = $this->getFilePath($fileCode);

        $result = Http::post($this->createServerUrl($uri), function ($http) use ($postData, $filePath) {
            $this->applyHttpAttributes($http, $postData);
            $http->toFile($filePath);
        });

        if ($result->code !== 200) {
            throw new ApplicationException(File::get($filePath));
        }

        if (md5_file($filePath) !== $expectedHash) {
            @unlink($filePath);
            throw new ApplicationException(Lang::get('system::lang.server.file_corrupt'));
        }
    }

    /**
     * getFilePath calculates a file path for a file code
     * @param  string $fileCode A unique file code
     * @return string           Full path on the disk
     */
    protected function getFilePath($fileCode)
    {
        $name = md5($fileCode) . '.arc';
        return $this->tempDirectory . '/' . $name;
    }

    /**
     * Set the API security for all transmissions.
     * @param string $key    API Key
     * @param string $secret API Secret
     */
    public function setSecurity($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * createServerUrl creates a complete gateway server URL from supplied URI
     * @param  string $uri URI
     * @return string      URL
     */
    protected function createServerUrl($uri)
    {
        $gateway = env('APP_UPDATE_GATEWAY', Config::get('system.update_gateway', 'https://gateway.octobercms.com/api'));
        if (substr($gateway, -1) !== '/') {
            $gateway .= '/';
        }

        return $gateway . $uri;
    }

    /**
     * applyHttpAttributes modifies the Network HTTP object with common attributes.
     * @param  Http $http      Network object
     * @param  array $postData Post data
     * @return void
     */
    protected function applyHttpAttributes($http, $postData)
    {
        $postData['protocol_version'] = '2.0';
        $postData['client'] = 'October CMS';

        $postData['server'] = base64_encode(json_encode([
            'php' => PHP_VERSION,
            'url' => Url::to('/'),
            'ip' => Request::ip(),
            'since' => PluginVersion::orderBy('created_at')->value('created_at')
        ]));

        if ($projectKey = Parameter::get('system::project.key')) {
            $postData['project'] = $projectKey;
        }

        if ($this->key && $this->secret) {
            $postData['nonce'] = $this->createNonce();
            $http->header('Rest-Key', $this->key);
            $http->header('Rest-Sign', $this->createSignature($postData, $this->secret));
        }

        if ($credentials = Config::get('system.update_gateway_auth')) {
            $http->auth($credentials);
        }

        $http->noRedirect();
        $http->data($postData);
    }

    /**
     * Create a nonce based on millisecond time
     * @return int
     */
    protected function createNonce()
    {
        $mt = explode(' ', microtime());
        return $mt[1] . substr($mt[0], 2, 6);
    }

    /**
     * Create a unique signature for transmission.
     * @return string
     */
    protected function createSignature($data, $secret)
    {
        return base64_encode(hash_hmac('sha512', http_build_query($data, '', '&'), base64_decode($secret), true));
    }

    /**
     * getBuildFromVersion will return the patch version of a semver string
     * eg: 1.2.3 -> 3, 1.2.3-dev -> 3
     */
    protected function getBuildFromVersion(string $version): int
    {
        $parts = explode('.', $version);
        if (count($parts) !== 3) {
            return 0;
        }

        $lastPart = $parts[2];
        if (!is_numeric($lastPart)) {
            $lastPart = explode('-', $lastPart)[0];
        }

        if (!is_numeric($lastPart)) {
            return 0;
        }

        return $lastPart;
    }

    /**
     * getMigrationTableName returns the migration table name
     */
    public function getMigrationTableName(): string
    {
        return Config::get('database.migrations', 'migrations');
    }
}
