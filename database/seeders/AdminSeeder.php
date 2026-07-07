<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ التحقق من عدم وجود admin مسبقاً
        if (User::where('role', 'admin')->exists()) {
            $this->command->info('✅ Admin user already exists, skipping...');
            return;
        }

        // ✅ في الإنتاج: كلمة مرور قوية وعشوائية
        if (app()->environment('production')) {
            $password = $this->generateStrongPassword();

            $admin = User::create([
                'name' => 'System Admin',
                'email' => 'admin@trinova.com',
                'password' => Hash::make($password),
                'phone' => '0590000000',
                'role' => 'admin',
                'email_verified_at' => now(), // ✅ مهم جداً
                'account_status' => 'active', // ✅ مهم جداً
                'preferred_language' => 'ar',
            ]);

            // ✅ طباعة كلمة المرور (مرة واحدة فقط!)
            $this->command->newLine();
            $this->command->warn('═══════════════════════════════════════════════════');
            $this->command->error('🔐 PRODUCTION ADMIN CREDENTIALS (SAVE THIS!)');
            $this->command->warn('═══════════════════════════════════════════════════');
            $this->command->info("📧 Email:    admin@trinova.com");
            $this->command->info("🔑 Password: {$password}");
            $this->command->warn('═══════════════════════════════════════════════════');
            $this->command->error('⚠️  This password will NOT be shown again!');
            $this->command->error('⚠️  Copy it NOW and store it securely!');
            $this->command->warn('═══════════════════════════════════════════════════');
            $this->command->newLine();

            return;
        }

        // ✅ في التطوير: كلمة مرور بسيطة
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@trinova.com',
            'password' => Hash::make('admin123'),
            'phone' => '0590000000',
            'role' => 'admin',
            'email_verified_at' => now(), // ✅ مهم جداً
            'account_status' => 'active', // ✅ مهم جداً
            'preferred_language' => 'ar',
        ]);

        $this->command->info('✅ Admin user created (development)');
    }

    /**
     * إنشاء كلمة مرور قوية
     */
    private function generateStrongPassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';

        $all = $uppercase . $lowercase . $numbers . $symbols;

        // ✅ التأكد من وجود كل نوع
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // ✅ ملء الباقي عشوائياً
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // ✅ خلط الحروف
        return str_shuffle($password);
    }
}
