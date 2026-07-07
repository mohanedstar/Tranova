<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * بيانات التطوير (لا تُستخدم في الإنتاج)
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            StudentSeeder::class,
            ProviderSeeder::class,
            SupervisorSeeder::class,
            InternshipOpportunitySeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('✅ Development seeding completed successfully!');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('📧 Login Credentials:');
        $this->command->info('┌─────────────────────────────────────────────────┐');
        $this->command->info('│ 👨‍💼 Admin:      admin@trinova.com / admin123     │');
        $this->command->info('│ 👨‍🎓 Student:    ahmed@student.com / password123  │');
        $this->command->info('│ 🏢 Provider:    hr@techcorp.com / password123    │');
        $this->command->info('│ 👨‍🏫 Supervisor: dr.mohammed@iugaza.edu.ps / pass123 │');
        $this->command->info('└─────────────────────────────────────────────────┘');
        $this->command->newLine();
    }
}
