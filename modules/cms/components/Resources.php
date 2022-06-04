<?php namespace Cms\Components;

use File;
use Cms\Classes\ComponentBase;

/**
 * Resources component
 */
class Resources extends ComponentBase
{
    /**
     * @var string jsDir for JavaScript files.
     */
    public $jsDir = 'js';

    /**
     * @var string cssDir for CSS files.
     */
    public $cssDir = 'css';

    /**
     * @var string lessDir for LESS files.
     */
    public $lessDir = 'less';

    /**
     * @var string scssDir for SCSS files.
     */
    public $scssDir = 'scss';

    /**
     * componentDetails
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name' => 'Resources',
            'description' => 'Reference assets and variables included on this page.',
        ];
    }

    /**
     * defineProperties
     * @return array
     */
    public function defineProperties()
    {
        return [
            'js' => [
                'title' => 'JavaScript',
                'description' => 'JavaScript file(s) in the assets/js folder',
                'type' => 'stringList',
                'showExternalParam' => false
            ],
            'less' => [
                'title' => 'LESS',
                'description' => 'LESS file(s) in the assets/less folder',
                'type' => 'stringList',
                'showExternalParam' => false
            ],
            'scss' => [
                'title' => 'SCSS',
                'description' => 'SCSS file(s) in the assets/scss folder',
                'type' => 'stringList',
                'showExternalParam' => false
            ],
            'css' => [
                'title' => 'CSS',
                'description' => 'Stylesheet file(s) in the assets/css folder',
                'type' => 'stringList',
                'showExternalParam' => false
            ],
            'vars' => [
                'title' => 'Variables',
                'description' => 'Page variables name(s) and value(s)',
                'type' => 'dictionary',
                'showExternalParam' => false
            ],
            'headers' => [
                'title' => 'Headers',
                'description' => 'Page header name(s) and value(s)',
                'type' => 'dictionary',
                'showExternalParam' => false
            ]
        ];
    }

    /**
     * init
     */
    public function init()
    {
        $this->assetPath = $this->controller->assetPath;
        $this->assetLocalPath = $this->controller->assetLocalPath;
        $this->jsDir = $this->guessAssetDirectory(['js', 'javascript'], $this->jsDir);
        $this->scssDir = $this->guessAssetDirectory(['scss', 'sass'], $this->scssDir);
    }

    /**
     * onRun
     */
    public function onRun()
    {
        // JavaScript
        if ($assets = $this->property('js')) {
            foreach ((array) $assets as $asset) {
                $this->controller->addJsBundle($this->prefixJs($asset), 'cms-js');
            }
        }

        // LESS
        if ($assets = $this->property('less')) {
            foreach ((array) $assets as $asset) {
                $this->controller->addCssBundle($this->prefixLess($asset), 'cms-less');
            }
        }

        // SCSS
        if ($assets = $this->property('scss')) {
            foreach ((array) $assets as $asset) {
                $this->controller->addCssBundle($this->prefixScss($asset), 'cms-scss');
            }
        }

        // CSS
        if ($assets = $this->property('css')) {
            foreach ((array) $assets as $asset) {
                $this->controller->addCssBundle($this->prefixCss($asset), 'cms-css');
            }
        }

        // Variables and Headers
        $this->controller->bindEvent('page.beforeRenderPage', function ($page) {
            if ($vars = $this->property('vars')) {
                foreach ((array) $vars as $key => $value) {
                    $this->page[$key] = $value;
                }
            }

            if ($headers = $this->property('headers')) {
                foreach ((array) $headers as $key => $value) {
                    $this->controller->setResponseHeader($key, $value);
                }
            }
        });
    }

    /**
     * prefixJs
     */
    protected function prefixJs($value)
    {
        return 'assets/'.$this->jsDir.'/'.trim($value);
    }

    /**
     * prefixCss
     */
    protected function prefixCss($value)
    {
        return 'assets/'.$this->cssDir.'/'.trim($value);
    }

    /**
     * prefixLess
     */
    protected function prefixLess($value)
    {
        return 'assets/'.$this->lessDir.'/'.trim($value);
    }

    /**
     * prefixScss
     */
    protected function prefixScss($value)
    {
        return 'assets/'.$this->scssDir.'/'.trim($value);
    }

    /**
     * guessAssetDirectory determines an inner asset directory, eg: scss or sass
     */
    protected function guessAssetDirectory(array $possible, $default = null)
    {
        $themeDir = $this->getTheme()->getDirName();
        foreach ($possible as $option) {
            if (File::isDirectory(themes_path($themeDir.'/assets/'.$option))) {
                return $option;
            }
        }

        return $default;
    }
}
