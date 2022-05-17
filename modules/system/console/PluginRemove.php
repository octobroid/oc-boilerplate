<?php namespace System\Console;

use Config;
use System;
use Illuminate\Console\Command;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use System\Helpers\Cache as CacheHelper;
use October\Rain\Process\Composer as ComposerProcess;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * PluginRemove removes a plugin.
 *
 * This completely deletes an existing plugin, including database tables, files
 * and directories.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginRemove extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * @var string name of console command
     */
    protected $name = 'plugin:remove';

    /**
     * @var string description of the console command
     */
    protected $description = 'Removes an existing plugin.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->output->writeln('<info>Removing Plugin...</info>');

        if ($this->handleComposer() === true) {
            return;
        }

        $manager = PluginManager::instance();
        $name = $manager->normalizeIdentifier($this->argument('name'));

        // Lookup
        if (!$manager->hasPlugin($name)) {
            return $this->output->error("Unable to find plugin '${name}'");
        }

        if (!$this->confirmToProceed(sprintf('This will DELETE plugin "%s" from the filesystem and database.', $name))) {
            return;
        }

        // Remove via composer
        if ($composerCode = $manager->getComposerCode($name)) {
            UpdateManager::instance()->setNotesOutput($this->output)->rollbackPlugin($name);

            // Composer remove
            $this->comment("Executing: composer remove {$composerCode}");
            $this->output->newLine();

            $composer = new ComposerProcess;
            $composer->setCallback(function($message) { echo $message; });
            $composer->remove($composerCode);

            if ($composer->lastExitCode() !== 0) {
                $this->output->error('Remove failed. Check output above');
                exit(1);
            }
        }

        $manager->deletePlugin($name);

        $this->output->success("Plugin '${name}' removed");
    }

    /**
     * handleComposer is internally used by composer
     */
    protected function handleComposer(): bool
    {
        // Called internally via composer
        if (!$this->option('composer')) {
            return false;
        }

        // Clear meta cache
        CacheHelper::instance()->clearMeta();

        // Disabled by config
        if (Config::get('system.auto_rollback_plugins') !== true) {
            return true;
        }

        $manager = PluginManager::instance();
        $name = $manager->normalizeIdentifier($this->argument('name'));
        $name = System::composerToOctoberCode($name);

        // Lookup
        if (!$manager->hasPlugin($name)) {
            return true;
        }

        // Rollback plugin
        UpdateManager::instance()->setNotesOutput($this->output)->rollbackPlugin($name);

        return true;
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
            ['composer', null, InputOption::VALUE_NONE, 'Command triggered from composer.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run.'],
        ];
    }

    /**
     * getDefaultConfirmCallback specifies the default confirmation callback
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return true;
        };
    }
}
