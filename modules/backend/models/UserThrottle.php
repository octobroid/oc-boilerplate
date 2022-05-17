<?php namespace Backend\Models;

use October\Rain\Auth\Models\Throttle as ThrottleBase;

/**
 * UserThrottle model for backend users
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class UserThrottle extends ThrottleBase
{
    /**
     * @var string table associated with the model
     */
    protected $table = 'backend_user_throttle';

    /**
     * @var array belongsTo relation
     */
    public $belongsTo = [
        'user' => User::class
    ];
}
