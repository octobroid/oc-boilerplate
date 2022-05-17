<?php namespace Cms\Console;

use Cms\Classes\Theme;
use Cms\Classes\ThemeManager;
use October\Rain\Process\Composer as ComposerProcess;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;

/**
 * ThemeRemove removes a theme.
 *
 * This completely deletes an existing theme, including all files and directories.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeRemove extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * @var string name of console command
     */
    protected $name = 'theme:remove';

    /**
     * @var string description of the console command
     */
    protected $description = 'Delete an existing theme.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $manager = ThemeManager::instance();
        $name = $suppliedName = (string) $this->argument('name');
        $themeExists = Theme::exists($name);

        if (!$themeExists) {
            $name = (string) $manager->findDirectoryName($name);
            $themeExists = Theme::exists($name);
        }

        $this->output->writeln('<info>Removing Plugin...</info>');

        if (!$themeExists || !$name) {
            return $this->output->error("Unable to find theme '${suppliedName}'");
        }

        if (!$this->confirmToProceed(sprintf('This will DELETE theme "%s" from the filesystem and database.', $name))) {
            return;
        }

        // Remove via composer
        if ($composerCode = $manager->getComposerCode($name)) {

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

        // Remove via filesystem
        $manager->deleteTheme($name);

        $this->output->success("Theme '${name}' removed");
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The directory name of the theme.'],
        ];
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
