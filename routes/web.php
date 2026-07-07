<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ============================================
// ⚠️ TEMPORARY SETUP ROUTE - REMOVE AFTER SETUP!
// ============================================
Route::get('/setup-migrate', function () {
    try {
        // 1. Run migrations
        Artisan::call('migrate', ['--force' => true]);
        $migrationOutput = Artisan::output();
        
        // 2. Create admin if not exists
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
            
            $adminInfo = "Admin created:\nEmail: admin@trinova.com\nPassword: {$password}\n⚠️ SAVE THIS PASSWORD!";
        } else {
            $adminInfo = "Admin already exists";
        }
        
        // 3. Clear cache
        Artisan::call('optimize:clear');
        $cacheOutput = Artisan::output();
        
        return response()->json([
            'success' => true,
            'message' => 'Setup completed successfully!',
            'migrations' => $migrationOutput,
            'admin' => $adminInfo,
            'cache' => $cacheOutput,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
});

// ============================================
// Home Route
// ============================================
Route::get('/', function () {
    return response()->json([
        'app' => 'Trinova Platform',
        'version' => '1.0.0',
        'status' => 'active',
        'api_docs' => url('/api/documentation'),
    ]);
});