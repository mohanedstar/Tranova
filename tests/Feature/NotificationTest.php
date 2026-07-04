<?php

/**
 * @property \App\Models\User $studentUser
 * @property \App\Models\User $providerUser
 * @property \App\Models\User $supervisorUser
 * @property \App\Models\User $adminUser
 * @property \App\Models\Student $student
 * @property \App\Models\Provider $provider
 * @property \App\Models\Supervisor $supervisor
 * @property \App\Models\InternshipOpportunity $opportunity
 * @property \App\Models\Application $application
 */

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use App\Models\SupervisorAssignment;
use App\Models\InternshipOpportunity;
use App\Models\Application;
use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // إنشاء الطالب
    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(), // ✅ موثق لتجنب مشاكل التحقق
    ]);
    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    // إنشاء المزود والفرصة
    $this->providerUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
    ]);
    $this->provider = Provider::create([
        'user_id' => $this->providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $this->opportunity = InternshipOpportunity::create([
        'provider_id' => $this->provider->id,
        'title' => 'Laravel Dev',
        'description' => 'Test',
        'requirements' => 'Laravel',
        'required_major' => 'IT',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'start_date' => now(),
        'end_date' => now()->addMonths(3),
        'application_deadline' => now()->addDays(30),
        'is_remote' => true,
        'is_paid' => true,
        'salary' => 1000,
        'status' => 'open',
    ]);

    // المشرف
    $this->supervisorUser = User::factory()->create([
        'role' => 'supervisor',
        'email_verified_at' => now(),
    ]);
    $this->supervisor = Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    SupervisorAssignment::create([
        'supervisor_id' => $this->supervisor->id,
        'student_id' => $this->student->id,
        'assigned_by' => $this->adminUser->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
});

// ==================== عرض الإشعارات ====================

test('الطالب يمكنه عرض إشعاراته', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/notifications');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'notifications',
            'unread_count',
        ]);
});

test('المزود يمكنه عرض إشعاراته', function () {
    $response = $this->actingAs($this->providerUser)
        ->getJson('/api/notifications');

    $response->assertStatus(200);
});

test('المشرف يمكنه عرض إشعاراته', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->getJson('/api/notifications');

    $response->assertStatus(200);
});

test('المدير يمكنه عرض إشعاراته', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/notifications');

    $response->assertStatus(200);
});

test('المستخدم غير المصادق عليه لا يمكنه عرض الإشعارات', function () {
    $response = $this->getJson('/api/notifications');

    $response->assertStatus(401);
});

// ==================== الإشعارات غير المقروءة ====================

test('الطالب يمكنه عرض الإشعارات غير المقروءة', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/notifications/unread');

    $response->assertStatus(200);
});

// ==================== تعليم كمقروء ====================

test('الطالب يمكنه تعليم إشعار كمقروء', function () {
    $this->studentUser->notify(new \App\Notifications\ApplicationStatusChanged(
        Application::create([
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'cover_letter' => 'Test',
            'cv_path' => 'cvs/cv.pdf',
            'status' => 'accepted',
            'applied_at' => now(),
        ]),
        'accepted'
    ));

    $notification = $this->studentUser->notifications()->first();

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/notifications/{$notification->id}/read");

    $response->assertStatus(200);

    $this->assertNotNull($this->studentUser->notifications()->find($notification->id)->read_at);
});

test('المستخدم لا يمكنه تعليم إشعار مستخدم آخر كمقروء', function () {
    $this->studentUser->notify(new \App\Notifications\ApplicationStatusChanged(
        Application::create([
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'cover_letter' => 'Test',
            'cv_path' => 'cvs/cv.pdf',
            'status' => 'accepted',
            'applied_at' => now(),
        ]),
        'accepted'
    ));

    $notification = $this->studentUser->notifications()->first();

    $response = $this->actingAs($this->providerUser)
        ->postJson("/api/notifications/{$notification->id}/read");

    $response->assertStatus(404);
});

// ==================== تعليم الكل كمقروء ====================

test('الطالب يمكنه تعليم جميع الإشعارات كمقروءة', function () {
    $this->studentUser->notify(new \App\Notifications\ApplicationStatusChanged(
        Application::create([
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'cover_letter' => 'Test',
            'cv_path' => 'cvs/cv.pdf',
            'status' => 'accepted',
            'applied_at' => now(),
        ]),
        'accepted'
    ));

    $this->assertEquals(1, $this->studentUser->unreadNotifications->count());

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/notifications/read-all');

    $response->assertStatus(200);

    $this->studentUser->refresh();
    $this->assertEquals(0, $this->studentUser->unreadNotifications->count());
});

// ==================== حذف الإشعارات ====================

