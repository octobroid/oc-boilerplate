<?php namespace System\Classes;

use Lang;
use System;
use ApplicationException;
use Illuminate\Routing\Controller as ControllerBase;
use Exception;
use Response;

/**
 * SystemController is the master controller for system related routing.
 * It is currently only responsible for serving up the asset combiner contents.
 *
 * @see System\Classes\CombineAssets Asset combiner class
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class SystemController extends ControllerBase
{
    /**
     * combine JavaScript and StyleSheet asset files
     * @param string $name Combined file code
     * @return string Combined content.
     */
    public function combine($name)
    {
        try {
            if (!strpos($name, '-')) {
                throw new ApplicationException(Lang::get('system::lang.combiner.not_found', ['name' => $name]));
            }

            $parts = explode('-', $name);

            $cacheId = $parts[0];

            $combiner = CombineAssets::instance();

            return $combiner->getContents($cacheId);
        }
        catch (Exception $ex) {
            if (System::checkDebugMode()) {
                return Response::make($ex, 404);
            }
            else {
                return Response::make('/* '.e($ex->getMessage()).' */', 404);
            }
        }
    }

    /**
     * resize an image
     * @param string $name Combined file code
     * @return RedirectResponse
     */
    public function resize($name)
    {
        try {
            if (!strpos($name, '-')) {
                throw new ApplicationException(Lang::get('system::lang.resizer.not_found', ['name' => $name]));
            }

            $parts = explode('-', $name);

            $cacheId = $parts[0];

            $combiner = ResizeImages::instance();

            return $combiner->getContents($cacheId);
        }
        catch (Exception $ex) {
            if (System::checkDebugMode()) {
                return Response::make($ex, 404);
            }
            else {
                return Response::make('/* '.e($ex->getMessage()).' */', 404);
            }
        }
    }
}
