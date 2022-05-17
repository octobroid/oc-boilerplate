<?php namespace Cms\Classes;

use Lang;
use BackendAuth;
use SystemException;
use Cms\Classes\Theme;
use Cms\Classes\Asset;
use Editor\Classes\ExtensionBase;
use Backend\VueComponents\TreeView\SectionList;
use Backend\VueComponents\DropdownMenu\ItemDefinition;
use Exception;

/**
 * EditorExtension adds CMS objects to October Editor IDE
 */
class EditorExtension extends ExtensionBase
{
    const DOCUMENT_TYPE_PAGE = 'cms-page';
    const DOCUMENT_TYPE_LAYOUT = 'cms-layout';
    const DOCUMENT_TYPE_PARTIAL = 'cms-partial';
    const DOCUMENT_TYPE_CONTENT = 'cms-content';
    const DOCUMENT_TYPE_ASSET = 'cms-asset';

    use \Cms\Traits\EditorExtensionState;
    use \Cms\Traits\EditorExtensionAssetsState;
    use \Cms\Traits\EditorComponentListLoader;
    use \Cms\Traits\EditorExtensionCrud;
    use \Cms\Traits\EditorExtensionAssetsCrud;
    use \Cms\Traits\EditorIntellisense;
    use \Cms\Traits\EditorExtensionThemesState;
    use \Cms\Traits\EditorExtensionThemeCrud;
    use \Cms\Traits\EditorExtensionExtensibility;

    const ICON_COLOR_PAGE = '#6A6CF7';
    const ICON_COLOR_PARTIAL = '#9ACD43';
    const ICON_COLOR_LAYOUT = '#5FA75F';
    const ICON_COLOR_CONTENT = '#9D54A1';
    const ICON_COLOR_ASSET = '#E75252';

    const DOCUMENT_TYPE_PERMISSIONS = [
        EditorExtension::DOCUMENT_TYPE_PAGE => ['cms.manage_pages'],
        EditorExtension::DOCUMENT_TYPE_PARTIAL => ['cms.manage_partials'],
        EditorExtension::DOCUMENT_TYPE_LAYOUT => ['cms.manage_layouts'],
        EditorExtension::DOCUMENT_TYPE_CONTENT => ['cms.manage_content'],
        EditorExtension::DOCUMENT_TYPE_ASSET => ['cms.manage_assets']
    ];

    private $cachedEditTheme = false;

    /**
     * getNamespace returns unique extension namespace
     */
    public function getNamespace(): string
    {
        return 'cms';
    }

    /**
     * Returns a list of JavaScript files required for the extension.
     * @return array Returns an associative array of JavaScript file paths and attributes.
     */
    public function listJsFiles()
    {
        return [
            '/modules/cms/assets/js/cms.editor.extension.js' => 'core',
            '/modules/cms/assets/js/cms.editor.extension.documentcomponent.base.js' => 'core',
            '/modules/cms/assets/js/cms.editor.extension.documentcontroller.page.js' => 'core',
            '/modules/cms/assets/js/cms.editor.extension.documentcontroller.layout.js' => 'core',
            '/modules/cms/assets/js/cms.editor.extension.documentcontroller.partial.js' => 'core',
            '/modules/cms/assets/js/cms.editor.extension.documentcontroller.content.js' => 'core',
            '/modules/cms/assets/js/cms.editor.extension.documentcontroller.asset.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.utils.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.clickhandler.base.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.clickhandler.template.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.clickhandler.cssimports.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.base.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.partials.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.content.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.assets.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.pages.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.twigfilters.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.completer.octobertags.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.hoverprovider.base.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.hoverprovider.octobertags.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.hoverprovider.twigfilters.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.actionhandler.base.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.actionhandler.expandcomponent.js' => 'core',
            '/modules/cms/assets/js/cms.editor.intellisense.js' => 'core'
        ];
    }

    /**
     * Returns a list of language strings required by the client-side extension controller.
     * @return array
     */
    public function getClientSideLangStrings()
    {
        return [
            'cms::lang.page.editor_markup',
            'cms::lang.page.editor_code',
            'cms::lang.template.saved',
            'cms::lang.template.reloaded',
            'cms::lang.template.deleted',
            'cms::lang.editor.preview',
            'cms::lang.editor.page',
            'cms::lang.editor.partial',
            'cms::lang.editor.asset',
            'cms::lang.page.no_layout',
            'cms::lang.editor.info',
            'cms::lang.template.storage_filesystem',
            'cms::lang.template.storage_database',
            'cms::lang.template.last_modified',
            'cms::lang.template.storage',
            'cms::lang.template.template_file',
            'cms::lang.template.saved_to_db',
            'cms::lang.template.update_file',
            'cms::lang.template.reset_from_file',
            'cms::lang.editor.component_list',
            'cms::lang.template.file_updated',
            'cms::lang.template.reset_from_template_success',
            'cms::lang.intellisense.learn_more',
            'cms::lang.component.expand_partial',
            'cms::lang.content.editor_content',
            'cms::lang.asset.rename',
            'cms::lang.asset.delete',
            'cms::lang.asset.new',
            'cms::lang.asset.delete_confirm',
            'cms::lang.asset.delete_confirm_single',
            'cms::lang.asset.moving',
            'cms::lang.asset.moved',
            'cms::lang.asset.saved',
            'cms::lang.asset.deleted',
            'cms::lang.asset.upload_files',
            'cms::lang.asset.open',
            'cms::lang.asset.create_directory',
            'cms::lang.editor.change_edit_theme',
            'cms::lang.editor.edit_theme_saved_changed_tabs',
            'cms::lang.theme.setting_edit_theme',
            'cms::lang.theme.edit_theme_changed',
            'backend::lang.form.save',
            'backend::lang.form.delete',
        ];
    }

