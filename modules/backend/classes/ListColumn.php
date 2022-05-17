<?php namespace Backend\Classes;

use October\Rain\Database\Model;
use October\Rain\Html\Helper as HtmlHelper;
use October\Rain\Element\Lists\ColumnDefinition;

/**
 * List Columns definition
 * A translation of the list column configuration
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ListColumn extends ColumnDefinition
{
    /**
     * @var string valueFrom is a model attribute to use for the accessed value
     */
    public $valueFrom;

    /**
     * @var string displayFrom is a model attribute to use for the displayed value
     */
    public $displayFrom;

    /**
     * @var string defaults specifies a default value when value is empty
     */
    public $defaults;

    /**
     * @var string sqlSelect is a custom SQL for selecting this record display value,
     * the `@` symbol is replaced with the table name.
     */
    public $sqlSelect;

    /**
     * @var string Relation name, if this column represents a model relationship.
     */
    public $relation;

    /**
     * @var bool Count mode to display the number of related records.
     */
    public $relationCount = false;

    /**
     * @var string sets the column width, can be specified in percents (10%) or pixels (50px).
     * There could be a single column without width specified, it will be stretched to take the
     * available space.
     */
    public $width;

    /**
     * @var string Specify a CSS class to attach to the list cell element.
     */
    public $cssClass;

    /**
     * @var string Specify a CSS class to attach to the list header cell element.
     */
    public $headCssClass;

    /**
     * @var string Specify a format or style for the column value, such as a Date.
     */
    public $format;

    /**
     * @var string Specifies a path for partial-type fields.
     */
    public $path;

    /**
     * __construct the column
     * @todo remove this method if year >= 2023
     */
    public function __construct($columnName, $label)
    {
        parent::__construct((string) $columnName);

        $this->label((string) $label);
    }

    /**
     * evalConfig from an array and apply them to the object
     */
    protected function evalConfig(array $config): void
    {
        parent::evalConfig($config);

        if (isset($config['width'])) {
            $this->width = $config['width'];
        }
        if (isset($config['cssClass'])) {
            $this->cssClass = $config['cssClass'];
        }
        if (isset($config['headCssClass'])) {
            $this->headCssClass = $config['headCssClass'];
        }
        if (isset($config['valueFrom'])) {
            $this->valueFrom = $config['valueFrom'];
        }
        if (isset($config['displayFrom'])) {
            $this->displayFrom = $config['displayFrom'];
        }
        if (isset($config['default'])) {
            $this->defaults = $config['default'];
        }
        if (isset($config['select'])) {
            $this->sqlSelect = $config['select'];
        }
        if (isset($config['relation'])) {
            $this->relation = $config['relation'];
        }
        if (isset($config['relationCount'])) {
            $this->relationCount = (bool) $config['relationCount'];
        }
        if (isset($config['format'])) {
            $this->format = $config['format'];
        }
        if (isset($config['path'])) {
            $this->path = $config['path'];
        }
    }

    /**
     * getName returns a HTML valid name for the column name.
     * @return string
     */
    public function getName()
    {
        return HtmlHelper::nameToId($this->columnName);
    }

    /**
     * getId returns a value suitable for the column id property.
     * @param  string $suffix Specify a suffix string
     * @return string
     */
    public function getId($suffix = null)
    {
        $id = 'column';

        $id .= '-'.$this->columnName;

        if ($suffix) {
            $id .= '-'.$suffix;
        }

        return HtmlHelper::nameToId($id);
    }

    /**
     * getAlignClass returns the column specific aligment css class.
     * @return string
     */
    public function getAlignClass()
    {
        return $this->align ? 'list-cell-align-' . $this->align : '';
    }

    /**
     * useRelationCount
     */
    public function useRelationCount(): bool
    {
        if (!$this->relation) {
            return false;
        }

        // @deprecated use relationCount instead
        if (($value = $this->getConfig('useRelationCount')) !== null) {
            return $value;
        }

        return $this->relationCount;
    }

    /**
     * getConfig returns a raw config item value
     * @param  string $value
     * @param  string $default
     * @return mixed
     */
    public function getConfig($value, $default = null)
    {
        return array_get($this->config, $value, $default);
    }

    /**
     * getValueFromData returns this columns value from a supplied data set, which can be
     * an array or a model or another generic collection.
     * @param mixed $data
     * @param mixed $default
     * @return mixed
     */
    public function getValueFromData($data, $default = null)
    {
        $columnName = $this->valueFrom ?: $this->columnName;

        return $this->getColumnNameFromData($columnName, $data, $default);
    }

    /**
     * Internal method to extract the value of a column name from a data set.
     * @param string $columnName
     * @param mixed $data
     * @param mixed $default
     * @return mixed
     */
    protected function getColumnNameFromData($columnName, $data, $default = null)
    {
        /*
         * Array column name, eg: column[key][key2][key3]
         */
        $keyParts = HtmlHelper::nameToArray($columnName);
        $result = $data;

        /*
         * Loop the column key parts and build a value.
         * To support relations only the last column should return the
         * relation value, all others will look up the relation object as normal.
         */
        foreach ($keyParts as $key) {
            if ($result instanceof Model && $result->hasRelation($key)) {
                $result = $result->{$key};
            }
            else {
                if (is_array($result) && array_key_exists($key, $result)) {
                    $result = $result[$key];
                }
                elseif (!isset($result->{$key})) {
                    return $default;
                }
                else {
                    $result = $result->{$key};
                }
            }
        }

        return $result;
    }
}
