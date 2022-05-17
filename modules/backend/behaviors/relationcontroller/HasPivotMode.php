<?php namespace Backend\Behaviors\RelationController;

use Db;
use Lang;
use ApplicationException;
use Backend\Widgets\Form as FormWidget;

/**
 * HasPivotMode contains logic for managing pivot records
 */
trait HasPivotMode
{
    /**
     * @var Backend\Classes\WidgetBase pivotWidget for relations with pivot data
     */
    protected $pivotWidget;

    /**
     * @var Model pivotModel is a reference to the model used for pivot form
     */
    protected $pivotModel;

    /**
     * @var string pivotTitle used for the pivot popup
     */
    protected $pivotTitle;

    /**
     * @var int foreignId of a selected pivot record
     */
    protected $foreignId;

    /**
     * makePivotWidget return a form widget based on pivot configuration
     */
    protected function makePivotWidget()
    {
        $config = $this->makeConfigForMode('pivot', 'form');
        $config->model = $this->relationModel;
        $config->arrayName = class_basename($this->relationModel);
        $config->context = $this->evalFormContext('pivot', !!$this->manageId);
        $config->alias = $this->alias . 'ManagePivotForm';

        $foreignKeyName = $this->relationModel->getQualifiedKeyName();

        /*
         * Existing record
         */
        if ($this->manageId) {
            $this->pivotModel = $this->relationObject->where($foreignKeyName, $this->manageId)->first();

            if ($this->pivotModel) {
                $config->model = $this->pivotModel;
            }
            else {
                throw new ApplicationException(Lang::get('backend::lang.model.not_found', [
                    'class' => get_class($config->model),
                    'id' => $this->manageId,
                ]));
            }
        }
        /*
         * New record
         */
        else {
            if ($this->foreignId) {
                $foreignModel = $this->relationModel
                    ->whereIn($foreignKeyName, (array) $this->foreignId)
                    ->first();

                if ($foreignModel) {
                    $foreignModel->exists = false;
                    $config->model = $foreignModel;
                }
            }

            $config->model->setRelation('pivot', $this->relationObject->newPivot());
        }

        return $this->makeWidget(FormWidget::class, $config);
    }

    /**
     * onRelationManageAddPivot adds multiple items using a single pivot form.
     */
    public function onRelationManageAddPivot()
    {
        return $this->onRelationManagePivotForm();
    }

    /**
     * onRelationManagePivotForm
     */
    public function onRelationManagePivotForm()
    {
        $this->beforeAjax();

        $this->vars['foreignId'] = $this->foreignId ?: post('checked');

        return $this->relationMakePartial('pivot_form');
    }

    /**
     * onRelationManagePivotCreate
     */
    public function onRelationManagePivotCreate()
    {
        $this->beforeAjax();

        /*
         * If the pivot model fails for some reason, abort the sync
         */
        Db::transaction(function () {
            // Add the checked IDs to the pivot table
            $foreignIds = (array) $this->foreignId;
            $this->relationObject->sync($foreignIds, false);

            // Save data to models
            $foreignKeyName = $this->relationModel->getQualifiedKeyName();
            $hydratedModels = $this->relationObject->whereIn($foreignKeyName, $foreignIds)->get();
            $saveData = $this->pivotWidget->getSaveData();
            foreach ($hydratedModels as $hydratedModel) {
                $modelsToSave = $this->prepareModelsToSave($hydratedModel, $saveData);
                foreach ($modelsToSave as $modelToSave) {
                    $modelToSave->save(null, $this->pivotWidget->getSessionKey());
                }
            }
        });

        $this->showFlashMessage('flashAdd');

        return ['#'.$this->relationGetId('view') => $this->relationRenderView()];
    }

    /**
     * onRelationManagePivotUpdate
     */
    public function onRelationManagePivotUpdate()
    {
        $this->beforeAjax();

        // Save data to model
        $saveData = $this->pivotWidget->getSaveData();
        $modelsToSave = $this->prepareModelsToSave($this->pivotModel, $saveData);

        foreach ($modelsToSave as $modelToSave) {
            $modelToSave->save(null, $this->pivotWidget->getSessionKey());
        }

        $this->showFlashMessage('flashUpdate');

        return ['#'.$this->relationGetId('view') => $this->relationRenderView()];
    }

    /**
     * evalPivotTitle determines the pivot mode popup title
     */
    protected function evalPivotTitle(): string
    {
        if ($customTitle = $this->getConfig('pivot[title]')) {
            return $customTitle;
        }

        return $this->getCustomLang('titlePivotForm');
    }
}
