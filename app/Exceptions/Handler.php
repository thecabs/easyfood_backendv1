<?php

namespace App\Exceptions;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;
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
        if ($request->expectsJson()) {
            if ($exception instanceof ValidationException) {
                return $this->validationError($exception->errors());
            }

            if ($exception instanceof ModelNotFoundException) {
                return $this->errorResponse('Ressource introuvable', 404);
            }

            if ($exception instanceof AuthorizationException) {
                return $this->errorResponse('Action non autorisée', 403);
            }

            if ($exception instanceof AuthenticationException) {
                return $this->errorResponse('Non authentifié', 401);
            }

            if ($exception instanceof NotFoundHttpException) {
                return $this->errorResponse('Route introuvable', 404);
            }

            if ($exception instanceof HttpException) {
                return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
            }

            // 🔥 Catch-all pour les erreurs techniques
            Log::error('Erreur technique API', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'user_id' => auth()->id() ?? null
            ]);

            return $this->errorResponse();
        }

        return parent::render($request, $exception);
    }
}