    /**
     * Returns a list of Vue components required for the extension.
     * @return array Array of Vue component class names
     */
    public function listVueComponents()
    {
        return [
            // Important - load modules that use `define()` before Monaco. Monaco pollutes
            // the global space and breaks `define()`.
            // See https://github.com/microsoft/vscode-loader/issues/19#issuecomment-439810640
            //
            \Backend\VueComponents\DocumentMarkdownEditor::class,
            \Backend\VueComponents\RichEditorDocumentConnector::class,

            \Cms\VueComponents\PageEditor::class,
            \Cms\VueComponents\PartialEditor::class,
            \Cms\VueComponents\LayoutEditor::class,
            \Cms\VueComponents\ContentEditor::class,
            \Cms\VueComponents\AssetEditor::class,
            \Cms\VueComponents\CmsComponentListPopup::class
        ];
    }

    /**
     * Initializes extension's sidebar Navigator sections.
     */
    public function listNavigatorSections(SectionList $sectionList, $documentType = null)
    {
        $user = BackendAuth::getUser();

        $editTheme = $this->getTheme();
        $sectionTitle = 'CMS';
        if ($editTheme) {
            $sectionTitle .= ' - '.$editTheme->getConfigValue('name', $editTheme->getDirName());
        }
        else {
            $sectionTitle .= ' - '.Lang::get('cms::lang.theme.no_themes_found');
        }

        $cmsSection = $sectionList->addSection($sectionTitle, 'cms');

        if (!$editTheme) {
            return;
        }

        $this->addSectionMenuItems($cmsSection);

        if (EditorExtension::hasAccessToDocType($user, self::DOCUMENT_TYPE_PAGE)
            && (!$documentType || $documentType === self::DOCUMENT_TYPE_PAGE)) {
            $this->addPagesNavigatorNodes($this->getTheme(), $cmsSection);
        }

        if (EditorExtension::hasAccessToDocType($user, self::DOCUMENT_TYPE_PARTIAL)
            && (!$documentType || $documentType === self::DOCUMENT_TYPE_PARTIAL)) {
            $this->addPartialsNavigatorNodes($this->getTheme(), $cmsSection);
        }

        if (EditorExtension::hasAccessToDocType($user, self::DOCUMENT_TYPE_LAYOUT)
            && (!$documentType || $documentType === self::DOCUMENT_TYPE_LAYOUT)) {
            $this->addLayoutsNavigatorNodes($this->getTheme(), $cmsSection);
        }

        if (EditorExtension::hasAccessToDocType($user, self::DOCUMENT_TYPE_CONTENT)
            && (!$documentType || $documentType === self::DOCUMENT_TYPE_CONTENT)) {
            $this->addContentNavigatorNodes($this->getTheme(), $cmsSection);
        }

        if (EditorExtension::hasAccessToDocType($user, self::DOCUMENT_TYPE_ASSET)
            && (!$documentType || $documentType === self::DOCUMENT_TYPE_ASSET)) {
            $this->addAssetsNavigatorNodes($this->getTheme(), $cmsSection);
        }
    }

    public function listInspectorConfigurations()
    {
        return $this->loadAndLocalizeJsonFile(__DIR__.'/editorextension/inspector-configs.json');
    }

    /**
     * getCustomData returns custom state data required for the extension client-side controller
     */
    public function getCustomData(): array
    {
        $user = BackendAuth::getUser();
        $theme = $this->getTheme();

        return [
            'layouts' => $this->loadLayoutsForUiLists($theme, $user),
            'partials' => $this->loadPartialsForUiLists($theme, $user),
            'assets' => $this->loadAssetsForUiLists($theme, $user),
            'pages' => $this->loadPagesForUiLists($theme, $user),
            'content' => $this->loadContentForUiLists($theme, $user),
            'components' => $this->loadComponentsForUiLists(),
            'canManagePartials' => $user->hasAnyAccess(['cms.manage_partials']),
            'canManagePages' => $user->hasAnyAccess(['cms.manage_pages']),
            'canManageAssets' => $user->hasAnyAccess(['cms.manage_assets']),
            'canManageContent' => $user->hasAnyAccess(['cms.manage_content']),
            'editableAssetExtensions' => Asset::getEditableExtensions(),
            'databaseTemplatesEnabled' => $theme ? $theme->secondLayerEnabled() : false,
            'assetExtensionList' => $this->getAssetExtensionListInitialState(),
            'intellisense' => [
                'octoberTags' => $this->intellisenseLoadOctoberTags(),
                'twigFilters' => $this->intellisenseLoadTwigFilters()
            ],
            'theme' => $theme ? $theme->getDirName() : null,
            'customToolbarSettingsButtons' => $this->getToolbarCustomSettingsButtons()
        ];
    }

