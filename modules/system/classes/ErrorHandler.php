<?php namespace System\Classes;

use View;
use Lang;
use System;
use Cms\Classes\Controller as CmsController;
use October\Rain\Exception\ErrorHandler as ErrorHandlerBase;
use October\Rain\Exception\ApplicationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

/**
 * ErrorHandler handles application exception events
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ErrorHandler extends ErrorHandlerBase
{
    /**
     * @inheritDoc
     */
    public function handleException(Exception $proposedException)
    {
        return parent::handleException($this->prepareException($proposedException));
    }

    /**
     * beforeReport Twig errors masking Http exceptions
     */
    public function beforeReport($exception)
    {
        return $this->prepareException($exception);
    }

    /**
     * handleCustomError looks up an error page using the CMS route "/error". If the route
     * does not exist, this function will use the error view found in the CMS module.
     * @return mixed Error page contents.
     */
    public function handleCustomError()
    {
        if (System::checkDebugMode()) {
            return null;
        }

        if (System::hasModule('Cms')) {
            $result = CmsController::pageError();
        }
        else {
            $result = View::make('system::error');
        }

        // Extract content from response object
        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            $result = $result->getContent();
        }

        return $result;
    }

    /**
     * handleCustomNotFound checks if using a custom 404 page, if so return the contents.
     * Return NULL if a custom 404 is not set up.
     * @return mixed 404 page contents.
     */
    public function handleCustomNotFound()
    {
        if (System::hasModule('Cms')) {
            $result = CmsController::pageNotFound();
        }
        else {
            $result = View::make('system::404');
        }

        // Extract content from response object
        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            $result = $result->getContent();
        }

        return $result;
    }

    /**
     * handleDetailedError displays the detailed system exception page.
     * @return View Object containing the error page.
     */
    public function handleDetailedError($exception)
    {
        // Ensure System view path is registered
        View::addNamespace('system', base_path().'/modules/system/views');

        return View::make('system::exception', ['exception' => $exception]);
    }

    /**
     * getDetailedMessage returns a more descriptive error message based on the context.
     * @param Exception $exception
     * @return string
     */
    public static function getDetailedMessage($exception)
    {
        // ApplicationException never displays a detailed error
        if ($exception instanceof ApplicationException) {
            return $exception->getMessage();
        }

        // Debug mode is on
        if (System::checkDebugMode()) {
            return parent::getDetailedMessage($exception);
        }

        // Prevent PHP and database exceptions from leaking
        if (
            $exception instanceof \Illuminate\Database\QueryException ||
            $exception instanceof \ErrorException
        ) {
            return Lang::get('system::lang.page.custom_error.help');
        }

        return $exception->getMessage();
    }

    /**
     * prepareException
     */
    protected function prepareException(Exception $exception)
    {
        if (
            $exception instanceof \Twig\Error\RuntimeError &&
            ($previousException = $exception->getPrevious())
        ) {
            // The Twig runtime error is not very useful sometimes, so
            // uncomment this for an alternative debugging option
            // if (!$previousException instanceof \Cms\Classes\CmsException) {
            //     $exception = $previousException;
            // }

            // Convert HTTP exceptions
            if ($previousException instanceof HttpException) {
                $exception = $previousException;
            }

            // Convert Not Found exceptions
            if ($this->isNotFoundException($previousException)) {
                $exception = $previousException;
            }
        }

        return $exception;
    }
}
