<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use App\Models\InternshipOpportunity;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // إنشاء طالب
    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S001',
        'major' => 'IT',
        'university' => 'Test University',
        'year_of_study' => '3',
    ]);

    // إنشاء مزود
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

    // إنشاء فرصة
    $this->opportunity = InternshipOpportunity::create([
        'provider_id' => $this->provider->id,
        'title' => 'Laravel Developer',
        'description' => 'Test',
        'requirements' => 'Laravel',
        'required_major' => 'IT',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'start_date' => now(),
        'end_date' => now()->addMonths(3),
        'application_deadline' => now()->addDays(30),
        'status' => 'open',
    ]);

    // إنشاء تقديم
    $this->application = Application::create([
        'student_id' => $this->student->id,
        'opportunity_id' => $this->opportunity->id,
        'cover_letter' => 'I am interested',
        'cv_path' => 'cvs/cv.pdf',
        'status' => 'pending',
        'applied_at' => now(),
    ]);
});

test('المزود يمكنه عرض ملف الطالب الشخصي', function () {
    $response = $this->actingAs($this->providerUser)
        ->getJson("/api/provider/applicants/{$this->student->id}/profile");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'student' => [
                    'student_id' => 'S001',
                    'major' => 'IT',
                ],
                'user' => [
                    'name' => $this->studentUser->name,
                ],
            ],
        ]);
});

test('المزود لا يمكنه عرض ملف طالب لم يتقدم لديه', function () {
    // إنشاء طالب آخر لم يتقدم
    $otherStudentUser = User::factory()->create(['role' => 'student']);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'student_id' => 'S002',
        'major' => 'CS',
        'university' => 'Other',
        'year_of_study' => '2',
    ]);

    $response = $this->actingAs($this->providerUser)
        ->getJson("/api/provider/applicants/{$otherStudent->id}/profile");

    $response->assertStatus(404);
});

test('المزود لا يمكنه عرض ملف طالب تقدم لدى مزود آخر', function () {
    // ✅ إنشاء طالب جديد لم يتقدم لدى providerUser
    $otherStudentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    $otherStudent = Student::create([
        'user_id' => $otherStudentUser->id,
        'student_id' => 'S999',
        'major' => 'CS',
        'university' => 'Other University',
        'year_of_study' => '2',
    ]);

    // ✅ إنشاء مزود آخر
    $otherProviderUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
    ]);
    $otherProvider = Provider::create([
        'user_id' => $otherProviderUser->id,
        'organization_name' => 'Other Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // ✅ إنشاء فرصة للمزود الآخر
    $otherOpportunity = InternshipOpportunity::create([
        'provider_id' => $otherProvider->id,
        'title' => 'Other Opportunity',
        'description' => 'Test',
        'requirements' => 'Test',
        'required_major' => 'CS',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'start_date' => now(),
        'end_date' => now()->addMonths(3),
        'application_deadline' => now()->addDays(30),
        'status' => 'open',
    ]);

    // ✅ الطالب الجديد تقدم لدى المزود الآخر فقط (وليس لدى providerUser)
    Application::create([
        'student_id' => $otherStudent->id,
        'opportunity_id' => $otherOpportunity->id,
        'cover_letter' => 'Test',
        'cv_path' => 'cvs/cv.pdf',
        'status' => 'pending',
        'applied_at' => now(),
    ]);

    // ✅ المزود الأول يحاول عرض ملف الطالب الذي تقدم لدى مزود آخر فقط
    $response = $this->actingAs($this->providerUser)
        ->getJson("/api/provider/applicants/{$otherStudent->id}/profile");

    // ✅ يجب أن يفشل لأن الطالب لم يتقدم لدى providerUser
    $response->assertStatus(404);
});

test('الطالب لا يمكنه عرض ملف طالب آخر', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/provider/applicants/{$this->student->id}/profile");

    $response->assertStatus(403);
});

test('المشرف لا يمكنه عرض ملف الطالب', function () {
    $supervisorUser = User::factory()->create([
        'role' => 'supervisor',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($supervisorUser)
        ->getJson("/api/provider/applicants/{$this->student->id}/profile");

    $response->assertStatus(403);
});
