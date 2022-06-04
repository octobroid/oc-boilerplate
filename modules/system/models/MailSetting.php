<?php namespace System\Models;

use App;
use Model;

/**
 * MailSetting model
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class MailSetting extends Model
{
    use \October\Rain\Database\Traits\Validation;

    const MODE_LOG = 'log';
    const MODE_MAIL = 'mail';
    const MODE_SENDMAIL = 'sendmail';
    const MODE_SMTP = 'smtp';
    const MODE_MAILGUN = 'mailgun';
    const MODE_SES = 'ses';
    const MODE_POSTMARK = 'postmark';

    /**
     * @var array Behaviors implemented by this model.
     */
    public $implement = [
        \System\Behaviors\SettingsModel::class
    ];

    /**
     * @var string settingsCode is a unique code for these settings
     */
    public $settingsCode = 'system_mail_settings';

    /**
     * @var mixed settingsFields definitions
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'sender_name'  => 'required',
        'sender_email' => 'required|email'
    ];

    /**
     * initSettingsData for this model. This only executes when the
     * model is first created or reset to default.
     * @return void
     */
    public function initSettingsData()
    {
        $config = App::make('config');
        $this->send_mode = $config->get('mail.default', static::MODE_MAIL);
        $this->sender_name = $config->get('mail.from.name', 'Your Site');
        $this->sender_email = $config->get('mail.from.address', 'admin@domain.tld');
        $this->sendmail_path = $config->get('mail.mailers.sendmail.path', '/usr/sbin/sendmail');
        $this->smtp_address = $config->get('mail.mailers.smtp.host');
        $this->smtp_port = $config->get('mail.mailers.smtp.port', 587);
        $this->smtp_user = $config->get('mail.mailers.smtp.username');
        $this->smtp_password = $config->get('mail.mailers.smtp.password');
        $this->smtp_authorization = !!strlen($this->smtp_user);
        $this->smtp_encryption = $config->get('mail.mailers.smtp.encryption');
        $this->mailgun_domain = $config->get('services.mailgun.domain');
        $this->mailgun_secret = $config->get('services.mailgun.secret');
        $this->ses_key = $config->get('services.ses.key');
        $this->ses_secret = $config->get('services.ses.secret');
        $this->ses_region = $config->get('services.ses.region');
        $this->postmark_token = $config->get('services.postmark.secret');
    }

    /**
     * getSendModeOptions
     */
    public function getSendModeOptions()
    {
        return [
            static::MODE_LOG => 'system::lang.mail.log_file',
            static::MODE_MAIL => 'system::lang.mail.php_mail',
            static::MODE_SENDMAIL => 'system::lang.mail.sendmail',
            static::MODE_SMTP => 'system::lang.mail.smtp',
            static::MODE_MAILGUN => 'system::lang.mail.mailgun',
            static::MODE_SES => 'system::lang.mail.ses',
            static::MODE_POSTMARK => 'system::lang.mail.postmark',
        ];
    }

    /**
     * applyConfigValues
     */
    public static function applyConfigValues()
    {
        $config = App::make('config');
        $settings = self::instance();
        $config->set('mail.default', $settings->send_mode);
        $config->set('mail.from.name', $settings->sender_name);
        $config->set('mail.from.address', $settings->sender_email);

        switch ($settings->send_mode) {
            case self::MODE_SMTP:
                $config->set('mail.mailers.smtp.host', $settings->smtp_address);
                $config->set('mail.mailers.smtp.port', $settings->smtp_port);
                if ($settings->smtp_authorization) {
                    $config->set('mail.mailers.smtp.username', $settings->smtp_user);
                    $config->set('mail.mailers.smtp.password', $settings->smtp_password);
                }
                else {
                    $config->set('mail.mailers.smtp.username', null);
                    $config->set('mail.mailers.smtp.password', null);
                }
                if ($settings->smtp_encryption) {
                    $config->set('mail.mailers.smtp.encryption', $settings->smtp_encryption);
                }
                else {
                    $config->set('mail.mailers.smtp.encryption', null);
                }
                break;

            case self::MODE_SENDMAIL:
                $config->set('mail.mailers.sendmail.path', $settings->sendmail_path);
                break;

            case self::MODE_MAILGUN:
                $config->set('services.mailgun.domain', $settings->mailgun_domain);
                $config->set('services.mailgun.secret', $settings->mailgun_secret);
                break;

            case self::MODE_SES:
                $config->set('services.ses.key', $settings->ses_key);
                $config->set('services.ses.secret', $settings->ses_secret);
                $config->set('services.ses.region', $settings->ses_region);
                break;

            case self::MODE_POSTMARK:
                $config->set('services.postmark.token', $settings->postmark_token);
                break;
        }
    }

    /**
     * getSmtpEncryptionOptions values
     * @return array
     */
    public function getSmtpEncryptionOptions()
    {
        return [
            '' => 'system::lang.mail.smtp_encryption_none',
            'tls' => 'system::lang.mail.smtp_encryption_tls',
            'ssl' => 'system::lang.mail.smtp_encryption_ssl',
        ];
    }
}
