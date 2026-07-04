<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ==================== إعداد الأدوار ====================

beforeEach(function () {
    // إنشاء مستخدمين لكل دور
    $this->studentUser = User::factory()->create(['role' => 'student']);
    Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $this->providerUser = User::factory()->create(['role' => 'provider']);
    Provider::create([
        'user_id' => $this->providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $this->supervisorUser = User::factory()->create(['role' => 'supervisor']);
    Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    $this->adminUser = User::factory()->create(['role' => 'admin']);
});

// ==================== اختبارات الطالب ====================

test('الطالب يمكنه الوصول للوحة تحكم الطالب', function () {
    $this->actingAs($this->studentUser)
        ->getJson('/api/student/dashboard')
        ->assertStatus(200);
});

test('الطالب لا يمكنه الوصول للوحة تحكم المزود', function () {
    $this->actingAs($this->studentUser)
        ->getJson('/api/provider/dashboard')
        ->assertStatus(403);
});

test('الطالب لا يمكنه الوصول للوحة تحكم المشرف', function () {
    $this->actingAs($this->studentUser)
        ->getJson('/api/supervisor/dashboard')
        ->assertStatus(403);
});

test('الطالب لا يمكنه الوصول للوحة تحكم المدير', function () {
    $this->actingAs($this->studentUser)
        ->getJson('/api/admin/dashboard')
        ->assertStatus(403);
});

// ==================== اختبارات المزود ====================

test('المزود يمكنه الوصول للوحة تحكم المزود', function () {
    $this->actingAs($this->providerUser)
        ->getJson('/api/provider/dashboard')
        ->assertStatus(200);
});

test('المزود لا يمكنه الوصول للوحة تحكم الطالب', function () {
    $this->actingAs($this->providerUser)
        ->getJson('/api/student/dashboard')
        ->assertStatus(403);
});

test('المزود لا يمكنه الوصول للوحة تحكم المشرف', function () {
    $this->actingAs($this->providerUser)
        ->getJson('/api/supervisor/dashboard')
        ->assertStatus(403);
});

test('المزود لا يمكنه الوصول للوحة تحكم المدير', function () {
    $this->actingAs($this->providerUser)
        ->getJson('/api/admin/dashboard')
        ->assertStatus(403);
});

// ==================== اختبارات المشرف ====================

test('المشرف يمكنه الوصول للوحة تحكم المشرف', function () {
    $this->actingAs($this->supervisorUser)
        ->getJson('/api/supervisor/dashboard')
        ->assertStatus(200);
});

test('المشرف لا يمكنه الوصول للوحة تحكم الطالب', function () {
    $this->actingAs($this->supervisorUser)
        ->getJson('/api/student/dashboard')
        ->assertStatus(403);
});

test('المشرف لا يمكنه الوصول للوحة تحكم المزود', function () {
    $this->actingAs($this->supervisorUser)
        ->getJson('/api/provider/dashboard')
        ->assertStatus(403);
});

test('المشرف لا يمكنه الوصول للوحة تحكم المدير', function () {
    $this->actingAs($this->supervisorUser)
        ->getJson('/api/admin/dashboard')
        ->assertStatus(403);
});

// ==================== اختبارات المدير ====================

test('المدير يمكنه الوصول للوحة تحكم المدير', function () {
    $this->actingAs($this->adminUser)
        ->getJson('/api/admin/dashboard')
        ->assertStatus(200);
});

test('المدير لا يمكنه الوصول للوحة تحكم الطالب', function () {
    $this->actingAs($this->adminUser)
        ->getJson('/api/student/dashboard')
        ->assertStatus(403);
});

test('المدير لا يمكنه الوصول للوحة تحكم المزود', function () {
    $this->actingAs($this->adminUser)
        ->getJson('/api/provider/dashboard')
        ->assertStatus(403);
});

test('المدير لا يمكنه الوصول للوحة تحكم المشرف', function () {
    $this->actingAs($this->adminUser)
        ->getJson('/api/supervisor/dashboard')
        ->assertStatus(403);
});

// ==================== اختبارات المصادقة ====================

test('المستخدم غير المصادق عليه لا يمكنه الوصول للوحات التحكم', function () {
    $this->getJson('/api/student/dashboard')->assertStatus(401);
    $this->getJson('/api/provider/dashboard')->assertStatus(401);
    $this->getJson('/api/supervisor/dashboard')->assertStatus(401);
    $this->getJson('/api/admin/dashboard')->assertStatus(401);
});

// ==================== اختبارات المسارات الحساسة ====================

test('الطالب لا يمكنه إنشاء فرصة', function () {
    $this->actingAs($this->studentUser)
        ->postJson('/api/provider/opportunities', [
            'title' => 'Test',
            'description' => 'Test',
            'required_major' => 'IT',
            'available_positions' => 2,
            'location' => 'Gaza',
            'duration_months' => 3,
            'application_deadline' => now()->addDays(30)->format('Y-m-d H:i:s'),
        ])
        ->assertStatus(403);
});

test('المزود لا يمكنه إرسال تقرير أسبوعي', function () {
    $this->actingAs($this->providerUser)
        ->postJson('/api/student/reports', [
            'opportunity_id' => 1,
            'report_date' => now()->format('Y-m-d'),
            'week_number' => 1,
            'training_hours' => 30,
            'completed_tasks' => 'Test',
        ])
        ->assertStatus(403);
});

test('الطالب لا يمكنه عرض قائمة الطلاب للمدير', function () {
    $this->actingAs($this->studentUser)
        ->getJson('/api/admin/students')
        ->assertStatus(403);
});

test('المشرف لا يمكنه توليد الشهادات', function () {
    $this->actingAs($this->supervisorUser)
        ->postJson('/api/admin/records/1/generate-certificate')
        ->assertStatus(403);
});
