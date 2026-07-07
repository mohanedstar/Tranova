<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint for Render monitoring
     * Returns system status without requiring authentication
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
            ],
        ];

        // Determine overall status
        $allHealthy = collect($checks['checks'])->every(fn($check) => $check['status'] === 'ok');
        $checks['status'] = $allHealthy ? 'ok' : 'degraded';

        $httpCode = $allHealthy ? 200 : 503;

        return response()->json($checks, $httpCode);
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'driver' => config('database.default'),
                'duration_ms' => $duration,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => config('app.debug') ? $e->getMessage() : 'Database connection failed',
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $value === 'ok' ? 'ok' : 'error',
                'driver' => config('cache.default'),
                'duration_ms' => $duration,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => config('app.debug') ? $e->getMessage() : 'Cache check failed',
            ];
        }
    }

    /**
     * Check storage writability
     */
    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/health_check_test.txt');
            $written = file_put_contents($testFile, 'test');
            
            if ($written !== false) {
                unlink($testFile);
                return [
                    'status' => 'ok',
                    'path' => storage_path(),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Storage not writable',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => config('app.debug') ? $e->getMessage() : 'Storage check failed',
            ];
        }
    }
}