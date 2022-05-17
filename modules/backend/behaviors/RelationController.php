<?php namespace Backend\Behaviors;

use Lang;
use Flash;
use Request;
use Form as FormHelper;
use Backend\Classes\ControllerBehavior;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use October\Rain\Database\Model;
use ApplicationException;

/**
 * RelationController uses a combination of lists and forms for managing Model relations.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         \Backend\Behaviors\RelationController::class,
 *     ];
 *
 *     public $relationConfig = 'config_relation.yaml';
 *
 * The `$relationConfig` property makes reference to the configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class RelationController extends ControllerBehavior
{
    use \Backend\Traits\FormModelSaver;
    use \Backend\Behaviors\RelationController\HasViewMode;
    use \Backend\Behaviors\RelationController\HasManageMode;
    use \Backend\Behaviors\RelationController\HasPivotMode;

    /**
     * @var const PARAM_FIELD postback parameter for the active relationship field
     */
    const PARAM_FIELD = '_relation_field';

    /**
     * @var const PARAM_MODE postback parameter for the active management mode
     */
    const PARAM_MODE = '_relation_mode';

    /**
     * @var const PARAM_EXTRA_CONFIG postback parameter for read only mode
     */
    const PARAM_EXTRA_CONFIG = '_relation_extra_config';

    /**
     * @var Backend\Widgets\Search searchWidget
     */
    protected $searchWidget;

    /**
     * @var Backend\Widgets\Toolbar toolbarWidget
     */
    protected $toolbarWidget;

    /**
     * @var array requiredProperties
     */
    protected $requiredProperties = ['relationConfig'];

    /**
     * @var array requiredRelationProperties that must exist for each relationship definition
     */
    protected $requiredRelationProperties = ['label'];

    /**
     * @var array requiredConfig that must exist when applying the primary config file
     */
    protected $requiredConfig = [];

    /**
     * @var array actions visible in context of the controller
     */
    protected $actions = [];

    /**
     * @var object originalConfig values
     */
    protected $originalConfig;

    /**
     * @var array extraConfig provided by the relationRender method
     */
    protected $extraConfig;

    /**
     * @var bool initialized informs if everything is ready
     */
    protected $initialized = false;

    /**
     * @var string relationType
     */
    public $relationType;

    /**
     * @var string relationName
     */
    public $relationName;

    /**
     * @var Model relationModel
     */
    public $relationModel;

    /**
     * @var Model relationObject
     */
    public $relationObject;

    /**
     * @var Model model used as parent of the relationship
     */
    protected $model;

    /**
     * @var Model field for the relationship as defined in the configuration
     */
    protected $field;

    /**
     * @var string alias is something unique to pass to widgets
     */
    protected $alias;

    /**
     * @var array toolbarButtons to display in view mode.
     */
    protected $toolbarButtons;

    /**
     * @var string eventTarget that triggered an AJAX event (button, list)
     */
    protected $eventTarget;

    /**
     * @var string sessionKey used for deferred bindings
     */
    public $sessionKey;

    /**
     * @var bool readOnly disables the ability to add, update, delete or create relations
     */
    public $readOnly = false;

    /**
     * @var bool deferredBinding defers all binding actions using a session key
     */
    public $deferredBinding = false;

    /**
     * @var array customMessages contains default messages that you can override
     */
    protected $customMessages = [
        'buttonCreate' => 'backend::lang.relation.create_name',
        'buttonUpdate' => 'backend::lang.relation.update_name',
        'buttonAdd' => 'backend::lang.relation.add_name',
        'buttonLink' => 'backend::lang.relation.link_name',
        'buttonDelete' => 'backend::lang.relation.delete',
        'buttonRemove' => 'backend::lang.relation.remove',
        'buttonUnlink' => 'backend::lang.relation.unlink',
        'confirmDelete' => 'backend::lang.relation.delete_confirm',
        'confirmUnlink' => 'backend::lang.relation.unlink_confirm',
        'titlePreviewForm' => 'backend::lang.relation.preview_name',
        'titleCreateForm' => 'backend::lang.relation.create_name',
        'titleUpdateForm' => 'backend::lang.relation.update_name',
        'titleLinkForm' => 'backend::lang.relation.link_a_new',
        'titleAddForm' => 'backend::lang.relation.add_a_new',
        'titlePivotForm' => 'backend::lang.relation.related_data',
        'flashCreate' => 'backend::lang.form.create_success',
        'flashUpdate' => 'backend::lang.form.update_success',
        'flashDelete' => 'backend::lang.form.delete_success',
        'flashAdd' => 'backend::lang.relation.add_success',
        'flashLink' => 'backend::lang.relation.link_success',
        'flashRemove' => 'backend::lang.relation.remove_success',
        'flashUnlink' => 'backend::lang.relation.unlink_success',
    ];

    /**
     * __construct the behavior
     * @param Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        /*
         * Build configuration
         */
        $this->config = $this->originalConfig = $this->makeConfig($controller->relationConfig, $this->requiredConfig);
    }

    /**
     * beforeDisplay fires before the page is displayed and AJAX is executed.
     */
    public function beforeDisplay()
    {
        $this->addJs('js/october.relation.js', 'core');
        $this->addCss('css/relation.css', 'core');
    }

    /**
     * validateField validates the supplied field and initializes the relation manager.
     * @param string $field The relationship field.
     * @return string The active field name.
     */
    protected function validateField($field = null)
    {
        $field = $field ?: post(self::PARAM_FIELD);

        if ($field && $field !== $this->field) {
            $this->initRelation($this->model, $field);
        }

        if (!$field && !$this->field) {
            throw new ApplicationException(Lang::get('backend::lang.relation.missing_definition', compact('field')));
        }

        return $field ?: $this->field;
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        $this->vars['relationLabel'] = $this->config->label ?: $this->field;
        $this->vars['relationField'] = $this->field;
        $this->vars['relationType'] = $this->relationType;
        $this->vars['relationSearchWidget'] = $this->searchWidget;
        $this->vars['relationToolbarWidget'] = $this->toolbarWidget;
        $this->vars['relationToolbarButtons'] = $this->toolbarButtons;
        $this->vars['relationSessionKey'] = $this->relationGetSessionKey();
        $this->vars['relationExtraConfig'] = $this->extraConfig;

        // Manage
        $this->vars['relationManageId'] = $this->manageId;
        $this->vars['relationManageTitle'] = $this->manageTitle;
        $this->vars['relationManageFilterWidget'] = $this->manageFilterWidget;
        $this->vars['relationManageWidget'] = $this->manageWidget;
        $this->vars['relationManageMode'] = $this->manageMode;

        // View
        $this->vars['relationViewFilterWidget'] = $this->viewFilterWidget;
        $this->vars['relationViewMode'] = $this->viewMode;
        $this->vars['relationViewWidget'] = $this->viewWidget;
        $this->vars['relationViewModel'] = $this->viewModel;

        // Pivot
        $this->vars['relationPivotTitle'] = $this->pivotTitle;
        $this->vars['relationPivotWidget'] = $this->pivotWidget;
    }

    /**
     * beforeAjax is needed because the controller action is responsible for supplying
     * the parent model so it's action must be fired. Additionally, each AJAX request
     * must supply the relation's field name (_relation_field).
     */
    protected function beforeAjax()
    {
        if ($this->initialized) {
            return;
        }

        $this->controller->pageAction();
        if ($fatalError = $this->controller->getFatalError()) {
            throw new ApplicationException($fatalError);
        }

        $this->validateField();
        $this->prepareVars();
        $this->initialized = true;
    }

    //
    // Interface
    //

    /**
     * initRelation prepares the widgets used by this behavior
     * @param Model $model
     * @param string $field
     * @return void
     */
    public function initRelation($model, $field = null)
    {
        if ($field === null) {
            $field = post(self::PARAM_FIELD);
        }

        $this->config = $this->originalConfig;
        $this->model = $model;
        $this->field = $field;

        if (!$field) {
            return;
        }

        if (!$this->model) {
            throw new ApplicationException(Lang::get('backend::lang.relation.missing_model', [
                'class' => get_class($this->controller),
            ]));
        }

        if (!$this->model instanceof Model) {
            throw new ApplicationException(Lang::get('backend::lang.model.invalid_class', [
                'model' => get_class($this->model),
                'class' => get_class($this->controller),
            ]));
        }

        if (!$this->relationHasField($field)) {
            throw new ApplicationException(Lang::get('backend::lang.relation.missing_definition', compact('field')));
        }

        if ($extraConfig = post(self::PARAM_EXTRA_CONFIG)) {
            $this->applyExtraConfig($extraConfig);
        }

        $this->alias = camel_case('relation ' . $field);
        $this->config = $this->makeConfig($this->getConfig($field), $this->requiredRelationProperties);
        $this->controller->relationExtendConfig($this->config, $this->field, $this->model);

        /*
         * Relationship details
         */
        $this->relationName = $field;
        $this->relationType = $this->model->getRelationType($field);
        $this->relationObject = $this->model->{$field}();
        $this->relationModel = $this->relationObject instanceof HasOneOrMany
            ? $this->relationObject->make()
            : $this->relationObject->getRelated();

        $this->manageId = post('manage_id');
        $this->foreignId = post('foreign_id');
        $this->readOnly = $this->getConfig('readOnly');
        $this->deferredBinding = $this->evalDeferredBinding();
        $this->viewMode = $this->evalViewMode();
        $this->manageMode = $this->evalManageMode();
        $this->manageTitle = $this->evalManageTitle();
        $this->pivotTitle = $this->evalPivotTitle();
        $this->toolbarButtons = $this->evalToolbarButtons();

        /*
         * Toolbar widget
         */
        if ($this->toolbarWidget = $this->makeToolbarWidget()) {
            $this->toolbarWidget->bindToController();
        }

        /*
         * Search widget
         */
        if ($this->searchWidget = $this->makeSearchWidget()) {
            $this->searchWidget->bindToController();
        }

        /*
         * Filter widgets (optional)
         */
        if ($this->manageFilterWidget = $this->makeFilterWidget('manage')) {
            $this->controller->relationExtendManageFilterWidget($this->manageFilterWidget, $this->field, $this->model);
            $this->manageFilterWidget->bindToController();
        }

        if ($this->viewFilterWidget = $this->makeFilterWidget('view')) {
            $this->controller->relationExtendViewFilterWidget($this->viewFilterWidget, $this->field, $this->model);
            $this->viewFilterWidget->bindToController();
        }

        /*
         * View widget
         */
        if ($this->viewWidget = $this->makeViewWidget()) {
            $this->controller->relationExtendViewWidget($this->viewWidget, $this->field, $this->model);
            $this->viewWidget->bindToController();
        }

        /*
         * Manage widget
         */
        if ($this->manageWidget = $this->makeManageWidget()) {
            $this->controller->relationExtendManageWidget($this->manageWidget, $this->field, $this->model);
            $this->manageWidget->bindToController();
        }

        /*
         * Pivot widget
         */
        if ($this->manageMode === 'pivot' && $this->pivotWidget = $this->makePivotWidget()) {
            $this->controller->relationExtendPivotWidget($this->pivotWidget, $this->field, $this->model);
            $this->pivotWidget->bindToController();
        }
    }

    /**
     * relationHasField
     */
    public function relationHasField(string $field): bool
    {
        return (bool) $this->getConfig($field);
    }

    /**
     * Renders the relationship manager.
     * @param string $field The relationship field.
     * @param array $options
     * @return string Rendered HTML for the relationship manager.
     */
    public function relationRender($field, $options = [])
    {
        /*
         * Session key
         */
        if (is_string($options)) {
            $options = ['sessionKey' => $options];
        }

        if (isset($options['sessionKey'])) {
            $this->sessionKey = $options['sessionKey'];
        }

        /*
         * Apply options and extra config
         */
        $allowConfig = ['readOnly', 'recordUrl', 'recordOnClick'];
        $extraConfig = array_only($options, $allowConfig);
        $this->extraConfig = $extraConfig;
        $this->applyExtraConfig($extraConfig, $field);

        /*
         * Initialize
         */
        $this->validateField($field);
        $this->prepareVars();

        /*
         * Determine the partial to use based on the supplied section option
         */
        $section = $options['section'] ?? null;
        switch (strtolower($section)) {
            case 'toolbar':
                return $this->toolbarWidget ? $this->toolbarWidget->render() : null;

            case 'view':
                return $this->relationMakePartial('view');

            default:
                return $this->relationMakePartial('container');
        }
    }

    /**
     * Refreshes the relation container only, useful for returning in custom AJAX requests.
     * @param  string $field Relation definition.
     * @return array The relation element selector as the key, and the relation view contents are the value.
     */
    public function relationRefresh($field = null)
    {
        $field = $this->validateField($field);

        $result = ['#'.$this->relationGetId('view') => $this->relationRenderView($field)];
        if ($toolbar = $this->relationRenderToolbar($field)) {
            $result['#'.$this->relationGetId('toolbar')] = $toolbar;
        }

        if ($eventResult = $this->controller->relationExtendRefreshResults($field)) {
            $result = $eventResult + $result;
        }

        return $result;
    }

    /**
     * Renders the toolbar only.
     * @param string $field The relationship field.
     * @return string Rendered HTML for the toolbar.
     */
    public function relationRenderToolbar($field = null)
    {
        return $this->relationRender($field, ['section' => 'toolbar']);
    }

    /**
     * Renders the view only.
     * @param string $field The relationship field.
     * @return string Rendered HTML for the view.
     */
    public function relationRenderView($field = null)
    {
        return $this->relationRender($field, ['section' => 'view']);
    }

    /**
     * relationMakePartial is a controller accessor for making partials within this behavior.
     * @param string $partial
     * @param array $params
     * @return string Partial contents
     */
    public function relationMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial('relation_'.$partial, $params + $this->vars, false);
        if (!$contents) {
            $contents = $this->makePartial($partial, $params);
        }

        return $contents;
    }

    /**
     * relationGetId returns a unique ID for this relation and field combination.
     * @param string $suffix A suffix to use with the identifier.
     * @return string
     */
    public function relationGetId($suffix = null)
    {
        $id = class_basename($this);
        if ($this->field) {
            $id .= '-' . $this->field;
        }

        if ($suffix !== null) {
            $id .= '-' . $suffix;
        }

        return $this->controller->getId($id);
    }

    /**
     * relationGetSessionKey returns the active session key.
     */
    public function relationGetSessionKey($force = false)
    {
        if ($this->sessionKey && !$force) {
            return $this->sessionKey;
        }

        if (post('_relation_session_key')) {
            return $this->sessionKey = post('_relation_session_key');
        }

        if (post('_session_key')) {
            return $this->sessionKey = post('_session_key');
        }

        return $this->sessionKey = FormHelper::getSessionKey();
    }

    /**
     * relationGetMessage is a public API for accessing custom messages
     */
    public function relationGetMessage(string $code): string
    {
        return $this->getCustomLang($code);
    }

    //
    // Widgets
    //

    /**
     * makeFilterWidget
     * @param $type string Either 'manage' or 'view'
     * @return \Backend\Classes\WidgetBase|null
     */
    protected function makeFilterWidget($type)
    {
        if (!$this->getConfig($type . '[filter]')) {
            return null;
        }

        $filterConfig = $this->makeConfig($this->getConfig($type . '[filter]'));
        $filterConfig->alias = $this->alias . ucfirst($type) . 'Filter';
        $filterConfig->extraData = ['_relation_field' => $this->field];
        $filterWidget = $this->makeWidget(\Backend\Widgets\Filter::class, $filterConfig);

        return $filterWidget;
    }

    /**
     * makeToolbarWidget
     */
    protected function makeToolbarWidget()
    {
        $defaultConfig = [];

        /*
         * Add buttons to toolbar
         */
        $defaultButtons = null;

        if (!$this->readOnly && $this->toolbarButtons) {
            $defaultButtons = '~/modules/backend/behaviors/relationcontroller/partials/_toolbar.htm';
        }

        $defaultConfig['buttons'] = $this->getConfig('view[toolbarPartial]', $defaultButtons);

        /*
         * Make config
         */
        $toolbarConfig = $this->makeConfig($this->getConfig('toolbar', $defaultConfig));
        $toolbarConfig->alias = $this->alias . 'Toolbar';

        /*
         * Add search to toolbar
         */
        $useSearch = $this->viewMode === 'multi' && $this->getConfig('view[showSearch]');

        if ($useSearch) {
            $toolbarConfig->search = [
                'prompt' => 'backend::lang.list.search_prompt'
            ];
        }

        /*
         * No buttons, no search should mean no toolbar
         */
        if (empty($toolbarConfig->search) && empty($toolbarConfig->buttons)) {
            return;
        }

        $toolbarWidget = $this->makeWidget(\Backend\Widgets\Toolbar::class, $toolbarConfig);
        $toolbarWidget->cssClasses[] = 'list-header';

        return $toolbarWidget;
    }

    /**
     * makeSearchWidget
     */
    protected function makeSearchWidget()
    {
        if (!$this->getConfig('manage[showSearch]')) {
            return null;
        }

        $config = $this->makeConfig();
        $config->alias = $this->alias . 'ManageSearch';
        $config->growable = false;
        $config->prompt = 'backend::lang.list.search_prompt';
        $widget = $this->makeWidget(\Backend\Widgets\Search::class, $config);
        $widget->cssClasses[] = 'recordfinder-search';

        /*
         * Persist the search term across AJAX requests only
         */
        if (!Request::ajax()) {
            $widget->setActiveTerm(null);
        }

        return $widget;
    }

    //
    // Overrides
    //

    /**
     * relationExtendConfig provides an opportunity to manipulate the field configuration.
     * @param object $config
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendConfig($config, $field, $model)
    {
    }

    /**
     * relationExtendViewWidget provides an opportunity to manipulate the view widget.
     * @param Backend\Classes\WidgetBase $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendViewWidget($widget, $field, $model)
    {
    }

    /**
     * relationExtendManageWidget provides an opportunity to manipulate the manage widget.
     * @param Backend\Classes\WidgetBase $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendManageWidget($widget, $field, $model)
    {
    }

    /**
     * relationExtendPivotWidget provides an opportunity to manipulate the pivot widget.
     * @param Backend\Classes\WidgetBase $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendPivotWidget($widget, $field, $model)
    {
    }

    /**
     * relationExtendManageFilterWidget provides an opportunity to manipulate the manage filter widget.
     * @param \Backend\Widgets\Filter $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendManageFilterWidget($widget, $field, $model)
    {
    }

    /**
     * relationExtendViewFilterWidget provides an opportunity to manipulate the view filter widget.
     * @param \Backend\Widgets\Filter $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendViewFilterWidget($widget, $field, $model)
    {
    }

    /**
     * relationExtendRefreshResults is needed because the view widget is often
     * refreshed when the manage widget makes a change, you can use this method
     * to inject additional containers when this process occurs. Return an array
     * with the extra values to send to the browser, eg:
     *
     * return ['#myCounter' => 'Total records: 6'];
     *
     * @param string $field
     * @return array
     */
    public function relationExtendRefreshResults($field)
    {
    }

    //
    // Helpers
    //

    /**
     * findExistingRelationIds returns the existing record IDs for the relation.
     */
    protected function findExistingRelationIds($checkIds = null)
    {
        $foreignKeyName = $this->relationModel->getQualifiedKeyName();

        $results = $this->relationObject
            ->getBaseQuery()
            ->select($foreignKeyName);

        if ($checkIds !== null && is_array($checkIds) && count($checkIds)) {
            $results = $results->whereIn($foreignKeyName, $checkIds);
        }

        return $results->pluck($foreignKeyName)->all();
    }

    /**
     * evalDeferredBinding
     */
    protected function evalDeferredBinding(): bool
    {
        if ($this->relationType === 'hasManyThrough') {
            return false;
        }

        return $this->getConfig('deferredBinding') || !$this->model->exists;
    }

    /**
     * evalToolbarButtons determines the default buttons based on the model relationship type.
     */
    protected function evalToolbarButtons(): array
    {
        $buttons = $this->getConfig('view[toolbarButtons]');

        if ($buttons === false) {
            return [];
        }
        elseif (is_string($buttons)) {
            return array_map('trim', explode('|', $buttons));
        }
        elseif (is_array($buttons)) {
            return $buttons;
        }

        if ($this->manageMode === 'pivot') {
            return ['add', 'remove'];
        }

        switch ($this->relationType) {
            case 'hasMany':
            case 'morphMany':
                return ['create', 'delete'];

            case 'morphToMany':
            case 'morphedByMany':
            case 'belongsToMany':
                return ['create', 'add', 'delete', 'remove'];

            case 'hasOne':
            case 'morphOne':
            case 'belongsTo':
                return ['create', 'update', 'link', 'delete', 'unlink'];

            case 'hasManyThrough':
                return [];
        }
    }

    /**
     * evalFormContext determines supplied form context
     */
    protected function evalFormContext($mode = 'manage', $exists = false)
    {
        $config = $this->config->{$mode} ?? [];

        if (($context = array_get($config, 'context')) && is_array($context)) {
            $context = $exists
                ? array_get($context, 'update')
                : array_get($context, 'create');
        }

        if (!$context) {
            $context = $exists ? 'update' : 'create';
        }

        return $context;
    }

    /**
     * applyExtraConfig
     */
    protected function applyExtraConfig($config, $field = null)
    {
        if (!$field) {
            $field = $this->field;
        }

        if (!$config || !isset($this->originalConfig->{$field})) {
            return;
        }

        if (
            !is_array($config) &&
            (!$config = @json_decode(@base64_decode($config), true))
        ) {
            return;
        }

        $parsedConfig = array_only($config, ['readOnly']);
        $parsedConfig['view'] = array_only($config, ['recordUrl', 'recordOnClick']);

        $this->originalConfig->{$field} = array_replace_recursive(
            $this->originalConfig->{$field},
            $parsedConfig
        );
    }

    /**
     * makeConfigForMode returns the configuration for a mode (view, manage, pivot) for an
     * expected type (list, form) and uses fallback configuration
     */
    protected function makeConfigForMode($mode = 'view', $type = 'list', $throwException = true)
    {
        $config = null;

        /*
         * Look for $this->config->view['list']
         */
        if (
            isset($this->config->{$mode}) &&
            array_key_exists($type, $this->config->{$mode})
        ) {
            $config = $this->config->{$mode}[$type];
        }
        /*
         * Look for $this->config->list
         */
        elseif (isset($this->config->{$type})) {
            $config = $this->config->{$type};
        }

        /*
         * Apply substitutes:
         *
         * - view.list => manage.list
         */
        if (!$config) {
            if ($mode === 'manage' && $type === 'list') {
                return $this->makeConfigForMode('view', $type);
            }

            if ($throwException) {
                throw new ApplicationException('Missing configuration for '.$mode.'.'.$type.' in RelationController definition '.$this->field);
            }

            return false;
        }

        return $this->makeConfig($config);
    }

    /**
     * getCustomLang parses custom messages provided by the config
     */
    protected function getCustomLang(string $name, string $default = null, array $extras = []): string
    {
        $foundKey = $this->getConfig("customMessages[${name}]");

        if ($foundKey === null) {
            $foundKey = $this->originalConfig->customMessages[$name] ?? null;
        }

        if ($foundKey === null) {
            $foundKey = $default;
        }

        if ($foundKey === null) {
            $foundKey = $this->customMessages[$name] ?? '???';
        }

        $vars = $extras + [
            'name' => Lang::get($this->getConfig('label', $this->field))
        ];

        return Lang::get($foundKey, $vars);
    }

    /**
     * showFlashMessage displays a flash message if its found
     */
    protected function showFlashMessage(string $message): void
    {
        if (!$this->useFlashMessages()) {
            return;
        }

        if ($message = $this->getCustomLang($message)) {
            Flash::success($message);
        }
    }

    /**
     * useFlashMessages determines if flash messages should be used
     */
    protected function useFlashMessages(): bool
    {
        $useFlash = $this->getConfig('showFlash');

        if ($useFlash === null) {
            $useFlash = $this->originalConfig->showFlash ?? null;
        }

        if ($useFlash === null) {
            $useFlash = true;
        }

        return $useFlash;
    }
}
