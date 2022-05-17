<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Media\Classes\MediaLibrary;

class MediaLibraryTest extends TestCase // @codingStandardsIgnoreLine
{
    public function setUp(): void
    {
        MediaLibrary::forgetInstance();
        parent::setUp();
    }

    public function invalidPathsProvider()
    {
        return [
            ['./file'],
            ['../secret'],
            ['.../secret'],
            ['/../secret'],
            ['/.../secret'],
            ['/secret/..'],
            ['file/../secret'],
            ['file/..'],
            ['......./secret'],
            ['./file'],
        ];
    }

    public function validPathsProvider()
    {
        return [
            ['file'],
            ['folder/file'],
            ['/file'],
            ['/folder/file'],
            ['/.file'],
            ['/..file'],
            ['/...file'],
            ['file.ext'],
            ['file..ext'],
            ['file...ext'],
            ['one,two.ext'],
            ['one(two)[].ext'],
            ['one=(two)[].ext'],
            ['one_(two)[].ext'],
            /*
            Example of a unicode-based filename with a single quote
            @see: https://github.com/octobercms/october/pull/4564
            */
            ['BG中国通讯期刊(Blend\'r)创刊号.pdf'],
        ];
    }

    /**
     * @dataProvider invalidPathsProvider
     */
    public function testInvalidPathsOnValidatePath($path)
    {
        $this->expectException('ApplicationException');
        MediaLibrary::validatePath($path);
    }

    /**
     * @dataProvider validPathsProvider
     */
    public function testValidPathsOnValidatePath($path)
    {
        $result = MediaLibrary::validatePath($path);
        $this->assertIsString($result);
    }

    public function testListAllDirectories()
    {
        $disk = $this->createConfiguredMock(FilesystemAdapter::class, [
            'allDirectories' => [
                '/media/.ignore1',
                '/media/.ignore2',
                '/media/dir',
                '/media/dir/sub',
                '/media/exclude',
                '/media/hidden',
                '/media/hidden/sub1',
                '/media/hidden/sub1/deep1',
                '/media/hidden/sub2',
                '/media/hidden but not really',
                '/media/name'
            ]
        ]);

        $this->app['config']->set('system.storage.media.folder', 'media');
        $this->app['config']->set('media.ignore_files', ['hidden']);
        $this->app['config']->set('media.ignore_patterns', ['^\..*']);
        $instance = MediaLibrary::instance();
        $this->setProtectedProperty($instance, 'storageDisk', $disk);

        $this->assertEquals(['/', '/dir', '/dir/sub', '/hidden but not really', '/name'], $instance->listAllDirectories(['/exclude']));
    }
}
