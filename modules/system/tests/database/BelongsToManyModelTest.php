<?php

use Database\Tester\Models\Category;
use Database\Tester\Models\Post as PostModel;
use Database\Tester\Models\Role;
use Database\Tester\Models\Author;
use Database\Tester\Models\Product;

class BelongsToManyModelTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Role.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Author.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Category.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Post.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Product.php';

        $this->runPluginRefreshCommand('Database.Tester');
    }

    public function testSetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        // Add/remove to collection
        $this->assertFalse($author->roles->contains($role1->id));
        $author->roles()->add($role1);
        $author->roles()->add($role2);
        $this->assertTrue($author->roles->contains($role1->id));
        $this->assertTrue($author->roles->contains($role2->id));

        // Set by Model object
        $author->roles = $role1;
        $this->assertEquals(1, $author->roles->count());
        $this->assertEquals('Designer', $author->roles->first()->name);

        $author->roles = [$role1, $role2, $role3];
        $this->assertEquals(3, $author->roles->count());

        // Set by primary key
        $author->roles = $role2->id;
        $this->assertEquals(1, $author->roles->count());
        $this->assertEquals([$role2->id], $author->getRelationValue('roles'));
        $this->assertEquals('Programmer', $author->roles->first()->name);

        $author->roles = [$role2->id, $role3->id];
        $this->assertEquals(2, $author->roles->count());

        // Nullify
        $author->roles = null;
        $this->assertEquals(0, $author->roles->count());

        // Extra nullify checks (still exists in DB until saved)
        $author->reloadRelations('roles');
        $this->assertEquals(2, $author->roles->count());
        $author->save();
        $author->reloadRelations('roles');
        $this->assertEquals(0, $author->roles->count());

        // Deferred in memory
        $author->roles = [$role2->id, $role3->id];
        $this->assertEquals(2, $author->roles->count());
        $this->assertEquals('Programmer', $author->roles->first()->name);
    }

    public function testGetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        Model::reguard();

        $author->roles()->add($role1);
        $author->roles()->add($role2);

        $this->assertEquals([$role1->id, $role2->id], $author->getRelationValue('roles'));
    }

    public function testDeferredBinding()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        Model::reguard();

        // Deferred add
        $author->roles()->add($role1, $sessionKey);
        $author->roles()->add($role2, $sessionKey);
        $this->assertEmpty($author->roles);

        $this->assertEquals(0, $author->roles()->count());
        $this->assertEquals(2, $author->roles()->withDeferred($sessionKey)->count());

        // Get simple value (implicit)
        $author->reloadRelations();
        $author->sessionKey = $sessionKey;
        $this->assertEquals([$role1->id, $role2->id], $author->getRelationValue('roles'));

        // Get simple value (explicit)
        $relatedIds = $author->roles()->allRelatedIds($sessionKey)->all();
        $this->assertEquals([$role1->id, $role2->id], $relatedIds);

        // Commit deferred
        $author->save(null, $sessionKey);
        $this->assertEquals(2, $author->roles()->count());
        $this->assertEquals('Designer', $author->roles->first()->name);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->roles()->remove($role1, $sessionKey);
        $author->roles()->remove($role2, $sessionKey);
        $this->assertEquals(2, $author->roles()->count());
        $this->assertEquals(0, $author->roles()->withDeferred($sessionKey)->count());
        $this->assertEquals('Designer', $author->roles->first()->name);

        // Commit deferred
        $author->save(null, $sessionKey);
        $this->assertEquals(0, $author->roles()->count());
        $this->assertEquals(0, $author->roles->count());
    }

    public function testDeferredBindingWithPivot()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $category = Category::create(['name' => 'News']);
        $post1 = PostModel::create(['title' => 'First post']);
        $post2 = PostModel::create(['title' => 'Second post']);
        Model::reguard();

        // Deferred add
        $category->posts()->add($post1, $sessionKey, [
            'category_name' => $category->name . ' in pivot',
            'post_name' => $post1->title . ' in pivot',
        ]);
        $category->posts()->add($post2, $sessionKey, [
            'category_name' => $category->name . ' in pivot',
            'post_name' => $post2->title . ' in pivot',
        ]);
        $this->assertEmpty($category->posts);

        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(2, $category->posts()->withDeferred($sessionKey)->count());

        // Get simple value (implicit)
        $category->reloadRelations();
        $category->sessionKey = $sessionKey;
        $this->assertEquals([$post1->id, $post2->id], $category->getRelationValue('posts'));

        // Get simple value (explicit)
        $relatedIds = $category->posts()->allRelatedIds($sessionKey)->all();
        $this->assertEquals([$post1->id, $post2->id], $relatedIds);

        // Commit deferred
        $category->save(null, $sessionKey);
        $this->assertEquals(2, $category->posts()->count());
        $this->assertEquals('First post', $category->posts->first()->title);
        $this->assertEquals('Second post', $category->posts->last()->title);
        $this->assertEquals('First post in pivot', $category->posts->first()->pivot->post_name);
        $this->assertEquals('Second post in pivot', $category->posts->last()->pivot->post_name);
        $this->assertEquals('News in pivot', $category->posts->first()->pivot->category_name);
        $this->assertEquals('News in pivot', $category->posts->last()->pivot->category_name);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $category->posts()->remove($post1, $sessionKey);
        $category->posts()->remove($post2, $sessionKey);
        $this->assertEquals(2, $category->posts()->count());
        $this->assertEquals(0, $category->posts()->withDeferred($sessionKey)->count());
        $this->assertEquals('First post', $category->posts->first()->title);
        $this->assertEquals('Second post', $category->posts->last()->title);
        $this->assertEquals('First post in pivot', $category->posts->first()->pivot->post_name);
        $this->assertEquals('Second post in pivot', $category->posts->last()->pivot->post_name);
        $this->assertEquals('News in pivot', $category->posts->first()->pivot->category_name);
        $this->assertEquals('News in pivot', $category->posts->last()->pivot->category_name);

        // Commit deferred
        $category->save(null, $sessionKey);
        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(0, $category->posts->count());
    }

    public function testDetachAfterDelete()
    {
        // Needed for other "delete" events
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/User.php';
        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/EventLog.php';

        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        $author->roles()->add($role1);
        $author->roles()->add($role2);
        $author->roles()->add($role3);
        $this->assertEquals(3, Db::table('database_tester_authors_roles')->where('author_id', $author->id)->count());

        $author->delete();
        $this->assertEquals(0, Db::table('database_tester_authors_roles')->where('author_id', $author->id)->count());
    }

    public function testConditionsWithPivotAttributes()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        $author->roles()->add($role1, null, ['is_executive' => 1]);
        $author->roles()->add($role2, null, ['is_executive' => 1]);
        $author->roles()->add($role3, null, ['is_executive' => 0]);

        $this->assertEquals([1, 2], $author->executive_authors->pluck('id')->all());
        $this->assertEquals([1, 2], $author->executive_authors()->pluck('id')->all());
        $this->assertEquals([1, 2], $author->executive_authors()->get()->pluck('id')->all());
    }

    public function testCustomPivotKeys()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld', 'code' => 'STEVIE']);
        $product1 = Product::create(['name' => "Stevie Goes to the Mall", 'code' => "SKU001"]);
        $product2 = Product::create(['name' => "Stevie Goes Camping", 'code' => "SKU002"]);
        $product3 = Product::create(['name' => "Stevie Cooks Dinner", 'code' => "SKU003"]);
        Model::reguard();

        $author->products()->add($product1);
        $author->products()->add($product2);
        $author->products()->add($product3);

        $result = Db::table('database_tester_authors_products')->get();
        $this->assertEquals($result[0]->author_code, 'STEVIE');
        $this->assertEquals($result[0]->product_code, 'SKU001');
        $this->assertEquals($result[1]->author_code, 'STEVIE');
        $this->assertEquals($result[1]->product_code, 'SKU002');
        $this->assertEquals($result[2]->author_code, 'STEVIE');
        $this->assertEquals($result[2]->product_code, 'SKU003');
        $this->assertEquals([1, 2, 3], $author->products->pluck('id')->all());
    }
}
