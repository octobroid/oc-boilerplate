<?php namespace Editor\Classes;

use Backend\VueComponents\TreeView\SectionList;

/**
 * ExtensionBase is a base class for Editor extensions
 *
 * @package october\editor
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ExtensionBase
{
    use \System\Traits\EventEmitter;

    /**
     * getNamespace returns unique extension namespace, for example 'cms'.
     * Plugins extending Editor must also define a client-side state
     * controller class in $.oc.editor.extension[namespace].
     * State controller classes must extend $.oc.editor.stateController.
     * @return string
     */
    abstract public function getNamespace(): string;

    public function getNamespaceNormalized()
    {
        return trim(strtolower($this->getNamespace()));
    }

    /**
     * listVueComponents returns a list of Vue components required for the extension.
     * @return array Array of Vue component class names
     */
    public function listVueComponents()
    {
        return [];
    }

    /**
     * listJsFiles returns a list of JavaScript files required for the extension.
     * @return array Returns an associative array of JavaScript file paths and attributes.
     */
    public function listJsFiles()
    {
        return [];
    }

    /**
     * listNavigatorSections initializes extension's sidebar Navigator sections.
     * The sections must be added to the supplied section list.
     */
    public function listNavigatorSections(SectionList $sectionList, $documentType = null)
    {
    }

    /**
     * listInspectorConfigurations returns a list of Inspector configurations that must
     * be available on the client side.
     * @return array Returns an array: ['config-name'=>config]
     */
    public function listInspectorConfigurations()
    {
        return [];
    }

    /**
     * getNewDocumentsData returns a list of new document descriptions, allowing creating
     * documents on the client side.
     * @return array Returns an array of NewDocumentDescription objects. The array indexes must be
     * strings matching the corresponding document types.
     */
    public function getNewDocumentsData()
    {
        return [];
    }

    /**
     * getSettingsForms returns a list of settings form configurations for document types
     * supported by the extension.
     * @return array Returns an array of JSON strings. The array indexes must be
     * strings matching the corresponding document types.
     */
    public function getSettingsForms()
    {
        return [];
    }

    /**
     * getClientSideLangStrings returns a list of language strings required for the
     * client-side extension controller.
     * @return array
     */
    public function getClientSideLangStrings()
    {
        return [];
    }

    /**
     * getCustomData returns custom state data required for the extension client-side controller.
     */
    public function getCustomData()
    {
        return null;
    }

    /**
     * runCommand handles client-side requests
     */
    public function runCommand($command)
    {
        $commandName = 'command_'.$command;
        return $this->$commandName();
    }

    /**
     * loadSettingsFile
     */
    protected function loadSettingsFile(string $rootPath, string $documentDirectoryName)
    {
        $path = $rootPath.'/'.$documentDirectoryName.'/settings-fields.json';

        if (file_exists($path)) {
            return $this->loadAndLocalizeJsonFile($path);
        }

        return [];
    }

    /**
     * loadAndLocalizeJsonFile
     */
    protected function loadAndLocalizeJsonFile($path)
    {
        $contents = json_decode(file_get_contents($path), true);

        array_walk_recursive($contents, function(&$value, $key) {
            if (is_string($value)) {
                $value = trans($value);
            }
        });

        return $contents;
    }
}
