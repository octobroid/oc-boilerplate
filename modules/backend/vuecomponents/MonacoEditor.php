<?php namespace Backend\VueComponents;

use Url;
use Backend\Classes\VueComponentBase;
use Backend\Models\Preference as BackendPreference;

/**
 * Monaco editor Vue component
 *
 * Dev notes.
 * - Emmet is not currently supported. Available third-party libraries did not work well. (July 2020)
 * - Automatic tag closing is not implemented. See https://github.com/microsoft/monaco-editor/issues/221
 *
 * @see https://github.com/microsoft/monaco-editor
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class MonacoEditor extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\Tabs::class
    ];

    /**
     * Adds dependency assets required for the component.
     * This method is called before the component's default resources are loaded.
     * Use $this->addJs() and $this->addCss() to register new assets to include
     * on the page.
     * @return void
     */
    protected function loadDependencyAssets()
    {
        $this->addJs('vendor/emmet-monaco-es@4.6.2/min/emmet-monaco.min.js');
        $this->addJs('vendor/monaco@0.23.0/min/vs/loader.js');
        $this->addJsBundle('js/modelreference.js', 'core');
        $this->addJsBundle('js/modeldefinition.js', 'core');
    }

    protected function prepareVars()
    {
        $preferences = BackendPreference::instance();

        $configuration = [
            'vendorPath' => Url::asset('/modules/backend/vuecomponents/monacoeditor/assets/vendor/monaco@0.23.0/min'),
            'fontSize' => $preferences->editor_font_size.'px',
            'tabSize' => $preferences->editor_tab_size,
            'renderLineHighlight' => $preferences->editor_highlight_active_line ? 'all' : 'none',
            'useTabStops' => !!$preferences->editor_use_hard_tabs,
            'renderIndentGuides' => !!$preferences->editor_display_indent_guides,
            'renderWhitespace' => $preferences->editor_show_invisibles ? 'all' : 'none',
            'autoClosingBrackets' => $preferences->editor_auto_closing ? 'languageDefined' : 'never',
            'autoClosingQuotes' => $preferences->editor_auto_closing ? 'languageDefined' : 'never',
            'hover' => ['delay' => 750]
        ];

        if (!$preferences->editor_show_gutter) {
            $configuration['lineDecorationsWidth'] = 0;
            $configuration['lineNumbersMinChars'] = 0;
            $configuration['lineNumbers'] = false;
        }

        $wordWrap = $preferences->editor_word_wrap;
        if ($wordWrap === 'off') {
            $configuration['wordWrap'] = $wordWrap;
        }
        elseif ($wordWrap === 'fluid') {
            $configuration['wordWrap'] = 'on';
        }
        else {
            $configuration['wordWrap'] = 'wordWrapColumn';
            $configuration['wordWrapColumn'] = $wordWrap;
        }

        $theme = $preferences->editor_theme;
        if ($theme === 'sqlserver') {
            $configuration['theme'] = 'vs';
        }
        elseif ($theme === 'merbivore') {
            $configuration['theme'] = 'hc-black';
        }
        else {
            $configuration['theme'] = 'vs-dark';
        }

        $this->vars['configuration'] = json_encode($configuration);
    }
}
