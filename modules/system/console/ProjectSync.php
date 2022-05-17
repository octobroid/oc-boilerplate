<?php namespace System\Console;

use System;
use System\Classes\UpdateManager;
use October\Rain\Process\Composer as ComposerProcess;
use Illuminate\Console\Command;
use Exception;

/**
 * ProjectSync installs all plugins and themes belonging to a project
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ProjectSync extends Command
{
     /**
     * @var string name of console command
     */
    protected $name = 'project:sync';

    /**
     * @var string description of the console command
     */
    protected $description = 'Install plugins and themes belonging to a project.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->output->writeln('<info>Synchronizing Project...</info>');

        try {
            // Install project packages
            $this->installDefinedPlugins();

            // Composer update
            $this->comment("Executing: composer update");
            $composer = new ComposerProcess;
            $composer->setCallback(function($message) { echo $message; });
            $composer->update();

            // Check dependencies
            passthru('php artisan plugin:check --no-migrate');

            // Lock themes
            if (System::hasModule('Cms')) {
                passthru('php artisan theme:check');
            }

            // Migrate database
            $this->comment("Executing: php artisan october:migrate");
            $this->output->newLine();

            $errCode = null;
            passthru('php artisan october:migrate', $errCode);

            if ($errCode !== 0) {
                $this->output->error('Migration failed. Check output above');
                exit(1);
            }

            $this->output->success("Project synchronized");
        }
        catch (Exception $e) {
            $this->output->error($e->getMessage());
        }
    }

    /**
     * installDefinedPlugins
     */
    protected function installDefinedPlugins()
    {
        $installPackages = UpdateManager::instance()->syncProjectPackages();

        // Nothing to do
        if (count($installPackages) === 0) {
            $this->info('All packages already installed');
            return;
        }

        // Composer install differences
        foreach ($installPackages as $installPackage) {
            $this->comment("Executing: composer require {$installPackage} --no-update");
            $this->output->newLine();

            $composer = new ComposerProcess;
            $composer->setCallback(function($message) { echo $message; });
            $composer->requireNoUpdate($installPackage);

            if ($composer->lastExitCode() !== 0) {
                $this->output->error('Sync failed. Check output above');
                exit(1);
            }
        }
    }
}
