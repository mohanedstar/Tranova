<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // ✅ إنشاء Admin للاختبار
    $this->adminUser = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);

    // ✅ إنشاء Student للاختبار
    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S_TEST_001',
        'major' => 'IT',
        'university' => 'Test University',
        'year_of_study' => '3',
    ]);

    // ✅ إنشاء Provider للاختبار
    $this->providerUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    Provider::create([
        'user_id' => $this->providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // ✅ إنشاء Supervisor للاختبار
    $this->supervisorUser = User::factory()->create([
        'role' => 'supervisor',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP_TEST_001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);
});

// ═══════════════════════════════════════════════════════════════
// 📋 List Users (index)
// ═══════════════════════════════════════════════════════════════

test('admin can list all users', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/users');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'data',
                'total',
            ],
        ]);
});

test('admin can filter users by role', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/users?role=student');

    $response->assertStatus(200);

    $users = $response->json('data.data');
    foreach ($users as $user) {
        expect($user['role'])->toBe('student');
    }
});

test('admin can filter users by status', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/users?status=active');

    $response->assertStatus(200);

    $users = $response->json('data.data');
    foreach ($users as $user) {
        expect($user['account_status'])->toBe('active');
    }
});

test('admin can search users by name', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/users?search=' . urlencode($this->studentUser->name));

    $response->assertStatus(200);
});

test('admin can search users by email', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/users?search=' . urlencode($this->studentUser->email));

    $response->assertStatus(200);
});

test('student cannot list users', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('provider cannot list users', function () {
    $response = $this->actingAs($this->providerUser)
        ->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('supervisor cannot list users', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('unauthenticated user cannot list users', function () {
    $response = $this->getJson('/api/admin/users');

    $response->assertStatus(401);
});

// ═══════════════════════════════════════════════════════════════
// 👤 Show User (show)
// ═══════════════════════════════════════════════════════════════

test('admin can view user details', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/admin/users/{$this->studentUser->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $this->studentUser->id,
                'name' => $this->studentUser->name,
                'email' => $this->studentUser->email,
                'role' => 'student',
            ],
        ]);
});

test('admin can view user with related records', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/admin/users/{$this->studentUser->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'role',
                'student',
            ],
        ]);
});

test('admin cannot view non-existent user', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/users/99999');

    $response->assertStatus(404);
});

test('student cannot view other user details', function () {
    $response = $this->actingAs($this->studentUser)
        ->getJson("/api/admin/users/{$this->providerUser->id}");

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// ➕ Create User (store)
// ═══════════════════════════════════════════════════════════════

test('admin can create new student', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'New Student',
            'email' => 'newstudent@test.com',
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '20240999',
            'major' => 'IT',
            'university' => 'Test University',
            'year_of_study' => '3',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'New Student',
                'email' => 'newstudent@test.com',
                'role' => 'student',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newstudent@test.com',
        'role' => 'student',
    ]);

    $this->assertDatabaseHas('students', [
        'student_id' => '20240999',
    ]);
});

test('admin can create new provider', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'New Provider',
            'email' => 'newprovider@test.com',
            'password' => 'password123',
            'role' => 'provider',
            'organization_name' => 'New Corp',
            'organization_type' => 'company',
            'address' => 'Gaza City',
            'city' => 'Gaza',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'role' => 'provider',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newprovider@test.com',
        'role' => 'provider',
    ]);
});

test('admin can create new supervisor', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'Dr. New Supervisor',
            'email' => 'newsupervisor@iugaza.edu.ps',
            'password' => 'password123',
            'role' => 'supervisor',
            'employee_id' => 'EMP999',
            'department' => 'Computer Science',
            'academic_title' => 'professor',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'role' => 'supervisor',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newsupervisor@iugaza.edu.ps',
        'role' => 'supervisor',
    ]);
});

test('admin can create new admin', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'New Admin',
            'email' => 'newadmin@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'role' => 'admin',
            ],
        ]);
});

test('admin cannot create user with duplicate email', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'Duplicate Email',
            'email' => $this->studentUser->email, // Email موجود
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '20240888',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('admin cannot create user without required fields', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'Incomplete',
            // ❌ email ناقص
            // ❌ password ناقص
            // ❌ role ناقص
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'role']);
});

