<?php namespace System\Console;

use System\Models\PluginVersion;
use System\Classes\PluginManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

/**
 * PluginDisable disables a plugin in file system and user preferences
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginDisable extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'plugin:disable';

    /**
     * @var string description of the console command
     */
    protected $description = 'Disable an existing plugin.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $manager = PluginManager::instance();
        $name = $manager->normalizeIdentifier($this->argument('name'));

        // Lookup
        if (!$manager->hasPlugin($name)) {
            return $this->output->error("Unable to find plugin '${name}'");
        }

        // Disable in filesystem
        $manager->disablePlugin($name);

        // Disable user preference
        if ($plugin = PluginVersion::where('code', $name)->first()) {
            $plugin->is_disabled = true;
            $plugin->save();
        }

        $this->output->success("Plugin '${name}' disabled");
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'Name of the plugin, eg: Author.Plugin'],
        ];
    }
}
