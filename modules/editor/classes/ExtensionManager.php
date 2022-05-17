<?php namespace Editor\Classes;

use Event;
use SystemException;

/**
 * Manages Editor extensions.
 *
 * @method static ExtensionManager instance()
 *
 * @package october\editor
 * @author Alexey Bobkov, Samuel Georges
 */
class ExtensionManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array extensionClassNames is a collection of registered extensions
     */
    protected $extensionClassNames = [];

    private $extensions = [];

    /**
     * init initializes the extension manager
     */
    protected function init()
    {
        $this->registerExtensions();
    }

    /**
     * listVueComponents
     */
    public function listVueComponents()
    {
        $extensions = $this->listExtensions();

        $result = [];
        foreach ($extensions as $extension) {
            $result += $extension->listVueComponents();
        }

        return $result;
    }

    /**
     * listJsFiles
     */
    public function listJsFiles()
    {
        $extensions = $this->listExtensions();

        $result = [];
        foreach ($extensions as $extension) {
            $result += $extension->listJsFiles();
        }

        return $result;
    }

    /**
     * listExtensions returns a collection of registered extension objects
     */
    public function listExtensions()
    {
        $result = [];
        foreach ($this->extensionClassNames as $className) {
            $result[] = $this->getExtension($className);
        }

        return $result;
    }

    /**
     * runCommand
     */
    public function runCommand($namespace, $command)
    {
        $extension = $this->getExtensionByNamespace($namespace);

        return $extension->runCommand($command);
    }

    /**
     * getExtensionByNamespace
     */
    public function getExtensionByNamespace($namespace)
    {
        $extensions = $this->listExtensions();
        foreach ($extensions as $extension) {
            if ($extension->getNamespaceNormalized() == $namespace) {
                return $extension;
            }
        }

        throw new SystemException(sprintf('Cannot find editor extension by namespace: %s', $namespace));
    }

    /**
     * makeExtension will create an extension object from a class name
     */
    protected function makeExtension(string $className)
    {
        if (!class_exists($className)) {
            throw new SystemException(sprintf('Editor extension class not found: %s', $className));
        }

        $extension = new $className();
        if (!$extension instanceof ExtensionBase) {
            throw new SystemException(
                sprintf('Editor extension class must be a descendant of Editor\Classes\ExtensionBase: %s', $className)
            );
        }

        return $extension;
    }

    /**
     * assertNamespaceUnique
     */
    private function assertNamespaceUnique($namespace)
    {
        foreach ($this->extensions as $extension) {
            if ($namespace == $extension->getNamespaceNormalized()) {
                throw new SystemException(sprintf('Editor extension namespace is already in use: %s', $namespace));
            }
        }
    }

    /**
     * getExtension will create and validate an extension object
     */
    protected function getExtension(string $className)
    {
        if (array_key_exists($className, $this->extensions)) {
            return $this->extensions[$className];
        }

        $extension = $this->makeExtension($className);
        $namespace = $extension->getNamespaceNormalized();

        if (!strlen($namespace)) {
            throw new SystemException(sprintf('Editor extension namespace must not be empty: %s', $className));
        }

        $this->assertNamespaceUnique($namespace);

        return $this->extensions[$className] = $extension;
    }

    /**
     * registerExtensions will build a collection of registered extensions
     */
    protected function registerExtensions()
    {
        $apiResult = Event::fire('editor.extension.register');

        if (!is_array($apiResult)) {
            return;
        }

        foreach ($apiResult as $extensionClassName) {
            if (!is_string($extensionClassName)) {
                continue;
            }

            $this->extensionClassNames[] = $extensionClassName;
        }
    }
}
