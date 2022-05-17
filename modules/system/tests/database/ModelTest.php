<?php

use Database\Tester\Models\Post;

class ModelTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        include_once base_path() . '/modules/system/tests/fixtures/plugins/database/tester/models/Post.php';

        $this->runPluginRefreshCommand('Database.Tester');
    }

    public function testCreateFirstPost()
    {
        Post::truncate();
        $post = new Post;
        $post->title = "First post";
        $post->description = "Yay!!";
        $post->save();
        $this->assertEquals(1, $post->id);
    }

    public function testGuardedAttribute()
    {
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        $this->expectExceptionMessageMatches('/title/');

        Post::create(['title' => 'Hi!', 'slug' => 'authenticity']);
    }

    public function testAddDynamicPoperty()
    {
        $post = new Post;

        $post->addDynamicProperty('myDynamicProperty', 'myDynamicPropertyValue');

        // Dynamic property should not hit attributes
        $this->assertArrayNotHasKey('myDynamicProperty', $post->attributes);

        // Should be a real property
        $this->assertTrue(property_exists($post, 'myDynamicProperty'));
        $this->assertEquals('myDynamicPropertyValue', $post->myDynamicProperty);
    }
}
