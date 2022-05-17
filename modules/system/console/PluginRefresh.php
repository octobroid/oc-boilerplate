<?php namespace System\Console;

use Illuminate\Console\Command;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * PluginRefresh refreshes a plugin.
 *
 * This destroys all database tables for a specific plugin, then builds them up again.
 * It is a great way for developers to debug and develop new plugins.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginRefresh extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'plugin:refresh';

    /**
     * @var string description of the console command
     */
    protected $description = 'Rollback and migrate database tables for a plugin.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $manager = PluginManager::instance();
        $name = $manager->normalizeIdentifier($this->argument('name'));

        if (!$manager->hasPlugin($name)) {
            return $this->output->error("Unable to find plugin '${name}'");
        }

        if ($this->userAbortedFromWarning($name)) {
            return;
        }

        if ($this->option('rollback') !== false) {
            return $this->handleRollback($name);
        }
        else {
            return $this->handleRefresh($name);
        }
    }

    /**
     * handleRollback performs a database rollback
     */
    protected function handleRefresh($name)
    {
        // Rollback plugin migration
        $manager = UpdateManager::instance()->setNotesOutput($this->output);
        $manager->rollbackPlugin($name);

        // Rerun migration
        $this->output->writeln('<info>Reinstalling plugin...</info>');
        $manager->updatePlugin($name);
    }

    /**
     * handleRollback performs a database rollback
     */
    protected function handleRollback($name)
    {
        // Rollback plugin migration
        $manager = UpdateManager::instance()->setNotesOutput($this->output);

        if ($toVersion = $this->option('rollback')) {
            $manager->rollbackPluginToVersion($name, $toVersion);
        }
        else {
            $manager->rollbackPlugin($name);
        }
    }

    /**
     * userAbortedFromWarning
     */
    protected function userAbortedFromWarning($name): bool
    {
        // Bypass from force
        if ($this->option('force', false)) {
            return false;
        }

        // Warn user
        if ($toVersion = $this->option('rollback')) {
            if (!$this->confirm("This will DESTROY database tables for plugin '${name}' up to version '${toVersion}'.")) {
                return true;
            }
        }
        else {
            if (!$this->confirm("This will DESTROY database tables for plugin '${name}'.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the plugin. Eg: AuthorName.PluginName'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run.'],
            ['rollback', 'r', InputOption::VALUE_OPTIONAL, 'Specify a version to rollback to, otherwise rollback to the beginning.', false],
        ];
    }
}
