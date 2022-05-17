<?php namespace Backend\FormWidgets\Repeater;

/**
 * HasJsonStore contains logic for related repeater items
 */
trait HasJsonStore
{
    /**
     * processSaveForJson
     */
    protected function processSaveForJson($value)
    {
        foreach ($value as $index => $data) {
            if (!isset($this->formWidgets[$index])) {
                continue;
            }

            // Give repeated form field widgets an opportunity to process the data.
            $widget = $this->formWidgets[$index];
            $value[$index] = $widget->getSaveData();

            if ($this->useGroups) {
                $this->setGroupCodeOnJson($value[$index], $data[$this->groupKeyFrom] ?? '');
            }
        }

        return array_values($value);
    }

    /**
     * getGroupCodeFromJson
     */
    protected function getGroupCodeFromJson($value)
    {
        return array_get($value, $this->groupKeyFrom, null);
    }

    /**
     * setGroupCodeOnJson
     */
    protected function setGroupCodeOnJson(&$value, $groupCode)
    {
        $value[$this->groupKeyFrom] = $groupCode;
    }
}
