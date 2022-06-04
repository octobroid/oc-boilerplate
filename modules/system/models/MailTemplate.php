<?php namespace System\Models;

use View;
use Model;
use System\Classes\MailManager;
use October\Rain\Mail\MailParser;
use File as FileHelper;

/**
 * MailTemplate
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class MailTemplate extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    protected $table = 'system_mail_templates';

    /**
     * @var array guarded fields
     */
    protected $guarded = [];

    /**
     * @var array fillable fields
     */
    protected $fillable = [];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'code' => 'required|unique:system_mail_templates',
        'subject' => 'required',
        'description' => 'required',
        'content_html' => 'required',
    ];

    public $belongsTo = [
        'layout' => MailLayout::class
    ];

    /**
     * listAllTemplates returns an array of template codes and descriptions.
     * @return array
     */
    public static function listAllTemplates()
    {
        $fileTemplates = (array) MailManager::instance()->listRegisteredTemplates();
        $dbTemplates = (array) self::lists('code', 'code');
        $templates = $fileTemplates + $dbTemplates;
        ksort($templates);
        return $templates;
    }

    /**
     * allTemplates returns a list of all mail templates.
     * @return array Returns an array of the MailTemplate objects.
     */
    public static function allTemplates()
    {
        $result = [];
        $codes = array_keys(self::listAllTemplates());

        foreach ($codes as $code) {
            $result[] = self::findOrMakeTemplate($code);
        }

        return $result;
    }

    /**
     * Syncronise all file templates to the database.
     * @return void
     */
    public static function syncAll()
    {
        MailLayout::createLayouts();
        MailPartial::createPartials();

        $templates = MailManager::instance()->listRegisteredTemplates();
        $dbTemplates = self::lists('is_custom', 'code');
        $newTemplates = array_diff_key($templates, $dbTemplates);

        // Clean up non-customized templates
        foreach ($dbTemplates as $code => $isCustom) {
            if ($isCustom) {
                continue;
            }

            if (!array_key_exists($code, $templates)) {
                self::whereCode($code)->delete();
            }
        }

        // Create new templates
        foreach ($newTemplates as $code) {
            $sections = self::getTemplateSections($code);
            $layoutCode = array_get($sections, 'settings.layout', 'default');
            $description = array_get($sections, 'settings.description');

            $template = self::make();
            $template->code = $code;
            $template->description = $description;
            $template->is_custom = 0;
            $template->layout_id = MailLayout::getIdFromCode($layoutCode);
            $template->forceSave();
        }
    }

    /**
     * afterFetch
     */
    public function afterFetch()
    {
        if (!$this->is_custom) {
            $this->fillFromView($this->code);
        }
    }

    /**
     * fillFromContent
     */
    public function fillFromContent($content)
    {
        $this->fillFromSections(MailParser::parse($content));
    }

    /**
     * fillFromView
     */
    public function fillFromView($path)
    {
        $this->fillFromSections(self::getTemplateSections($path));
    }

    /**
     * fillFromSections
     */
    protected function fillFromSections($sections)
    {
        $this->content_html = $sections['html'] ?? '';
        $this->content_text = $sections['text'] ?? '';
        $this->subject = array_get($sections, 'settings.subject', 'No subject');

        $layoutCode = array_get($sections, 'settings.layout', 'default');
        $this->layout = MailLayout::findOrMakeLayout($layoutCode);
    }

    /**
     * getTemplateSections
     */
    protected static function getTemplateSections($code)
    {
        return MailParser::parse(FileHelper::get(View::make($code)->getPath()));
    }

    /**
     * findOrMakeTemplate
     */
    public static function findOrMakeTemplate($code)
    {
        $template = self::whereCode($code)->first();

        if (!$template && View::exists($code)) {
            $template = new self;
            $template->code = $code;
            $template->fillFromView($code);
        }

        return $template;
    }
}