test('الطالب يمكنه حذف إشعار', function () {
    $this->studentUser->notify(new \App\Notifications\ApplicationStatusChanged(
        Application::create([
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'cover_letter' => 'Test',
            'cv_path' => 'cvs/cv.pdf',
            'status' => 'accepted',
            'applied_at' => now(),
        ]),
        'accepted'
    ));

    $notification = $this->studentUser->notifications()->first();

    $response = $this->actingAs($this->studentUser)
        ->deleteJson("/api/notifications/{$notification->id}");

    $response->assertStatus(200);

    $this->assertNull($this->studentUser->notifications()->find($notification->id));
});

test('الطالب يمكنه حذف جميع الإشعارات', function () {
    $this->studentUser->notify(new \App\Notifications\ApplicationStatusChanged(
        Application::create([
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'cover_letter' => 'Test',
            'cv_path' => 'cvs/cv.pdf',
            'status' => 'accepted',
            'applied_at' => now(),
        ]),
        'accepted'
    ));

    $response = $this->actingAs($this->studentUser)
        ->deleteJson('/api/notifications');

    $response->assertStatus(200);

    $this->assertEquals(0, $this->studentUser->notifications()->count());
});

// ==================== إشعار التقديم الجديد ====================

test('المزود يستلم إشعار عند تقديم طالب', function () {
    Notification::fake();

    $this->actingAs($this->studentUser)
        ->postJson("/api/student/opportunities/{$this->opportunity->id}/apply", [
            'cover_letter' => 'Test',
            'cv' => \Illuminate\Http\UploadedFile::fake()->create('cv.pdf', 100),
        ]);

    Notification::assertSentTo(
        $this->providerUser,
        \App\Notifications\NewApplicationReceived::class
    );
});

// ==================== إشعار قبول التقديم ====================

test('الطالب يستلم إشعار عند قبول تقديمه', function () {
    Notification::fake();

    $application = Application::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'cover_letter' => 'Test',
        'cv_path' => 'cvs/cv.pdf',
        'status' => 'pending',
        'applied_at' => now(),
    ]);

    $this->actingAs($this->providerUser)
        ->postJson("/api/provider/applications/{$application->id}/review", [
            'status' => 'accepted',
            'provider_notes' => 'Welcome!',
        ]);

    Notification::assertSentTo(
        $this->studentUser,
        \App\Notifications\ApplicationStatusChanged::class
    );
});

// ==================== إشعار التقرير الجديد ====================

test('المشرف يستلم إشعار عند إرسال تقرير', function () {
    Notification::fake();

    $this->actingAs($this->studentUser)
        ->postJson('/api/student/reports', [
            'opportunity_id' => $this->opportunity->id,
            'report_date' => now()->format('Y-m-d'),
            'week_number' => 1,
            'training_hours' => 30,
            'completed_tasks' => 'Test',
        ]);

    Notification::assertSentTo(
        $this->supervisorUser,
        \App\Notifications\NewReportSubmitted::class
    );
});

// ==================== ✅ إشعارات التحقق من البريد (جديد) ====================

test('المستخدم يستلم إشعار تحقق من البريد عند التسجيل', function () {
    Notification::fake();

    $this->postJson('/api/register', [
        'name' => 'New User Email',
        'email' => 'newemail@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
        'student_id' => '20249997',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $user = User::where('email', 'newemail@example.com')->first();

    Notification::assertSentTo(
        $user,
        \App\Notifications\VerifyEmailNotification::class
    );
});

test('يمكن إعادة إرسال إشعار التحقق من البريد', function () {
    Notification::fake();

    // إنشاء مستخدم غير موثق
    $unverifiedUser = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => bcrypt('password123'),
        'email_verified_at' => null,
        'role' => 'student',
    ]);

    $response = $this->actingAs($unverifiedUser)
        ->postJson('/api/email/resend');

    $response->assertStatus(200);

    Notification::assertSentTo(
        $unverifiedUser,
        \App\Notifications\VerifyEmailNotification::class
    );
});

test('إشعار التحقق من البريد لا يُرسل إذا كان البريد موثقاً مسبقاً', function () {
    Notification::fake();

    // مستخدم موثق مسبقاً
    $verifiedUser = User::factory()->create([
        'email' => 'verified@example.com',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'role' => 'student',
    ]);

    $response = $this->actingAs($verifiedUser)
        ->postJson('/api/email/resend');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'بريدك الإلكتروني موثق بالفعل',
        ]);

    // ✅ التأكد من أن الإشعار لم يُرسل
    Notification::assertNotSentTo(
        $verifiedUser,
        \App\Notifications\VerifyEmailNotification::class
    );
});
