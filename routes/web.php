<?php

use Illuminate\Support\Facades\Route;

// ✅ هذا السطر يحل مشكلة "Route [login] not defined" نهائياً
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'هذا API فقط. يرجى استخدام /api/login لتسجيل الدخول'
    ], 401);
})->name('login');

Route::get('/', function () {
    return view('welcome');
});
