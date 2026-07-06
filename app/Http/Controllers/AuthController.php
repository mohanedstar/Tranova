<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use App\Models\Application;
use App\Notifications\NewStudentRegistered;
use App\Rules\UniversityEmail; // ✅ جديد: للتحقق من البريد الجامعي

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:student,provider,supervisor',
        ]);

        // ✅ التحقق من البريد الجامعي للمشرف فقط
        if ($request->role === 'supervisor') {
            $request->validate([
                'email' => ['required', 'email', 'unique:users,email', new UniversityEmail],
            ]);
        }

        // ✅ تحديد حالة الحساب بناءً على الدور
        $accountStatus = User::requiresAdminReview($request->role)
            ? 'pending_review'
            : 'active';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => $request->role,
            'account_status' => $accountStatus,
        ]);

        // إنشاء السجل الفرعي حسب الدور
        switch ($request->role) {
            case 'student':
                $request->validate([
                    'student_id' => 'required|string|unique:students,student_id',
                    'major' => 'required|string',
                    'university' => 'required|string',
                    'year_of_study' => 'required|in:1,2,3,4,5',
                ]);
                Student::create([
                    'user_id' => $user->id,
                    'student_id' => $request->student_id,
                    'major' => $request->major,
                    'university' => $request->university,
                    'year_of_study' => $request->year_of_study,
                ]);

                // ✅ إشعار المديرين بتسجيل طالب جديد
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new NewStudentRegistered($user));
                }
                break;

            case 'provider':
                $request->validate([
                    'organization_name' => 'required|string',
                    'organization_type' => 'required|in:company,hospital,government,nonprofit,other',
                    'address' => 'required|string',
                    'city' => 'required|string',
                ]);
                Provider::create([
                    'user_id' => $user->id,
                    'organization_name' => $request->organization_name,
                    'organization_type' => $request->organization_type,
                    'address' => $request->address,
                    'city' => $request->city,
                    'country' => $request->country ?? 'Palestine',
                ]);
                break;

            case 'supervisor':
                $request->validate([
                    'employee_id' => 'required|string|unique:supervisors,employee_id',
                    'department' => 'required|string',
                    'academic_title' => 'required|in:professor,assistant_professor,lecturer,instructor',
                ]);
                Supervisor::create([
                    'user_id' => $user->id,
                    'employee_id' => $request->employee_id,
                    'department' => $request->department,
                    'academic_title' => $request->academic_title,
                ]);
                break;
        }

        // ✅ إرسال إشعار التحقق من البريد الإلكتروني
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('trinova-token')->plainTextToken;

        // ✅ تحميل العلاقة المناسبة فقط إذا لم يكن admin
        $userData = $user;
        if (in_array($user->role, ['student', 'provider', 'supervisor'])) {
            $userData = $user->load($user->role);
        }

     // ✅ تحديد رسالة التسجيل المناسبة بناءً على حالة الحساب
if ($accountStatus === 'pending_review') {
    $message = __('messages.auth.register_pending');
} else {
    $message = __('messages.auth.register_success');
}
        return response()->json([
            'message' => $message,
            'token' => $token,
            'user' => $userData,
            'account_status' => $accountStatus,
            'email_verification_required' => true,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => __('messages.auth.invalid_credentials')], 401);
        }

        // ✅ التحقق من توثيق البريد الإلكتروني
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('messages.auth.email_not_verified'),
                'email_verification_required' => true,
            ], 403);
        }

        // ✅ التحقق من حالة الحساب
        if ($user->account_status === 'pending_review') {
            return response()->json([
                'message' => __('messages.auth.account_pending_review'),
                'account_status' => 'pending_review',
            ], 403);
        }

        if ($user->account_status === 'rejected') {
            return response()->json([
                'message' => __('messages.auth.account_rejected') . ($user->rejection_reason ?? ''),
                'account_status' => 'rejected',
                'rejection_reason' => $user->rejection_reason,
            ], 403);
        }

        if ($user->account_status === 'suspended') {
            return response()->json([
                'message' => __('messages.auth.account_suspended'),
                'account_status' => 'suspended',
            ], 403);
        }

        $token = $user->createToken('trinova-token')->plainTextToken;

        // ✅ تحميل العلاقة المناسبة فقط إذا لم يكن admin
        $userData = $user;
        if (in_array($user->role, ['student', 'provider', 'supervisor'])) {
            $userData = $user->load($user->role);
        }

        return response()->json([
            'message' => __('messages.auth.login_success'),
            'token' => $token,
            'user' => $userData,
            'email_verified' => true,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => __('messages.auth.logout_success')]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        // ✅ تحميل العلاقة المناسبة فقط إذا لم يكن admin
        $userData = $user;
        if (in_array($user->role, ['student', 'provider', 'supervisor'])) {
            $userData = $user->load($user->role);
        }

        return response()->json([
            'user' => $userData,
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    }

    // عرض ملف الطالب الشخصي (للمزود فقط)
    public function applicantProfile(Request $request, $studentId)
    {
        $provider = $request->user()->provider;

        // ✅ التحقق من وجود provider record
        if (!$provider) {
            return response()->json([
                'message' => __('messages.auth.incomplete_provider_data')
            ], 400);
        }

        // التحقق من وجود التقديم من هذا الطالب لدى مزود التدريب
        $application = Application::where('student_id', $studentId)
            ->whereHas('opportunity', function ($query) use ($provider) {
                $query->where('provider_id', $provider->id);
            })
            ->with(['student.user', 'opportunity'])
            ->first();

        if (!$application) {
            return response()->json([
                'message' => __('messages.application.no_application_found')
            ], 404);
        }

        $student = $application->student;
        $user = $student->user;

        // إرجاع البيانات المسموح بها فقط
        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'major' => $student->major,
                    'university' => $student->university,
                    'year_of_study' => $student->year_of_study,
                ],
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'application' => [
                    'id' => $application->id,
                    'opportunity_title' => $application->opportunity->title,
                    'cover_letter' => $application->cover_letter,
                    'cv_url' => $application->cv_path ? asset('storage/' . $application->cv_path) : null,
                    'status' => $application->status,
                    'applied_at' => $application->applied_at,
                ],
            ],
        ]);
    }
}
