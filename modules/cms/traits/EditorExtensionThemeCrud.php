<?php namespace Cms\Traits;

use Lang;
use Cms\Classes\Theme;
use Editor\Classes\ApiHelpers;
use ApplicationException;

/**
 * EditorExtensionThemeCrud implements Theme CRUD operations for the CMS Editor Extension
 */
trait EditorExtensionThemeCrud
{
    /**
     * command_onSetEditTheme sets the editing theme for the user
     */
    protected function command_onSetEditTheme()
    {
        $metadata = $this->getRequestMetadata();

        $selectedTheme = ApiHelpers::assertGetKey($metadata, 'theme');

        $this->assertCmsThemeExists($selectedTheme);

        Theme::setEditTheme($selectedTheme);
    }

    /**
     * assertCmsThemeExists ensures a theme directory exists
     */
    protected function assertCmsThemeExists(string $themeDir)
    {
        $themes = Theme::all();
        foreach ($themes as $theme) {
            if ($theme->getDirName() === $themeDir) {
                return true;
            }
        }

        throw new ApplicationException(Lang::get('cms::lang.theme.not_found_name', ['name' => $themeDir]));
    }
}
