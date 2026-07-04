<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorAssignment;
use App\Models\InternshipRecord;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // عرض جميع الطلاب
    public function students()
    {
        $students = Student::with('user')
            ->withCount('applications', 'weeklyReports')
            ->paginate(20);

        return response()->json(['students' => $students]);
    }

    // عرض جميع المشرفين
    public function supervisors()
    {
        $supervisors = Supervisor::with('user')
            ->withCount('assignments')
            ->paginate(20);

        return response()->json(['supervisors' => $supervisors]);
    }

    // تعيين مشرف لطالب
    public function assignSupervisor(Request $request)
    {
        $validated = $request->validate([
            'supervisor_id' => 'required|exists:supervisors,id',
            'student_id' => 'required|exists:students,id',
            'notes' => 'nullable|string',
        ]);

        // إلغاء التعيينات السابقة
        SupervisorAssignment::where('student_id', $validated['student_id'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $assignment = SupervisorAssignment::create([
            'supervisor_id' => $validated['supervisor_id'],
            'student_id' => $validated['student_id'],
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
            'is_active' => true,
            'notes' => $validated['notes'],
        ]);

        return response()->json([
            'message' => 'تم تعيين المشرف بنجاح',
            'assignment' => $assignment->load('supervisor.user', 'student.user')
        ], 201);
    }

    // الموافقة على سجل تدريب
    public function approveRecord(Request $request, InternshipRecord $record)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $record->update([
            'status' => $validated['status'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم تحديث السجل',
            'record' => $record
        ]);
    }

    // إحصائيات عامة
    public function statistics()
    {
        return response()->json([
            'total_students' => User::where('role', 'student')->count(),
            'total_providers' => User::where('role', 'provider')->count(),
            'total_supervisors' => User::where('role', 'supervisor')->count(),
            'active_opportunities' => \App\Models\InternshipOpportunity::where('status', 'open')->count(),
            'total_applications' => \App\Models\Application::count(),
            'accepted_applications' => \App\Models\Application::where('status', 'accepted')->count(),
        ]);
    }
}
