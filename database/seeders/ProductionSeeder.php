<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * البيانات الآمنة للإنتاج فقط
     * ⚠️ لا تحتوي على بيانات وهمية أو كلمات مرور ضعيفة
     */
    public function run(): void
    {
        // ✅ فقط Admin في الإنتاج
        $this->call([
            AdminSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('✅ Production seeding completed successfully!');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->warn('⚠️  Note: Only Admin user was created.');
        $this->command->warn('⚠️  Providers, Supervisors, and Students will register');
        $this->command->warn('⚠️  through the platform normally.');
        $this->command->newLine();
    }
}
