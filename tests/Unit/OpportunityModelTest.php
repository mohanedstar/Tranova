<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\InternshipOpportunity;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpportunityModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_opportunity_belongs_to_a_provider(): void
    {
        $providerUser = User::factory()->create(['role' => 'provider']);
        $provider = Provider::create([
            'user_id' => $providerUser->id,
            'organization_name' => 'Tech Corp',
            'organization_type' => 'company',
            'address' => 'Gaza',
            'city' => 'Gaza',
        ]);

        $opportunity = InternshipOpportunity::create([
            'provider_id' => $provider->id,
            'title' => 'Laravel Developer',
            'description' => 'Test',
            'requirements' => 'Laravel',
            'required_major' => 'IT',
            'location' => 'Gaza',
            'is_remote' => true,
            'is_paid' => true,
            'salary' => 1000,
            'available_positions' => 2,
            'duration_months' => 3,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'application_deadline' => now()->addMonth(),
            'status' => 'open',
        ]);

        $this->assertInstanceOf(Provider::class, $opportunity->provider);
        $this->assertEquals('Tech Corp', $opportunity->provider->organization_name);
    }

    #[Test]
    public function an_opportunity_can_have_multiple_applications(): void
    {
        // إنشاء فرصة
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
            'title' => 'Test Opportunity',
            'description' => 'Test',
            'requirements' => 'Test',
            'required_major' => 'IT',
            'location' => 'Gaza',
            'is_remote' => true,
            'is_paid' => true,
            'salary' => 1000,
            'available_positions' => 2,
            'duration_months' => 3,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'application_deadline' => now()->addMonth(),
            'status' => 'open',
        ]);

        // إنشاء طالبين
        $studentUser1 = User::factory()->create(['role' => 'student']);
        $student1 = Student::create([
            'user_id' => $studentUser1->id,
            'student_id' => 'S1',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

        $studentUser2 = User::factory()->create(['role' => 'student']);
        $student2 = Student::create([
            'user_id' => $studentUser2->id,
            'student_id' => 'S2',
            'major' => 'IT',
            'university' => 'Test',
            'year_of_study' => '3',
        ]);

        // ✅ إنشاء التقديمات مع cv_path
        Application::create([
            'student_id' => $student1->id,
            'opportunity_id' => $opportunity->id,
            'cover_letter' => 'My cover letter',
            'cv_path' => 'cvs/cv1.pdf', // ✅ أضفنا هذا
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        Application::create([
            'student_id' => $student2->id,
            'opportunity_id' => $opportunity->id,
            'cover_letter' => 'My cover letter',
            'cv_path' => 'cvs/cv2.pdf', // ✅ أضفنا هذا
            'status' => 'accepted',
            'applied_at' => now(),
        ]);

        // التأكيدات
        $this->assertCount(2, $opportunity->applications);
        $this->assertEquals(1, $opportunity->applications()->where('status', 'accepted')->count());
    }
}
