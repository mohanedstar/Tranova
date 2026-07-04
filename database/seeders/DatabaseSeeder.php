<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 بدء ملء قاعدة البيانات...');

        $this->call([
            AdminSeeder::class,
            StudentSeeder::class,
            ProviderSeeder::class,
            SupervisorSeeder::class,
            InternshipOpportunitySeeder::class,
        ]);

        $this->command->info('✅ تم ملء قاعدة البيانات بنجاح!');
        $this->command->info('');
        $this->command->info('📧 بيانات الدخول:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('👨‍💼 Admin:    admin@trinova.com / admin123');
        $this->command->info('👨‍🎓 Student:  ahmed@student.com / password123');
        $this->command->info('🏢 Provider:  hr@techcorp.com / password123');
        $this->command->info('👨‍🏫 Supervisor: dr.mohammed@university.edu / password123');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
