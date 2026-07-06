<?php

use App\Models\User;
use App\Models\Provider;
use App\Models\InternshipOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // مدير
    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);

    // مزود قيد المراجعة
    $this->pendingProviderUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'pending_review',
    ]);
    $this->pendingProvider = Provider::create([
        'user_id' => $this->pendingProviderUser->id,
        'organization_name' => 'Pending Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // مزود نشط
    $this->activeProviderUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    $this->activeProvider = Provider::create([
        'user_id' => $this->activeProviderUser->id,
        'organization_name' => 'Active Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);
});

// ==================== التسجيل ====================

test('المزود الجديد يتم تسجيله بحالة pending_review', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'New Provider',
        'email' => 'newprovider@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'provider',
        'organization_name' => 'New Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'account_status' => 'pending_review',
    ]);

    $user = User::where('email', 'newprovider@test.com')->first();
    $this->assertEquals('pending_review', $user->account_status);
});

test('الطالب يتم تسجيله بحالة active مباشرة', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'New Student',
        'email' => 'newstudent@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20249999',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'account_status' => 'active',
    ]);
});

// ==================== تسجيل الدخول ====================

test('المزود قيد المراجعة لا يمكنه تسجيل الدخول', function () {
    $response = $this->postJson('/api/login', [
        'email' => $this->pendingProviderUser->email,
        'password' => 'password',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'account_status' => 'pending_review',
        ]);
});

test('المزود المرفوض لا يمكنه تسجيل الدخول', function () {
    $this->pendingProviderUser->reject('بيانات غير صحيحة');

    $response = $this->postJson('/api/login', [
        'email' => $this->pendingProviderUser->email,
        'password' => 'password',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'account_status' => 'rejected',
        ]);
});

// ==================== نشر الفرص ====================

test('المزود قيد المراجعة لا يمكنه نشر فرصة', function () {
    $response = $this->actingAs($this->pendingProviderUser)
        ->postJson('/api/provider/opportunities', [
            'title' => 'Test Opportunity',
            'description' => 'Test',
            'requirements' => 'Test',
            'required_major' => 'IT',
            'available_positions' => 2,
            'location' => 'Gaza',
            'duration_months' => 3,
            'application_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

    $response->assertStatus(403);
});

test('المزود النشط يمكنه نشر فرصة', function () {
    $response = $this->actingAs($this->activeProviderUser)
        ->postJson('/api/provider/opportunities', [
            'title' => 'Test Opportunity',
            'description' => 'Test',
            'requirements' => 'Test',
            'required_major' => 'IT',
            'available_positions' => 2,
            'location' => 'Gaza',
            'duration_months' => 3,
            'application_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

    $response->assertStatus(201);
});

// ==================== موافقة المدير ====================

test('المدير يمكنه عرض المزودين قيد المراجعة', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/providers/pending');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('المدير يمكنه الموافقة على مزود', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProviderUser->id}/approve");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => __('messages.admin.provider_approved'),
        ]);
});

test('المدير يمكنه رفض مزود مع سبب', function () {
    Notification::fake();

    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProviderUser->id}/reject", [
            'reason' => 'بيانات المؤسسة غير مكتملة',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->pendingProviderUser->refresh();
    $this->assertEquals('rejected', $this->pendingProviderUser->account_status);
    $this->assertEquals('بيانات المؤسسة غير مكتملة', $this->pendingProviderUser->rejection_reason);
});

test('غير المدير لا يمكنه الموافقة على مزود', function () {
    $response = $this->actingAs($this->activeProviderUser)
        ->postJson("/api/admin/providers/{$this->pendingProviderUser->id}/approve");

    $response->assertStatus(403);
});

test('بعد الموافقة يمكن للمزود نشر فرص', function () {
    Notification::fake();

    // الموافقة أولاً
    $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProviderUser->id}/approve");

    // ✅ مهم: إعادة تحميل المستخدم من قاعدة البيانات
    $this->pendingProviderUser->refresh();

    // التأكد من أن الحالة تم تحديثها
    $this->assertEquals('active', $this->pendingProviderUser->account_status);

    // الآن يحاول نشر فرصة
    $response = $this->actingAs($this->pendingProviderUser)
        ->postJson('/api/provider/opportunities', [
            'title' => 'Test Opportunity',
            'description' => 'Test',
            'requirements' => 'Test',
            'required_major' => 'IT',
            'available_positions' => 2,
            'location' => 'Gaza',
            'duration_months' => 3,
            'application_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

    $response->assertStatus(201);
});
