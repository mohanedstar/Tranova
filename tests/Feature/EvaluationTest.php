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
use App\Models\Evaluation;
use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    // إنشاء المشرف وتعيينه
    $this->supervisorUser = User::factory()->create(['role' => 'supervisor']);
    $this->supervisor = Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    // ✅ إنشاء مدير للتعيين
    $this->adminUser = User::factory()->create(['role' => 'admin']);

    SupervisorAssignment::create([
        'supervisor_id' => $this->supervisor->id,
        'student_id' => $this->student->id,
        'assigned_by' => $this->adminUser->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
});

// ==================== تقييم المزود ====================

test('المزود يمكنه تقييم الطالب', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/provider/evaluations', [
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'attendance_grade' => 90,
            'commitment_grade' => 85,
            'technical_skills_grade' => 88,
            'teamwork_grade' => 92,
            'communication_grade' => 87,
            'evaluation_notes' => 'طالب ممتاز',
            'strengths' => 'مهارات تقنية عالية',
            'areas_for_improvement' => 'تحسين العرض',
            'is_final' => true,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('evaluations', [
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'is_final' => true,
    ]);
});

test('المزود لا يمكنه تقييم طالب ليس في فرصته', function () {
    // إنشاء فرصة أخرى لمزود آخر
    $otherProviderUser = User::factory()->create(['role' => 'provider']);
    $otherProvider = Provider::create([
        'user_id' => $otherProviderUser->id,
        'organization_name' => 'Other Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $otherOpportunity = InternshipOpportunity::create([
        'provider_id' => $otherProvider->id,
        'title' => 'Other Opp',
        'description' => 'Test',
        'requirements' => 'Test',
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

    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/provider/evaluations', [
            'student_id' => $this->student->id,
            'opportunity_id' => $otherOpportunity->id,
            'attendance_grade' => 90,
            'commitment_grade' => 85,
            'technical_skills_grade' => 88,
            'teamwork_grade' => 92,
            'communication_grade' => 87,
            'is_final' => true,
        ]);

    $response->assertStatus(403);
});

test('الطالب لا يمكنه تقييم نفسه', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/provider/evaluations', [
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'attendance_grade' => 100,
            'commitment_grade' => 100,
            'technical_skills_grade' => 100,
            'teamwork_grade' => 100,
            'communication_grade' => 100,
            'is_final' => true,
        ]);

    $response->assertStatus(403);
});

// ==================== تقييم المشرف ====================

test('المشرف يمكنه تقييم الطالب', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->postJson('/api/supervisor/evaluations', [
            'student_id' => $this->student->id,
            'opportunity_id' => $this->opportunity->id,
            'technical_skills_grade' => 85,
            'commitment_grade' => 90,
            'evaluation_notes' => 'التزام أكاديمي ممتاز',
            'is_final' => true,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('evaluations', [
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'is_final' => true,
    ]);
});

test('المشرف لا يمكنه تقييم طالب ليس تابعاً له', function () {
    // إنشاء طالب آخر مع مشرف آخر
    $otherStudentUser = User::factory()->create(['role' => 'student']);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'student_id' => 'S2',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $otherSupervisorUser = User::factory()->create(['role' => 'supervisor']);
    $otherSupervisor = Supervisor::create([
        'user_id' => $otherSupervisorUser->id,
        'employee_id' => 'EMP002',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);

    SupervisorAssignment::create([
        'supervisor_id' => $otherSupervisor->id,
        'student_id' => $otherStudent->id,
        'assigned_by' => $this->adminUser->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    // التقديم المقبول للطالب الآخر
    Application::create([
        'student_id' => $otherStudent->id,
        'opportunity_id' => $this->opportunity->id,
        'cover_letter' => 'Test',
        'cv_path' => 'cvs/cv2.pdf',
        'status' => 'accepted',
        'applied_at' => now(),
    ]);

    $response = $this->actingAs($this->supervisorUser)
        ->postJson('/api/supervisor/evaluations', [
            'student_id' => $otherStudent->id,
            'opportunity_id' => $this->opportunity->id,
            'technical_skills_grade' => 85,
            'commitment_grade' => 90,
            'is_final' => true,
        ]);

    $response->assertStatus(403);
});

// ==================== عرض التقييمات ====================

test('الطالب يمكنه عرض تقييماته', function () {
    Evaluation::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'evaluator_id' => $this->provider->id,
        'evaluator_type' => 'provider',
        'evaluation_date' => now(),
        'attendance_grade' => 90,
        'commitment_grade' => 85,
        'technical_skills_grade' => 88,
        'teamwork_grade' => 92,
        'communication_grade' => 87,
        'is_final' => true,
    ]);

    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/student/evaluations');

    $response->assertStatus(200);
});

test('الطالب يمكنه عرض التقييم النهائي لفرصة', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/student/evaluations/opportunity/{$this->opportunity->id}/final");

    $response->assertStatus(200);
});

// ==================== حساب التقييم النهائي (Admin) ====================

test('المدير يمكنه حساب التقييم النهائي', function () {
    Evaluation::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'evaluator_id' => $this->provider->id,
        'evaluator_type' => 'provider',
        'evaluation_date' => now(),
        'attendance_grade' => 90,
        'commitment_grade' => 85,
        'technical_skills_grade' => 88,
        'teamwork_grade' => 92,
        'communication_grade' => 87,
        'is_final' => true,
    ]);

    Evaluation::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'evaluator_id' => $this->supervisor->id,
        'evaluator_type' => 'supervisor',
        'evaluation_date' => now(),
        'technical_skills_grade' => 85,
        'commitment_grade' => 90,
        'is_final' => true,
    ]);

    WeeklyReport::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'approved',
        'submitted_at' => now(),
        'grade' => 90,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/students/{$this->student->id}/opportunities/{$this->opportunity->id}/calculate");

    // ✅ تغيير من 200 إلى 201 لأن الدالة تُرجع 201
    $response->assertStatus(201);
});

test('الطالب لا يمكنه حساب التقييم النهائي', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/admin/students/{$this->student->id}/opportunities/{$this->opportunity->id}/calculate");

    $response->assertStatus(403);
});

test('المزود لا يمكنه حساب التقييم النهائي', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson("/api/admin/students/{$this->student->id}/opportunities/{$this->opportunity->id}/calculate");

    $response->assertStatus(403);
});

// ==================== إحصائيات التقييمات ====================

test('المدير يمكنه عرض إحصائيات التقييمات', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/evaluations/statistics');

    $response->assertStatus(200);
});

test('المدير يمكنه عرض جميع التقييمات النهائية', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/evaluations/final');

    $response->assertStatus(200);
});

test('الطالب لا يمكنه عرض إحصائيات التقييمات', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/admin/evaluations/statistics');

    $response->assertStatus(403);
});
