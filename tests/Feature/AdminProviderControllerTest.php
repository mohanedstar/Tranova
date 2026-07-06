<?php

use App\Models\User;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // ✅ إنشاء Admin
    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);

    // ✅ إنشاء Student
    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);

    // ✅ إنشاء Provider قيد المراجعة
    $this->pendingProvider = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'pending_review',
    ]);
    Provider::create([
        'user_id' => $this->pendingProvider->id,
        'organization_name' => 'Pending Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // ✅ إنشاء Provider مرفوض
    $this->rejectedProvider = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'rejected',
        'rejection_reason' => 'بيانات غير مكتملة',
    ]);
    Provider::create([
        'user_id' => $this->rejectedProvider->id,
        'organization_name' => 'Rejected Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // ✅ إنشاء Provider موثق
    $this->activeProvider = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    Provider::create([
        'user_id' => $this->activeProvider->id,
        'organization_name' => 'Active Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);
});

// ═══════════════════════════════════════════════════════════════
// 📋 List Providers
// ═══════════════════════════════════════════════════════════════

test('admin can list all providers', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/providers');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('admin can list pending providers', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/providers/pending');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('student cannot list providers', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/admin/providers');

    $response->assertStatus(403);
});

test('provider cannot list other providers', function () {
    $response = $this->actingAs($this->activeProvider)
        ->getJson('/api/admin/providers');

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// ✅ Approve Provider
// ═══════════════════════════════════════════════════════════════

test('admin can approve pending provider', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProvider->id}/approve");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->pendingProvider->id,
        'account_status' => 'active',
    ]);
});

test('admin receives error when approving already active provider', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->activeProvider->id}/approve");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

test('admin receives error when approving non-existent provider', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/providers/99999/approve');

    $response->assertStatus(404);
});

test('student cannot approve provider', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/admin/providers/{$this->pendingProvider->id}/approve");

    $response->assertStatus(403);
});

test('approved provider can login', function () {
    // ✅ الموافقة على المزود
    $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProvider->id}/approve");

    // ✅ محاولة تسجيل الدخول
    $response = $this->postJson('/api/login', [
        'email' => $this->pendingProvider->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token',
            'user',
        ]);
});

// ═══════════════════════════════════════════════════════════════
// ❌ Reject Provider
// ═══════════════════════════════════════════════════════════════

test('admin can reject provider with reason', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProvider->id}/reject", [
            'reason' => 'بيانات المؤسسة غير صحيحة',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->pendingProvider->id,
        'account_status' => 'rejected',
        'rejection_reason' => 'بيانات المؤسسة غير صحيحة',
    ]);
});

test('admin cannot reject provider without reason', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$this->pendingProvider->id}/reject", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

test('rejected provider cannot login', function () {
    $response = $this->postJson('/api/login', [
        'email' => $this->rejectedProvider->email,
        'password' => 'password',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'account_status' => 'rejected',
        ]);
});

test('student cannot reject provider', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/admin/providers/{$this->pendingProvider->id}/reject", [
            'reason' => 'Test',
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// 🎯 Integration Tests
// ═══════════════════════════════════════════════════════════════

test('full provider workflow: register, pending, approve, login', function () {
    // 1️⃣ تسجيل مزود جديد
    $registerResponse = $this->postJson('/api/register', [
        'name' => 'Workflow Provider',
        'email' => 'workflow@provider.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'provider',
        'organization_name' => 'Workflow Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $registerResponse->assertStatus(201);
    $providerId = $registerResponse->json('user.id');

    expect($registerResponse->json('account_status'))->toBe('pending_review');

    // 2️⃣ محاولة تسجيل الدخول (يجب أن تفشل - البريد غير موثق)
    $loginResponse = $this->postJson('/api/login', [
        'email' => 'workflow@provider.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(403);

    // 3️⃣ ✅ التحقق من البريد الإلكتروني (خطوة جديدة!)
    $providerUser = \App\Models\User::find($providerId);
    $providerUser->markEmailAsVerified();

    // 4️⃣ محاولة تسجيل الدخول مرة أخرى (يجب أن تفشل - pending_review)
    $loginResponse2 = $this->postJson('/api/login', [
        'email' => 'workflow@provider.com',
        'password' => 'password123',
    ]);

    $loginResponse2->assertStatus(403)
        ->assertJson([
            'account_status' => 'pending_review',
        ]);

    // 5️⃣ المدير يوافق
    $approveResponse = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/providers/{$providerId}/approve");

    $approveResponse->assertStatus(200);

    // 6️⃣ تسجيل الدخول (يجب أن ينجح الآن!)
    $finalLoginResponse = $this->postJson('/api/login', [
        'email' => 'workflow@provider.com',
        'password' => 'password123',
    ]);

    $finalLoginResponse->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token',
            'user',
        ]);
});
