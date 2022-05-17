<?php

class ChangelogWidgetTest extends TestCase
{

    //
    // Tests
    //

    public function testGetPluginVersionFile()
    {
        $widget = $this->getMockBuilder(System\Widgets\Changelog::class)->disableOriginalConstructor()->getMock();

        $expectedVersions = [
            '1.0.5' => [
                'Create blog settings table',
                'Another update message',
                'Yet one more update message'
            ],
            '1.0.4' => [
                'Another fix'
            ],
            '1.0.3' => [
                'Bug fix update that uses no scripts'
            ],
            '1.0.2' => [
                'Create blog post comments table',
                'Multiple update messages are allowed'
            ],
            '1.0.1' => [
                'Added some upgrade file and some seeding',
                'some_upgrade_file.php', //does not exist
                'some_seeding_file.php' //does not exist
            ]
        ];

        $versions = self::callProtectedMethod(
            $widget,
            'getPluginVersionFile',
            [
                base_path() . '/modules/system/tests/fixtures/plugins/october/tester/',
                'updates/version.yaml'
            ]
        );

        $this->assertNotNull($versions);
        $this->assertEquals($expectedVersions, $versions);
    }
}
