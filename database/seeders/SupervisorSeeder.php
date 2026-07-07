<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Supervisor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupervisorSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ في الإنتاج: تخطي تماماً
        if (app()->environment('production')) {
            $this->command->info('⏭️  Skipping SupervisorSeeder in production');
            return;
        }

        $this->command->info('🔧 Creating test supervisors...');

        $supervisors = [
            [
                'name' => 'د. محمد أحمد',
                'email' => 'dr.mohammed@iugaza.edu.ps', // ✅ إصلاح النطاق الجامعي
                'phone' => '0594444444',
                'supervisor_data' => [
                    'employee_id' => 'EMP001',
                    'department' => 'قسم تكنولوجيا المعلومات',
                    'academic_title' => 'professor',
                    'specialization' => 'هندسة البرمجيات',
                    'office_location' => 'مبنى 3، غرفة 201',
                    'max_students' => 10,
                ]
            ],
            [
                'name' => 'د. فاطمة علي',
                'email' => 'dr.fatima@alazhar.edu.ps', // ✅ إصلاح النطاق الجامعي
                'phone' => '0595555555',
                'supervisor_data' => [
                    'employee_id' => 'EMP002',
                    'department' => 'قسم علوم الحاسوب',
                    'academic_title' => 'assistant_professor',
                    'specialization' => 'الذكاء الاصطناعي',
                    'office_location' => 'مبنى 2، غرفة 105',
                    'max_students' => 8,
                ]
            ],
        ];

        foreach ($supervisors as $supervisorData) {
            $user = User::create([
                'name' => $supervisorData['name'],
                'email' => $supervisorData['email'],
                'password' => Hash::make('password123'),
                'phone' => $supervisorData['phone'],
                'role' => 'supervisor',
                'email_verified_at' => now(), // ✅ مهم جداً
                'account_status' => 'active', // ✅ مهم جداً
                'preferred_language' => 'ar',
            ]);

            Supervisor::create([
                'user_id' => $user->id,
                'employee_id' => $supervisorData['supervisor_data']['employee_id'],
                'department' => $supervisorData['supervisor_data']['department'],
                'academic_title' => $supervisorData['supervisor_data']['academic_title'],
                'specialization' => $supervisorData['supervisor_data']['specialization'],
                'office_location' => $supervisorData['supervisor_data']['office_location'],
                'max_students' => $supervisorData['supervisor_data']['max_students'],
            ]);
        }

        $this->command->info('✅ ' . count($supervisors) . ' supervisors created');
    }
}
