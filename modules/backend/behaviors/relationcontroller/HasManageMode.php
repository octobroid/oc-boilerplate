<?php namespace Backend\Behaviors\RelationController;

use Lang;
use Request;
use Backend\Behaviors\RelationController;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Backend\Widgets\Form as FormWidget;
use Backend\Widgets\Lists as ListWidget;
use ApplicationException;

/**
 * HasManageMode contains logic for managing related records
 */
trait HasManageMode
{
    /**
     * @var Backend\Classes\WidgetBase manageWidget used for relation management
     */
    protected $manageWidget;

    /**
     * @var Model manageModel is a reference to the model used for manage form
     */
    protected $manageModel;

    /**
     * @var \Backend\Widgets\Filter manageFilterWidget
     */
    protected $manageFilterWidget;

    /**
     * @var string manageTitle used for the manage popup
     */
    protected $manageTitle;

    /**
     * @var string manageMode of relation as list, form, or pivot
     */
    protected $manageMode;

    /**
     * @var string forceManageMode
     */
    protected $forceManageMode;

    /**
     * @var int manageId is the primary id of an existing relation record
     */
    protected $manageId;

    /**
     * relationGetManageWidget returns the manage widget used by this behavior
     * @return \Backend\Classes\WidgetBase
     */
    public function relationGetManageWidget()
    {
        return $this->manageWidget;
    }

    /**
     * makeManageWidget returns a form or list widget based on configuration
     */
    protected function makeManageWidget()
    {
        // Multiple (has many, belongs to many)
        if ($this->manageMode === 'list' || $this->manageMode === 'pivot') {
            return $this->makeManageWidgetAsList();
        }
        // Single (belongs to, has one)
        elseif ($this->manageMode === 'form') {
            return $this->makeManageWidgetAsForm();
        }

        return null;
    }

    /**
     * makeManageWidgetAsList prepares the list widget for management
     */
    protected function makeManageWidgetAsList(): ?ListWidget
    {
        $isPivot = $this->manageMode === 'pivot';

        $config = $this->makeConfigForMode('manage', 'list');
        $config->model = $this->relationModel;
        $config->alias = $this->alias . 'ManageList';
        $config->showSetup = false;
        $config->showCheckboxes = $this->getConfig('manage[showCheckboxes]', !$isPivot);
        $config->showSorting = $this->getConfig('manage[showSorting]', !$isPivot);
        $config->defaultSort = $this->getConfig('manage[defaultSort]');
        $config->recordsPerPage = $this->getConfig('manage[recordsPerPage]');

        if ($this->viewMode === 'single') {
            $config->showCheckboxes = false;
            $config->recordOnClick = sprintf(
                "$.oc.relationBehavior.clickManageListRecord(':%s', '%s', '%s')",
                $this->relationModel->getKeyName(),
                $this->relationGetId(),
                $this->relationGetSessionKey()
            );
        }
        elseif ($config->showCheckboxes) {
            $config->recordOnClick = "$.oc.relationBehavior.toggleListCheckbox(this)";
        }
        elseif ($isPivot) {
            $config->recordOnClick = sprintf(
                "$.oc.relationBehavior.clickManagePivotListRecord(':%s', '%s', '%s')",
                $this->relationModel->getKeyName(),
                $this->relationGetId(),
                $this->relationGetSessionKey()
            );
        }

        $widget = $this->makeWidget(ListWidget::class, $config);

        // Apply defined constraints
        if ($sqlConditions = $this->getConfig('manage[conditions]')) {
            $widget->bindEvent('list.extendQueryBefore', function ($query) use ($sqlConditions) {
                $query->whereRaw($sqlConditions);
            });
        }
        elseif ($scopeMethod = $this->getConfig('manage[scope]')) {
            $widget->bindEvent('list.extendQueryBefore', function ($query) use ($scopeMethod) {
                $query->$scopeMethod($this->model);
            });
        }
        else {
            $widget->bindEvent('list.extendQueryBefore', function ($query) {
                $this->relationObject->addDefinedConstraintsToQuery($query);

                // Reset any orders that may have come from the definition
                // because it has a tendency to break things
                $query->getQuery()->orders = [];
            });
        }

        // Link the Search Widget to the List Widget
        if ($this->searchWidget) {
            $this->searchWidget->bindEvent('search.submit', function () use ($widget) {
                $widget->setSearchTerm($this->searchWidget->getActiveTerm());
                return $widget->onRefresh();
            });

            // Linkage for JS plugins
            $this->searchWidget->listWidgetId = $widget->getId();

            // Pass search options
            $widget->setSearchOptions([
                'mode' => $this->getConfig('manage[searchMode]'),
                'scope' => $this->getConfig('manage[searchScope]'),
            ]);

            // Persist the search term across AJAX requests only
            if (Request::ajax()) {
                $widget->setSearchTerm($this->searchWidget->getActiveTerm());
            }
        }

        // Link the Filter Widget to the List Widget
        if ($this->manageFilterWidget) {
            $this->manageFilterWidget->bindEvent('filter.update', function () use ($widget) {
                return $widget->onFilter();
            });

            // Apply predefined filter values
            $widget->addFilter([$this->manageFilterWidget, 'applyAllScopesToQuery']);
        }

        // Exclude existing relationships
        $widget->bindEvent('list.extendQuery', function ($query) {
            // Where not in the current list of related records
            $existingIds = $this->findExistingRelationIds();
            if (count($existingIds)) {
                $query->whereNotIn($this->relationModel->getQualifiedKeyName(), $existingIds);
            }
        });

        return $widget;
    }

