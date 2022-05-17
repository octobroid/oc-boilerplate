<?php namespace Backend\Models;

use October\Rain\Auth\Models\Group as GroupBase;

/**
 * UserGroup for an administrator
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class UserGroup extends GroupBase
{
    const CODE_OWNERS = 'owners';

    /**
     * @var string table associated with the model
     */
    protected $table = 'backend_user_groups';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|between:2,128|unique:backend_user_groups',
    ];

    /**
     * @var array belongsToMany relationship
     */
    public $belongsToMany = [
        'users' => [User::class, 'table' => 'backend_users_groups']
    ];

    /**
     * afterCreate event
     */
    public function afterCreate()
    {
        if ($this->is_new_user_default) {
            $this->addAllUsersToGroup();
        }
    }

    /**
     * addAllUsersToGroup adds everyone to this group
     */
    public function addAllUsersToGroup()
    {
        $this->users()->sync(User::lists('id'));
    }
}
