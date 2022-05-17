<?php namespace System\Controllers;

use Lang;
use File;
use Flash;
use Block;
use Config;
use Redirect;
use BackendMenu;
use Backend\Classes\SettingsController;
use System\Models\MailBrandSetting;
use System\Classes\SettingsManager;
use System\Classes\MailManager;
use System\Models\MailLayout;
use System\Models\MailTemplate;

/**
 * MailBrandSettings for customizing mail brand settings
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 *
 */
class MailBrandSettings extends SettingsController
{
    /**
     * @var array implement extensions
     */
    public $implement = [
        \Backend\Behaviors\FormController::class
    ];

    /**
     * @var array formConfig `FormController` configuration.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string settingsItemCode determines the settings code
     */
    public $settingsItemCode = 'mail_brand_settings';

    /**
     * @var array requiredPermissions to view this page.
     */
    public $requiredPermissions = ['system.manage_mail_templates'];

    /**
     * @var string bodyClass HTML body tag class
     */
    public $bodyClass = 'compact-container';

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        $this->pageTitle = 'system::lang.mail_brand.page_title';
    }

    /**
     * index action
     */
    public function index()
    {
        $this->addJs('/modules/system/assets/js/mailbrandsettings/mailbrandsettings.js', 'core');
        $this->addCss('/modules/system/assets/css/mailbrandsettings/mailbrandsettings.css', 'core');

        Block::append('head', $this->renderSampleMessageAsScript());

        $setting = MailBrandSetting::instance();

        $setting->resetCache();

        return $this->create();
    }

    /**
     * index_onSave
     */
    public function index_onSave()
    {
        return $this->create_onSave();
    }

    /**
     * index_onResetDefault
     */
    public function index_onResetDefault()
    {
        $setting = MailBrandSetting::instance();

        $setting->resetDefault();

        Flash::success(Lang::get('backend::lang.form.reset_success'));

        return Redirect::refresh();
    }

    /**
     * onUpdateSampleMessage
     */
    public function onUpdateSampleMessage()
    {
        $this->pageAction();

        $this->formGetWidget()->setFormValues();

        return ['previewHtml' => $this->renderSampleMessage()];
    }

    /**
     * renderSampleMessage
     */
    public function renderSampleMessage()
    {
        $data = [
            'subject' => Config::get('app.name'),
            'appName' => Config::get('app.name'),
            'texts' => Lang::get('system::lang.mail_brand.sample_template')
        ];

        $layout = new MailLayout;
        $layout->fillFromCode('default');

        $template = new MailTemplate;
        $template->layout = $layout;
        $template->content_html = File::get(base_path('modules/system/models/mailbrandsetting/sample_template.htm'));

        return MailManager::instance()->renderTemplate($template, $data);
    }

    /**
     * renderSampleMessageAsScript template
     */
    protected function renderSampleMessageAsScript()
    {
        return '<script type="text/template" id="'.$this->getId('mailPreviewTemplate').'">'.$this->renderSampleMessage().'</script>';
    }

    /**
     * formCreateModelObject
     */
    public function formCreateModelObject()
    {
        return MailBrandSetting::instance();
    }
}
