<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * البوابة الرئيسية للـ seeders
     * يختار تلقائياً بين الإنتاج والتطوير
     */
    public function run(): void
    {
        // ✅ في بيئة الإنتاج: بيانات آمنة فقط
        if (app()->environment('production')) {
            $this->command->newLine();
            $this->command->warn('═══════════════════════════════════════════════════');
            $this->command->warn('🚀 PRODUCTION ENVIRONMENT DETECTED');
            $this->command->warn('═══════════════════════════════════════════════════');
            $this->command->info('Running ProductionSeeder (safe data only)...');
            $this->command->newLine();

            $this->call([
                ProductionSeeder::class,
            ]);

            return;
        }

        // ✅ في بيئة التطوير: كل البيانات
        $this->command->newLine();
        $this->command->info('🔧 DEVELOPMENT ENVIRONMENT DETECTED');
        $this->command->info('Running DevelopmentSeeder (all test data)...');
        $this->command->newLine();

        $this->call([
            DevelopmentSeeder::class,
        ]);
    }
}
