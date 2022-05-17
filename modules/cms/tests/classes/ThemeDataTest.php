<?php

use Cms\Classes\Theme;

class ThemeDataTest extends TestCase
{
    /**
     * testAutoJsonable
     */
    public function testAutoJsonable()
    {
        $theme = Theme::load('test')->getCustomData();

        $this->assertTrue($theme->isJsonable('nestedform'));
        $this->assertTrue($theme->isJsonable('breakdown'));
        $this->assertTrue($theme->isJsonable('nested'));
    }
}
