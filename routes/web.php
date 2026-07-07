<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================
// ⚠️ TEMPORARY SETUP ROUTE - REMOVE AFTER SETUP!
// ============================================
Route::get('/setup-migrate', function () {
    $results = [
        'success' => false,
        'steps' => [],
    ];

    try {
        // Step 1: Test database connection
        try {
            DB::connection()->getPdo();
            $results['steps'][] = [
                'step' => 1,
                'name' => 'Database Connection',
                'status' => '✅ OK',
            ];
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        // Step 2: Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = Artisan::output();
            $results['steps'][] = [
                'step' => 2,
                'name' => 'Migrations',
                'status' => '✅ OK',
                'output' => $migrationOutput,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Migration failed: ' . $e->getMessage());
        }

        // Step 3: Create admin
        try {
            $admin = \App\Models\User::where('role', 'admin')->first();

            if (!$admin) {
                $password = 'Admin@123456';
                \App\Models\User::create([
                    'name' => 'System Admin',
                    'email' => 'admin@trinova.com',
                    'password' => bcrypt($password),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                    'account_status' => 'active',
                    'preferred_language' => 'ar',
                ]);

                $results['steps'][] = [
                    'step' => 3,
                    'name' => 'Admin Creation',
                    'status' => '✅ Created',
                    'credentials' => [
                        'email' => 'admin@trinova.com',
                        'password' => $password,
                    ],
                ];
            } else {
                $results['steps'][] = [
                    'step' => 3,
                    'name' => 'Admin Creation',
                    'status' => '⚠️ Already exists',
                    'email' => $admin->email,
                ];
            }
        } catch (\Exception $e) {
            throw new \Exception('Admin creation failed: ' . $e->getMessage());
        }

        // Step 4: Clear cache
        try {
            Artisan::call('optimize:clear');
            $results['steps'][] = [
                'step' => 4,
                'name' => 'Cache Clear',
                'status' => '✅ OK',
            ];
        } catch (\Exception $e) {
            $results['steps'][] = [
                'step' => 4,
                'name' => 'Cache Clear',
                'status' => '⚠️ Warning: ' . $e->getMessage(),
            ];
        }

        // Step 5: Check health
        try {
            Artisan::call('health:check');
            $results['steps'][] = [
                'step' => 5,
                'name' => 'Health Check',
                'status' => '✅ OK',
            ];
        } catch (\Exception $e) {
            $results['steps'][] = [
                'step' => 5,
                'name' => 'Health Check',
                'status' => '⚠️ Warning: ' . $e->getMessage(),
            ];
        }

        $results['success'] = true;
        $results['message'] = '✅ Setup completed successfully!';

    } catch (\Exception $e) {
        $results['error'] = $e->getMessage();
        $results['file'] = $e->getFile();
        $results['line'] = $e->getLine();
        $results['trace'] = config('app.debug') ? explode("\n", $e->getTraceAsString()) : null;
    }

    return response()->json($results);
});

// ============================================
// Home Route
// ============================================
Route::get('/', function () {
    return response()->json([
        'app' => 'Trinova Platform',
        'version' => '1.0.0',
        'status' => 'active',
        'timestamp' => now()->toIso8601String(),
    ]);
});
