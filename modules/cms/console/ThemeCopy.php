<?php namespace Cms\Console;

use Cms\Classes\Theme as CmsTheme;
use Cms\Classes\ThemeManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * ThemeCopy will duplicate a specified theme
 *
 * theme:copy theme-name new-theme-name
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeCopy extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'theme:copy';

    /**
     * @var string description of the console command
     */
    protected $description = 'Duplicates an existing theme';

    /**
     * @var \Cms\Classes\ThemeManager themeManager
     */
    protected $themeManager;

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->themeManager = ThemeManager::instance();

        $sourceDir = $this->argument('name');
        $targetDir = $this->argument('new-name');

        if (!CmsTheme::exists($sourceDir)) {
            $this->output->error("Theme '${sourceDir}' could not be found");
            exit(1);
        }

        if ($targetDir) {
            $this->handleDuplicateTheme($sourceDir, $targetDir);
        }

        if ($this->option('import-db')) {
            $this->handleImportDatabase($sourceDir, $targetDir);
        }

        if ($this->option('purge-db')) {
            $this->handlePurgeDatabase($sourceDir, $targetDir);
        }
    }

    /**
     * handleDuplicateTheme
     */
    protected function handleDuplicateTheme($sourceDir, $targetDir)
    {
        $this->info("Duplicating Theme [${sourceDir}] to [${targetDir}]...");

        if ($this->option('child')) {
            $result = $this->themeManager->createChildTheme($sourceDir, $targetDir);
        }
        else {
            $result = $this->themeManager->duplicateTheme($sourceDir, $targetDir);
        }

        if ($result) {
            $this->output->success("Theme '${targetDir}' duplicated");
        }
        else {
            $this->output->error("Theme '${targetDir}' already exists");
            exit(1);
        }
    }

    /**
     * handleImportDatabase
     */
    protected function handleImportDatabase($sourceDir, $targetDir = null)
    {
        $dirName = $targetDir ?: $sourceDir;

        $this->info("Importing database contents from '${sourceDir}' to '${dirName}'");

        $this->themeManager->importDatabaseTemplates($dirName, $sourceDir);
    }

    /**
     * handlePurgeDatabase
     */
    protected function handlePurgeDatabase($sourceDir, $targetDir = null)
    {
        if (!$this->option('import-db')) {
            $this->error('The --purge-db flag must be used with the --import-db flag.');
            exit(1);
        }

        $dirName = $targetDir ?: $sourceDir;

        if (
            !$this->option('force', false) &&
            !$this->confirm("This will DESTROY database templates for theme '${dirName}'.")
        ) {
            return;
        }

        $this->info("Deleting database contents from '${dirName}'");

        $this->themeManager->purgeDatabaseTemplates($dirName);
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the theme (directory name) to duplicate.'],
            ['new-name', InputArgument::OPTIONAL, 'The desired name for the the new theme (directory name).'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run.'],
            ['child', 'c', InputOption::VALUE_NONE, 'Create a child theme.'],
            ['import-db', null, InputOption::VALUE_NONE, 'Includes the database templates in the copy.'],
            ['purge-db', null, InputOption::VALUE_NONE, 'Deletes all templates from the database.'],
        ];
    }
}
