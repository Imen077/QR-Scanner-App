<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'apiKey' => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                // handle validation exception
            if ($e instanceof ValidationException) {
                return response()->json(
                    [
                    'status' => 'Error',
                    'message' => 'Validation Error',
                    'errors' => $e->errors(),
                    ], 
                     422,
                );
              }
            //   handling authentication exception
            if ($e instanceof AuthenticationException) {
                return response()->json(
                    [
                    'status' => 'Error',
                    'message' => 'Unauthenticated',
                    'errors' => [],
                    ], 
                     401,
                );
            }
            //handle authorization exception
            if ($e instanceof AuthorizationException) {
                return response()->json(
                    [
                    'status' => 'Error',
                    'message' => 'Unauthorized',
                    'errors' => [],
                    ], 
                     403,
                );
            }

            //Fallback to 500
            return response()->json(
                [
                'status' => 'Error',
                'message' => $e->getMessage() ?: 'Internal Server Error',
                'errors' => [],
                ], 
                $e->getCode() ?: 500,
            );
          }
        });
    })
->create();