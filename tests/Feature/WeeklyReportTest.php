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
    $this->studentUser = User::factory()->create(['role' => 'student']);
    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    // إنشاء المزود والفرصة
    $this->providerUser = User::factory()->create(['role' => 'provider']);
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

    // إنشاء التقديم المقبول
    $this->application = Application::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'cover_letter' => 'Test',
        'cv_path' => 'cvs/cv.pdf',
        'status' => 'accepted',
        'applied_at' => now(),
    ]);

    // إنشاء المشرف
    $this->supervisorUser = User::factory()->create(['role' => 'supervisor']);
    $this->supervisor = Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    // ✅ إنشاء مدير للتعيين
    $this->adminUser = User::factory()->create(['role' => 'admin']);

    // تعيين المشرف للطالب مع assigned_by
    SupervisorAssignment::create([
        'supervisor_id' => $this->supervisor->id,
        'student_id' => $this->student->id,
        'assigned_by' => $this->adminUser->id,  // ✅ الحل
        'assigned_at' => now(),
        'is_active' => true,
    ]);
});

// ==================== اختبار إرسال التقرير ====================

test('الطالب يمكنه إرسال تقرير أسبوعي', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/reports', [
            'opportunity_id' => $this->opportunity->id,
            'report_date' => now()->format('Y-m-d'),
            'week_number' => 1,
            'training_hours' => 30,
            'completed_tasks' => 'تعلمت Laravel',
            'challenges' => 'صعوبة في العلاقات',
            'next_week_plan' => 'سأتعلم Vue.js',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'تم إرسال التقرير بنجاح',
        ]);

    $this->assertDatabaseHas('weekly_reports', [
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'week_number' => 1,
        'status' => 'submitted',
    ]);
});

test('الطالب لا يمكنه إرسال تقرير بدون بيانات ضرورية', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/reports', []);

    $response->assertStatus(422);
});

test('الطالب لا يمكنه إرسال تقرير لفرصة غير موجودة', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/reports', [
            'opportunity_id' => 9999,
            'report_date' => now()->format('Y-m-d'),
            'week_number' => 1,
            'training_hours' => 30,
            'completed_tasks' => 'Test',
        ]);

    $response->assertStatus(422);
});

// ==================== اختبار عرض التقارير ====================

test('الطالب يمكنه عرض تقاريره', function () {
    WeeklyReport::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/student/reports');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'count' => 1,
        ]);
});

test('المشرف يمكنه عرض تقارير طلابه', function () {
    WeeklyReport::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->supervisorUser)
        ->getJson('/api/supervisor/reports');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'count' => 1,
        ]);
});

test('الطالب لا يمكنه عرض تقارير الطلاب الآخرين', function () {
    $otherStudentUser = User::factory()->create(['role' => 'student']);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'student_id' => 'S2',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    WeeklyReport::create([
        'student_id' => $otherStudent->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/student/reports');

    $response->assertJson(['count' => 0]);
});

// ==================== اختبار مراجعة التقرير ====================

test('المشرف يمكنه مراجعة التقرير', function () {
    $report = WeeklyReport::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->supervisorUser)
        ->postJson("/api/supervisor/reports/{$report->id}/review", [
            'status' => 'approved',
            'supervisor_comments' => 'عمل ممتاز',
            'grade' => 95,
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('weekly_reports', [
        'id' => $report->id,
        'status' => 'approved',
        'grade' => 95,
    ]);
});

test('الطالب لا يمكنه مراجعة التقرير', function () {
    $report = WeeklyReport::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/supervisor/reports/{$report->id}/review", [
            'status' => 'approved',
            'grade' => 95,
        ]);

    $response->assertStatus(403);
});

// ==================== اختبار الإشعارات ====================

test('يتم إرسال إشعار للمشرف عند إرسال التقرير', function () {
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
