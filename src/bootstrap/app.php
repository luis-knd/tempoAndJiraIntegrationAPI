<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (Middleware $middleware) {
            //
        }
    )
    ->withExceptions(
        function (Exceptions $exceptions) {
            $exceptions->render(function (ValidationException $throwable) {
                return jsonResponse(
                    status: Response::HTTP_UNPROCESSABLE_ENTITY,
                    message: $throwable->getMessage(),
                    errors: $throwable->errors()
                );
            });
            $exceptions->render(function (AccessDeniedHttpException $throwable) {
                return jsonResponse(
                    status: Response::HTTP_UNAUTHORIZED,
                    message: $throwable->getMessage()
                );
            });
            $exceptions->render(function (Exception $throwable) {
                return jsonResponse(
                    status: Response::HTTP_BAD_GATEWAY,
                    message: "Has error occurred",
                    errors: ['error' => $throwable->getMessage()]
                );
            });
        }
    )->create();
