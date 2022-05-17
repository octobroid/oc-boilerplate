<?php namespace Cms\Traits;

use Lang;
use Config;
use Request;
use BackendAuth;
use SystemException;
use ApplicationException;
use Cms\Classes\Page;
use Cms\Classes\Asset;
use Cms\Classes\Layout;
use Cms\Classes\Partial;
use Cms\Classes\Content;
use Cms\Classes\Router;
use Cms\Classes\CmsCompoundObject;
use Cms\Classes\ComponentManager;
use Cms\Classes\ComponentPartial;
use Cms\Classes\EditorExtension;
use October\Rain\Halcyon\Model as HalcyonModel;
use October\Rain\Router\Router as RainRouter;
use Editor\Classes\ApiHelpers;

/**
 * EditorExtensionCrud implements CRUD operations for the CMS Editor Extension
 */
trait EditorExtensionCrud
{
    protected function command_onOpenDocument()
    {
        $documentData = post('documentData');
        if (!is_array($documentData)) {
            throw new SystemException('Document data is not provided');
        }

        $key = ApiHelpers::assertGetKey($documentData, 'key');
        $documentType = ApiHelpers::assertGetKey($documentData, 'type');
        $this->assertDocumentTypePermissions($documentType);

        $extraData = $this->getRequestExtraData();

        $isResetFromTemplateFileRequest = isset($extraData['resetFromTemplateFile']);
        if ($isResetFromTemplateFileRequest) {
            $this->resetFromTemplateFile($documentType, $key);
        }

        $template = $this->loadTemplate($documentType, $key);
        if ($documentType !== EditorExtension::DOCUMENT_TYPE_ASSET) {
            $templateData = $template->toArray();
        }
        else {
            $templateData = [
                'content' => $template->content
            ];
        }

        $templateData['fileName'] = ltrim($template->getFileName(), '/');

        if ($template instanceof CmsCompoundObject) {
            $templateData['components'] = $this->loadTemplateComponents($template);
        }

        if ($template instanceof Layout && isset($templateData['settings']['description'])) {
            $templateData['description'] = $templateData['settings']['description'];
        }

        $templateData = $this->handleEmptyValuesOnLoad($template, $templateData);

        $result = [
            'document' => $templateData,
            'metadata' => $this->loadTemplateMetadata($template, $documentData)
        ];

        if ($documentType == EditorExtension::DOCUMENT_TYPE_PAGE) {
            $result['previewUrl'] = $this->getPagePreviewUrl($template);
        }

        return $result;
    }