    public function getNewDocumentsData()
    {
        return [
            EditorExtension::DOCUMENT_TYPE_PAGE => $this->getCmsPageNewDocumentData(),
            EditorExtension::DOCUMENT_TYPE_PARTIAL => $this->getCmsPartialNewDocumentData(),
            EditorExtension::DOCUMENT_TYPE_LAYOUT => $this->getCmsLayoutNewDocumentData(),
            EditorExtension::DOCUMENT_TYPE_CONTENT => $this->getCmsContentNewDocumentData(),
            EditorExtension::DOCUMENT_TYPE_ASSET => $this->getCmsAssetNewDocumentData()
        ];
    }

    public function getSettingsForms()
    {
        return [
            EditorExtension::DOCUMENT_TYPE_PAGE => $this->loadAndExtendCmsSettingsFile(__DIR__, 'page'),
            EditorExtension::DOCUMENT_TYPE_PARTIAL => $this->loadAndExtendCmsSettingsFile(__DIR__, 'partial'),
            EditorExtension::DOCUMENT_TYPE_LAYOUT => $this->loadAndExtendCmsSettingsFile(__DIR__, 'layout')
        ];
    }

    public static function hasAccessToDocType($user, $documentType)
    {
        if (!array_key_exists($documentType, EditorExtension::DOCUMENT_TYPE_PERMISSIONS)) {
            throw new SystemException(sprintf('Unknown document type: %s', $documentType));
        }

        return $user->hasAnyAccess(EditorExtension::DOCUMENT_TYPE_PERMISSIONS[$documentType]);
    }

    /**
     * getTheme returns the theme object to use for the editor
     */
    protected function getTheme()
    {
        if ($this->cachedEditTheme !== false) {
            return $this->cachedEditTheme;
        }

        // Locate edit theme
        try {
            if ($editTheme = Theme::getEditTheme()) {
                return $this->cachedEditTheme = $editTheme;
            }
        }
        catch (Exception $ex) {}

        // Locate active theme
        try {
            if ($activeTheme = Theme::getActiveTheme()) {
                return $this->cachedEditTheme = $activeTheme;
            }
        }
        catch (Exception $ex) {}

        // Use first theme
        $themes = Theme::all();
        foreach ($themes as $theme) {
            return $this->cachedEditTheme = $theme;
        }

        // Nothing
        return $this->cachedEditTheme = null;
    }

    private function addSectionMenuItems($section)
    {
        $user = BackendAuth::getUser();

        $section->addMenuItem(ItemDefinition::TYPE_TEXT, Lang::get('cms::lang.editor.refresh'), 'cms:refresh-navigator');

        $createMenuItem = new ItemDefinition(ItemDefinition::TYPE_TEXT, Lang::get('cms::lang.editor.create'), 'cms:create');
        $menuConfiguration = [
            'cms.manage_pages' => [
                'label' => 'cms::lang.editor.page',
                'document' => EditorExtension::DOCUMENT_TYPE_PAGE
            ],
            'cms.manage_partials' => [
                'label' => 'cms::lang.editor.partial',
                'document' => EditorExtension::DOCUMENT_TYPE_PARTIAL
            ],
            'cms.manage_layouts' => [
                'label' => 'cms::lang.editor.layout',
                'document' => EditorExtension::DOCUMENT_TYPE_LAYOUT
            ],
            'cms.manage_content' => [
                'label' => 'cms::lang.editor.content',
                'document' => EditorExtension::DOCUMENT_TYPE_CONTENT
            ],
            'cms.manage_assets' => [
                'label' => 'cms::lang.editor.asset',
                'document' => EditorExtension::DOCUMENT_TYPE_ASSET
            ]
        ];

        foreach ($menuConfiguration as $permission=>$itemConfig) {
            if (!$user->hasAnyAccess([$permission])) {
                continue;
            }

            $createMenuItem->addItemObject(
                $section->addCreateMenuItem(
                    ItemDefinition::TYPE_TEXT,
                    Lang::get($itemConfig['label']),
                    'cms:create-document@'.$itemConfig['document']
                )
            );
        }

        if ($createMenuItem->hasItems()) {
            $section->addMenuItemObject($createMenuItem);
        }

        $this->createCmsSectionThemeMenuItems($section);
    }

    /**
     * getAssetsPath returns the asset path for a theme
     * @param $theme Theme
     * @return string
     */
    public function getAssetsPath($theme = null)
    {
        if (!$theme) {
            $theme = Theme::getActiveTheme();
        }

        return $theme->getPath() . '/assets';
    }
}
