<?php namespace Cms\Console;

use Cms\Classes\Theme;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * ThemeUse switches the active theme to another one, saved to the database.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeUse extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * @var string name of console command
     */
    protected $name = 'theme:use';

    /**
     * @var string description of the console command
     */
    protected $description = 'Switch the active theme.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        if (!$this->confirmToProceed('Change the active theme?')) {
            return;
        }

        $newThemeName = $this->argument('name');
        $newTheme = Theme::load($newThemeName);

        if (!$newTheme->exists($newThemeName)) {
            return $this->error(sprintf('The theme %s does not exist.', $newThemeName));
        }

        if ($newTheme->isActiveTheme()) {
            return $this->error(sprintf('%s is already the active theme.', $newTheme->getId()));
        }

        $from = Theme::getActiveThemeCode() ?: 'nothing';
        $this->info(sprintf('Switching theme from %s to %s', $from, $newTheme->getId()));

        Theme::setActiveTheme($newThemeName);
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
