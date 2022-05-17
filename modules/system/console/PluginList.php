<?php namespace System\Console;

use Illuminate\Console\Command;
use System\Models\PluginVersion;

/**
 * PluginList lists existing plugins
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginList extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'plugin:list';

    /**
     * @var string description of the console command
     */
    protected $description = 'List plugins available in the system';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $tableRows = [];

        foreach (PluginVersion::all() as $plugin) {
            $tableRows[] = [
                $plugin->code,
                $plugin->version,
                (!$plugin->is_disabled) ? 'Yes': 'No'
            ];
        }

        $this->output->table(['Plugin', 'Version', 'Plugin Enabled'], $tableRows);
    }
}
