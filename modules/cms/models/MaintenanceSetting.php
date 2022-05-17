<?php namespace Cms\Models;

use Model;
use System;
use BackendAuth;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use ApplicationException;
use Exception;

/**
 * MaintenanceSetting for maintenance mode
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class MaintenanceSetting extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array implement behaviors
     */
    public $implement = [
        \System\Behaviors\SettingsModel::class
    ];

    /**
     * @var string settingsCode is a unique code
     */
    public $settingsCode = 'cms_maintenance_settings';

    /**
     * @var mixed settingsFields defitions
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * initSettingsData initializes the seed data for this model. This only executes when the
     * model is first created or reset to default.
     * @return void
     */
    public function initSettingsData()
    {
        $this->is_enabled = false;
    }

    /**
     * isEnabled returns true if maintenance mode should be used
     */
    public static function isEnabled(): bool
    {
        if (!System::hasDatabase() || BackendAuth::getUser()) {
            return false;
        }

        return self::get('is_enabled', false);
    }

    /**
     * getCmsPageOptions
     */
    public function getCmsPageOptions()
    {
        if (!$theme = $this->findTargetTheme()) {
            throw new ApplicationException('Unable to find the active theme.');
        }

        return array_map(function($code) use ($theme) {
            return "{$theme->getDirName()}/${code}";
        }, Page::listInTheme($theme)->lists('fileName', 'fileName'));
    }

    /**
     * beforeValidate ensures each theme has its own CMS page, store it inside a mapping array.
     */
    public function beforeValidate()
    {
        if (!$theme = $this->findTargetTheme()) {
            throw new ApplicationException('Unable to find the active theme.');
        }

        $themeMap = $this->getSettingsValue('theme_map', []);
        $themeMap[$theme->getDirName()] = $this->getSettingsValue('cms_page');
        $this->setSettingsValue('theme_map', $themeMap);
    }

    /**
     * afterFetch restores the CMS page found in the mapping array, or disable the
     * maintenance mode.
     */
    public function afterFetch()
    {
        if (
            ($theme = $this->findTargetTheme())
            && ($themeMap = array_get($this->attributes, 'theme_map'))
            && ($cmsPage = array_get($themeMap, $theme->getDirName()))
        ) {
            $this->cms_page = $cmsPage;
        }
        else {
            $this->is_enabled = false;
        }
    }

    /**
     * findTargetTheme will attempt to use the edit theme, with active theme fallback.
     */
    protected function findTargetTheme()
    {
        try {
            return Theme::getEditTheme();
        }
        catch (Exception $ex) {
            return Theme::getActiveTheme();
        }
    }
}
