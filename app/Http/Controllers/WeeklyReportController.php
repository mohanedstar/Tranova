<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Http\Requests\StoreWeeklyReportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WeeklyReportController extends Controller
{
    // إنشاء تقرير أسبوعي (للطالب)
public function store(StoreWeeklyReportRequest $request)
{
    // ✅ تسجيل بداية العملية فوراً
    Log::info('=== REPORT STORE FUNCTION CALLED ===', [
        'user_id' => $request->user()->id,
        'request_data' => $request->all(),
        'headers' => $request->headers->all(),
    ]);

    $student = $request->user()->student;

    if (!$student) {
        Log::error('Student record not found', ['user_id' => $request->user()->id]);
        return response()->json([
            'success' => false,
            'message' => __('messages.auth.incomplete_student_data')
        ], 400);
    }

    Log::info('Student found', ['student_id' => $student->id]);

    $validated = $request->validated();

    Log::info('Validation passed', ['validated_data' => $validated]);

    $validated['student_id'] = $student->id;
    $validated['status'] = 'submitted';
    $validated['submitted_at'] = now();

    // معالجة الملفات المرفقة
    if ($request->hasFile('attachments')) {
        $attachments = [];
        foreach ($request->file('attachments') as $file) {
            $attachments[] = $file->store('reports', 'public');
        }
        $validated['attachments'] = $attachments;
    }

    try {
        $report = WeeklyReport::create($validated);

        Log::info('✅ Report created successfully', [
            'report_id' => $report->id,
            'student_id' => $report->student_id,
            'opportunity_id' => $report->opportunity_id,
        ]);
        // ✅ إضافة: إرسال إشعار للمدير
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\ReportPendingApproval($report));
        }

        // إرسال إشعار للمشرف
        $supervisor = $report->student->currentSupervisor;
        if ($supervisor && $supervisor->user) {
            $supervisor->user->notify(new \App\Notifications\NewReportSubmitted($report));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.report.submitted'),
            'report' => $report
        ], 201);

    } catch (\Exception $e) {
        Log::error('❌ Error creating report', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => __('messages.report.save_error_prefix') . $e->getMessage(),
        ], 500);
    }
}

    // عرض تقارير الطالب
    public function myReports(Request $request)
    {
        $student = $request->user()->student;

        // ✅ التحقق من وجود الطالب
        if (!$student) {
            return response()->json([
                'message' => __('messages.auth.incomplete_student_data'),
                'reports' => []
            ], 400);
        }

        // ✅ تسجيل معلومات للتشخيص
        Log::info('Fetching reports for student', [
            'student_id' => $student->id,
            'user_id' => $request->user()->id
        ]);

        $reports = WeeklyReport::where('student_id', $student->id)
            ->with('opportunity.provider.user', 'reviewer.user')
            ->orderBy('week_number', 'desc')
            ->get();

        // ✅ تسجيل عدد التقارير
        Log::info('Reports found', [
            'count' => $reports->count(),
            'student_id' => $student->id
        ]);

        return response()->json([
            'success' => true,
            'reports' => $reports,
            'count' => $reports->count()
        ]);
    }

    // عرض تقارير الطلاب التابعين للمشرف
    public function studentReports(Request $request)
    {
        $supervisor = $request->user()->supervisor;

        // ✅ التحقق من وجود المشرف
        if (!$supervisor) {
            return response()->json([
                'message' => __('messages.auth.incomplete_supervisor_data'),
                'reports' => []
            ], 400);
        }

        $studentIds = $supervisor->currentStudents()->pluck('students.id');

        // ✅ إذا لم يكن هناك طلاب تابعين
        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'reports' => [],
                'message' => __('messages.report.no_students')
            ]);
        }

        $reports = WeeklyReport::whereIn('student_id', $studentIds)
            ->with('student.user', 'opportunity')
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'reports' => $reports,
            'count' => $reports->count()
        ]);
    }

    // مراجعة تقرير (للمشرف)
    public function review(Request $request, WeeklyReport $report)
    {
        $supervisor = $request->user()->supervisor;

        // ✅ التحقق من وجود المشرف
        if (!$supervisor) {
            return response()->json([
                'message' => __('messages.auth.incomplete_supervisor_data')
            ], 400);
        }

        $request->validate([
            'supervisor_comments' => 'nullable|string',
            'grade' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:reviewed,approved',
        ]);

        $report->update([
            'status' => $request->status,
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => now(),
            'supervisor_comments' => $request->supervisor_comments,
            'grade' => $request->grade,
        ]);

        return response()->json([
            'message' => __('messages.report.reviewed'),
            'report' => $report
        ]);
    }

    // عرض الطلاب المتأخرين في تسليم التقارير (للمشرف)
public function lateStudents(Request $request)
{
    $supervisor = $request->user()->supervisor;

    if (!$supervisor) {
        return response()->json([
            'message' => __('messages.auth.incomplete_supervisor_data')
        ], 400);
    }

    // الحصول على طلاب المشرف
    $studentIds = $supervisor->currentStudents()->pluck('students.id');

    if ($studentIds->isEmpty()) {
        return response()->json([
            'success' => true,
            'late_students' => [],
            'message' => __('messages.report.no_assigned_students')
        ]);
    }

    // تحديد آخر أسبوع يجب أن يكون قد تم تسليم تقريره
    // نفترض أن التقارير تُسلم أسبوعياً - آخر تقرير يجب أن يكون خلال آخر 14 يوماً
    $deadline = now()->subDays(14);

    // البحث عن الطلاب الذين لم يسلموا تقريراً منذ التاريخ المحدد
    $lateStudents = \App\Models\Student::whereIn('id', $studentIds)
        ->whereDoesntHave('weeklyReports', function ($query) use ($deadline) {
            $query->where('submitted_at', '>=', $deadline);
        })
        ->with(['user', 'weeklyReports' => function ($query) {
            $query->orderBy('submitted_at', 'desc')->limit(1);
        }])
        ->get()
        ->map(function ($student) {
            $lastReport = $student->weeklyReports->first();
            return [
                'student_id' => $student->id,
                'student_number' => $student->student_id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'major' => $student->major,
                'last_report_date' => $lastReport?->submitted_at?->format('Y-m-d'),
                'days_since_last_report' => $lastReport?->submitted_at
                    ? now()->diffInDays($lastReport->submitted_at)
                    : null,
                'status' => $lastReport ? 'late' : 'never_submitted',
            ];
        });

    return response()->json([
        'success' => true,
        'late_students' => $lateStudents,
        'count' => $lateStudents->count(),
        'deadline' => $deadline->format('Y-m-d'),
    ]);
}
}
