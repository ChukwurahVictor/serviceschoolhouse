<?php

namespace App\Exceptions;

use ErrorException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Swift_RfcComplianceException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
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

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => "Endpoint not found."
                ], 404);
            }
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => $e->getMessage()
                ], 405);
            }
        });

        $this->renderable(function (QueryException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => $e->getMessage()
                ], 500);
            }
        });

        $this->renderable(function (ErrorException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => $e->getMessage()
                ], 500);
            }
        });

        $this->renderable(function (Swift_RfcComplianceException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    "success" => false,
                    "message" => "Email could not be sent. " . $e->getMessage()
                ], 500);
            }
        });
    }
}
