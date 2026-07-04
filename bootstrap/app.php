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
        // ✅ فقط سجل الـ Middleware المخصص للأدوار
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // ✅ استبدل الـ Authenticate middleware الافتراضي
        // ليُرجع JSON بدلاً من redirect لصفحة login
        $middleware->redirectGuestsTo(fn () => response()->json([
            'success' => false,
            'message' => 'غير مصرح - يرجى تسجيل الدخول أولاً',
        ], 401));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ✅ معالجة AuthenticationException
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح - يرجى تسجيل الدخول أولاً',
            ], 401);
        });

        // ✅ معالجة AuthorizationException
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المورد',
            ], 403);
        });

        // ✅ معالجة ValidationException
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors(),
            ], 422);
        });

        // ✅ معالجة NotFoundHttpException
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'المسار غير موجود',
            ], 404);
        });

        // ✅ معالجة عامة لجميع الأخطاء الأخرى
        $exceptions->render(function (\Throwable $e, $request) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            $response = [
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'حدث خطأ في الخادم',
            ];

            if (config('app.debug')) {
                $response['exception'] = get_class($e);
                $response['file'] = basename($e->getFile());
                $response['line'] = $e->getLine();
            }

            return response()->json($response, $statusCode);
        });
    })->create();
