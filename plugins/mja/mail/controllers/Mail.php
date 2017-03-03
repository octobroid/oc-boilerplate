<?php namespace Mja\Mail\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Mja\Mail\Models\Email;
use Mja\Mail\Models\EmailOpens;

/**
 * Back-end Controller
 */
class Mail extends Controller
{
    public $hide_hints = false;

    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public $requiredPermissions = ['mja.mail.mail'];

    public $bodyClass = 'compact-container';

    /**
     * Ensure that by default our menu sidebar is active
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mja.Mail', 'mail', 'mail');
    }

    public function index()
    {
        $this->vars['opens'] = EmailOpens::count();
        $this->vars['sent'] = Email::whereSent(true)->count();
        $this->vars['bounced'] = Email::whereSent(false)->count();
        $this->vars['emails'] = Email::select('code')->groupBy('code')->get();

        $this->asExtension('ListController')->index();
    }
}
