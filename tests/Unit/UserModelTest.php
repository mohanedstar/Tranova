<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_student_user_with_relationship(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $student = Student::create([
            'user_id' => $user->id,
            'student_id' => '20240001',
            'major' => 'IT',
            'university' => 'Test University',
            'year_of_study' => '3',
        ]);

        $this->assertInstanceOf(Student::class, $user->student);
        $this->assertEquals('20240001', $user->student->student_id);
        $this->assertEquals($user->id, $student->user->id);
    }

    #[Test]
    public function it_can_create_a_provider_user_with_relationship(): void
    {
        $user = User::factory()->create(['role' => 'provider']);

        $provider = Provider::create([
            'user_id' => $user->id,
            'organization_name' => 'Tech Corp',
            'organization_type' => 'company',
            'address' => 'Gaza',
            'city' => 'Gaza',
        ]);

        $this->assertInstanceOf(Provider::class, $user->provider);
        $this->assertEquals('Tech Corp', $user->provider->organization_name);
    }

    #[Test]
    public function it_can_create_a_supervisor_user_with_relationship(): void
    {
        $user = User::factory()->create(['role' => 'supervisor']);

        $supervisor = Supervisor::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP001',
            'department' => 'CS',
            'academic_title' => 'professor',
        ]);

        $this->assertInstanceOf(Supervisor::class, $user->supervisor);
        $this->assertEquals('EMP001', $user->supervisor->employee_id);
    }
}
