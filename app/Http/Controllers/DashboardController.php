<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\InternshipOpportunity;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard الطالب
     */
    public function studentDashboard(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير مكتملة'], 400);
        }

        // الإحصائيات
        $stats = [
            'total_applications' => Application::where('student_id', $student->id)->count(),
            'pending_applications' => Application::where('student_id', $student->id)
                ->where('status', 'pending')->count(),
            'accepted_applications' => Application::where('student_id', $student->id)
                ->where('status', 'accepted')->count(),
            'rejected_applications' => Application::where('student_id', $student->id)
                ->where('status', 'rejected')->count(),
            'total_reports' => WeeklyReport::where('student_id', $student->id)->count(),
            'pending_reports' => WeeklyReport::where('student_id', $student->id)
                ->where('status', 'submitted')->count(),
            'approved_reports' => WeeklyReport::where('student_id', $student->id)
                ->where('status', 'approved')->count(),
            'available_opportunities' => InternshipOpportunity::where('status', 'open')
                ->where('application_deadline', '>=', now())->count(),
        ];

        // آخر التقديمات
        $recentApplications = Application::where('student_id', $student->id)
            ->with('opportunity.provider.user')
            ->orderBy('applied_at', 'desc')
            ->limit(5)
            ->get();

        // آخر التقارير
        $recentReports = WeeklyReport::where('student_id', $student->id)
            ->with('opportunity')
            ->orderBy('week_number', 'desc')
            ->limit(5)
            ->get();

        // المشرف الحالي
        $currentSupervisor = $student->currentSupervisor;

        return response()->json([
            'stats' => $stats,
            'recent_applications' => $recentApplications,
            'recent_reports' => $recentReports,
            'current_supervisor' => $currentSupervisor?->load('user'),
            'user' => $request->user()->only(['id', 'name', 'email', 'phone']),
            'student' => $student,
        ]);
    }

    /**
     * Dashboard المزود
     */
    public function providerDashboard(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;

        if (!$provider) {
            return response()->json(['message' => 'بيانات المزود غير مكتملة'], 400);
        }

        // الإحصائيات
        $stats = [
            'total_opportunities' => InternshipOpportunity::where('provider_id', $provider->id)->count(),
            'active_opportunities' => InternshipOpportunity::where('provider_id', $provider->id)
                ->where('status', 'open')->count(),
            'closed_opportunities' => InternshipOpportunity::where('provider_id', $provider->id)
                ->where('status', 'closed')->count(),
            'total_applications' => Application::whereHas('opportunity', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id);
            })->count(),
            'pending_applications' => Application::whereHas('opportunity', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id);
            })->where('status', 'pending')->count(),
            'accepted_applications' => Application::whereHas('opportunity', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id);
            })->where('status', 'accepted')->count(),
            'total_filled_positions' => InternshipOpportunity::where('provider_id', $provider->id)
                ->sum('filled_positions'),
        ];

        // آخر التقديمات (معلقة)
        $pendingApplications = Application::whereHas('opportunity', function ($q) use ($provider) {
            $q->where('provider_id', $provider->id);
        })
            ->where('status', 'pending')
            ->with('student.user', 'opportunity')
            ->orderBy('applied_at', 'desc')
            ->limit(10)
            ->get();

        // فرص المزود
        $opportunities = InternshipOpportunity::where('provider_id', $provider->id)
            ->withCount('applications')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'pending_applications' => $pendingApplications,
            'opportunities' => $opportunities,
            'user' => $request->user()->only(['id', 'name', 'email', 'phone']),
            'provider' => $provider,
        ]);
    }

    /**
     * Dashboard المشرف
     */
    public function supervisorDashboard(Request $request): JsonResponse
    {
        $supervisor = $request->user()->supervisor;

        if (!$supervisor) {
            return response()->json(['message' => 'بيانات المشرف غير مكتملة'], 400);
        }

        // الطلاب المُشرف عليهم حالياً
        $currentStudents = $supervisor->currentStudents()
            ->with('user')
            ->get();

        // الإحصائيات
        $studentIds = $currentStudents->pluck('id');

        $stats = [
            'total_students' => $currentStudents->count(),
            'max_students' => $supervisor->max_students,
            'available_slots' => max(0, $supervisor->max_students - $currentStudents->count()),
            'total_reports' => WeeklyReport::whereIn('student_id', $studentIds)->count(),
            'pending_reports' => WeeklyReport::whereIn('student_id', $studentIds)
                ->where('status', 'submitted')->count(),
            'reviewed_reports' => WeeklyReport::whereIn('student_id', $studentIds)
                ->whereIn('status', ['reviewed', 'approved'])->count(),
        ];

        // التقارير المعلقة
        $pendingReports = WeeklyReport::whereIn('student_id', $studentIds)
            ->where('status', 'submitted')
            ->with('student.user', 'opportunity')
            ->orderBy('submitted_at', 'desc')
            ->limit(10)
            ->get();

        // آخر التقارير المراجعة
        $recentReports = WeeklyReport::whereIn('student_id', $studentIds)
            ->whereIn('status', ['reviewed', 'approved'])
            ->with('student.user')
            ->orderBy('reviewed_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'current_students' => $currentStudents,
            'pending_reports' => $pendingReports,
            'recent_reports' => $recentReports,
            'user' => $request->user()->only(['id', 'name', 'email', 'phone']),
            'supervisor' => $supervisor,
        ]);
    }

    /**
     * Dashboard المدير
     */
    public function adminDashboard(Request $request): JsonResponse
    {
        // الإحصائيات العامة
        $stats = [
            'total_users' => User::count(),
            'total_students' => User::where('role', 'student')->count(),
            'total_providers' => User::where('role', 'provider')->count(),
            'total_supervisors' => User::where('role', 'supervisor')->count(),
            'total_opportunities' => InternshipOpportunity::count(),
            'active_opportunities' => InternshipOpportunity::where('status', 'open')->count(),
            'total_applications' => Application::count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
            'accepted_applications' => Application::where('status', 'accepted')->count(),
            'rejected_applications' => Application::where('status', 'rejected')->count(),
            'total_reports' => WeeklyReport::count(),
            'pending_reports' => WeeklyReport::where('status', 'submitted')->count(),
        ];

        // إحصائيات الشهر الحالي
        $currentMonthStart = now()->startOfMonth();
        $stats['monthly'] = [
            'new_students' => User::where('role', 'student')
                ->where('created_at', '>=', $currentMonthStart)->count(),
            'new_opportunities' => InternshipOpportunity::where('created_at', '>=', $currentMonthStart)->count(),
            'new_applications' => Application::where('created_at', '>=', $currentMonthStart)->count(),
        ];

        // آخر المستخدمين المسجلين
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        // آخر التقديمات
        $recentApplications = Application::with('student.user', 'opportunity.provider.user')
            ->orderBy('applied_at', 'desc')
            ->limit(10)
            ->get();

        // أفضل الفرص (الأكثر تقديمات)
        $topOpportunities = InternshipOpportunity::with('provider.user')
            ->withCount('applications')
            ->orderBy('applications_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_users' => $recentUsers,
            'recent_applications' => $recentApplications,
            'top_opportunities' => $topOpportunities,
            'user' => $request->user()->only(['id', 'name', 'email', 'phone']),
        ]);
    }
}
