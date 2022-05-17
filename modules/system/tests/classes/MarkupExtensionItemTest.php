<?php

use System\Classes\MarkupExtensionItem;

class MarkupExtensionItemTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        include_once base_path() . '/modules/system/tests/fixtures/plugins/october/tester/Plugin.php';
    }

    //
    // Tests
    //

    /**
     * testOutputEscaping tests that the last argument of a definition can be used
     * to define the output escaping used in the Twig environment
    */
    public function testOutputEscaping()
    {
        // Escaped
        $item = $this->defineMarkupExtensionItem([
            'name' => 'test_func',
            'type' => MarkupExtensionItem::TYPE_FILTER,
            'definition' => ['count', true]
        ]);

        $this->assertTrue($item->escapeOutput);
        $this->assertTrue(is_callable($item->callback));

        // Not escaped
        $item = $this->defineMarkupExtensionItem([
            'name' => 'test_func',
            'type' => MarkupExtensionItem::TYPE_FILTER,
            'definition' => 'count'
        ]);

        $this->assertFalse($item->escapeOutput);
        $this->assertTrue(is_callable($item->callback));
    }

    /**
     * testIsWildCallable tests if wild callbacks work as they should
     */
    public function testIsWildCallable()
    {
        $item = $this->defineMarkupExtensionItem([
            'name' => 'test_func',
            'type' => MarkupExtensionItem::TYPE_FILTER,
            'definition' => null
        ]);

        /*
         * Negatives
         */
        $item->callback = 'something';
        $this->assertFalse($item->isWildCallable());

        $item->callback = ['Form', 'open'];
        $this->assertFalse($item->isWildCallable());

        $item->callback = function () {
            return 'O, Hai!';
        };
        $this->assertFalse($item->isWildCallable());

        /*
         * String
         */
        $item->callback = 'something_*';
        $this->assertTrue($item->isWildCallable());
        $this->assertEquals('something_delicious', $item->getWildCallback('delicious'));

        /*
         * Array
         */
        $item->callback = ['Class', 'foo_*'];
        $this->assertTrue($item->isWildCallable());

        $result = $item->getWildCallback('bar');
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('Class', $result[0]);
        $this->assertEquals('foo_bar', $result[1]);

        $item->callback = ['My*', 'method'];
        $this->assertTrue($item->isWildCallable());

        $result = $item->getWildCallback('Class');
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('MyClass', $result[0]);
        $this->assertEquals('method', $result[1]);

        $item->callback = ['My*', 'my*'];
        $this->assertTrue($item->isWildCallable());

        $result = $item->getWildCallback('Food');
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('MyFood', $result[0]);
        $this->assertEquals('myFood', $result[1]);
    }

    /**
     * defineMarkupExtensionItem
     */
    protected function defineMarkupExtensionItem(array $config): MarkupExtensionItem
    {
        return (new MarkupExtensionItem)->useConfig($config);
    }
}
