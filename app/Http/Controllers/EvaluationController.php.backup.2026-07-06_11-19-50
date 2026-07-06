<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\InternshipOpportunity;
use App\Models\Application;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    // تقييم من المزود
    public function storeProviderEvaluation(Request $request)
    {
        $provider = $request->user()->provider;

        if (!$provider) {
            return response()->json(['message' => 'بيانات المزود غير مكتملة'], 400);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'opportunity_id' => 'required|exists:internship_opportunities,id',
            'attendance_grade' => 'nullable|numeric|min:0|max:100',
            'commitment_grade' => 'nullable|numeric|min:0|max:100',
            'technical_skills_grade' => 'nullable|numeric|min:0|max:100',
            'teamwork_grade' => 'nullable|numeric|min:0|max:100',
            'communication_grade' => 'nullable|numeric|min:0|max:100',
            'evaluation_notes' => 'nullable|string',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'is_final' => 'boolean',
        ]);

        // ✅ التحقق من أن الفرصة تابعة للمزود
        $opportunity = InternshipOpportunity::where('id', $validated['opportunity_id'])
            ->where('provider_id', $provider->id)
            ->first();

        if (!$opportunity) {
            return response()->json(['message' => 'غير مصرح - هذه الفرصة لا تابعة لك'], 403);
        }

        // ✅ التحقق من أن الطالب مقبول في الفرصة
        $application = Application::where('student_id', $validated['student_id'])
            ->where('opportunity_id', $validated['opportunity_id'])
            ->where('status', 'accepted')
            ->first();

        if (!$application) {
            return response()->json(['message' => 'الطالب غير مقبول في هذه الفرصة'], 403);
        }

        $validated['evaluator_type'] = 'provider';
        $validated['evaluator_id'] = $provider->id;
        $validated['evaluation_date'] = now();

        // حساب الدرجة النهائية
        $grades = array_filter([
            $validated['attendance_grade'] ?? null,
            $validated['commitment_grade'] ?? null,
            $validated['technical_skills_grade'] ?? null,
            $validated['teamwork_grade'] ?? null,
            $validated['communication_grade'] ?? null,
        ]);

        if (count($grades) > 0) {
            $validated['overall_grade'] = array_sum($grades) / count($grades);
        }

        $evaluation = Evaluation::create($validated);

        return response()->json([
            'message' => 'تم حفظ التقييم',
            'evaluation' => $evaluation
        ], 201);
    }

    // تقييم من المشرف
    public function storeSupervisorEvaluation(Request $request)
    {
        $supervisor = $request->user()->supervisor;

        if (!$supervisor) {
            return response()->json(['message' => 'بيانات المشرف غير مكتملة'], 400);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'opportunity_id' => 'required|exists:internship_opportunities,id',
            'technical_skills_grade' => 'nullable|numeric|min:0|max:100',
            'commitment_grade' => 'nullable|numeric|min:0|max:100',
            'evaluation_notes' => 'nullable|string',
            'is_final' => 'boolean',
        ]);

        // ✅ التحقق من أن الطالب تابع للمشرف
        $isAssigned = $supervisor->currentStudents()
            ->where('students.id', $validated['student_id'])
            ->exists();

        if (!$isAssigned) {
            return response()->json(['message' => 'غير مصرح - هذا الطالب ليس تابعاً لك'], 403);
        }

        // ✅ التحقق من أن الطالب مقبول في الفرصة
        $application = Application::where('student_id', $validated['student_id'])
            ->where('opportunity_id', $validated['opportunity_id'])
            ->where('status', 'accepted')
            ->first();

        if (!$application) {
            return response()->json(['message' => 'الطالب غير مقبول في هذه الفرصة'], 403);
        }

        $validated['evaluator_type'] = 'supervisor';
        $validated['evaluator_id'] = $supervisor->id;
        $validated['evaluation_date'] = now();

        $grades = array_filter([
            $validated['technical_skills_grade'] ?? null,
            $validated['commitment_grade'] ?? null,
        ]);

        if (count($grades) > 0) {
            $validated['overall_grade'] = array_sum($grades) / count($grades);
        }

        $evaluation = Evaluation::create($validated);

        return response()->json([
            'message' => 'تم حفظ التقييم',
            'evaluation' => $evaluation
        ], 201);
    }

    // عرض تقييمات الطالب
    public function myEvaluations(Request $request)
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير مكتملة'], 400);
        }

        $evaluations = Evaluation::where('student_id', $student->id)
            ->with('opportunity.provider.user')
            ->get();

        return response()->json(['evaluations' => $evaluations]);
    }
}
