<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorAssignment;
use App\Models\InternshipOpportunity;
use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // إنشاء مشرف
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

    // إنشاء طالب متأخر (لم يسلم تقرير منذ 20 يوم)
    $this->lateStudentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    $this->lateStudent = Student::create([
        'user_id' => $this->lateStudentUser->id,
        'student_id' => 'S001',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    // إنشاء طالب ملتزم (سلم تقرير أمس)
    $this->goodStudentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    $this->goodStudent = Student::create([
        'user_id' => $this->goodStudentUser->id,
        'student_id' => 'S002',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    // إنشاء طالب لم يسلم أي تقرير
    $this->neverSubmittedUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    $this->neverSubmittedStudent = Student::create([
        'user_id' => $this->neverSubmittedUser->id,
        'student_id' => 'S003',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '2',
    ]);

    // تعيين الطلاب للمشرف
    $adminUser = User::factory()->create(['role' => 'admin']);
    SupervisorAssignment::create([
        'supervisor_id' => $this->supervisor->id,
        'student_id' => $this->lateStudent->id,
        'assigned_by' => $adminUser->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
    SupervisorAssignment::create([
        'supervisor_id' => $this->supervisor->id,
        'student_id' => $this->goodStudent->id,
        'assigned_by' => $adminUser->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);
    SupervisorAssignment::create([
        'supervisor_id' => $this->supervisor->id,
        'student_id' => $this->neverSubmittedStudent->id,
        'assigned_by' => $adminUser->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    // إنشاء فرصة
    $providerUser = User::factory()->create(['role' => 'provider']);
    $provider = \App\Models\Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);
    $this->opportunity = InternshipOpportunity::create([
        'provider_id' => $provider->id,
        'title' => 'Test',
        'description' => 'Test',
        'requirements' => 'Test',
        'required_major' => 'IT',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'start_date' => now()->subMonths(3),
        'end_date' => now()->addMonths(3),
        'application_deadline' => now()->addDays(30),
        'status' => 'open',
    ]);

    // إنشاء تقرير قديم للطالب المتأخر
    WeeklyReport::create([
        'student_id' => $this->lateStudent->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now()->subDays(20),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now()->subDays(20),
    ]);

    // إنشاء تقرير حديث للطالب الملتزم
    WeeklyReport::create([
        'student_id' => $this->goodStudent->id,
        'opportunity_id' => $this->opportunity->id,
        'report_date' => now()->subDay(),
        'week_number' => 1,
        'training_hours' => 30,
        'completed_tasks' => 'Test',
        'status' => 'submitted',
        'submitted_at' => now()->subDay(),
    ]);
});

test('المشرف يمكنه عرض الطلاب المتأخرين', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->getJson('/api/supervisor/students/late');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $lateStudents = $response->json('late_students');

    // يجب أن يظهر الطالب المتأخر والطالب الذي لم يسلم أبداً
    $this->assertGreaterThanOrEqual(2, count($lateStudents));
});

test('الطالب الملتزم لا يظهر في القائمة', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->getJson('/api/supervisor/students/late');

    $lateStudents = $response->json('late_students');
    $goodStudentInList = collect($lateStudents)->contains(function ($student) {
        return $student['student_id'] === $this->goodStudent->id;
    });

    $this->assertFalse($goodStudentInList);
});

test('الطالب الذي لم يسلم أبداً يظهر في القائمة', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->getJson('/api/supervisor/students/late');

    $lateStudents = $response->json('late_students');
    $neverSubmittedInList = collect($lateStudents)->contains(function ($student) {
        return $student['student_id'] === $this->neverSubmittedStudent->id
            && $student['status'] === 'never_submitted';
    });

    $this->assertTrue($neverSubmittedInList);
});

test('الطالب لا يمكنه عرض الطلاب المتأخرين', function () {
    $response = $this->actingAs($this->lateStudentUser)
        ->getJson('/api/supervisor/students/late');

    $response->assertStatus(403);
});

test('المزود لا يمكنه عرض الطلاب المتأخرين', function () {
    $providerUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($providerUser)
        ->getJson('/api/supervisor/students/late');

    $response->assertStatus(403);
});
