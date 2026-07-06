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
            'message' => __('messages.auth.verification_notice'),
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
                'message' => __('messages.auth.invalid_verification_link'),
            ], 403);
        }

        // ✅ البحث عن المستخدم
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => __('messages.auth.user_not_found'),
            ], 404);
        }

        // ✅ التحقق من تطابق hash
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => __('messages.auth.invalid_link'),
            ], 403);
        }

        // ✅ التحقق من أن البريد لم يُوثّق مسبقاً
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('messages.auth.already_verified'),
                'verified' => true,
            ]);
        }

        // ✅ توثيق البريد
        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json([
            'message' => __('messages.auth.email_verified'),
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
                'message' => __('messages.general.unauthorized_login'),
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('messages.auth.already_verified_message'),
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => __('messages.auth.verification_resent'),
        ]);
    }
}
