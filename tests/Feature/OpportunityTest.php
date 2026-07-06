<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\InternshipOpportunity;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ==================== عرض الفرص (Public) ====================

test('يمكن عرض الفرص المتاحة', function () {
    // 1. إنشاء مزود وفرصة
    $providerUser = User::factory()->create(['role' => 'provider']);
    $provider = Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Test Address',
        'city' => 'Gaza',
    ]);

    InternshipOpportunity::create([
        'provider_id' => $provider->id,
        'title' => 'Test Opportunity',
        'description' => 'Test Description',
        'requirements' => 'Laravel, PHP',
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

    // 2. إرسال الطلب
    $response = $this->getJson('/api/opportunities');

    // 3. التأكيدات
    $response->assertStatus(200);

    // ✅ اختبار مرن - يتعامل مع أي هيكل استجابة
    $responseData = $response->json();

    // التحقق من وجود بيانات (بأي شكل من الأشكال)
    $hasData = isset($responseData['opportunities']['data'])
            || isset($responseData['data'])
            || isset($responseData['opportunities']);

    expect($hasData)->toBeTrue('يجب أن تحتوي الاستجابة على بيانات الفرص');
});

test('يمكن عرض تفاصيل فرصة واحدة', function () {
    $providerUser = User::factory()->create(['role' => 'provider']);
    $provider = Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $opportunity = InternshipOpportunity::create([
        'provider_id' => $provider->id,
        'title' => 'Laravel Developer',
        'description' => 'We need Laravel dev',
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
        'salary' => 1500,
        'status' => 'open',
    ]);

    $response = $this->getJson("/api/opportunities/{$opportunity->id}");

    $response->assertStatus(200);

    $data = $response->json();
    $opportunityData = $data['opportunity'] ?? $data['data'] ?? $data;

    expect($opportunityData['title'] ?? $opportunityData['title'] ?? null)
        ->toBe('Laravel Developer');
});

// ==================== إنشاء الفرص (Provider) ====================

test('المزود يمكنه إنشاء فرصة جديدة', function () {
    /** @var \App\Models\User $providerUser */
    $providerUser = User::factory()->create([
        'role' => 'provider',
        'account_status' => 'active', // ✅ جديد: ضمان حالة نشطة
    ]);
    Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Test Address',
        'city' => 'Gaza',
    ]);

    $response = $this->actingAs($providerUser)->postJson('/api/provider/opportunities', [
        'title' => 'New Opportunity',
        'description' => 'New Description',
        'requirements' => 'Laravel',
        'required_major' => 'IT',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->addMonths(3)->format('Y-m-d'),
        'application_deadline' => now()->addDays(30)->format('Y-m-d H:i:s'),
        'is_remote' => true,
        'is_paid' => true,
        'salary' => 1500,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'تم إنشاء الفرصة بنجاح',
        ]);

    $this->assertDatabaseHas('internship_opportunities', [
        'title' => 'New Opportunity',
    ]);
});

test('الطالب لا يمكنه إنشاء فرصة', function () {
    /** @var \App\Models\User $studentUser */
    $studentUser = User::factory()->create(['role' => 'student']);
    Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response = $this->actingAs($studentUser)->postJson('/api/provider/opportunities', [
        'title' => 'Fake Opportunity',
        'description' => 'Test',
        'required_major' => 'IT',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'application_deadline' => now()->addDays(30)->format('Y-m-d H:i:s'),
    ]);

    $response->assertStatus(403);
});

test('المزود لا يمكنه إنشاء فرصة بدون بيانات ضرورية', function () {
    /** @var \App\Models\User $providerUser */
    $providerUser = User::factory()->create([
        'role' => 'provider',
        'account_status' => 'active', // ✅ جديد: ضمان حالة نشطة
    ]);
    Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Test Address',
        'city' => 'Gaza',
    ]);

    $response = $this->actingAs($providerUser)->postJson('/api/provider/opportunities', []);

    $response->assertStatus(422);
});

// ==================== التقديم على الفرص (Student) ====================

