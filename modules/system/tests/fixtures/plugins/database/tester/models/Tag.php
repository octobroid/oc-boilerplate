<?php namespace Database\Tester\Models;

use Model;

class Tag extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_tags';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    public $morphedByMany = [
        'authors' => [
            Author::class,
            'name' => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by'],
        ],
        'posts'   => [
            Post::class,
            'name' => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by'],
        ],
    ];
}
