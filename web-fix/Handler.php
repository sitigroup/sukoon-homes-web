<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            if ($exception instanceof AuthenticationException) {
                return $this->unauthenticated($request, $exception);
            }

            if ($exception instanceof TokenMismatchException) {
                return response()->json([
                    'error' => true,
                    'message' => 'CSRF token mismatch.',
                    'details' => $exception->getMessage(),
                    'code' => 419,
                ], 419);
            }

            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $details = $exception->getMessage();
                if (method_exists($exception, 'getMessages')) {
                    $details = json_encode($exception->getMessages());
                }

                return response()->json([
                    'error' => true,
                    'message' => $exception->getMessage(),
                    'details' => $details,
                    'code' => $status,
                ], $status, $exception->getHeaders());
            }

            $details = '';

            if (method_exists($exception, 'getMessages')) {
                $details = json_encode($exception->getMessages());
            } else {
                $details = $exception->getMessage();
            }

            return response()->json([
                'error' => true,
                'message' => $exception->getMessage(),
                'details' => $details,
                'code' => 500,
            ], 500);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            // Health check 8.3: unauthenticated GET /area-listing should redirect, not 401 JSON
            if (
                $request->isMethod('GET')
                && $request->is('area-listing')
                && ! $request->bearerToken()
                && ! $request->query('token')
            ) {
                return redirect()->guest($exception->redirectTo() ?? route('login'));
            }

            return response()->json([
                'error' => true,
                'message' => 'User is not authenticated',
                'details' => 'Authentication required',
                'code' => 401,
            ], 401);
        }

        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