    /**
     * makeManageWidgetAsForm prepares the form widget for management
     */
    protected function makeManageWidgetAsForm(): ?FormWidget
    {
        if (!$config = $this->makeConfigForMode('manage', 'form', false)) {
            return null;
        }

        $config->model = $this->relationModel;
        $config->arrayName = class_basename($this->relationModel);
        $config->context = $this->evalFormContext('manage', !!$this->manageId);
        $config->alias = $this->alias . 'ManageForm';

        // Existing record
        if ($this->manageId) {
            $this->manageModel = $config->model->find($this->manageId);
            if ($this->manageModel) {
                $config->model = $this->manageModel;
            }
            else {
                throw new ApplicationException(Lang::get('backend::lang.model.not_found', [
                    'class' => get_class($config->model),
                    'id' => $this->manageId,
                ]));
            }
        }

        $widget = $this->makeWidget(FormWidget::class, $config);

        return $widget;
    }

    /**
     * onRelationManageForm
     */
    public function onRelationManageForm()
    {
        $this->beforeAjax();

        // Updating an existing record
        if ($this->manageMode === 'pivot' && $this->manageId) {
            return $this->onRelationManagePivotForm();
        }

        // The form should not share its session key with the parent
        $this->vars['newSessionKey'] = str_random(40);

        $view = 'manage_' . $this->manageMode;

        return $this->relationMakePartial($view);
    }

    /**
     * onRelationManageCreate a new related model
     */
    public function onRelationManageCreate()
    {
        $this->forceManageMode = 'form';

        $this->beforeAjax();

        $saveData = $this->manageWidget->getSaveData();
        $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey(true) : null;
        $parentModel = $this->relationObject->getParent();
        $newModel = $this->relationModel;

        $modelsToSave = $this->prepareModelsToSave($newModel, $saveData);
        foreach ($modelsToSave as $modelToSave) {
            $modelToSave->save(null, $this->manageWidget->getSessionKey());
        }

        // No need to add relationships that have a valid assocation via HasOneOrMany::make
        if (!$this->relationObject instanceof HasOneOrMany || !$parentModel->exists) {
            $this->relationObject->add($newModel, $sessionKey);
        }

        // Belongs To won't save when using add() so it should occur if the conditions are right.
        if ($this->relationType === 'belongsTo' && $parentModel->exists && !$this->deferredBinding) {
            $parentModel->save();
        }

        // Display updated form
        if ($this->viewMode === 'single') {
            $this->viewWidget->setFormValues($saveData);
        }

        $this->showFlashMessage('flashCreate');

        return $this->relationRefresh();
    }

    /**
     * onRelationManageUpdate an existing related model's fields
     */
    public function onRelationManageUpdate()
    {
        $this->forceManageMode = 'form';

        $this->beforeAjax();

        $saveData = $this->manageWidget->getSaveData();

        $modelsToSave = $this->prepareModelsToSave($this->manageModel, $saveData);
        foreach ($modelsToSave as $modelToSave) {
            $modelToSave->save(null, $this->manageWidget->getSessionKey());
        }

        // Display updated form
        if ($this->viewMode === 'single') {
            $this->viewWidget->setFormValues($saveData);
        }

        $this->showFlashMessage('flashUpdate');

        return $this->relationRefresh();
    }

    /**
     * onRelationManageDelete an existing related model completely
     */
    public function onRelationManageDelete()
    {
        $this->beforeAjax();

        /*
         * Multiple (has many, belongs to many)
         */
        if ($this->viewMode === 'multi') {
            if (($checkedIds = post('checked')) && is_array($checkedIds)) {
                foreach ($checkedIds as $relationId) {
                    if (!$obj = $this->relationModel->find($relationId)) {
                        continue;
                    }

                    $obj->delete();
                }
            }
        }
        /*
         * Single (belongs to, has one)
         */
        elseif ($this->viewMode === 'single') {
            $relatedModel = $this->viewModel;
            if ($relatedModel->exists) {
                $relatedModel->delete();
            }

            $this->resetViewWidgetModel();
            $this->viewModel = $this->relationModel;
        }

        $this->showFlashMessage('flashDelete');

        return $this->relationRefresh();
    }

