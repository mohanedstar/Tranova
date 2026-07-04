<?php

/**
 * @property \App\Models\User $user
 * @property \App\Models\User $verifiedUser
 */

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use App\Notifications\VerifyEmailNotification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // إنشاء مستخدم غير موثق
    $this->user = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => bcrypt('password123'),
        'email_verified_at' => null,
    ]);

    // إنشاء مستخدم موثق
    $this->verifiedUser = User::factory()->create([
        'email' => 'verified@example.com',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);
});

// ==================== اختبار التسجيل وإرسال الإشعار ====================

test('التسجيل يرسل إشعار التحقق من البريد', function () {
    Notification::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20249999',
        'major' => 'IT',
        'university' => 'Test University',
        'year_of_study' => '3',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'email_verification_required' => true,
        ]);

    // التحقق من إرسال الإشعار
    $user = User::where('email', 'newuser@example.com')->first();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('المستخدم الجديد غير موثق بعد التسجيل', function () {
    Notification::fake();

    $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'newuser2@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20249998',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $user = User::where('email', 'newuser2@example.com')->first();
    $this->assertFalse($user->hasVerifiedEmail());
    $this->assertNull($user->email_verified_at);
});

// ==================== اختبار تسجيل الدخول ====================

test('تسجيل الدخول يفشل بدون تحقق من البريد', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'unverified@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'يرجى التحقق من بريدك الإلكتروني أولاً',
            'email_verification_required' => true,
        ]);
});

test('تسجيل الدخول ينجح مع بريد موثق', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'verified@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'تم تسجيل الدخول بنجاح',
            'email_verified' => true,
        ])
        ->assertJsonStructure([
            'token',
            'user',
        ]);
});

test('تسجيل الدخول يفشل بكلمة مرور خاطئة حتى مع بريد موثق', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'verified@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

// ==================== اختبار التحقق من البريد ====================

test('يمكن التحقق من البريد عبر رابط صالح', function () {
    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        Carbon::now()->addMinutes(60),
        [
            'id' => $this->user->id,
            'hash' => sha1($this->user->email),
        ]
    );

    $response = $this->get($verificationUrl);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'تم التحقق من بريدك الإلكتروني بنجاح!',
            'verified' => true,
        ]);

    // التحقق من تحديث قاعدة البيانات
    $this->user->refresh();
    $this->assertTrue($this->user->hasVerifiedEmail());
    $this->assertNotNull($this->user->email_verified_at);

    // التحقق من إطلاق الحدث
    Event::assertDispatched(Verified::class);
});

test('لا يمكن التحقق من رابط بـ hash خاطئ', function () {
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        Carbon::now()->addMinutes(60),
        [
            'id' => $this->user->id,
            'hash' => sha1('wrong@email.com'), // hash خاطئ
        ]
    );

    $response = $this->get($verificationUrl);

    $response->assertStatus(403);

    // التحقق من أن البريد لم يُوثّق
    $this->user->refresh();
    $this->assertFalse($this->user->hasVerifiedEmail());
});

test('لا يمكن التحقق من رابط منتهي الصلاحية', function () {
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        Carbon::now()->subMinutes(10), // منتهي منذ 10 دقائق
        [
            'id' => $this->user->id,
            'hash' => sha1($this->user->email),
        ]
    );

    $response = $this->get($verificationUrl);

    $response->assertStatus(403);
});

test('لا يمكن التحقق من رابط بدون توقيع', function () {
    $response = $this->getJson("/api/email/verify/{$this->user->id}/" . sha1($this->user->email));

    $response->assertStatus(403);
});

test('لا يمكن التحقق من بريد موثق مسبقاً', function () {
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        Carbon::now()->addMinutes(60),
        [
            'id' => $this->verifiedUser->id,
            'hash' => sha1($this->verifiedUser->email),
        ]
    );

    $response = $this->actingAs($this->verifiedUser)->get($verificationUrl);

    // يجب أن ينجح ولكن بدون تغيير شيء
    $response->assertStatus(200);
});

// ==================== اختبار إعادة إرسال رابط التحقق ====================

test('يمكن إعادة إرسال رابط التحقق', function () {
    Notification::fake();

    $response = $this->actingAs($this->user)
        ->postJson('/api/email/resend');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'تم إرسال رابط التحقق مرة أخرى',
        ]);

    Notification::assertSentTo($this->user, VerifyEmailNotification::class);
});

test('إعادة إرسال رابط التحقق تفشل إذا كان البريد موثقاً', function () {
    $response = $this->actingAs($this->verifiedUser)
        ->postJson('/api/email/resend');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'بريدك الإلكتروني موثق بالفعل',
        ]);
});

test('المستخدم غير المصادق عليه لا يمكنه إعادة إرسال رابط التحقق', function () {
    $response = $this->postJson('/api/email/resend');

    $response->assertStatus(401);
});

// ==================== اختبار صفحة التنبيه ====================

test('يمكن الوصول لصفحة تنبيه التحقق', function () {
    $response = $this->getJson('/api/email/verify-notice');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'يرجى التحقق من بريدك الإلكتروني. تم إرسال رابط التحقق إليك.',
        ]);
});

// ==================== اختبار Profile ====================

test('profile يعرض حالة التحقق من البريد', function () {
    // مستخدم غير موثق
    $response = $this->actingAs($this->user)->getJson('/api/profile');
    $response->assertStatus(200)
        ->assertJson([
            'email_verified' => false,
        ]);

    // مستخدم موثق
    $response = $this->actingAs($this->verifiedUser)->getJson('/api/profile');
    $response->assertStatus(200)
        ->assertJson([
            'email_verified' => true,
        ]);
});

// ==================== اختبار Helper Methods ====================

test('hasVerifiedEmail يُرجع الحالة الصحيحة', function () {
    $this->assertFalse($this->user->hasVerifiedEmail());
    $this->assertTrue($this->verifiedUser->hasVerifiedEmail());
});

test('markEmailAsVerified يُوثّق البريد', function () {
    $this->user->markEmailAsVerified();
    $this->user->refresh();

    $this->assertTrue($this->user->hasVerifiedEmail());
    $this->assertNotNull($this->user->email_verified_at);
});

test('getEmailForVerification يُرجع البريد الصحيح', function () {
    $this->assertEquals('unverified@example.com', $this->user->getEmailForVerification());
});

// ==================== اختبار التكامل الكامل ====================

test('التكامل الكامل: تسجيل → تحقق → دخول', function () {
    Notification::fake();
    Event::fake();

    // 1. التسجيل
    $registerResponse = $this->postJson('/api/register', [
        'name' => 'Integration Test',
        'email' => 'integration@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20248888',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $registerResponse->assertStatus(201);
    $token = $registerResponse->json('token');

    // 2. محاولة تسجيل الدخول (يجب أن تفشل)
    $loginResponse = $this->postJson('/api/login', [
        'email' => 'integration@example.com',
        'password' => 'password123',
    ]);
    $loginResponse->assertStatus(403);

    // 3. الحصول على المستخدم وإنشاء رابط التحقق
    $user = User::where('email', 'integration@example.com')->first();
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        Carbon::now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    // 4. التحقق من البريد
    $verifyResponse = $this->get($verificationUrl);
    $verifyResponse->assertStatus(200);

    // 5. تسجيل الدخول (يجب أن ينجح الآن)
    $finalLoginResponse = $this->postJson('/api/login', [
        'email' => 'integration@example.com',
        'password' => 'password123',
    ]);
    $finalLoginResponse->assertStatus(200)
        ->assertJson([
            'email_verified' => true,
        ]);
});