    protected function command_onSaveDocument()
    {
        $documentData = $this->getRequestDocumentData();
        $metadata = $this->getRequestMetadata();
        $extraData = $this->getRequestExtraData();

        $isUpdateTemplateRequest = isset($extraData['updateTemplateFile']);

        $this->validateRequestTheme($metadata);

        $documentType = ApiHelpers::assertGetKey($metadata, 'type');
        $this->assertDocumentTypePermissions($documentType);

        $templatePath = trim(ApiHelpers::assertGetKey($metadata, 'path'));
        $template = $this->loadOrCreateTemplate($documentType, $templatePath);
        $templateData = [];

        if ($isUpdateTemplateRequest) {
            return $this->updateTemplateFile($template, $documentType, $templatePath);
        }

        $settings = $this->upgradeSettings($documentData);
        if ($settings) {
            $templateData['settings'] = $settings;
        }

        $fields = ['markup', 'code', 'fileName', 'content'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $documentData)) {
                $templateData[$field] = $documentData[$field];
            }
        }

        $templateData = $this->handleLineEndings($templateData);
        $templateData = $this->handleEmptyValuesOnSave($template, $templateData);

        if ($response = $this->handleMtimeMismatch($template, $metadata)) {
            return $response;
        }

        if (!$template instanceof Asset) {
            $template->attributes = [];
        }

        $template->fill($templateData);

        // Call validate() explicitly because of
        // the `force` flag in save().
        //
        $template->validate();

        // Forcing the operation is required. Failing to
        // do so results in components removed in the UI
        // to persist in the template if there are no
        // other changed attributes.
        //
        $template->save(['force' => true]);

        /**
         * @event cms.template.save
         * Fires after a CMS template (page|partial|layout|content|asset) has been saved.
         *
         * Example usage:
         *
         *     Event::listen('cms.template.save', function ((\Cms\Classes\EditorExtension) $editorExtension, (mixed) $templateObject, (string) $type) {
         *         \Log::info("A $type has been saved");
         *     });
         */
        $this->fireSystemEvent('cms.template.save', [$template, $documentType]);

        return $this->getUpdateResponse($template, $documentType, $templateData);
    }

    protected function command_onDeleteDocument()
    {
        [$template, $documentType] = $this->loadRequestedTemplate();
        $this->assertDocumentTypePermissions($documentType);

        $template->delete();

        /**
         * @event cms.template.delete
         * Fires after a CMS template (page|partial|layout|content|asset) has been deleted.
         *
         * Example usage:
         *
         *     Event::listen('cms.template.delete', function ((\Cms\Classes\EditorExtension) $editorExtension, (string) $type) {
         *         \Log::info("A $type has been deleted");
         *     });
         */
        $this->fireSystemEvent('cms.template.delete', [$documentType]);
    }

    protected function command_onExpandCmsComponent()
    {
        if (!$componentAlias = post('componentAlias')) {
            throw new ApplicationException(trans('cms::lang.component.no_records'));
        }

        $documentData = $this->getRequestDocumentData();
        $componentClass = $this->findComponentClassByAlias($componentAlias, $documentData);

        if (!$componentClass) {
            throw new ApplicationException(trans('cms::lang.component.not_found', ['name' => $componentAlias]));
        }

        $manager = ComponentManager::instance();
        $componentObj = $manager->makeComponent($componentClass);
        $partial = ComponentPartial::load($componentObj, 'default');
        $content = $partial->getContent();
        $content = str_replace('__SELF__', $componentAlias, $content);

        return [
            'content' => $content
        ];
    }

    /**
     * Returns an existing template of a given type
     * @param string $documentType
     * @param string $path
     * @return mixed
     */
    private function loadTemplate($documentType, $path)
    {
        $class = $this->resolveTypeClassName($documentType);

        if (!($template = call_user_func([$class, 'load'], $this->getTheme(), $path))) {
            throw new ApplicationException(trans('cms::lang.template.not_found'));
        }

        /**
         * @event cms.template.processSettingsAfterLoad
         * Fires immediately after a CMS template (page|partial|layout|content|asset) has been loaded and provides an opportunity to interact with it.
         *
         * Example usage:
         *
         *     Event::listen('cms.template.processSettingsAfterLoad', function ((\Cms\Classes\EditorExtension) $extension, (mixed) $templateObject) {
         *         // Make some modifications to the $template object
         *     });
         */
        $this->fireSystemEvent('cms.template.processSettingsAfterLoad', [$template, 'editor']);

        return $template;
    }

    /**
     * Resolves a template type to its class name
     * @param string $documentType
     * @return string
     */
    private function resolveTypeClassName($documentType)
    {
        $types = [
            EditorExtension::DOCUMENT_TYPE_PAGE     => Page::class,
            EditorExtension::DOCUMENT_TYPE_PARTIAL  => Partial::class,
            EditorExtension::DOCUMENT_TYPE_LAYOUT   => Layout::class,
            EditorExtension::DOCUMENT_TYPE_CONTENT  => Content::class,
            EditorExtension::DOCUMENT_TYPE_ASSET    => Asset::class
        ];

        if (!array_key_exists($documentType, $types)) {
            throw new SystemException(trans('cms::lang.template.invalid_type'));
        }

        return $types[$documentType];
    }

    /**
     * makeMetadataForNewTemplate builds meta data for new templates
     */
    protected function makeMetadataForNewTemplate(string $documentType): array
    {
        return [
            'mtime' => null,
            'path' => null,
            'theme' => ($theme = $this->getTheme()) ? $theme->getDirName() : null,
            'type' => $documentType,
            'isNewDocument' => true
        ];
    }

    private function loadTemplateMetadata($template, $documentData)
    {
        $theme = $this->getTheme();
        $themeDirName = $theme->getDirName();

        $typeNames = [
            EditorExtension::DOCUMENT_TYPE_PAGE => Lang::get('cms::lang.editor.page'),
            EditorExtension::DOCUMENT_TYPE_LAYOUT => Lang::get('cms::lang.editor.layout'),
            EditorExtension::DOCUMENT_TYPE_PARTIAL => Lang::get('cms::lang.editor.partial'),
            EditorExtension::DOCUMENT_TYPE_CONTENT => Lang::get('cms::lang.editor.content'),
            EditorExtension::DOCUMENT_TYPE_ASSET => Lang::get('cms::lang.editor.asset')
        ];

        $documentType = $documentData['type'];
        if (!array_key_exists($documentType, $typeNames)) {
            throw new SystemException(sprintf('Document type name is not defined: %s', $documentData['type']));
        }

        $typeDirName = $this->getDocumentTypeDirName($template);
        $fileName = ltrim($template->fileName, '/');

        $result = [
            'mtime' => $template->mtime,
            'path' => $fileName,
            'theme' => $themeDirName,
            'canUpdateTemplateFile' => $this->canUpdateTemplateFile($template),
            'canResetFromTemplateFile' => $this->canResetFromTemplateFile($template),
            'fullPath' => $typeDirName.'/'.$fileName,
            'type' => $documentType,
            'typeName' => $typeNames[$documentType]
        ];

        return $result;
    }

    /**
     * canUpdateTemplateFile returns true if the template file can be updated with the database
     * content. Only available in debug mode, the database templates must be enabled, and the
     * template must exist in the database.
     */
    protected function canUpdateTemplateFile($template): bool
    {
        if (!Config::get('app.debug', false)) {
            return false;
        }

        if (!$template instanceof HalcyonModel) {
            return false;
        }

        if (!$this->getTheme()->secondLayerEnabled()) {
            return false;
        }

        return $this->getThemeDatasource()->hasModelAtIndex(1, $template);
    }

    /**
     * updateTemplateFile
     */
    protected function updateTemplateFile($template, $documentType, $templatePath)
    {
        if (!$this->canUpdateTemplateFile($template)) {
            throw new ApplicationException('The template cannot be updated.');
        }

        // Update second layer, then delete first layer
        $datasource = $this->getThemeDatasource();
        $datasource->updateModelAtIndex(1, $template);
        $datasource->forceDeleteModelAtIndex(0, $template);

        $template = $this->loadTemplate($documentType, $templatePath);
        return [
            'metadata' => $this->loadTemplateMetadata($template, ['type'=>$documentType]),
            'templateFileUpdated' => true
        ];
    }

    /**
     * canResetFromTemplateFile returns true if the database template can be reloaded from the
     * template file. Only available when the database templates are enabled, and the template
     * exists in both the database and filesystem.
     */
    protected function canResetFromTemplateFile($template): bool
    {
        if (!$template instanceof HalcyonModel) {
            return false;
        }

        if (!$this->getTheme()->secondLayerEnabled()) {
            return false;
        }

        $datasource = $this->getThemeDatasource();
        return $datasource->hasModelAtIndex(0, $template) &&
            $datasource->hasModelAtIndex(1, $template);
    }

    /**
     * resetFromTemplateFile
     */
    protected function resetFromTemplateFile($documentType, $templatePath)
    {
        $template = $this->loadTemplate($documentType, $templatePath);
        if (!$this->canResetFromTemplateFile($template)) {
            throw new ApplicationException('Cannot reset template from file.');
        }

        // Delete first layer
        $datasource = $this->getThemeDatasource();
        $datasource->forceDeleteModelAtIndex(0, $template);
    }

    /**
     * getThemeDatasource returns a theme datasource object
     */
    protected function getThemeDatasource()
    {
        return $this->getTheme()->getDatasource();
    }

    private function getRequestMetadata()
    {
        $metadata = Request::input('documentMetadata');
        if (!is_array($metadata)) {
            throw new SystemException('Invalid documentMetadata');
        }

        return $metadata;
    }

    private function getRequestExtraData()
    {
        $extraData = Request::input('extraData');
        if (!is_array($extraData)) {
            return [];
        }

        return $extraData;
    }

    private function getRequestDocumentData()
    {
        $documentData = Request::input('documentData');
        if (!is_array($documentData)) {
            throw new SystemException('Invalid documentData');
        }

        return $documentData;
    }

    private function createTemplate($documentType)
    {
        $class = $this->resolveTypeClassName($documentType);

        if (!($template = $class::inTheme($this->getTheme()))) {
            throw new ApplicationException(trans('cms::lang.template.not_found'));
        }

        return $template;
    }

    private function loadOrCreateTemplate($documentType, $templatePath)
    {
        if ($templatePath) {
            return $this->loadTemplate($documentType, $templatePath);
        }

        return $this->createTemplate($documentType);
    }

    /**
     * Processes the component settings so they are ready to be saved
     * @param array $settings
     * @return array
     */
    private function upgradeSettings($documentData)
    {
        $settings = array_key_exists('settings',  $documentData)
            ?  $documentData['settings']
            : [];

        if (array_key_exists('components', $documentData)) {
            $components = ApiHelpers::assertIsArray($documentData['components']);
            foreach ($components as $component) {
                $component = ApiHelpers::assertIsArray($component);
                $name = ApiHelpers::assertGetKey($component, 'name');
                $alias = ApiHelpers::assertGetKey($component, 'alias');
                $propertyValues = ApiHelpers::assertGetKey($component, 'propertyValues');

                $properties = json_decode($propertyValues, true);
                unset($properties['oc.alias'],
                    $properties['inspectorProperty'],
                    $properties['inspectorClassName']
                );

                $section = $name;
                if ($alias != $name) {
                    $section .= ' '.$alias;
                }

                $settings[$section] = $properties;
            }
        }

        /*
         * Handle view bag
         */
        if (isset($documentData['viewBag'])) {
            $settings['viewBag'] = $documentData['viewBag'];
        }

        if (isset($settings['viewBag']) && count($settings['viewBag']) === 0) {
            unset($settings['viewBag']);
        }

        /*
         * This fixes a problem where a partial with PHP code
         * and Twig markup is saved without any section data.
         * This creates a template with the PHP code defined
         * in the first section, which is expected to be INI.
         */
        if (isset($documentData['code']) && strlen($documentData['code']) && !$settings) {
            $settings['viewBag'] = [];
        }

        /**
         * @event cms.template.processSettingsBeforeSave
         * Fires before a CMS template (page|partial|layout|content|asset) is saved and provides an opportunity to interact with the settings data. `$dataHolder` = {settings: []}
         *
         * Example usage:
         *
         *     Event::listen('cms.template.processSettingsBeforeSave', function ((\Cms\Classes\EditorExtension) $editorExtension, (object) $dataHolder) {
         *         // Make some modifications to the $dataHolder object
         *     });
         */
        $dataHolder = (object) ['settings' => $settings];
        $this->fireSystemEvent('cms.template.processSettingsBeforeSave', [$dataHolder]);

        return $dataHolder->settings;
    }

    /**
     * Validate that the current request is within the active theme
     * @return void
     */
    private function validateRequestTheme($metadata)
    {
        if ($this->getTheme()->getDirName() != $metadata['theme']) {
            throw new ApplicationException(trans('cms::lang.theme.edit.not_match'));
        }
    }

    private function handleLineEndings($templateData)
    {
        $convertLineEndings = Config::get('system.convert_line_endings', false) === true;
        if (!$convertLineEndings) {
            return $templateData;
        }

        if (!empty($templateData['markup'])) {
            $templateData['markup'] = $this->convertLineEndings($templateData['markup']);
        }

        if (!empty($templateData['code'])) {
            $templateData['code'] = $this->convertLineEndings($templateData['code']);
        }

        return $templateData;
    }

    private function handleEmptyValuesOnSave($template, $templateData)
    {
        if ($template instanceof Page || $template instanceof Layout || $template instanceof Partial) {
            if (!array_key_exists('components', $templateData)) {
                $templateData['components'] = [];
            }
        }

        return $templateData;
    }

    private function handleEmptyValuesOnLoad($template, $templateData)
    {
        if ($template instanceof Page || $template instanceof Layout || $template instanceof Partial) {
            // On the client side empty markup and code values
            // are strings. By converting nulls to strings we
            // avoid the false positive "document changed" state
            // in the editor.
            $properties = ['markup', 'code'];
            foreach ($properties as $property) {
                if (array_key_exists($property, $templateData) && $templateData[$property] === null) {
                    $templateData[$property] = '';
                }
            }
        }

        return $templateData;
    }

    /**
     * Replaces Windows style (/r/n) line endings with unix style (/n)
     * line endings.
     * @param string $markup The markup to convert to unix style endings
     * @return string
     */
    private function convertLineEndings($markup)
    {
        $markup = str_replace(["\r\n", "\r"], "\n", $markup);

        return $markup;
    }

    private function handleMtimeMismatch($template, $metadata)
    {
        $requestMtime = ApiHelpers::assertGetKey($metadata, 'mtime');

        if (!$template->mtime) {
            return;
        }

        if (post('documentForceSave')) {
            return;
        }

        if ($requestMtime != $template->mtime) {
            return ['mtimeMismatch' => true];
        }
    }

    private function getUpdateResponse($template, $documentType, $templateData)
    {
        $navigatorPath = dirname($template->fileName);
        if ($navigatorPath == '.') {
            $navigatorPath = "";
        }

        if ($template instanceof Page) {
            $theme = $this->getTheme();
            $router = new Router($theme);
            $router->clearCache();
            CmsCompoundObject::clearCache($theme);
        }

        $typeDirName = $this->getDocumentTypeDirName($template);

        $result = [
            'metadata' => [
                'mtime' => $template->mtime,
                'path' => $template->fileName,
                'fullPath' => $typeDirName.'/'.$template->fileName,
                'navigatorPath' => $navigatorPath,
                'uniqueKey' => $template->getFileName(),
                'canUpdateTemplateFile' => $this->canUpdateTemplateFile($template),
                'canResetFromTemplateFile' => $this->canResetFromTemplateFile($template)
            ]
        ];

        if ($documentType == EditorExtension::DOCUMENT_TYPE_PAGE) {
            $result['previewUrl'] = $this->getPagePreviewUrl($template, $templateData);
        }

        return $result;
    }

    private function getPagePreviewUrl($template, $templateData = null)
    {
        $router = new RainRouter();
        $url = isset($templateData['settings']['url']) ?
            $templateData['settings']['url'] :
            $template->url;

        return $router->urlFromPattern($url);
    }

    private function loadRequestedTemplate()
    {
        $metadata = $this->getRequestMetadata();

        $documentType = ApiHelpers::assertGetKey($metadata, 'type');
        $templatePath = trim(ApiHelpers::assertGetKey($metadata, 'path'));

        return [
            $this->loadTemplate($documentType, $templatePath),
            $documentType
        ];
    }

    private function findComponentClassByAlias($componentAlias, $documentData)
    {
        $components = ApiHelpers::assertIsArray($documentData['components']);
        foreach ($components as $component) {
            $component = ApiHelpers::assertIsArray($component);
            $className = ApiHelpers::assertGetKey($component, 'className');
            $alias = ApiHelpers::assertGetKey($component, 'alias');

            if ($componentAlias === $alias) {
                return $className;
            }
        }
    }

    private function getDocumentTypeDirName($template)
    {
        if ($template instanceof Asset) {
            return '/assets';
        }

        return $template->getObjectTypeDirName();
    }

    private function assertDocumentTypePermissions($documentType)
    {
        $user = BackendAuth::getUser();

        if (!EditorExtension::hasAccessToDocType($user, $documentType)) {
            throw new ApplicationException(Lang::get(
                'cms::lang.editor.error_no_doctype_permissions',
                ['doctype' => $documentType]
            ));
        }
    }
}