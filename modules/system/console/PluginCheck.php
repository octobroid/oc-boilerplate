<?php namespace System\Console;

use October\Rain\Process\Composer as ComposerProcess;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Console\Command;

/**
 * PluginCheck checks for missing plugin dependencies and installs them
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginCheck extends Command
{
     /**
     * @var string name of console command
     */
    protected $name = 'plugin:check';

    /**
     * @var string description of the console command
     */
    protected $description = 'Checks for missing plugin dependencies and installs them.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->output->writeln('<info>Checking Dependencies...</info>');

        $this->installRequiredPlugins();
    }

    /**
     * installRequiredPlugins
     */
    protected function installRequiredPlugins()
    {
        $pluginRequire = \System\Classes\PluginManager::instance()->findMissingDependencies();
        $themeRequire = \Cms\Classes\ThemeManager::instance()->findMissingDependencies();

        $deps = array_unique(array_merge($pluginRequire, $themeRequire));

        // Prompt?
        // foreach ($deps as $dep) {
        //     $this->info('[ ] '.$dep);
        // }

        foreach ($deps as $dep) {
            $this->call('plugin:install', ['name' => $dep, '--no-migrate' => true, '--no-update' => true]);
        }

        if (count($deps)) {
            // Composer update
            $this->comment("Executing: composer update");
            $composer = new ComposerProcess;
            $composer->setCallback(function($message) { echo $message; });
            $composer->update();

            // Migrate database
            if (!$this->option('no-migrate')) {
                $this->comment("Executing: php artisan october:migrate");
                $this->output->newLine();

                $errCode = null;
                passthru('php artisan october:migrate', $errCode);

                if ($errCode !== 0) {
                    $this->output->error('Migration failed. Check output above');
                    exit(1);
                }
            }
        }

        // Success
        $this->output->writeln('<info>All dependencies installed</info>');
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['no-migrate', null, InputOption::VALUE_NONE, 'Do not run migration after install.'],
        ];
    }
}
