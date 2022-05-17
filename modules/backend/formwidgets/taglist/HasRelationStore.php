<?php namespace Backend\FormWidgets\TagList;

use Illuminate\Support\Collection;
use October\Rain\Database\Relations\Relation as RelationBase;

/**
 * HasRelationStore contains logic for related tag items
 */
trait HasRelationStore
{
    /**
     * getLoadValueFromRelation
     */
    protected function getLoadValueFromRelation($names)
    {
        // Take value from options
        if ($this->formField->hasOptions()) {
            if (!$names) {
                return [];
            }

            $result = (new Collection($this->formField->options()))
                ->reject(function($value, $key) use ($names) {
                    return !in_array($key, $names);
                })
                ->all();
        }
        // Take existing relationship
        else {
            $result = $this->getRelationObject()->pluck($this->nameFrom)->all();
        }

        // Default value
        if (!$result && !$this->model->exists) {
            return $names;
        }

        return $result;
    }

    /**
     * getFieldOptionsForRelation
     */
    protected function getFieldOptionsForRelation()
    {
        return RelationBase::noConstraints(function () {
            $query = $this->getRelationObject()->newQuery();

            // Even though "no constraints" is applied, belongsToMany constrains the query
            // by joining its pivot table. Remove all joins from the query.
            $query->getQuery()->getQuery()->joins = [];

            return $query->pluck($this->nameFrom)->all();
        });
    }

    /**
     * processSaveForRelation
     */
    protected function processSaveForRelation($names)
    {
        if (!$names) {
            return $names;
        }

        $relationModel = $this->getRelationModel();

        // Options from form field
        if ($this->formField->hasOptions()) {
            $existingTags = (new Collection($this->formField->options()))
                ->reject(function($value, $key) use ($names) {
                    return !in_array($value, $names);
                })
                ->all()
            ;
        }
        // Options from model
        else {
            $existingTags = $relationModel
                ->whereIn($this->nameFrom, $names)
                ->pluck($this->nameFrom, $relationModel->getKeyName())
                ->all()
            ;
        }

        // Allow custom tags
        if ($this->customTags) {
            $newTags = array_diff($names, $existingTags);

            foreach ($newTags as $newTag) {
                $newModel = $relationModel->newInstance();
                $newModel->{$this->nameFrom} = $newTag;
                $newModel->save(['force' => true]);

                $existingTags[$newModel->getKey()] = $newTag;
            }
        }

        return array_keys($existingTags);
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
}
