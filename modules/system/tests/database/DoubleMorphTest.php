<?php

use Database\Tester\Models\Post;
use Database\Tester\Models\Role;
use Database\Tester\Models\Author;
use Database\Tester\Models\Category;

class DoubleMorphTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Role.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Author.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Category.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Post.php';

        $this->runPluginRefreshCommand('Database.Tester');
    }

    public function testSetRelationValue()
    {
        Model::unguard();
        $post = Post::create(['title' => 'First post']);
        $category1 = Category::create(['name' => 'News']);
        $category2 = Category::create(['name' => 'Great']);
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Actor", 'description' => "Excellent"]);
        Model::reguard();

        $post->double_categories()->attach($category1, ['host_type' => Post::class, 'entity_type' => Category::class]);
        $post->double_categories()->attach($category2, ['host_type' => Post::class, 'entity_type' => Category::class]);

        $this->assertEquals(2, $post->double_categories->count());
        $this->assertTrue($post->double_categories->contains($category1->id));
        $this->assertTrue($post->double_categories->contains($category2->id));

        $author->double_roles()->attach($role1, ['host_type' => Author::class, 'entity_type' => Role::class]);
        $author->double_roles()->attach($role2, ['host_type' => Author::class, 'entity_type' => Role::class]);

        $this->assertEquals(2, $author->double_roles->count());
        $this->assertTrue($author->double_roles->contains($role1->id));
        $this->assertTrue($author->double_roles->contains($role2->id));
    }
}
