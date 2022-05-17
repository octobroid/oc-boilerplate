<?php namespace Cms\Models;

use App;
use Model;
use BackendAuth;
use Cms\Classes\Theme;
use System\Models\LogSetting;
use October\Rain\Halcyon\Model as HalcyonModel;
use Exception;

/**
 * ThemeLog logs changes made to the theme
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeLog extends Model
{
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    /**
     * @var string table associated with the model
     */
    protected $table = 'cms_theme_logs';

    /**
     * @var array belongsTo relation
     */
    public $belongsTo = [
        'user' => \Backend\Models\User::class
    ];

    /**
     * @var array themeCache
     */
    protected $themeCache;

    /**
     * bindEventsToModel adds observers to the model for logging purposes.
     */
    public static function bindEventsToModel(HalcyonModel $template)
    {
        $template->bindEvent('model.beforeDelete', function () use ($template) {
            self::add($template, self::TYPE_DELETE);
        });

        $template->bindEvent('model.beforeSave', function () use ($template) {
            self::add($template, $template->exists ? self::TYPE_UPDATE : self::TYPE_CREATE);
        });
    }

    /**
     * add a new log record
     * @return self
     */
    public static function add(HalcyonModel $template, $type = null)
    {
        if (!App::hasDatabase()) {
            return;
        }

        if (!LogSetting::get('log_theme')) {
            return;
        }

        if (!$type) {
            $type = self::TYPE_UPDATE;
        }

        $isDelete = $type === self::TYPE_DELETE;
        $dirName = $template->getObjectTypeDirName();
        $templateName = $template->fileName;
        $oldTemplateName = $template->getOriginal('fileName');
        $newContent = $template->toCompiled();
        $oldContent = $template->getOriginal('content');

        // Content not dirty
        if ($newContent === $oldContent && $templateName === $oldTemplateName && !$isDelete) {
            return;
        }

        $record = new self;
        $record->type = $type;
        $record->theme = Theme::getEditThemeCode();
        $record->template = $isDelete ? '' : $dirName.'/'.$templateName;
        $record->old_template = $oldTemplateName ? $dirName.'/'.$oldTemplateName : '';
        $record->content = $isDelete ? '' : $newContent;
        $record->old_content = $oldContent;

        if ($user = BackendAuth::getUser()) {
            $record->user_id = $user->id;
        }

        try {
            $record->save();
        }
        catch (Exception $ex) {
        }

        return $record;
    }

    /**
     * getThemeNameAttribute
     */
    public function getThemeNameAttribute()
    {
        $code = $this->theme;

        if (!isset($this->themeCache[$code])) {
            $this->themeCache[$code] = Theme::load($code);
        }

        $theme = $this->themeCache[$code];

        return $theme->getConfigValue('name', $theme->getDirName());
    }

    /**
     * getTypeOptions
     */
    public function getTypeOptions()
    {
        return [
            self::TYPE_CREATE => 'cms::lang.theme_log.type_create',
            self::TYPE_UPDATE => 'cms::lang.theme_log.type_update',
            self::TYPE_DELETE => 'cms::lang.theme_log.type_delete'
        ];
    }

    /**
     * getAnyTemplateAttribute
     */
    public function getAnyTemplateAttribute()
    {
        return $this->template ?: $this->old_template;
    }

    /**
     * getTypeNameAttribute
     */
    public function getTypeNameAttribute()
    {
        return array_get($this->getTypeOptions(), $this->type);
    }
}
