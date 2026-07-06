<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('المشرف يمكنه التسجيل ببريد جامعي', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Dr. Ahmed',
        'email' => 'ahmed@iugaza.edu.ps',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'supervisor',
        'employee_id' => 'EMP999',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    $response->assertStatus(201);
});

test('المشرف لا يمكنه التسجيل ببريد شخصي', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Dr. Ahmed',
        'email' => 'ahmed@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'supervisor',
        'employee_id' => 'EMP999',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('الطالب يمكنه التسجيل بأي بريد', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Student',
        'email' => 'student@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20248888',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response->assertStatus(201);
});

test('المزود يمكنه التسجيل بأي بريد', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Provider',
        'email' => 'provider@company.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'provider',
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $response->assertStatus(201);
});

test('المشرف يمكنه التسجيل ببريد جامعة الأزهر', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Dr. Sara',
        'email' => 'sara@alazhar.edu.ps',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'supervisor',
        'employee_id' => 'EMP888',
        'department' => 'IT',
        'academic_title' => 'assistant_professor',
    ]);

    $response->assertStatus(201);
});
