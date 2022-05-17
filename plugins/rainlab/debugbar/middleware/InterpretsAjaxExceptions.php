<?php namespace RainLab\Debugbar\Middleware;

use Request;
use Closure;
use Response;
use Exception;
use Illuminate\Foundation\Application;
use October\Rain\Exception\ErrorHandler;
use October\Rain\Exception\AjaxException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class InterpretsAjaxExceptions
{
    /**
     * The Laravel Application
     *
     * @var Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     *
     * @param  Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugbar */
        $debugbar = $this->app['debugbar'];

        try {
            return $next($request);
        } catch (Exception $ex) {
            if (!Request::ajax()) {
                throw $ex;
            }
            $debugbar->addException($ex);
            $message = $ex instanceof AjaxException
                ? $ex->getContents() : ErrorHandler::getDetailedMessage($ex);

            return Response::make($message, $this->getStatusCode($ex), $debugbar->getDataAsHeaders());
        }
    }

    /**
     * Checks if the exception implements the HttpExceptionInterface, or returns
     * as generic 500 error code for a server side error.
     * @return int
     */
    protected function getStatusCode($exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        } elseif ($exception instanceof AjaxException) {
            $code = 406;
        } else {
            $code = 500;
        }

        return $code;
    }
}
