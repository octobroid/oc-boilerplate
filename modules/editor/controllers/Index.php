<?php namespace Editor\Controllers;

use Request;
use Response;
use BackendMenu;
use SystemException;
use Backend\Classes\Controller;
use Backend\Models\BrandSetting;
use Editor\Classes\ExtensionManager;
use October\Rain\Exception\ValidationException;

/**
 * Editor index controller
 *
 * @package october\editor
 * @author Alexey Bobkov, Samuel Georges
 */
class Index extends Controller
{
    use \Backend\Traits\InspectableContainer;

    public $requiredPermissions = ['editor.access_editor'];

    public $implement = [
        \Editor\Behaviors\StateManager::class
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.Editor', 'editor');

        $this->bodyClass = 'compact-container editor-page backend-document-layout';
        $this->pageTitle = 'editor::lang.plugin.name';
    }

    public function index()
    {
        $this->addCss('/modules/editor/assets/css/editor.css', 'core');

        $this->addJsBundle('/modules/editor/assets/js/editor.timeoutpromise.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.command.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.documenturi.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.store.tabmanager.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.store.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.page.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.extension.base.js', 'core');
        $this->addJsBundle('/modules/editor/assets/js/editor.extension.documentcontroller.base.js', 'core');

        $this->addJsBundle('/modules/editor/assets/js/editor.extension.documentcomponent.base.js', 'core');

        $this->registerVueComponent(\Backend\VueComponents\Document::class);
        $this->registerVueComponent(\Backend\VueComponents\Tabs::class);
        $this->registerVueComponent(\Backend\VueComponents\TreeView::class);
        $this->registerVueComponent(\Backend\VueComponents\Splitter::class);
        $this->registerVueComponent(\Backend\VueComponents\Modal::class);
        $this->registerVueComponent(\Backend\VueComponents\Inspector::class);
        $this->registerVueComponent(\Backend\VueComponents\Uploader::class);

        $this->registerVueComponent(\Editor\VueComponents\EditorConflictResolver::class);
        $this->registerVueComponent(\Editor\VueComponents\Application::class);

        $extensionManager = ExtensionManager::instance();
        $jsFiles = $extensionManager->listJsFiles();
        foreach ($jsFiles as $jsFile => $attributes) {
            $this->addJsBundle($jsFile, $attributes);
        }

        $componentClasses = $extensionManager->listVueComponents();
        foreach ($componentClasses as $componentClass) {
            $this->registerVueComponent($componentClass);
        }

        $directEditDocument = Request::query('document');
        if (strlen($directEditDocument)) {
            $this->vars['hideMainMenu'] = true;
        }

        $this->vars['customLogo'] = BrandSetting::getLogo();

        $this->vars['initialState'] = $this->makeInitialState([
            'directModeDocument' => $directEditDocument
        ]);
    }

    public function index_onCommand()
    {
        $extension = post('extension');
        if (!is_scalar($extension) || !strlen($extension)) {
            throw new SystemException('Missing extension name');
        }

        $command = post('command');
        if (!is_scalar($command) || !strlen($command)) {
            throw new SystemException('Missing command');
        }

        try {
            return ExtensionManager::instance()->runCommand($extension, $command);
        }
        catch (ValidationException $ex) {
            $messages = $ex->getErrors()->getMessages();
            if (!$messages) {
                throw $ex;
            }

            $responseData = ['validationErrors' => $messages];
            return Response::json($responseData, 406);
        }
    }

    public function onListExtensionNavigatorSections()
    {
        $namespace = post('extension');
        if (!is_scalar($namespace) || !strlen($namespace)) {
            throw new SystemException('Missing extension namespace');
        }

        $documentType = post('documentType');
        if ($documentType && !is_scalar($documentType)) {
            throw new SystemException('Invalid document type');
        }

        $extension = ExtensionManager::instance()->getExtensionByNamespace($namespace);
        $namespace = $extension->getNamespaceNormalized();

        return [
            'sections' => $this->listExtensionNavigatorSections($extension, $namespace, $documentType)
        ];
    }
}