    /**
     * onRelationManageAdd an existing related model to the primary model
     */
    public function onRelationManageAdd()
    {
        $this->beforeAjax();

        $recordId = post('record_id');
        $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey() : null;

        /*
         * Add
         */
        if ($this->viewMode === 'multi') {
            $checkedIds = $recordId ? [$recordId] : post('checked');

            if (is_array($checkedIds)) {
                /*
                 * Remove existing relations from the array
                 */
                $existingIds = $this->findExistingRelationIds($checkedIds);
                $checkedIds = array_diff($checkedIds, $existingIds);
                $foreignKeyName = $this->relationModel->getKeyName();

                $models = $this->relationModel->whereIn($foreignKeyName, $checkedIds)->get();
                foreach ($models as $model) {
                    $this->relationObject->add($model, $sessionKey);
                }
            }

            $this->showFlashMessage('flashAdd');
        }
        /*
         * Link
         */
        elseif ($this->viewMode === 'single') {
            if ($recordId && ($model = $this->relationModel->find($recordId))) {
                $this->relationObject->add($model, $sessionKey);
                $this->viewWidget->setFormValues($model->attributes);

                // Belongs To won't save when using add() so it should occur if the conditions are right.
                if ($this->relationType === 'belongsTo' && !$this->deferredBinding) {
                    $parentModel = $this->relationObject->getParent();
                    if ($parentModel->exists) {
                        $parentModel->save();
                    }
                }
            }

            $this->showFlashMessage('flashLink');
        }

        return $this->relationRefresh();
    }

    /**
     * onRelationManageRemove an existing related model from the primary model
     */
    public function onRelationManageRemove()
    {
        $this->beforeAjax();

        $recordId = post('record_id');
        $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey() : null;
        $relatedModel = $this->relationModel;

        /*
         * Remove
         */
        if ($this->viewMode === 'multi') {
            $checkedIds = $recordId ? [$recordId] : post('checked');

            if (is_array($checkedIds)) {
                $foreignKeyName = $relatedModel->getKeyName();

                $models = $relatedModel->whereIn($foreignKeyName, $checkedIds)->get();
                foreach ($models as $model) {
                    $this->relationObject->remove($model, $sessionKey);
                }
            }

            $this->showFlashMessage('flashRemove');
        }
        /*
         * Unlink
         */
        elseif ($this->viewMode === 'single') {
            if ($this->relationType === 'belongsTo') {
                $this->relationObject->dissociate();
                $this->relationObject->getParent()->save();
            }
            elseif ($this->relationType === 'hasOne' || $this->relationType === 'morphOne') {
                if ($obj = $relatedModel->find($recordId)) {
                    $this->relationObject->remove($obj, $sessionKey);
                }
                elseif ($this->viewModel->exists) {
                    $this->relationObject->remove($this->viewModel, $sessionKey);
                }
            }

            $this->resetViewWidgetModel();

            $this->showFlashMessage('flashUnlink');
        }

        return $this->relationRefresh();
    }

    /**
     * evalManageTitle determines the management mode popup title
     */
    protected function evalManageTitle(): string
    {
        if ($customTitle = $this->getConfig('manage[title]')) {
            return Lang::get($customTitle);
        }

        switch ($this->manageMode) {
            case 'pivot':
            case 'list':
                if ($this->eventTarget === 'button-link') {
                    return $this->getCustomLang('titleLinkForm');
                }
                else {
                    return $this->getCustomLang('titleAddForm');
                }
            case 'form':
                if ($this->readOnly) {
                    return $this->getCustomLang('titlePreviewForm');
                }
                elseif ($this->manageId) {
                    return $this->getCustomLang('titleUpdateForm');
                }
                else {
                    return $this->getCustomLang('titleCreateForm');
                }
        }

        return '';
    }

    /**
     * evalManageMode determines the management mode based on the relation type and settings
     * @return string
     */
    protected function evalManageMode()
    {
        if ($mode = post(RelationController::PARAM_MODE)) {
            return $mode;
        }

        if ($this->forceManageMode) {
            return $this->forceManageMode;
        }

        switch ($this->eventTarget) {
            case 'button-create':
            case 'button-update':
                return 'form';

            case 'button-link':
                return 'list';
        }

        switch ($this->relationType) {
            case 'belongsTo':
                return 'list';

            case 'morphToMany':
            case 'morphedByMany':
            case 'belongsToMany':
                if (isset($this->config->pivot)) {
                    return 'pivot';
                }
                elseif ($this->eventTarget === 'list') {
                    return 'form';
                }
                else {
                    return 'list';
                }

            case 'hasOne':
            case 'morphOne':
            case 'hasMany':
            case 'morphMany':
            case 'hasManyThrough':
                if ($this->eventTarget === 'button-add') {
                    return 'list';
                }

                return 'form';
        }
    }
}
