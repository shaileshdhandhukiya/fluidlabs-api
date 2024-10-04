<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Spatie\Permission\Exceptions\UnauthorizedException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Catch the Spatie UnauthorizedException
        if ($exception instanceof UnauthorizedException) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have the necessary permissions to access this resource.',
                'status' => 403, // HTTP 403 Forbidden
            ], 403);
        }

        return parent::render($request, $exception);
    }
}
