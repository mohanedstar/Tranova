<?php

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ==================== Registration Tests ====================

test('user can register successfully', function () {
    // يمكن للمستخدم التسجيل بنجاح
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '0591234567',
        'role' => 'student',
        'student_id' => '20240099',
        'major' => 'IT',
        'university' => 'Test University',
        'year_of_study' => '3',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => 'student',
    ]);
})->group('auth', 'registration');

test('user can login successfully', function () {
    // يمكن للمستخدم تسجيل الدخول بنجاح
    $user = User::factory()->create([
        'email' => 'login@test.com',
        'password' => bcrypt('password123'),
        'role' => 'student',
        'email_verified_at' => now(),
    ]);

    Student::create([
        'user_id' => $user->id,
        'student_id' => '20240088',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token',
            'user',
        ]);
})->group('auth', 'login');

test('login fails with invalid credentials', function () {
    // فشل تسجيل الدخول ببيانات خاطئة
    $response = $this->postJson('/api/login', [
        'email' => 'wrong@test.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
})->group('auth', 'login');

test('authenticated user can access profile', function () {
    // المستخدم المصادق عليه يمكنه الوصول للملف الشخصي
    $user = User::factory()->create([
        'email' => 'profile@test.com',
        'role' => 'student',
        'email_verified_at' => now(),
    ]);

    Student::create([
        'user_id' => $user->id,
        'student_id' => '20240077',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user',
            'email_verified',
        ]);
})->group('auth', 'profile');

test('unauthenticated user cannot access profile', function () {
    // المستخدم غير المصادق عليه لا يمكنه الوصول للملف الشخصي
    $response = $this->getJson('/api/profile');

    $response->assertStatus(401);
})->group('auth', 'profile');

test('user cannot register with duplicate email', function () {
    // لا يمكن للمستخدم التسجيل ببريد مكرر
    User::factory()->create([
        'email' => 'duplicate@test.com',
        'role' => 'student',
    ]);

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'duplicate@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20240066',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
})->group('auth', 'registration', 'validation');

test('user cannot register with weak password', function () {
    // لا يمكن للمستخدم التسجيل بكلمة مرور ضعيفة
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'weak@test.com',
        'password' => '123',
        'password_confirmation' => '123',
        'role' => 'student',
        'student_id' => '20240055',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
})->group('auth', 'registration', 'validation');

test('user cannot register with invalid role', function () {
    // لا يمكن للمستخدم التسجيل بدور غير صالح
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'invalid@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'invalid_role',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
})->group('auth', 'registration', 'validation');
