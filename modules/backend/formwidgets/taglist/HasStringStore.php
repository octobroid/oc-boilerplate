<?php namespace Backend\FormWidgets\TagList;

/**
 * HasStringStore contains logic for related tag items
 */
trait HasStringStore
{
    /**
     * getLoadValueFromString
     */
    protected function getLoadValueFromString($value)
    {
        return explode($this->getSeparatorCharacter(), $value);
    }

    /**
     * processSaveForString
     */
    protected function processSaveForString($value)
    {
        if (is_array($value)) {
            return implode($this->getSeparatorCharacter(), $value);
        }

        return $value;
    }

    /**
     * getCustomSeparators returns character(s) to use for separating keywords.
     * @return mixed
     */
    protected function getCustomSeparators()
    {
        if (!$this->customTags) {
            return false;
        }

        $separators = [];

        $separators[] = $this->getSeparatorCharacter();

        return implode('|', $separators);
    }

    /**
     * getSeparatorCharacter convert the character word to the singular character.
     */
    protected function getSeparatorCharacter(): string
    {
        switch (strtolower($this->separator)) {
            case 'comma':
                return ',';
            case 'space':
                return ' ';
        }
    }
}
