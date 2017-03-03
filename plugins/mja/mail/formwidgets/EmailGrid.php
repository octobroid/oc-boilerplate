<?php namespace Mja\Mail\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Backend\Widgets\Grid;

class EmailGrid extends FormWidgetBase
{
    public function widgetDetails()
    {
        return [
            'name'        => 'mja.mail::lang.formwidget.title',
            'description' => 'mja.mail::lang.formwidget.description'
        ];
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('emailgrid');
    }

    public function prepareVars()
    {
        $this->vars['emails'] = (array) $this->formField->value;
    }

    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }
}
