<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use App\Notifications\NewStudentRegistered;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:student,provider,supervisor',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => $request->role,
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

        return response()->json([
            'message' => 'تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني.',
            'token' => $token,
            'user' => $userData,
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
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        // ✅ التحقق من توثيق البريد الإلكتروني
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'يرجى التحقق من بريدك الإلكتروني أولاً',
                'email_verification_required' => true,
            ], 403);
        }

        $token = $user->createToken('trinova-token')->plainTextToken;

        // ✅ تحميل العلاقة المناسبة فقط إذا لم يكن admin
        $userData = $user;
        if (in_array($user->role, ['student', 'provider', 'supervisor'])) {
            $userData = $user->load($user->role);
        }

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'user' => $userData,
            'email_verified' => true,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
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
}
