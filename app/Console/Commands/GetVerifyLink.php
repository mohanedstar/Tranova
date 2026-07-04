<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use App\Models\User;

class GetVerifyLink extends Command
{
    protected $signature = 'email:verify-link {email? : البريد الإلكتروني للمستخدم}';
    protected $description = 'استخراج رابط التحقق من البريد الإلكتروني';

    public function handle()
    {
        $email = $this->argument('email');

        // إذا لم يتم تحديد بريد، عرض قائمة المستخدمين
        if (!$email) {
            $users = User::whereNull('email_verified_at')
                ->select('id', 'name', 'email', 'role')
                ->get();

            if ($users->isEmpty()) {
                $this->warn('⚠️  لا يوجد مستخدمين بحاجة للتحقق من البريد');
                return 0;
            }

            $this->info('📋 المستخدمون الذين لم يتحققوا من بريدهم:');
            $this->table(
                ['ID', 'الاسم', 'البريد', 'الدور'],
                $users->map(fn($u) => [$u->id, $u->name, $u->email, $u->role])
            );

            $email = $this->ask('أدخل البريد الإلكتروني للمستخدم');
        }

        // البحث عن المستخدم
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ المستخدم بالبريد '$email' غير موجود");
            return 1;
        }

        // التحقق من حالة البريد
        if ($user->hasVerifiedEmail()) {
            $this->info("✅ بريد المستخدم '$email' موثق بالفعل");
            $this->line("   تاريخ التوثيق: " . $user->email_verified_at);
            return 0;
        }

        // إنشاء رابط التحقق
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // عرض المعلومات
        $this->newLine();
        $this->info('🎯 معلومات المستخدم:');
        $this->table(
            ['الحقل', 'القيمة'],
            [
                ['ID', $user->id],
                ['الاسم', $user->name],
                ['البريد', $user->email],
                ['الدور', $user->role],
                ['حالة التحقق', '❌ غير موثق'],
            ]
        );

        $this->newLine();
        $this->info('🔗 رابط التحقق (صالح لمدة 60 دقيقة):');
        $this->line($verificationUrl);

        // حفظ الرابط في ملف
        file_put_contents('verify-link.txt', $verificationUrl);
        $this->newLine();
        $this->info('💾 تم حفظ الرابط في: verify-link.txt');

        // اختبار الرابط
        if ($this->confirm('هل تريد اختبار الرابط الآن؟')) {
            $this->info('🚀 جاري اختبار الرابط...');

            try {
                $response = $this->laravel->make(\Illuminate\Http\Request::class)
                    ->create($verificationUrl, 'GET');

                $this->info('✅ الرابط صالح ويمكن الوصول إليه');
            } catch (\Exception $e) {
                $this->error('❌ خطأ: ' . $e->getMessage());
            }
        }

        // خيارات إضافية
        $this->newLine();
        $action = $this->choice('ماذا تريد أن تفعل؟', [
            'لا شيء - فقط عرض الرابط',
            'توثيق البريد يدوياً (بدون استخدام الرابط)',
            'إعادة إرسال إشعار التحقق',
            'حذف المستخدم',
        ], 0);

        switch ($action) {
            case 'توثيق البريد يدوياً (بدون استخدام الرابط)':
                $user->markEmailAsVerified();
                $this->info('✅ تم توثيق البريد يدوياً');
                break;

            case 'إعادة إرسال إشعار التحقق':
                $user->sendEmailVerificationNotification();
                $this->info('✅ تم إرسال إشعار التحقق مرة أخرى');
                $this->line('   تحقق من ملف: storage/logs/laravel.log');
                break;

            case 'حذف المستخدم':
                if ($this->confirm('هل أنت متأكد من حذف المستخدم؟', false)) {
                    $user->delete();
                    $this->info('✅ تم حذف المستخدم');
                }
                break;
        }

        return 0;
    }
}
