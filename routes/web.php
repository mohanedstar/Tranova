<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// ============================================
// ⚠️ TEMPORARY SETUP ROUTE - NO SESSION!
// ============================================
Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
])->get('/setup-migrate', function () {

    // Disable logging temporarily to avoid permission issues
    config(['logging.default' => 'errorlog']);

    $results = ['success' => false, 'steps' => []];

    try {
        // Step 1: Test DB
        DB::connection()->getPdo();
        $results['steps'][] = ['step' => 1, 'name' => 'Database', 'status' => '✅ OK'];

        // Step 2: Run migrations
        Artisan::call('migrate', ['--force' => true]);
        $results['steps'][] = [
            'step' => 2,
            'name' => 'Migrations',
            'status' => '✅ OK',
            'output' => Artisan::output()
        ];

        // Step 3: Create admin
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
                'name' => 'Admin',
                'status' => '✅ Created',
                'email' => 'admin@trinova.com',
                'password' => $password,
            ];
        } else {
            $results['steps'][] = [
                'step' => 3,
                'name' => 'Admin',
                'status' => '⚠️ Exists',
                'email' => $admin->email,
            ];
        }

        // Step 4: Clear cache
        Artisan::call('optimize:clear');
        $results['steps'][] = ['step' => 4, 'name' => 'Cache', 'status' => '✅ OK'];

        $results['success'] = true;
        $results['message'] = '✅ Setup completed!';

    } catch (\Exception $e) {
        $results['error'] = $e->getMessage();
        $results['file'] = basename($e->getFile());
        $results['line'] = $e->getLine();
    }

    return response()->json($results)->header('Content-Type', 'application/json');
});

// Home
Route::get('/', function () {
    return response()->json([
        'app' => 'Trinova Platform',
        'version' => '1.0.0',
        'status' => 'active',
    ]);
});
