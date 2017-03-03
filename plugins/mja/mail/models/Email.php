<?php namespace Mja\Mail\Models;

use Carbon\Carbon;
use Hash;
use Model;

/**
 * Registration Model
 */
class Email extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'mja_mail_email_log';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    public $fillable = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    public $jsonable = ['to', 'cc', 'bcc', 'reply_to', 'sender'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'sent' => 'boolean',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'opens' => 'Mja\Mail\Models\EmailOpens',
    ];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function beforeCreate()
    {
        $this->hash = md5(time().'some-salt'.rand(1, 10000));
    }

    public function logEmailOpened()
    {
        $this->opens()->create([]);
    }

    public function getTimesOpenedAttribute()
    {
        return (int) $this->opens()->count();
    }

    public function getLastOpenedAttribute()
    {
        $open =  $this->opens->last();

        if (!$open) {
            return null;
        }

        return Carbon::parse($open->created_at)->diffForHumans();
    }

    public function getToEmailAttribute()
    {
        $emails = array_keys($this->to);
        return $emails[0];
    }

    public function getCcEmailsAttribute()
    {
        $emails = array_keys((array) $this->cc);
        return implode(', ', $emails);
    }

    public function getBccEmailsAttribute()
    {
        $emails = array_keys((array) $this->bcc);
        return implode(', ', $emails);
    }

    public function emailsByCode()
    {
        return (int) self::whereCode($this->code)->count();
    }
}
