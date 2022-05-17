<?php namespace Cms\Console;

use Illuminate\Console\Command;
use Cms\Classes\ThemeManager;
use Cms\Classes\Theme;

/**
 * ThemeList lists themes.
 *
 * This lists all the available themes in the system. It also shows the active theme.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeList extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'theme:list';

    /**
     * @var string description of the console command
     */
    protected $description = 'List available themes.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $themeManager = ThemeManager::instance();

        foreach (Theme::all() as $theme) {
            $flag = $theme->isActiveTheme() ? '[*] ' : '[-] ';
            $themeId = $theme->getId();
            $themeName = $themeManager->findInstalledCode($themeId) ?: $themeId;
            $this->info($flag . $themeName);
        }

        $this->info(PHP_EOL."[*] Active    [-] Installed    [ ] Not installed");
    }
}
