<?php namespace Backend\Classes;

/**
 * WildcardController is used for controllers with a single action
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class WildcardController extends Controller
{
    /**
     * run the action that is always index
     */
    public function run($action = null, $params = [])
    {
        if ($action !== 'index') {
            $params = array_merge([$action], $params);
        }

        return parent::run('index', $params);
    }

    /**
     * actionExists is always true for wildcards
     */
    public function actionExists($name, $internal = false)
    {
        return true;
    }
}
