<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\User;

class VerifyEmailController extends Controller
{
    /**
     * عرض صفحة التحقق من البريد (اختياري)
     */
    public function notice()
    {
        return response()->json([
            'message' => 'يرجى التحقق من بريدك الإلكتروني. تم إرسال رابط التحقق إليك.',
        ]);
    }

    /**
     * التحقق من البريد الإلكتروني
     */
    public function verify(Request $request, $id, $hash)
    {
        // ✅ التحقق من صحة التوقيع
        if (!URL::hasValidSignature($request)) {
            return response()->json([
                'message' => 'رابط التحقق غير صالح أو منتهي الصلاحية',
            ], 403);
        }

        // ✅ البحث عن المستخدم
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        // ✅ التحقق من تطابق hash
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'رابط التحقق غير صالح',
            ], 403);
        }

        // ✅ التحقق من أن البريد لم يُوثّق مسبقاً
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'البريد الإلكتروني موثق بالفعل',
                'verified' => true,
            ]);
        }

        // ✅ توثيق البريد
        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json([
            'message' => 'تم التحقق من بريدك الإلكتروني بنجاح!',
            'verified' => true,
        ]);
    }

    /**
     * إعادة إرسال رابط التحقق
     */
    public function resend(Request $request)
    {
        // ✅ محاولة الحصول على المستخدم من guard sanctum
        $user = $request->user('sanctum') ?? auth('sanctum')->user();

        // ✅ التحقق من أن المستخدم مصادق عليه
        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح - يرجى تسجيل الدخول أولاً',
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'بريدك الإلكتروني موثق بالفعل',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'تم إرسال رابط التحقق مرة أخرى',
        ]);
    }
}