test('admin cannot create user with weak password', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'Weak Password',
            'email' => 'weak@test.com',
            'password' => '123', // ❌ أقل من 8
            'role' => 'student',
            'student_id' => '20240777',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('admin cannot create user with invalid role', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'Invalid Role',
            'email' => 'invalid@test.com',
            'password' => 'password123',
            'role' => 'invalid_role', // ❌ دور غير صالح
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

test('student cannot create user', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/admin/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '20240666',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

    $response->assertStatus(403);
});

test('provider cannot create user', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/admin/users', [
            'name' => 'Test',
            'email' => 'test2@test.com',
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '20240555',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// ✏️ Update User (update)
// ═══════════════════════════════════════════════════════════════

test('admin can update user name', function () {
    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/admin/users/{$this->studentUser->id}", [
            'name' => 'Updated Name',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Updated Name',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->studentUser->id,
        'name' => 'Updated Name',
    ]);
});

test('admin can update user email', function () {
    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/admin/users/{$this->studentUser->id}", [
            'email' => 'updated@test.com',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $this->studentUser->id,
        'email' => 'updated@test.com',
    ]);
});

test('admin cannot update user with duplicate email', function () {
    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/admin/users/{$this->studentUser->id}", [
            'email' => $this->providerUser->email, // Email موجود لمشرف آخر
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('admin can update user phone', function () {
    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/admin/users/{$this->studentUser->id}", [
            'phone' => '0591234567',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $this->studentUser->id,
        'phone' => '0591234567',
    ]);
});

test('student cannot update other user', function () {
    $response = $this->actingAs($this->studentUser)
        ->putJson("/api/admin/users/{$this->providerUser->id}", [
            'name' => 'Hacked',
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// 🗑️ Delete User (destroy)
// ═══════════════════════════════════════════════════════════════

test('admin can delete student', function () {
    $studentToDelete = User::factory()->create([
        'role' => 'student',
        'account_status' => 'active',
    ]);
    Student::create([
        'user_id' => $studentToDelete->id,
        'student_id' => 'S_DELETE_001',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/admin/users/{$studentToDelete->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseMissing('users', [
        'id' => $studentToDelete->id,
    ]);
});

test('admin can delete provider', function () {
    $providerToDelete = User::factory()->create([
        'role' => 'provider',
        'account_status' => 'active',
    ]);
    Provider::create([
        'user_id' => $providerToDelete->id,
        'organization_name' => 'Delete Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/admin/users/{$providerToDelete->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('users', [
        'id' => $providerToDelete->id,
    ]);
});

test('admin cannot delete himself', function () {
    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/admin/users/{$this->adminUser->id}");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);

    // ✅ التأكد من أن الحساب لم يُحذف
    $this->assertDatabaseHas('users', [
        'id' => $this->adminUser->id,
    ]);
});

test('admin delete user removes related records', function () {
    $studentToDelete = User::factory()->create([
        'role' => 'student',
        'account_status' => 'active',
    ]);
    $studentRecord = Student::create([
        'user_id' => $studentToDelete->id,
        'student_id' => 'S_DELETE_002',
        'major' => 'IT',
        'university' => 'Test',
        'year_of_study' => '3',
    ]);

    $this->actingAs($this->adminUser)
        ->deleteJson("/api/admin/users/{$studentToDelete->id}");

    // ✅ التأكد من حذف السجل الفرعي
    $this->assertDatabaseMissing('students', [
        'id' => $studentRecord->id,
    ]);
});

test('student cannot delete user', function () {
    $response = $this->actingAs($this->studentUser)
        ->deleteJson("/api/admin/users/{$this->providerUser->id}");

    $response->assertStatus(403);
});

test('provider cannot delete user', function () {
    $response = $this->actingAs($this->providerUser)
        ->deleteJson("/api/admin/users/{$this->studentUser->id}");

    $response->assertStatus(403);
});

test('unauthenticated user cannot delete user', function () {
    $response = $this->deleteJson("/api/admin/users/{$this->studentUser->id}");

    $response->assertStatus(401);
});

// ═══════════════════════════════════════════════════════════════
// ⏸️ Suspend User (suspend)
// ═══════════════════════════════════════════════════════════════

test('admin can suspend student', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/suspend");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'account_status' => 'suspended',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->studentUser->id,
        'account_status' => 'suspended',
    ]);
});

test('admin can suspend provider', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->providerUser->id}/suspend");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'account_status' => 'suspended',
            ],
        ]);
});

