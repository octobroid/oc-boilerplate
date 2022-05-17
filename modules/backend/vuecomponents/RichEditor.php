<?php namespace Backend\VueComponents;

use App;
use Url;
use File;
use BackendAuth;
use Backend\Models\EditorSetting;
use Backend\Classes\VueComponentBase;

/**
 * RichEditor Vue component.
 *
 * Manages an instance of Froala Editor.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class RichEditor extends VueComponentBase
{
    /**
     * Prepares variables required by the component's partials
     */
    protected function prepareVars()
    {
        $configuration = [
            'editorLang' => $this->getValidEditorLang(),
            'useMediaManager' => BackendAuth::userHasAccess('media.manage_media'),
            'iframeStylesFile' => Url::asset('/modules/backend/vuecomponents/richeditor/assets/css/iframestyles.css'),

            'globalToolbarButtons' => $this->getGlobalButtons(),
            'allowEmptyTags' => EditorSetting::getConfigured('html_allow_empty_tags'),
            'allowTags' => EditorSetting::getConfigured('html_allow_tags'),
            'allowAttrs' => EditorSetting::getConfigured('html_allow_attrs'),
            'noWrapTags' => EditorSetting::getConfigured('html_no_wrap_tags'),
            'removeTags' => EditorSetting::getConfigured('html_remove_tags'),
            'lineBreakerTags' => EditorSetting::getConfigured('html_line_breaker_tags'),

            'imageStyles' => EditorSetting::getConfiguredStyles('html_style_image'),
            'linkStyles' => EditorSetting::getConfiguredStyles('html_style_link'),
            'paragraphStyles' => EditorSetting::getConfiguredStyles('html_style_paragraph'),
            'paragraphFormat' => EditorSetting::getConfiguredFormats('html_paragraph_formats'),
            'tableStyles' => EditorSetting::getConfiguredStyles('html_style_table'),
            'tableCellStyles' => EditorSetting::getConfiguredStyles('html_style_table_cell')
        ];

        $this->vars['configuration'] = json_encode($configuration);
    }

    /**
     * Adds component specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * The default component script and CSS file are loaded automatically.
     * @return void
     */
    protected function loadAssets()
    {
        // This Vue component uses Froala dependencies from the rich editor form widget
        //
        $this->addJs('/modules/backend/formwidgets/richeditor/assets/js/build-min.js', 'core');
        $this->addJs('/modules/backend/formwidgets/richeditor/assets/js/build-plugins-min.js', 'core');
        $this->addCss('/modules/backend/formwidgets/richeditor/assets/css/richeditor.css', 'core');

        if ($lang = $this->getValidEditorLang()) {
            $this->addJs('/modules/backend/formwidgets/richeditor/assets/vendor/froala/js/languages/'.$lang.'.js', 'core');
        }
    }

    /**
     * Returns a valid language code for Redactor.
     * @return string|mixed
     */
    protected function getValidEditorLang()
    {
        $locale = App::getLocale();

        // English is baked in
        if ($locale == 'en') {
            return null;
        }

        $locale = str_replace('-', '_', strtolower($locale));
        $path = base_path('modules/backend/formwidgets/richeditor/assets/vendor/froala/js/languages/'.$locale.'.js');

        return File::exists($path) ? $locale : false;
    }

    /**
     * getGlobalButtons
     */
    protected function getGlobalButtons()
    {
        $result = trim(EditorSetting::getConfigured('html_toolbar_buttons'));
        if (!strlen($result)) {
            return null;
        }

        $result = explode(',', $result);
        $buttons = [];
        foreach ($result as $button) {
            $buttons[] = trim($button);
        }

        return $buttons;
    }
}
