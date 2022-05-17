<?php namespace Backend\Models;

use BackendAuth;
use SystemException;
use October\Rain\Auth\Models\Preferences as PreferencesBase;

/**
 * UserPreference stores all preferences for the backend user
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class UserPreference extends PreferencesBase
{
    /**
     * @var string table associated with the model
     */
    protected $table = 'backend_user_preferences';

    /**
     * @var bool timestamps enabled
     */
    public $timestamps = false;

    /**
     * @var array cache
     */
    protected static $cache = [];

    /**
     * resolveUser checks for a supplied user or uses the default logged in. You should
     * override this method
     *
     * @param mixed $user An optional back-end user object.
     * @return User object
     */
    public function resolveUser($user)
    {
        $user = BackendAuth::getUser();
        if (!$user) {
            throw new SystemException(trans('backend::lang.user.preferences.not_authenticated'));
        }

        return $user;
    }
}