test('admin can suspend supervisor', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->supervisorUser->id}/suspend");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'account_status' => 'suspended',
            ],
        ]);
});

test('admin cannot suspend himself', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->adminUser->id}/suspend");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);

    // ✅ التأكد من أن الحساب لم يُعلّق
    $this->assertDatabaseHas('users', [
        'id' => $this->adminUser->id,
        'account_status' => 'active',
    ]);
});

test('suspended user cannot login', function () {
    // ✅ تعليق المستخدم
    $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/suspend");

    // ✅ محاولة تسجيل الدخول
    $response = $this->postJson('/api/login', [
        'email' => $this->studentUser->email,
        'password' => 'password', // كلمة المرور من factory
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'account_status' => 'suspended',
        ]);
});

test('student cannot suspend user', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/admin/users/{$this->providerUser->id}/suspend");

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// ▶️ Activate User (activate)
// ═══════════════════════════════════════════════════════════════

test('admin can activate suspended user', function () {
    // ✅ تعليق المستخدم أولاً
    $this->studentUser->update(['account_status' => 'suspended']);

    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/activate");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'account_status' => 'active',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->studentUser->id,
        'account_status' => 'active',
    ]);
});

test('admin can activate pending user', function () {
    $pendingUser = User::factory()->create([
        'role' => 'provider',
        'account_status' => 'pending_review',
    ]);

    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$pendingUser->id}/activate");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'account_status' => 'active',
            ],
        ]);
});

test('student cannot activate user', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/admin/users/{$this->providerUser->id}/activate");

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// 🔑 Reset Password (resetPassword)
// ═══════════════════════════════════════════════════════════════

test('admin can reset user password', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('admin reset password invalidates all tokens', function () {
    // ✅ إنشاء توكنين للمستخدم
    $token1 = $this->studentUser->createToken('token-1')->plainTextToken;
    $token2 = $this->studentUser->createToken('token-2')->plainTextToken;

    // ✅ التأكد من وجود التوكنات
    expect($this->studentUser->tokens()->count())->toBe(2);

    // ✅ إعادة تعيين كلمة المرور
    $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertStatus(200);

    // ✅ التأكد من أن جميع التوكنات تم حذفها
    expect($this->studentUser->fresh()->tokens()->count())->toBe(0);
});

test('admin cannot reset password with weak password', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/reset-password", [
            'password' => '123', // ❌ أقل من 8
            'password_confirmation' => '123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('admin cannot reset password with mismatched confirmation', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$this->studentUser->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('student cannot reset other user password', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson("/api/admin/users/{$this->providerUser->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// 🎯 Integration Tests
// ═══════════════════════════════════════════════════════════════

test('admin full workflow: create, suspend, activate, delete', function () {
    // 1️⃣ إنشاء مستخدم
    $createResponse = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/users', [
            'name' => 'Workflow User',
            'email' => 'workflow@test.com',
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '20240444',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

    $createResponse->assertStatus(201);
    $userId = $createResponse->json('data.id');

    // 2️⃣ تعليق المستخدم
    $suspendResponse = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$userId}/suspend");

    $suspendResponse->assertStatus(200);
    expect($suspendResponse->json('data.account_status'))->toBe('suspended');

    // 3️⃣ تفعيل المستخدم
    $activateResponse = $this->actingAs($this->adminUser)
        ->postJson("/api/admin/users/{$userId}/activate");

    $activateResponse->assertStatus(200);
    expect($activateResponse->json('data.account_status'))->toBe('active');

    // 4️⃣ حذف المستخدم
    $deleteResponse = $this->actingAs($this->adminUser)
        ->deleteJson("/api/admin/users/{$userId}");

    $deleteResponse->assertStatus(200);

    $this->assertDatabaseMissing('users', [
        'id' => $userId,
    ]);
});

test('admin can view statistics', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/admin/statistics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'total_students',
            'total_providers',
            'total_supervisors',
            'active_opportunities',
            'total_applications',
            'accepted_applications',
        ]);
});
