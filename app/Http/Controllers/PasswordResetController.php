<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\VerifyTokenRequest;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * طلب إرسال رابط إعادة تعيين كلمة المرور
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $user = User::where('email', $email)->first();

        // حذف أي توكنات سابقة لهذا البريد
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // إنشاء توكن عشوائي جديد
        $token = Str::random(60);

        // حفظ التوكن (مشفراً) في قاعدة البيانات
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // إرسال الإشعار عبر البريد
        $user->notify(new ResetPasswordNotification($token, $email));

        return response()->json([
            'message' => __('messages.auth.password_reset_sent'),
            // ⚠️ احذف هذا السطر في الإنتاج! (موجود للاختبار فقط)
            'token' => $token,
        ], 200);
    }

    /**
     * التحقق من صلاحية التوكن
     */
    public function verifyToken(VerifyTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $resetRecord = $this->getValidResetRecord($validated['email'], $validated['token']);

        if (!$resetRecord) {
            return response()->json([
                'message' => __('messages.auth.token_invalid'),
                'valid' => false,
            ], 400);
        }

        return response()->json([
            'message' => __('messages.auth.token_valid'),
            'valid' => true,
        ], 200);
    }

    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // التحقق من صلاحية التوكن
        $resetRecord = $this->getValidResetRecord($validated['email'], $validated['token']);

        if (!$resetRecord) {
            return response()->json([
                'message' => __('messages.auth.token_invalid'),
            ], 400);
        }

        // ✅ تحديث كلمة المرور (Laravel سيقوم بتشفيرها تلقائياً عبر hashed cast)
        $user = User::where('email', $validated['email'])->first();
        $user->update([
            'password' => $validated['password'],
        ]);

        // حذف التوكن بعد الاستخدام (لمرة واحدة فقط)
        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        // إلغاء جميع التوكنات الأخرى (تسجيل الخروج من جميع الأجهزة)
        $user->tokens()->delete();

        return response()->json([
            'message' => __('messages.auth.password_reset_success'),
        ], 200);
    }

    /**
     * دالة مساعدة للتحقق من التوكن وصلاحية الزمن
     */
    private function getValidResetRecord(string $email, string $token)
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return null;
        }

        // التحقق من صلاحية التوكن (60 دقيقة)
        $createdAt = Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return null;
        }

        // التحقق من مطابقة التوكن
        if (!Hash::check($token, $resetRecord->token)) {
            return null;
        }

        return $resetRecord;
    }
}
