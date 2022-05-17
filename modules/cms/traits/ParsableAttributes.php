<?php namespace Cms\Traits;

/**
 * ParsableAttributes allows CMS templates to use dynamic attributes
 *
 *     meta_title = "Blog - {{ post.title }}"
 *
 */
trait ParsableAttributes
{
    /**
     * @var array parsable attributes support using twig code.
     *
     * public $parsable = [];
     */

    /**
     * @var array parsableAttributes contains the translated attributes
     */
    protected $parsableAttributes = [];

    /**
     * __get with parsable attribute override.
     */
    public function __get($key)
    {
        if (
            in_array($key, $this->parsable) &&
            isset($this->parsableAttributes[$key])
        ) {
            return $this->parsableAttributes[$key];
        }

        return parent::__get($key);
    }

    /**
     * addParsable attributes for the model
     */
    public function addParsable(...$attributes)
    {
        if (is_array($attributes[0])) {
            $attributes = $attributes[0];
        }

        $this->parsable = array_merge($this->parsable, $attributes);
    }

    /**
     * setParsableAttribute
     */
    public function setParsableAttribute(string $key, $value): void
    {
        array_set($this->parsableAttributes, $key, $value);
    }

    /**
     * getParsableAttributes
     */
    public function getParsableAttributeValues(): array
    {
        $values = [];

        foreach ($this->parsable as $key) {
            $values[$key] = $this->$key;
        }

        return $values;
    }
}
