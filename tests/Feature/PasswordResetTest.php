<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ==================== طلب استعادة كلمة المرور ====================

test('المستخدم يمكنه طلب استعادة كلمة المرور', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/password/forgot', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200);

    // التحقق من وجود التوكن في قاعدة البيانات
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);

    Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
});

test('طلب استعادة كلمة المرور لبريد غير موجود', function () {
    $response = $this->postJson('/api/password/forgot', [
        'email' => 'nonexistent@example.com',
    ]);

    // يجب أن يفشل أو يُرجع رسالة عامة (لأمان أفضل)
    $this->assertTrue(in_array($response->status(), [200, 404, 422]));
});

test('طلب استعادة كلمة المرور بدون بريد إلكتروني', function () {
    $response = $this->postJson('/api/password/forgot', []);

    $response->assertStatus(422);
});

// ==================== التحقق من التوكن ====================

test('يمكن التحقق من توكن صالح', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    // إنشاء توكن يدوياً
    $token = 'test-token-123';
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/password/verify-token', [
        'token' => $token,
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => true,
        ]);
});

test('لا يمكن التحقق من توكن غير صالح', function () {
    $response = $this->postJson('/api/password/verify-token', [
        'token' => 'invalid-token',
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(400);
});

test('لا يمكن التحقق من توكن منتهي الصلاحية', function () {
    $token = 'expired-token';
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now()->subHours(2), // منتهي منذ ساعتين
    ]);

    $response = $this->postJson('/api/password/verify-token', [
        'token' => $token,
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(400);
});

// ==================== إعادة تعيين كلمة المرور ====================

test('يمكن إعادة تعيين كلمة المرور بتوكن صالح', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = 'reset-token-123';
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/password/reset', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(200);

    // التحقق من تغيير كلمة المرور
    $user->refresh();
    $this->assertTrue(Hash::check('new-password-123', $user->password));

    // التحقق من حذف التوكن
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

test('لا يمكن إعادة تعيين كلمة المرور بتوكن غير صالح', function () {
    // ✅ إنشاء مستخدم أولاً
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->postJson('/api/password/reset', [
        'token' => 'invalid-token',
        'email' => 'test@example.com',  // ✅ بريد موجود الآن
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(400);
});

test('لا يمكن إعادة تعيين كلمة المرور بكلمات مرور غير متطابقة', function () {
    $response = $this->postJson('/api/password/reset', [
        'token' => 'some-token',
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertStatus(422);
});

test('التوكن يُحذف بعد الاستخدام (استخدام لمرة واحدة)', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = 'one-time-token';
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    // الاستخدام الأول
    $this->postJson('/api/password/reset', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ]);

    // الاستخدام الثاني (يجب أن يفشل)
    $response = $this->postJson('/api/password/reset', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password-2',
        'password_confirmation' => 'new-password-2',
    ]);

    $response->assertStatus(400);
});

// ==================== إلغاء التوكنات الأخرى ====================

test('إعادة تعيين كلمة المرور تلغي جميع التوكنات الأخرى', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    // إنشاء توكنات تسجيل دخول
    $user->createToken('token1');
    $user->createToken('token2');

    $this->assertEquals(2, $user->tokens()->count());

    $token = 'reset-token';
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    // إعادة تعيين كلمة المرور
    $this->postJson('/api/password/reset', [
        'token' => $token,
        'email' => 'test@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    // التحقق من إلغاء جميع التوكنات
    $user->refresh();
    $this->assertEquals(0, $user->tokens()->count());
});
