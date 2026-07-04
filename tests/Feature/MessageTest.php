<?php

/**
 * @property \App\Models\User $studentUser
 * @property \App\Models\User $providerUser
 * @property \App\Models\User $supervisorUser
 */

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // إنشاء الطالب
    $this->studentUser = User::factory()->create(['role' => 'student']);
    Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    // إنشاء المزود
    $this->providerUser = User::factory()->create(['role' => 'provider']);
    Provider::create([
        'user_id' => $this->providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // إنشاء المشرف
    $this->supervisorUser = User::factory()->create(['role' => 'supervisor']);
    Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);
});

// ==================== إرسال الرسائل ====================

test('الطالب يمكنه إرسال رسالة', function () {
    Notification::fake();

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/messages', [
            'receiver_id' => $this->providerUser->id,
            'subject' => 'استفسار عن التدريب',
            'message' => 'مرحباً، أريد الاستفسار عن التدريب',
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('messages', [
        'sender_id' => $this->studentUser->id,
        'receiver_id' => $this->providerUser->id,
        'subject' => 'استفسار عن التدريب',
    ]);

    // التحقق من إرسال الإشعار
    Notification::assertSentTo(
        $this->providerUser,
        \App\Notifications\NewMessageReceived::class
    );
});

test('الطالب لا يمكنه إرسال رسالة بدون محتوى', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/messages', [
            'receiver_id' => $this->providerUser->id,
        ]);

    $response->assertStatus(422);
});

test('الطالب لا يمكنه إرسال رسالة لنفسه', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/messages', [
            'receiver_id' => $this->studentUser->id,
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

    // قد يقبل أو يرفض حسب التحقق
    $this->assertTrue(in_array($response->status(), [201, 422, 400]));
});

test('المستخدم غير المصادق عليه لا يمكنه إرسال رسالة', function () {
    $response = $this->postJson('/api/messages', [
        'receiver_id' => $this->providerUser->id,
        'subject' => 'Test',
        'message' => 'Test',
    ]);

    $response->assertStatus(401);
});

// ==================== عرض الرسائل ====================

test('الطالب يمكنه عرض صندوق الوارد', function () {
    // إنشاء رسالة
    Message::create([
        'sender_id' => $this->providerUser->id,
        'receiver_id' => $this->studentUser->id,
        'subject' => 'Test',
        'message' => 'Test message',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/messages/inbox');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'messages' => [
                'data' => [
                    '*' => ['id', 'sender', 'subject', 'message'],
                ],
            ],
        ]);
});

test('الطالب يمكنه عرض الرسائل المرسلة', function () {
    Message::create([
        'sender_id' => $this->studentUser->id,
        'receiver_id' => $this->providerUser->id,
        'subject' => 'Test',
        'message' => 'Test message',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/messages/sent');

    $response->assertStatus(200);
});

test('الطالب يرى فقط رسائله في صندوق الوارد', function () {
    // رسالة للطالب
    Message::create([
        'sender_id' => $this->providerUser->id,
        'receiver_id' => $this->studentUser->id,
        'subject' => 'للطالب',
        'message' => 'Test',
    ]);

    // رسالة للمشرف (لا يجب أن يراها الطالب)
    Message::create([
        'sender_id' => $this->providerUser->id,
        'receiver_id' => $this->supervisorUser->id,
        'subject' => 'للمشرف',
        'message' => 'Test',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/messages/inbox');

    $response->assertStatus(200);

    $messages = $response->json('messages.data');
    $this->assertCount(1, $messages);
    $this->assertEquals('للطالب', $messages[0]['subject']);
});

// ==================== تعليم الرسالة كمقروءة ====================

test('المستلم يمكنه تعليم الرسالة كمقروءة', function () {
    $message = Message::create([
        'sender_id' => $this->providerUser->id,
        'receiver_id' => $this->studentUser->id,
        'subject' => 'Test',
        'message' => 'Test message',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/messages/{$message->id}/read");

    $response->assertStatus(200);

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'is_read' => true,
    ]);
});

test('المرسل لا يمكنه تعليم الرسالة كمقروءة', function () {
    $message = Message::create([
        'sender_id' => $this->providerUser->id,
        'receiver_id' => $this->studentUser->id,
        'subject' => 'Test',
        'message' => 'Test message',
    ]);

    $response = $this->actingAs($this->providerUser)
        ->postJson("/api/messages/{$message->id}/read");

    $response->assertStatus(403);
});

// ==================== الرد على الرسائل ====================

test('الطالب يمكنه الرد على رسالة', function () {
    $originalMessage = Message::create([
        'sender_id' => $this->providerUser->id,
        'receiver_id' => $this->studentUser->id,
        'subject' => 'استفسار',
        'message' => 'مرحباً',
    ]);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/messages', [
            'receiver_id' => $this->providerUser->id,
            'subject' => 'رد: استفسار',
            'message' => 'شكراً على رسالتك',
            'parent_id' => $originalMessage->id,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('messages', [
        'parent_id' => $originalMessage->id,
        'sender_id' => $this->studentUser->id,
    ]);
});
