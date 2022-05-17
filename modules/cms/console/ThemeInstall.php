<?php namespace Cms\Console;

use System;
use Illuminate\Console\Command;
use Cms\Classes\ThemeManager;
use System\Classes\UpdateManager;
use October\Rain\Process\Composer as ComposerProcess;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Exception;

/**
 * Console command to install a new theme.
 *
 * This adds a new theme by requesting it from the October marketplace.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeInstall extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'theme:install';

    /**
     * @var string description of the console command
     */
    protected $description = 'Install a theme from the October marketplace or custom source.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->output->writeln('<info>Installing Theme...</info>');

        $name = $this->argument('name');

        $this->assertCanInstallTheme();

        if ($src = $this->option('from')) {
            $this->output->writeln("<info>Added Repo: {$src}</info>");
            $composerCode = System::octoberToComposerCode(
                $name,
                'theme',
                (bool) $this->option('oc')
            );

            $this->addRepoFromSource($composerCode, $src);
        }
        else {
            $info = UpdateManager::instance()->requestThemeDetails($name);
            $composerCode = array_get($info, 'composer_code');
        }

        // Splice in version
        $requirePackage = $composerCode;
        if ($requireVersion = $this->option('want')) {
            $requirePackage .= ':'.$requireVersion;
        }

        // Composer install
        $this->comment("Executing: composer require {$requirePackage}");
        $this->output->newLine();

        $composer = new ComposerProcess;
        $composer->setCallback(function($message) { echo $message; });
        $composer->require($requirePackage);

        if ($composer->lastExitCode() !== 0) {
            if ($src = $this->option('from')) {
                $this->output->writeln("<info>Reverted repo change</info>");
                $this->removeRepoFromSource($composerCode);
            }

            $this->output->error('Install failed. Check output above');
            exit(1);
        }

        if (!$this->option('no-lock')) {
            $this->performLockOnTheme();
        }

        // Check dependencies
        passthru('php artisan plugin:check');

        $this->output->success("Theme '${name}' installed");
    }

    /**
     * assertCanInstallTheme makes sure the theme isn't in use
     */
    protected function assertCanInstallTheme()
    {
        $name = strtolower($this->argument('name'));

        $parts = explode('.', $name);
        $themeFolder = $parts[1] ?? null;
        $themePath = themes_path($themeFolder);

        // Ensure a theme does not already exist
        if ($themeFolder && file_exists($themePath)) {
            throw new Exception("A theme already exists at '${themeFolder}' please rename this folder and try again.");
        }
    }

    /**
     * performLockOnTheme locks the theme and creates a child theme
     */
    protected function performLockOnTheme()
    {
        $name = strtolower($this->argument('name'));

        // Legacy composer installers
        $parts = explode('.', $name);
        $themeFolder = $parts[1] ?? null;
        $themePath = $themeFolder ? themes_path($themeFolder) : null;

        // New composer installers
        if (!$themePath || !file_exists($themePath)) {
            $themeFolder = strtolower(str_replace('.', '-', $name));
        }

        if (!$themeFolder) {
            return;
        }

        // Lock and create child theme
        $manager = ThemeManager::instance();
        $manager->createChildTheme((string) $themeFolder);
        $manager->performLockOnTheme((string) $themeFolder);
    }

    /**
     * addRepoFromSource adds a plugin to composer's repositories
     */
    protected function addRepoFromSource($composerCode, $src)
    {
        if (file_exists(base_path($src))) {
            if (file_exists(base_path($src . '/.git'))) {
                $srcType = 'git';
            }
            else {
                $srcType = 'path';
            }
        }
        else {
            $srcType = 'git';
        }

        $composer = new ComposerProcess;
        $composer->addRepository($composerCode, $srcType, $src);
    }

    /**
     * removeRepoFromSource removes a plugin from composer's repo
     */
    protected function removeRepoFromSource($composerCode)
    {
        $composer = new ComposerProcess;
        $composer->removeRepository($composerCode);
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the theme. Eg: AuthorName.ThemeName'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['oc', null, InputOption::VALUE_NONE, 'Package uses the oc- prefix.'],
            ['from', 'f', InputOption::VALUE_REQUIRED, 'Provide a custom source.'],
            ['want', 'w', InputOption::VALUE_REQUIRED, 'Provide a custom version.'],
            ['no-lock', null, InputOption::VALUE_NONE, 'Do not lock the provided theme.'],
        ];
    }
}
