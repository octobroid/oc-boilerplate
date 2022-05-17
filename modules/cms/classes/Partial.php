<?php namespace Cms\Classes;

/**
 * The CMS partial class.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class Partial extends CmsCompoundObject
{
    /**
     * @var string The container name associated with the model, eg: pages.
     */
    protected $dirName = 'partials';

    /**
     * Returns name of a PHP class to us a parent for the PHP class created for the object's PHP section.
     * @return string Returns the class name.
     */
    public function getCodeClassParent()
    {
        return PartialCode::class;
    }

    /**
     * validateRequestName checks to see if a partial name is valid from user input.
     */
    public static function validateRequestName(string $name): bool
    {
        if (!preg_match('/^(?:\w+\:{2}|@)?[a-z0-9\_\-\.\/]+$/i', $name)) {
            return false;
        }

        if (strpos($name, '..') !== false) {
            return false;
        }

        if (strpos($name, './') !== false || strpos($name, '//') !== false) {
            return false;
        }

        $maxNesting = (new self)->getMaxNesting();
        $segments = explode('/', $name);
        if ($maxNesting !== null && count($segments) > $maxNesting) {
            return false;
        }

        return true;
    }
}