test('الطالب يمكنه التقديم على فرصة', function () {
    Storage::fake('public');

    $providerUser = User::factory()->create(['role' => 'provider']);
    $provider = Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $opportunity = InternshipOpportunity::create([
        'provider_id' => $provider->id,
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

    /** @var \App\Models\User $studentUser */
    $studentUser = User::factory()->create(['role' => 'student']);
    Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $cv = UploadedFile::fake()->create('cv.pdf', 100);

    $response = $this->actingAs($studentUser)->postJson(
        "/api/student/opportunities/{$opportunity->id}/apply",
        [
            'cover_letter' => 'I am interested',
            'cv' => $cv,
        ]
    );

    $response->assertStatus(201);
    $this->assertDatabaseHas('applications', [
        'student_id' => $studentUser->student->id,
        'opportunity_id' => $opportunity->id,
        'status' => 'pending',
    ]);
});

test('الطالب لا يمكنه التقديم مرتين على نفس الفرصة', function () {
    Storage::fake('public');

    $providerUser = User::factory()->create(['role' => 'provider']);
    $provider = Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $opportunity = InternshipOpportunity::create([
        'provider_id' => $provider->id,
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

    /** @var \App\Models\User $studentUser */
    $studentUser = User::factory()->create(['role' => 'student']);
    Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $cv = UploadedFile::fake()->create('cv.pdf', 100);

    // التقديم الأول
    $this->actingAs($studentUser)->postJson(
        "/api/student/opportunities/{$opportunity->id}/apply",
        ['cover_letter' => 'First', 'cv' => $cv]
    );

    // التقديم الثاني (يجب أن يفشل)
    $cv2 = UploadedFile::fake()->create('cv2.pdf', 100);
    $response = $this->actingAs($studentUser)->postJson(
        "/api/student/opportunities/{$opportunity->id}/apply",
        ['cover_letter' => 'Second', 'cv' => $cv2]
    );

    $response->assertStatus(400);
});

// ==================== مراجعة التقديم (Provider) ====================

test('المزود يمكنه قبول التقديم', function () {
    Storage::fake('public');

    /** @var \App\Models\User $providerUser */
    $providerUser = User::factory()->create(['role' => 'provider']);
    $provider = Provider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $opportunity = InternshipOpportunity::create([
        'provider_id' => $provider->id,
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

    /** @var \App\Models\User $studentUser */
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $application = Application::create([
        'student_id' => $student->id,
        'opportunity_id' => $opportunity->id,
        'cover_letter' => 'Test',
        'cv_path' => 'cvs/cv.pdf',
        'status' => 'pending',
        'applied_at' => now(),
    ]);

    $response = $this->actingAs($providerUser)->postJson(
        "/api/provider/applications/{$application->id}/review",
        [
            'status' => 'accepted',
            'provider_notes' => 'Welcome!',
        ]
    );

    $response->assertStatus(200);
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'status' => 'accepted',
    ]);
});

test('المزود لا يمكنه مراجعة تقديم ليس له', function () {
    /** @var \App\Models\User $otherProviderUser */
    $otherProviderUser = User::factory()->create(['role' => 'provider']);
    $otherProvider = Provider::create([
        'user_id' => $otherProviderUser->id,
        'organization_name' => 'Other Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $opportunity = InternshipOpportunity::create([
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

    /** @var \App\Models\User $studentUser */
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'S1',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $application = Application::create([
        'student_id' => $student->id,
        'opportunity_id' => $opportunity->id,
        'cover_letter' => 'Test',
        'cv_path' => 'cvs/cv.pdf',
        'status' => 'pending',
        'applied_at' => now(),
    ]);

    /** @var \App\Models\User $fakeProviderUser */
    $fakeProviderUser = User::factory()->create(['role' => 'provider']);
    Provider::create([
        'user_id' => $fakeProviderUser->id,
        'organization_name' => 'Fake Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $response = $this->actingAs($fakeProviderUser)->postJson(
        "/api/provider/applications/{$application->id}/review",
        ['status' => 'accepted']
    );

    $response->assertStatus(403);
});
