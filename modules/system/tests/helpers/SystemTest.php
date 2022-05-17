<?php

use System\Helpers\System;

class SystemTest extends TestCase
{
    public function testComposerToOctoberCode()
    {
        $helper = new System;

        $code = $helper->composerToOctoberCode('acme.blog');
        $this->assertEquals('acme.blog', $code);

        $code = $helper->composerToOctoberCode('rainlab/mailchimp-plugin');
        $this->assertEquals('rainlab.mailchimp', $code);

        $code = $helper->composerToOctoberCode('rainlab/mailchimp-plugin-9999999');
        $this->assertEquals('rainlab.mailchimp', $code);
    }
}
