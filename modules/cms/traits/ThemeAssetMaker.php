<?php namespace Cms\Traits;

use Url;
use File;
use Config;
use System\Classes\CombineAssets;

/**
 * ThemeAssetMaker adds theme-based asset methods to a class
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
trait ThemeAssetMaker
{
    use \System\Traits\AssetMaker;

    /**
     * combineAssets
     * @inheritDoc
     */
    public function combineAssets(array $assets, $localPath = ''): string
    {
        if (empty($assets)) {
            return '';
        }

        $assetPath = $localPath ?: $this->assetLocalPath;

        return Url::to(CombineAssets::combine(
            $this->getMultipleThemeAssetPaths($assets),
            $assetPath
        ));
    }

    /**
     * getAssetPath
     * @inheritDoc
     */
    public function getAssetPath($fileName, $assetPath = null)
    {
        if (starts_with($fileName, ['//', 'http://', 'https://'])) {
            return $fileName;
        }

        if (!$assetPath) {
            $assetPath = $this->assetPath;
        }

        if (substr($fileName, 0, 1) == '/' || $assetPath === null) {
            return $fileName;
        }

        return $this->getThemeAssetPath($fileName);
    }

    /**
     * getMultipleThemeAssetPaths checks combiner paths in the theme
     * and rewrites them to parent assets, if necessary
     */
    protected function getMultipleThemeAssetPaths(array $urls): array
    {
        $theme = $this->getTheme();

        if (!$theme->hasParentTheme()) {
            return $urls;
        }

        foreach ($urls as &$url) {
            // Combiner alias
            if (substr($url, 0, 1) === '@') {
                continue;
            }

            // Path symbol
            if (File::isPathSymbol($url)) {
                continue;
            }

            // Fully qualified local path
            if (file_exists($url)) {
                continue;
            }

            // Parent asset
            if ($theme->useParentAsset($url)) {
                $url = $theme->getParentTheme()->getPath().'/'.$url;
            }
        }

        return $urls;
    }

    /**
     * getThemeAssetPath returns the public directory for theme assets
     */
    protected function getThemeAssetPath(string $relativePath = null): string
    {
        // Determine directory name for asset
        $theme = $this->getTheme();
        $dirName = $theme->getDirName();

        if (
            $relativePath !== null &&
            $theme->useParentAsset($relativePath) &&
            ($parentTheme = $theme->getParentTheme())
        ) {
            $dirName = $parentTheme->getDirName();
        }

        // Configuration for theme asset location
        $assetUrl = Config::get('system.themes_asset_url');

        if (!$assetUrl) {
            $assetUrl = Config::get('app.asset_url').'/themes';
        }

        // Build path
        $path = $assetUrl . '/' . $dirName;

        if ($relativePath !== null) {
            $path = $assetUrl . '/' . $dirName . '/' . $relativePath;
        }

        return $path;
    }
}
