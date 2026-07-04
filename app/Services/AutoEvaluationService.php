<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Evaluation;
use App\Models\InternshipRecord;
use App\Models\Student;
use App\Models\WeeklyReport;
// use Illuminate\Support\Facades\DB;

class AutoEvaluationService
{
    // أوزان التقييم
    private const PROVIDER_WEIGHT = 0.40;  // 40%
    private const SUPERVISOR_WEIGHT = 0.40; // 40%
    private const REPORTS_WEIGHT = 0.20;    // 20%

    /**
     * حساب الدرجة النهائية للطالب في فرصة تدريب
     */
    public function calculateFinalGrade(int $studentId, int $opportunityId): array
    {
        // 1️⃣ جلب تقييم المزود
        $providerEvaluation = Evaluation::where('student_id', $studentId)
            ->where('opportunity_id', $opportunityId)
            ->where('evaluator_type', 'provider')
            ->where('is_final', true)
            ->first();

        // 2️⃣ جلب تقييم المشرف
        $supervisorEvaluation = Evaluation::where('student_id', $studentId)
            ->where('opportunity_id', $opportunityId)
            ->where('evaluator_type', 'supervisor')
            ->where('is_final', true)
            ->first();

        // 3️⃣ حساب متوسط درجات التقارير الأسبوعية
        $reportsAverage = WeeklyReport::where('student_id', $studentId)
            ->where('opportunity_id', $opportunityId)
            ->whereNotNull('grade')
            ->avg('grade') ?? 0;

        // 4️⃣ حساب الدرجة النهائية
        $providerGrade = $providerEvaluation?->overall_grade ?? 0;
        $supervisorGrade = $supervisorEvaluation?->overall_grade ?? 0;

        $finalGrade = ($providerGrade * self::PROVIDER_WEIGHT)
                    + ($supervisorGrade * self::SUPERVISOR_WEIGHT)
                    + ($reportsAverage * self::REPORTS_WEIGHT);

        return [
            'student_id' => $studentId,
            'opportunity_id' => $opportunityId,
            'provider_grade' => $providerGrade,
            'provider_weight' => self::PROVIDER_WEIGHT * 100,
            'supervisor_grade' => $supervisorGrade,
            'supervisor_weight' => self::SUPERVISOR_WEIGHT * 100,
            'reports_average' => round($reportsAverage, 2),
            'reports_weight' => self::REPORTS_WEIGHT * 100,
            'final_grade' => round($finalGrade, 2),
            'status' => $this->getGradeStatus($finalGrade),
            'is_complete' => $providerEvaluation && $supervisorEvaluation,
        ];
    }

    /**
     * تحديد حالة الدرجة (ممتاز، جيد جداً، جيد، مقبول، راسب)
     */
    public function getGradeStatus(float $grade): string
    {
        return match (true) {
            $grade >= 90 => 'ممتاز (Excellent)',
            $grade >= 80 => 'جيد جداً (Very Good)',
            $grade >= 70 => 'جيد (Good)',
            $grade >= 60 => 'مقبول (Pass)',
            $grade >= 50 => 'ضعيف (Weak)',
            default => 'راسب (Fail)',
        };
    }

    /**
     * إنشاء سجل التدريب النهائي
     */
    public function createInternshipRecord(int $studentId, int $opportunityId): InternshipRecord
    {
        $student = Student::findOrFail($studentId);

    // ✅ التحقق من وجود التقديم بحالة accepted
    $application = Application::where('student_id', $studentId)
        ->where('opportunity_id', $opportunityId)
        ->where('status', 'accepted')
        ->first();

    // ✅ إذا لم يوجد تقديم مقبول، ارمي استثناء واضح
    if (!$application) {
        throw new \Exception('لم يتم قبول هذا الطالب في هذه الفرصة بعد. يجب أن يكون التقديم بحالة accepted.');
    }

    // حساب الدرجة النهائية
    $evaluation = $this->calculateFinalGrade($studentId, $opportunityId);

    // جلب الفرصة
    $opportunity = $application->opportunity;

    // جلب المشرف الحالي
    $supervisor = $student->currentSupervisor;

    // التحقق من وجود المشرف
    if (!$supervisor) {
        throw new \Exception('لم يتم تعيين مشرف لهذا الطالب بعد.');
    }

    // حساب إجمالي ساعات التدريب
    $totalHours = WeeklyReport::where('student_id', $studentId)
        ->where('opportunity_id', $opportunityId)
        ->sum('training_hours');

    // جلب التقييمات النهائية
    $providerEvaluation = Evaluation::where('student_id', $studentId)
        ->where('opportunity_id', $opportunityId)
        ->where('evaluator_type', 'provider')
        ->where('is_final', true)
        ->first();

    $supervisorEvaluation = Evaluation::where('student_id', $studentId)
        ->where('opportunity_id', $opportunityId)
        ->where('evaluator_type', 'supervisor')
        ->where('is_final', true)
        ->first();

    // التحقق من وجود التقييمات
    if (!$providerEvaluation || !$supervisorEvaluation) {
        throw new \Exception('لم يكتمل التقييم بعد. يجب أن يكون هناك تقييم نهائي من المزود والمشرف.');
    }

    // إنشاء السجل
    return InternshipRecord::updateOrCreate(
        [
            'student_id' => $studentId,
            'opportunity_id' => $opportunityId,
        ],
        [
            'supervisor_id' => $supervisor->id,
            'start_date' => $opportunity->start_date ?? now(),
            'end_date' => $opportunity->end_date ?? now(),
            'total_hours' => $totalHours,
            'provider_evaluation_id' => $providerEvaluation->id,
            'supervisor_evaluation_id' => $supervisorEvaluation->id,
            'final_grade' => $evaluation['final_grade'],
            'status' => $evaluation['final_grade'] >= 60 ? 'completed' : 'rejected',
        ]
    );
}
}
