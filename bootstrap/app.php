<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ✅ Register custom middlewares
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);

        // ✅ Apply SetLocale middleware AFTER auth:sanctum
        // Use append instead of prepend
        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // ✅ Replace default Authenticate middleware
        $middleware->redirectGuestsTo(fn () => response()->json([
            'success' => false,
            'message' => __('messages.general.unauthorized_login'),
        ], 401));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ✅ Handle AuthenticationException (401)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.general.unauthorized_login'),
            ], 401);
        });

        // ✅ Handle AuthorizationException (403)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.general.forbidden'),
            ], 403);
        });

        // ✅ Handle ValidationException (422)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.general.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        });

        // ✅ Handle NotFoundHttpException (404)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.general.not_found'),
            ], 404);
        });

        // ✅ Handle all other errors (500)
        $exceptions->render(function (\Throwable $e, $request) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            $response = [
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : __('messages.general.server_error'),
            ];

            if (config('app.debug')) {
                $response['exception'] = get_class($e);
                $response['file'] = basename($e->getFile());
                $response['line'] = $e->getLine();
            }
            return response()->json($response, $statusCode);
        });
    })->create();
