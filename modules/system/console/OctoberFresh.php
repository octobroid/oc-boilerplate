<?php namespace System\Console;

use File;
use Artisan;
use Illuminate\Console\Command;
use System\Classes\PluginManager;
use Symfony\Component\Console\Input\InputOption;

/**
 * OctoberFresh is a console command to remove boilerplate.
 *
 * This removes the demo theme and plugin. A great way to start a fresh project!
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class OctoberFresh extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * @var string name of console command
     */
    protected $name = 'october:fresh';

    /**
     * @var string description of the console command
     */
    protected $description = 'Removes the demo theme and plugin.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        if (!$this->confirmToProceed('Are you sure?')) {
            return;
        }

        $demoThemePath = themes_path().'/demo';

        if (File::exists($demoThemePath)) {
            File::deleteDirectory($demoThemePath);

            $manager = PluginManager::instance();
            $manager->deletePlugin('October.Demo');

            $this->info('Demo has been removed! Enjoy a fresh start.');
        }
        else {
            $this->error('Demo theme is already removed.');
        }
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run.'],
        ];
    }
}
