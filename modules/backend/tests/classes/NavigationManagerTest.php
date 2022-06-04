<?php

use Backend\Classes\NavigationManager;

class NavigationManagerTest extends TestCase
{
    public function testRegisterMenuItems()
    {
        $manager = NavigationManager::instance();
        $items = $manager->listMainMenuItems();
        $this->assertArrayNotHasKey('OCTOBER.TEST.DASHBOARD', $items);

        $manager->registerMenuItems('October.Test', [
            'dashboard' => [
                'label' => 'Dashboard',
                'icon' => 'icon-dashboard',
                'url' => 'http://dashboard.tld',
                'order' => 100
            ]
        ]);

        $items = $manager->listMainMenuItems();
        $this->assertArrayHasKey('OCTOBER.TEST.DASHBOARD', $items);

        $item = $items['OCTOBER.TEST.DASHBOARD'];
        $itemArr = $item->toArray();
        $this->assertArrayHasKey('code', $itemArr);
        $this->assertArrayHasKey('label', $itemArr);
        $this->assertArrayHasKey('icon', $itemArr);
        $this->assertArrayHasKey('url', $itemArr);
        $this->assertArrayHasKey('owner', $itemArr);
        $this->assertArrayHasKey('order', $itemArr);
        $this->assertArrayHasKey('permissions', $itemArr);
        $this->assertArrayHasKey('sideMenu', $itemArr);

        $this->assertEquals('dashboard', $item->code);
        $this->assertEquals('Dashboard', $item->label);
        $this->assertEquals('icon-dashboard', $item->icon);
        $this->assertEquals('http://dashboard.tld', $item->url);
        $this->assertEquals(100, $item->order);
        $this->assertEquals('October.Test', $item->owner);
    }

    public function testListMainMenuItems()
    {
        $manager = NavigationManager::instance();
        $items = $manager->listMainMenuItems();

        $this->assertArrayHasKey('OCTOBER.TESTER.BLOG', $items);
    }

    public function testListSideMenuItems()
    {
        $manager = NavigationManager::instance();

        $items = $manager->listSideMenuItems();
        $this->assertEmpty($items);

        $manager->setContext('October.Tester', 'blog');

        $items = $manager->listSideMenuItems();
        $this->assertIsArray($items);
        $this->assertArrayHasKey('posts', $items);
        $this->assertArrayHasKey('categories', $items);

        $item = $items['posts'];
        $otherItem = $items['categories'];
        $this->assertIsObject($item);
        $this->assertArrayHasKey('code', $item);
        $this->assertArrayHasKey('owner', $item);
        $this->assertEquals('posts', $item->code);
        $this->assertEquals('October.Tester', $item->owner);

        $this->assertArrayHasKey('permissions', $item);
        $this->assertIsArray($item->permissions);
        $this->assertCount(1, $item->permissions);

        $this->assertArrayHasKey('order', $item);
        $this->assertArrayHasKey('order', $otherItem);
        $this->assertEquals(100, $item->order);
        $this->assertEquals(200, $otherItem->order);
    }

    public function testAddMainMenuItems()
    {
        $manager = NavigationManager::instance();
        $manager->addMainMenuItems('October.Tester', [
            'print' => [
                'label' => 'Print',
                'icon' => 'icon-print',
                'url' => 'javascript:window.print()'
            ]
        ]);

        $items = $manager->listMainMenuItems();

        $this->assertIsArray($items);
        $this->assertArrayHasKey('OCTOBER.TESTER.PRINT', $items);

        $item = $items['OCTOBER.TESTER.PRINT'];
        $this->assertEquals('print', $item->code);
        $this->assertEquals('Print', $item->label);
        $this->assertEquals('icon-print', $item->icon);
        $this->assertEquals('javascript:window.print()', $item->url);
        $this->assertEquals(500, $item->order);
        $this->assertEquals('October.Tester', $item->owner);
    }

    public function testRemoveMainMenuItem()
    {
        $manager = NavigationManager::instance();
        $manager->addMainMenuItems('October.Tester', [
            'close' => [
                'label' => 'Close',
                'icon' => 'icon-times',
                'url' => 'javascript:window.close()'
            ]
        ]);

        $items = $manager->listMainMenuItems();
        $this->assertArrayHasKey('OCTOBER.TESTER.CLOSE', $items);

        $manager->removeMainMenuItem('October.Tester', 'close');

        $items = $manager->listMainMenuItems();
        $this->assertArrayNotHasKey('OCTOBER.TESTER.CLOSE', $items);
    }

    public function testAddSideMenuItems()
    {
        $manager = NavigationManager::instance();

        $manager->addSideMenuItems('October.Tester', 'blog', [
            'foo' => [
                'label' => 'Bar',
                'icon' => 'icon-derp',
                'url' => 'http://google.com',
                'permissions' => [
                    'october.tester.access_foo',
                    'october.tester.access_bar'
                ]
            ]
        ]);

        $manager->setContext('October.Tester', 'blog');
        $items = $manager->listSideMenuItems();

        $this->assertIsArray($items);
        $this->assertArrayHasKey('foo', $items);

        $item = $items['foo'];
        $this->assertIsObject($item);
        $this->assertArrayHasKey('code', $item);
        $this->assertArrayHasKey('owner', $item);
        $this->assertArrayHasKey('order', $item);

        $this->assertEquals(-1, $item->order);
        $this->assertEquals('foo', $item->code);
        $this->assertEquals('October.Tester', $item->owner);

        $this->assertArrayHasKey('permissions', $item);
        $this->assertIsArray($item->permissions);
        $this->assertCount(2, $item->permissions);
        $this->assertContains('october.tester.access_foo', $item->permissions);
        $this->assertContains('october.tester.access_bar', $item->permissions);
    }

    public function testRemoveSideMenuItem()
    {
        $manager = NavigationManager::instance();
        $manager->addSideMenuItems('October.Tester', 'blog', [
            'bar' => [
                'label' => 'Bar',
                'icon' => 'icon-bars',
                'url' => 'http://yahoo.com'
            ]
        ]);

        $manager->setContext('October.Tester', 'blog');

        $items = $manager->listSideMenuItems();
        $this->assertArrayHasKey('bar', $items);

        $manager->removeSideMenuItem('October.Tester', 'blog', 'bar');

        $items = $manager->listSideMenuItems();
        $this->assertArrayNotHasKey('bar', $items);
    }
}
