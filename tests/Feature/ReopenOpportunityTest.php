<?php

use App\Models\User;
use App\Models\Provider;
use App\Models\InternshipOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->providerUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'active',  // ✅ جديد

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
        'title' => 'Test Opportunity',
        'description' => 'Test Description',
        'requirements' => 'Test Requirements',
        'required_major' => 'IT',
        'available_positions' => 2,
        'location' => 'Gaza',
        'duration_months' => 3,
        'start_date' => now(),
        'end_date' => now()->addMonths(3),
        'application_deadline' => now()->addDays(30),
        'status' => 'closed', // مغلقة
    ]);
});

test('المزود يمكنه إعادة فتح فرصة مغلقة', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson("/api/provider/opportunities/{$this->opportunity->id}/reopen");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'تم إعادة فتح الفرصة بنجاح',
        ]);

    $this->opportunity->refresh();
    $this->assertEquals('open', $this->opportunity->status);
});

test('المزود لا يمكنه إعادة فتح فرصة مفتوحة', function () {
    $this->opportunity->update(['status' => 'open']);

    $response = $this->actingAs($this->providerUser)
        ->postJson("/api/provider/opportunities/{$this->opportunity->id}/reopen");

    $response->assertStatus(400);
});

test('المزود لا يمكنه إعادة فتح فرصة انتهت صلاحيتها', function () {
    $this->opportunity->update([
        'application_deadline' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->providerUser)
        ->postJson("/api/provider/opportunities/{$this->opportunity->id}/reopen");

    $response->assertStatus(400);
});

test('المزود لا يمكنه إعادة فتح فرصة لمؤسسة أخرى', function () {
    $otherProviderUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
            'account_status' => 'active',  // ✅ جديد

    ]);

    // ✅ إضافة: إنشاء Provider record للمستخدم الآخر
    $otherProvider = Provider::create([
        'user_id' => $otherProviderUser->id,
        'organization_name' => 'Other Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    $response = $this->actingAs($otherProviderUser)
        ->postJson("/api/provider/opportunities/{$this->opportunity->id}/reopen");

    $response->assertStatus(403);
});

test('الطالب لا يمكنه إعادة فتح فرصة', function () {
    $studentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($studentUser)
        ->postJson("/api/provider/opportunities/{$this->opportunity->id}/reopen");

    $response->assertStatus(403);
});
