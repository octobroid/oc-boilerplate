<?php namespace System\Console;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;

/**
 * ComposerScript is a collection of composer script logic
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ComposerScript
{
    /**
     * postAutoloadDump
     */
    public static function postAutoloadDump(Event $event)
    {
        self::clearMeta();

        passthru(PHP_BINARY.' artisan package:discover');
    }

    /**
     * postUpdateCmd occurs after the update command has been executed, or after
     * the install command has been executed without a lock file present.
     */
    public static function postUpdateCmd(Event $event)
    {
        passthru(PHP_BINARY.' artisan october:util set build');

        passthru(PHP_BINARY.' artisan october:mirror --composer');
    }

    /**
     * prePackageUninstall occurs before a package is uninstalled
     */
    public static function prePackageUninstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();

        if (self::isOfType($package, 'plugin')) {
            passthru(PHP_BINARY." artisan plugin:remove ${package} --composer");
        }
    }

    /**
     * isOfType checks if a package is a plugin or theme
     *
     * rainlab-vanilla-theme dev-master, theme -> true
     */
    protected static function isOfType(string $package, string $type): bool
    {
        if (substr($package, -strlen('-'.$type)) === (string) '-'.$type) {
            return true;
        }

        if (strpos($package, '-'.$type.'-') !== false) {
            return true;
        }

        return false;
    }

    /**
     * clearMeta purges meta files (discovered package cache, etc) to prevent errors
     */
    protected static function clearMeta()
    {
        $metaFiles = [
            'storage/framework/packages.php',
            'storage/framework/classes.php',
            'storage/framework/services.php',
            'storage/cms/manifest.php'
        ];

        foreach ($metaFiles as $filePath) {
            if (file_exists($packagesMeta = __DIR__ . '/../../../'.$filePath)) {
                @unlink($packagesMeta);
            }
        }
    }
}
