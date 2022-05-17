<?php namespace Database\Tester\Models;

use Model;

class Post extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_posts';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'author' => Author::class,
    ];

    public $morphMany = [
        'event_log' => [
            EventLog::class,
            'name' => 'related',
            'delete' => true,
            'softDelete' => true
        ],
    ];

    public $morphOne = [
        'meta' => [
            Meta::class,
            'name' => 'taggable'
        ],
    ];

    public $belongsToMany = [
        'categories' => [
            Category::class,
            'table' => 'database_tester_categories_posts',
            'pivot' => ['category_name', 'post_name']
        ],
        'double_categories' => [
            Category::class,
            'table' => 'database_tester_double_joins',
            'key' => 'host_id',
            'otherKey' => 'entity_id',
            'scope' => [self::class, 'applyDoubleJoinClassNames'],
            'pivot' => ['host_type', 'entity_type']
        ]
    ];

    public $morphToMany = [
        'tags' => [
            Tag::class,
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by']
        ],
    ];

    public static function applyDoubleJoinClassNames($query, $parent, $related)
    {
        $query->where('host_type', get_class($parent));
        $query->where('entity_type', get_class($related));
    }
}

class NullablePost extends Post
{
    use \October\Rain\Database\Traits\Nullable;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array List of attributes to nullify
     */
    protected $nullable = [
        'author_nickname',
    ];
}

class SluggablePost extends Post
{
    use \October\Rain\Database\Traits\Sluggable;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array List of attributes to automatically generate unique URL names (slugs) for.
     */
    protected $slugs = [
        'slug' => 'title',
        'long_slug' => ['title', 'description']
    ];
}

class RevisionablePost extends Post
{
    use \October\Rain\Database\Traits\Revisionable;
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Dates
     */
    protected $dates = ['published_at', 'deleted_at'];

    /**
     * @var array Monitor these attributes for changes.
     */
    protected $revisionable = [
        'title',
        'slug',
        'description',
        'is_published',
        'published_at',
        'deleted_at'
    ];

    /**
     * @var int Maximum number of revision records to keep.
     */
    public $revisionableLimit = 8;

    /**
     * @var array Relations
     */
    public $morphMany = [
        'revision_history' => [
            \System\Models\Revision::class,
            'name' => 'revisionable'
        ]
    ];

    /**
     * The user who made the revision.
     */
    public function getRevisionableUser()
    {
        return 7;
    }
}

class ValidationPost extends Post
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    public $rules = [
        'title' => 'required|min:3|max:255',
        'slug' => ['required', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:database_tester_posts'],
    ];
}
