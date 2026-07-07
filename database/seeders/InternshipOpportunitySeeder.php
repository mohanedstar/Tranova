<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\InternshipOpportunity;
use Illuminate\Database\Seeder;

class InternshipOpportunitySeeder extends Seeder
{
    public function run(): void
    {
        // ✅ في الإنتاج: تخطي تماماً
        if (app()->environment('production')) {
            $this->command->info('⏭️  Skipping InternshipOpportunitySeeder in production');
            return;
        }

        $providers = Provider::all();

        if ($providers->isEmpty()) {
            $this->command->warn('⚠️  لا يوجد مزودون. يرجى تشغيل ProviderSeeder أولاً.');
            return;
        }

        $this->command->info('🔧 Creating test opportunities...');

        $opportunities = [
            [
                'provider_index' => 0,
                'title' => 'مطور Laravel Backend',
                'description' => 'نبحث عن مطور Backend للعمل على مشاريع Laravel',
                'requirements' => 'إجادة PHP و Laravel و MySQL',
                'required_major' => 'تكنولوجيا المعلومات',
                'required_skills' => ['PHP', 'Laravel', 'MySQL', 'REST API'],
                'available_positions' => 3,
                'location' => 'غزة',
                'is_remote' => true,
                'duration_months' => 3,
                'start_date' => now()->addDays(7),
                'end_date' => now()->addMonths(3)->addDays(7),
                'application_deadline' => now()->addDays(30),
                'status' => 'open',
                'is_paid' => true,
                'salary' => 1500,
            ],
            [
                'provider_index' => 0,
                'title' => 'مطور Frontend React',
                'description' => 'فرصة تدريب في تطوير واجهات المستخدم باستخدام React',
                'requirements' => 'إجادة JavaScript و React',
                'required_major' => 'علوم الحاسوب',
                'required_skills' => ['JavaScript', 'React', 'HTML', 'CSS'],
                'available_positions' => 2,
                'location' => 'رام الله',
                'is_remote' => false,
                'duration_months' => 4,
                'start_date' => now()->addDays(14),
                'end_date' => now()->addMonths(4)->addDays(14),
                'application_deadline' => now()->addDays(20),
                'status' => 'open',
                'is_paid' => true,
                'salary' => 1200,
            ],
            [
                'provider_index' => 1,
                'title' => 'متدرب في قسم تكنولوجيا المعلومات',
                'description' => 'تدريب في قسم IT في المستشفى',
                'requirements' => 'معرفة بأنظمة الشبكات وقواعد البيانات',
                'required_major' => 'تكنولوجيا المعلومات',
                'required_skills' => ['Networking', 'Database', 'IT Support'],
                'available_positions' => 1,
                'location' => 'غزة',
                'is_remote' => false,
                'duration_months' => 6,
                'start_date' => now()->addDays(10),
                'end_date' => now()->addMonths(6)->addDays(10),
                'application_deadline' => now()->addDays(15),
                'status' => 'open',
                'is_paid' => false,
            ],
        ];

        foreach ($opportunities as $opportunityData) {
            $provider = $providers[$opportunityData['provider_index']];

            InternshipOpportunity::create([
                'provider_id' => $provider->id,
                'title' => $opportunityData['title'],
                'description' => $opportunityData['description'],
                'requirements' => $opportunityData['requirements'],
                'required_major' => $opportunityData['required_major'],
                'required_skills' => $opportunityData['required_skills'],
                'available_positions' => $opportunityData['available_positions'],
                'filled_positions' => 0,
                'location' => $opportunityData['location'],
                'is_remote' => $opportunityData['is_remote'],
                'duration_months' => $opportunityData['duration_months'],
                'start_date' => $opportunityData['start_date'],
                'end_date' => $opportunityData['end_date'],
                'application_deadline' => $opportunityData['application_deadline'],
                'status' => $opportunityData['status'],
                'is_paid' => $opportunityData['is_paid'],
                'salary' => $opportunityData['salary'] ?? null,
            ]);
        }

        $this->command->info('✅ ' . count($opportunities) . ' opportunities created');
    }
}
