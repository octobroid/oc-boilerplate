<?php namespace Backend\Models;

use App;
use Url;
use File;
use Lang;
use Model;
use Cache;
use Config;
use Backend;
use Less_Parser;
use Exception;

/**
 * BrandSetting that affect all users
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class BrandSetting extends Model
{
    use \System\Traits\ViewMaker;
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array implement these behavioral mixins
     */
    public $implement = [
        \System\Behaviors\SettingsModel::class
    ];

    /**
     * @var string settingsCode is a unique code for this object
     */
    public $settingsCode = 'backend_brand_settings';

    /**
     * @var mixed settingsFields defition file
     */
    public $settingsFields = 'fields.yaml';

    public $attachOne = [
        'favicon' => \System\Models\File::class,
        'logo' => \System\Models\File::class,
        'login_background_wallpaper' => \System\Models\File::class,
        'login_custom_image' => \System\Models\File::class
    ];

    /**
     * @var string cacheKey to store rendered CSS in the cache under
     */
    public $cacheKey = 'backend::brand.custom_css';

    const PRIMARY_COLOR = '#6a6cf7';
    const SECONDARY_COLOR = '#e67e22';
    const ACCENT_COLOR = '#3498db';
    const SELECTION_COLOR = '#6bc48d';

    const MENU_INLINE = 'inline';
    const MENU_TEXT = 'text';
    const MENU_TILE = 'tile';
    const MENU_COLLAPSE = 'collapse';
    const MENU_ICONS = 'icons';
    const MENU_LEFT = 'left';

    const DEFAULT_LOGIN_COLOR = '#fef6eb';
    const DEFAULT_LOGIN_BG_TYPE = 'color';
    const DEFAULT_LOGIN_IMG_TYPE = 'autumn_images';
    const DEFAULT_WALLPAPER_SIZE = 'auto';

    /**
     * rules for validation
     */
    public $rules = [
        'app_name' => 'required',
        'app_tagline' => 'required',
    ];

    /**
     * initSettingsData initializes the seed data for this model. This only executes
     * when the model is first created or reset to default.
     */
    public function initSettingsData(): void
    {
        $this->app_name = self::getBaseConfig('app_name', Lang::get('system::lang.app.name'));
        $this->app_tagline = self::getBaseConfig('tagline', Lang::get('system::lang.app.tagline'));
        $this->primary_color = self::getBaseConfig('primary_color', self::PRIMARY_COLOR);
        $this->secondary_color = self::getBaseConfig('secondary_color', self::SECONDARY_COLOR);
        $this->accent_color = self::getBaseConfig('accent_color', self::ACCENT_COLOR);
        $this->selection_color = self::getBaseConfig('selection_color', self::SELECTION_COLOR);
        $this->menu_mode = self::getBaseConfig('menu_mode', self::MENU_INLINE);
        $this->login_background_type = self::getBaseConfig('login_background_type', self::DEFAULT_LOGIN_BG_TYPE);
        $this->login_background_color = self::getBaseConfig('login_background_color', self::DEFAULT_LOGIN_COLOR);
        $this->login_background_wallpaper_size = self::getBaseConfig('login_background_wallpaper_size', self::DEFAULT_WALLPAPER_SIZE);
        $this->login_image_type = self::getBaseConfig('login_image_type', self::DEFAULT_LOGIN_IMG_TYPE);

        // Attempt to load custom CSS
        $brandCssPath = File::symbolizePath(self::getBaseConfig('stylesheet_path'));
        if ($brandCssPath && File::exists($brandCssPath)) {
            $this->custom_css = File::get($brandCssPath);
        }
    }

    /**
     * afterSave event
     */
    public function afterSave()
    {
        Cache::forget(self::instance()->cacheKey);
    }

    /**
     * getFavicon
     */
    public static function getFavicon()
    {
        $settings = self::instance();

        if ($settings->favicon) {
            return $settings->favicon->getPath();
        }

        return self::getDefaultFavicon() ?: null;
    }

    /**
     * getLogo
     */
    public static function getLogo()
    {
        $settings = self::instance();

        if ($settings->logo) {
            return $settings->logo->getPath();
        }

        return self::getDefaultLogo() ?: null;
    }

    /**
     * getLoginWallpaperImage
     */
    public static function getLoginWallpaperImage()
    {
        $bgType = self::get('login_background_type', self::DEFAULT_LOGIN_BG_TYPE);
        if ($bgType == self::DEFAULT_LOGIN_BG_TYPE) {
            return null;
        }

        $settings = self::instance();

        if ($settings->login_background_wallpaper) {
            return $settings->login_background_wallpaper->getPath();
        }

        return null;
    }

    /**
     * getLoginCustomImage
     */
    public static function getLoginCustomImage()
    {
        $imgType = self::get('login_image_type', self::DEFAULT_LOGIN_IMG_TYPE);
        if ($imgType == self::DEFAULT_LOGIN_IMG_TYPE) {
            return null;
        }

        $settings = self::instance();
        if ($settings->login_custom_image) {
            return $settings->login_custom_image->getPath();
        }

        $customImage = File::symbolizePath(self::getBaseConfig('login_custom_image'));
        if ($customImage && File::exists($customImage)) {
            return Url::asset(File::localToPublic($customImage));
        }

        return null;
    }

    /**
     * renderCss for the backend area
     */
    public static function renderCss()
    {
        $cacheKey = self::instance()->cacheKey;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $customCss = self::compileCss();
            Cache::forever($cacheKey, $customCss);
        }
        catch (Exception $ex) {
            $customCss = '/* ' . $ex->getMessage() . ' */';
        }

        return $customCss;
    }

    /**
     * compileCss for the backend area
     */
    public static function compileCss()
    {
        $parser = new Less_Parser(['compress' => true]);
        $basePath = base_path('modules/backend/models/brandsetting');

        $primaryColor = self::get('primary_color', self::PRIMARY_COLOR);
        $secondaryColor = self::get('secondary_color', self::PRIMARY_COLOR);
        $accentColor = self::get('accent_color', self::ACCENT_COLOR);
        $loginBgColor = self::get('login_background_color', self::DEFAULT_LOGIN_COLOR);
        $wallpaperSize = self::get('login_background_wallpaper_size', self::DEFAULT_WALLPAPER_SIZE);

        $parser->ModifyVars([
            'logo-image' => "'".self::getLogo()."'",
            'brand-primary' => $primaryColor,
            'brand-secondary' => $secondaryColor,
            'brand-accent' => $accentColor,
            'brand-selection' => $accentColor,
            'login-bg-color' => $loginBgColor,
            'login-wallpaper-size' => $wallpaperSize,
            'login-wallpaper' => "'".self::getLoginWallpaperImage()."'"
        ]);

        $parser->parse(
            File::get($basePath . '/custom.less') .
            self::get('custom_css')
        );

        return $parser->getCss();
    }

    /**
     * getLoginPageCustomization returns customization properites used by the login page
     */
    public static function getLoginPageCustomization()
    {
        return (object)[
            'loginImageType' => self::get('login_image_type', self::DEFAULT_LOGIN_IMG_TYPE),
            'loginCustomImage' => self::getLoginCustomImage()
        ];
    }

    //
    // Base line configuration
    //

    /**
     * getBaseConfig will only look at base config if the enabled flag is true
     */
    public static function getBaseConfig(string $value, string $default = null): ?string
    {
        if (!self::isBaseConfigured()) {
            return $default;
        }

        return Config::get('backend.brand.'.$value, $default);
    }

    /**
     * isBaseConfigured checks if base brand settings found in config
     */
    public static function isBaseConfigured(): bool
    {
        return (bool) Config::get('backend.brand.enabled', false);
    }

    /**
     * getDefaultFavicon returns a configured favicon image
     */
    public static function getDefaultFavicon()
    {
        $faviconPath = File::symbolizePath(self::getBaseConfig('favicon_path'));

        if ($faviconPath && File::exists($faviconPath)) {
            return Url::asset(File::localToPublic($faviconPath));
        }

        return Backend::skinAsset('assets/images/favicon.png');
    }

    /**
     * getDefaultLogo returns a default backend logo image
     */
    public static function getDefaultLogo()
    {
        $logoPath = File::symbolizePath(self::getBaseConfig('logo_path'));

        if ($logoPath && File::exists($logoPath)) {
            return Url::asset(File::localToPublic($logoPath));
        }

        return null;
    }
}
