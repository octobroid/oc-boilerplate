<?php namespace Editor\Behaviors;

use Lang;
use BackendAuth;
use Editor\Classes\ExtensionManager;
use Backend\Classes\ControllerBehavior;
use Backend\VueComponents\TreeView\SectionList;

/**
 * Manages Editor initial state
 *
 * @package october\editor
 * @author Alexey Bobkov, Samuel Georges
 */
class StateManager extends ControllerBehavior
{
    public function makeInitialState($params)
    {
        $result = [
            'extensions' => [],
            'params' => $params
        ];

        $extensions = ExtensionManager::instance()->listExtensions();
        foreach ($extensions as $extension) {
            $namespace = $extension->getNamespaceNormalized();

            $extensionState = [
                'navigatorSections' => $this->listExtensionNavigatorSections($extension, $namespace),
                'newDocumentData' => $this->getNewDocumentData($extension),
                'langStrings' => $this->getLangStrings($extension),
                'settingsForms' => $extension->getSettingsForms(),
                'inspectorConfigurations' => $extension->listInspectorConfigurations(),
                'customData' => $extension->getCustomData()
            ];

            $result['extensions'][$namespace] = $extensionState;
        }

        $result['langStrings'] = $this->getEditorLangStrings();
        $result['userData'] = $this->loadUserData();

        return $result;
    }

    public function listExtensionNavigatorSections($extension, $namespace, $documentType = null)
    {
        return $this->getNavigatorSections($extension, $namespace, $documentType);
    }

    private function getNavigatorSections($extension, $namespace, $documentType = null)
    {
        $sectionList = new SectionList();
        $sectionList->setChildKeyPrefix($namespace.':');

        $extension->listNavigatorSections($sectionList, $documentType);
        $extensionSections = $sectionList->getSections();

        foreach ($extensionSections as $section) {
            foreach ($section->getNodes() as $extensionNode)
            {
                $extensionNode->setUserDataElement('editorNamespace', $namespace);
            }
        }

        return $sectionList->toArray();
    }

    private function getLangStrings($extension)
    {
        $strings = $extension->getClientSideLangStrings();
        $result = [];
        foreach ($strings as $stringCode) {
            $result[$stringCode] = Lang::get($stringCode);
        }

        return $result;
    }

    private function getNewDocumentData($extension)
    {
        $result = [];

        $newDocumentData = $extension->getNewDocumentsData();
        foreach ($newDocumentData as $documentType=>$documentData) {
            $result[$documentType] = $documentData->toArray();
        }

        return $result;
    }

    private function getEditorLangStrings()
    {
        $result = [
            'editor::lang.common.error_saving',
            'editor::lang.common.error_loading',
            'editor::lang.common.error_deleting',
            'backend::lang.form.confirm_tab_close',
            'backend::lang.tabs.close',
            'editor::lang.common.discard_changes',
            'backend::lang.form.delete',
            'backend::lang.form.confirm',
            'backend::lang.form.apply',
            'editor::lang.common.confirm_delete',
            'editor::lang.common.toggle_document_header',
            'editor::lang.common.settings',
            'editor::lang.common.document_saved',
            'editor::lang.common.document_reloaded',
            'editor::lang.common.document_deleted',
            'editor::lang.common.document',
            'editor::lang.common.error',
            'editor::lang.common.reveal_in_sidebar',
            'editor::lang.common.apply_and_save',
        ];

        foreach ($result as $stringCode) {
            $result[$stringCode] = Lang::get($stringCode);
        }

        return $result;
    }

    private function loadUserData()
    {
        return [
            'useMediaManager' => BackendAuth::userHasAccess('media.manage_media')
        ];
    }
}