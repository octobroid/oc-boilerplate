<?php namespace Mja\Mail\Models;

use Model;
use Request;

/**
 * Registration Model
 */
class EmailOpens extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'mja_mail_email_opens';

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
    public $jsonable = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function beforeCreate()
    {
        $this->ip_address = Request::getClientIp();
        $this->setCreatedat($this->freshTimestamp());
    }
}
