<?php namespace System\Traits;

/**
 * PropertyContainer adds properties and methods for classes that could define properties,
 * like components or report widgets.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait PropertyContainer
{
    /**
     * @var array properties contains the object property values.
     */
    protected $properties = [];

    /**
     * validateProperties against the defined properties of the class.
     * This method also sets default properties.
     * @param array $properties The supplied property values.
     * @return array The validated property set, with defaults applied.
     */
    public function validateProperties(array $properties)
    {
        $definedProperties = $this->defineProperties() ?: [];

        /*
         * Determine and implement default values
         */
        $defaultProperties = [];

        foreach ($definedProperties as $name => $information) {
            if (array_key_exists('default', $information)) {
                $defaultProperties[$name] = $information['default'];
            }
        }

        $properties = array_merge($defaultProperties, $properties);

        return $properties;
    }

    /**
     * defineProperties used by this class.
     * This method should be used as an override in the extended class.
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * setProperties for multiple values
     * @param array $properties
     * @return void
     */
    public function setProperties($properties)
    {
        $this->properties = $this->validateProperties($properties);
    }

    /**
     * setProperty for a single value
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setProperty($name, $value)
    {
        array_set($this->properties, $name, $value);
    }

    /**
     * getProperties returns all properties.
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * property returns a defined property value or default if one is not set.
     * @param string $name The property name to look for.
     * @param string $default A default value to return if no name is found.
     * @return string The property value or the default specified.
     */
    public function property($name, $default = null)
    {
        return array_get($this->properties, $name, $default);
    }

    /**
     * getPropertyOptions returns options for multi-option properties (drop-downs, etc.)
     * @param string $property Specifies the property name
     * @return array Return an array of option values and descriptions
     */
    public function getPropertyOptions($property)
    {
        return [];
    }
}
