<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\InternshipOpportunity;
use App\Http\Requests\StoreApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * تقديم على فرصة (للطالب فقط)
     */
    public function store(StoreApplicationRequest $request, InternshipOpportunity $opportunity)
    {
        $student = $request->user()->student;

        // التحقق من أن الفرصة لا تزال مفتوحة
        if ($opportunity->status !== 'open' || $opportunity->application_deadline < now()) {
            return response()->json(['message' => __('messages.opportunity.not_available')], 400);
        }

        // التحقق من عدم التقديم مسبقاً
        $exists = Application::where('student_id', $student->id)
            ->where('opportunity_id', $opportunity->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => __('messages.opportunity.already_applied')], 400);
        }

        $validated = $request->validated();
        $cvPath = $request->file('cv')->store('cvs', 'public');

        $application = Application::create([
            'student_id' => $student->id,
            'opportunity_id' => $opportunity->id,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'cv_path' => $cvPath,
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        // ✅ إرسال إشعار للمدير
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewApplicationPending($application));
        }

        // إرسال إشعار للمزود
        $provider = $application->opportunity->provider;
        $provider->user->notify(new \App\Notifications\NewApplicationReceived($application));

        return response()->json([
            'message' => __('messages.application.submitted'),
            'application' => $application->load('opportunity.provider.user')
        ], 201);
    }

    /**
     * عرض تقديمات الطالب
     */
    public function myApplications(Request $request)
    {
        $student = $request->user()->student;
        $applications = Application::where('student_id', $student->id)
            ->with('opportunity.provider.user')
            ->orderBy('applied_at', 'desc')
            ->get();

        return response()->json(['applications' => $applications]);
    }

    /**
     * عرض المتقدمين لفرصة (للمزود فقط)
     */
    public function indexForOpportunity(Request $request, InternshipOpportunity $opportunity)
    {
        if ($opportunity->provider_id !== $request->user()->provider->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        $applications = Application::where('opportunity_id', $opportunity->id)
            ->with('student.user')
            ->orderBy('applied_at', 'desc')
            ->get();

        return response()->json(['applications' => $applications]);
    }

    /**
     * قبول أو رفض تقديم (للمزود فقط)
     */
    public function review(Request $request, Application $application)
    {
        $provider = $request->user()->provider;

        if ($application->opportunity->provider_id !== $provider->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected',
            'rejection_reason' => 'nullable|string',
            'provider_notes' => 'nullable|string',
        ]);

        $application->update([
            'status' => $request->status,
            'reviewed_at' => now(),
            'reviewed_by' => $provider->id,
            'rejection_reason' => $request->rejection_reason,
            'provider_notes' => $request->provider_notes,
        ]);

        // إرسال إشعار للطالب
        $student = $application->student;
        $student->user->notify(new \App\Notifications\ApplicationStatusChanged($application, $request->status));

        // تحديث عدد المقبولين
        if ($request->status === 'accepted') {
            $application->opportunity->increment('filled_positions');
        }

        return response()->json([
            'message' => __('messages.application.status_updated'),
            'application' => $application
        ]);
    }

    /**
     * انسحاب من التقديم (للطالب)
     */
    public function withdraw(Request $request, Application $application)
    {
        if ($application->student_id !== $request->user()->student->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        $application->update(['status' => 'withdrawn']);

        return response()->json(['message' => __('messages.application.withdrawn')]);
    }

    /**
     * ✅ جديد: عرض ملف الطالب الشخصي (US-018)
     * يسمح للمزود بعرض ملف الطالب الذي تقدم لديه فقط
     * لا يُرجع أي بيانات خارج نطاق التقديم (مثل التقارير)
     */
    public function applicantProfile(Request $request, $studentId)
    {
        // ✅ التحقق من وجود provider record
        $provider = $request->user()->provider;

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

        // إرجاع البيانات المسموح بها فقط (بدون التقارير أو بيانات حساسة أخرى)
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
