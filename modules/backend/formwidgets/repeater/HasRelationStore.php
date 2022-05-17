<?php namespace Backend\FormWidgets\Repeater;

use October\Rain\Database\Model;

/**
 * HasRelationStore contains logic for related repeater items
 */
trait HasRelationStore
{
    /**
     * processRelationMode
     */
    protected function processRelationMode()
    {
        [$model, $attribute] = $this->nearestModelAttribute($this->valueFrom);

        if ($model instanceof Model && $model->hasRelation($attribute)) {
            $this->useRelation = true;
        }
    }

    /**
     * getModelFromIndex returns the model at a given index
     */
    protected function getModelFromIndex(int $index)
    {
        return $this->getLoadValueFromRelation()[$index] ?? $this->getRelationModel();
    }

    /**
     * getLoadValueFromRelation
     */
    protected function getLoadValueFromRelation()
    {
        if ($this->relatedRecords !== null) {
            return $this->relatedRecords;
        }

        if ($this->isLoaded) {
            $value = $this->getLoadedValueFromPost();
            $ids = is_array($value) ? array_map(function($v) { return $v['_id'] ?? null; }, $value) : [];
            $records = $this->getRelationQuery()->find($ids);

            if ($records) {
                $indexes = array_flip($ids);
                foreach ($records as $model) {
                    $rIndex = $indexes[$model->getKey()] ?? null;
                    if ($rIndex !== null) {
                        $this->relatedRecords[$rIndex] = $model;
                    }
                }
            }
        }
        else {
            $this->relatedRecords = $this->getRelationObject()
                ->withDeferred($this->getSessionKey())
                ->get()
                ->all()
            ;
        }

        return $this->relatedRecords;
    }

    /**
     * getRelationQuery
     */
    protected function getRelationQuery()
    {
        $query = $this->getRelationModel()->newQuery();

        $this->getRelationObject()->addDefinedConstraintsToQuery($query);

        return $query;
    }

    /**
     * createRelationAtIndex prepares an empty model and adds it to the index
     */
    protected function createRelationAtIndex(int $index, string $groupCode = null)
    {
        $model = $this->getRelationModel();

        if ($this->useGroups) {
            $this->setGroupCodeOnRelation($model, $groupCode);
        }

        $model->save();

        $this->getRelationObject()->add($model, $this->sessionKey);

        $this->relatedRecords[$index] = $model;
    }

    /**
     * deleteRelationAtIndex
     */
    protected function deleteRelationAtIndex(int $index)
    {
        $model = $this->getModelFromIndex($index);
        if (!$model->exists) {
            return;
        }

        $this->getRelationObject()->remove($model, $this->sessionKey);
    }

    /**
     * processSaveForRelation
     */
    protected function processSaveForRelation($value)
    {
        $keys = [];
        $sortCount = 0;

        foreach ($value as $index => $data) {
            if (!isset($this->formWidgets[$index])) {
                continue;
            }

            // Give repeated form field widgets an opportunity to process the data.
            $widget = $this->formWidgets[$index];
            $saveData = $widget->getSaveData();

            // Save data to the model
            $model = $widget->model;

            $modelsToSave = $this->prepareModelsToSave($model, $saveData);

            if ($this->useGroups) {
                $this->setGroupCodeOnRelation($model, $data[$this->groupKeyFrom] ?? '');
            }

            if ($model->isClassInstanceOf(\October\Contracts\Database\SortableInterface::class)) {
                $this->processSortOrderForSortable($model, ++$sortCount);
            }

            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save(null, $widget->getSessionKeyWithSuffix());
            }

            $keys[] = $model->getKey();
        }

        return $keys;
    }

    /**
     * processSortOrderForSortable
     */
    protected function processSortOrderForSortable($model, $sortOrder): void
    {
        $orderColumn = $model->getSortOrderColumn();

        $model->$orderColumn = $sortOrder;
    }

    /**
     * getGroupCodeFromRelation
     */
    protected function getGroupCodeFromRelation($model)
    {
        $attrName = $this->groupKeyFrom;

        return $model->$attrName;
    }

    /**
     * setGroupCodeOnRelation
     */
    protected function setGroupCodeOnRelation($model, $groupCode)
    {
        $attrName = $this->groupKeyFrom;

        $model->$attrName = $groupCode;
    }
}
