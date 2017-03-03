<?php namespace Mja\Mail\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mja\Mail\Models\EmailOpens;
use System\Models\MailTemplate;

/**
 * Back-end Controller
 */
class Template extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public $requiredPermissions = ['mja.mail.template'];

    /**
     * Ensure that by default our menu sidebar is active
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mja.Mail', 'mail', 'template');
    }

    public function index()
    {
        MailTemplate::syncAll();
        $this->asExtension('ListController')->index();
    }

    public function stats($recordId = null, $context = null)
    {
        $this->asExtension('FormController')->preview($recordId, 'stats');

        $template = MailTemplate::findOrFail($recordId);
        $this->vars['lastWeek'] = $this->getOpensLastWeek($template);
        $this->vars['lastTs'] = end($this->vars['lastWeek'])->ts;
    }

    protected function getOpensLastWeek(MailTemplate $template)
    {
        $return = [];
        $emailOpens = new EmailOpens;

        $opens = $template->opens()
            ->where($emailOpens->table . '.created_at', '>=', Carbon::now()->subWeek())
            ->orderBy('created_at', 'asc')
            ->get();

        // Set placeholder data
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::createFromTime(null, 0, 0)->subDays($i);

            $return[$date->day] = (object) [
                'ts' => $date,
                'count' => 0
            ];
        }

        // Set actual data
        foreach ($opens as $open) {
            $date = $open->created_at;
            $return[$date->day]->count += 1;
        }

        return $return;
    }
}
