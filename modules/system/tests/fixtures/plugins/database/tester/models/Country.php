<?php namespace Database\Tester\Models;

use Model;

class Country extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_countries';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    public $hasMany = [
        'users' => [
            User::class,
        ],
    ];

    public $hasManyThrough = [
        'posts' => [
            Post::class,
            'through' => Author::class,
        ]
    ];
}

class SoftDeleteCountry extends Country
{
    use \October\Rain\Database\Traits\SoftDelete;
}
