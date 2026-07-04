<?php

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('يمكن للمستخدم التسجيل بنجاح', function () {
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
});

test('يمكن للمستخدم تسجيل الدخول', function () {
    $user = User::factory()->create([
        'email' => 'login@test.com',
        'password' => bcrypt('password123'),
        'role' => 'student',
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
});

test('فشل تسجيل الدخول ببيانات خاطئة', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'wrong@test.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'بيانات الدخول غير صحيحة',
        ]);
});

test('المستخدم المصادق عليه يمكنه الوصول للملف الشخصي', function () {
    $user = User::factory()->create(['role' => 'student']);

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
        ]);
});
