<?php

namespace App\Http\Controllers;

use App\Models\InternshipRecord;
use App\Services\AutoEvaluationService;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\InternshipOpportunity;

class EvaluationCalculationController extends Controller
{
    protected AutoEvaluationService $evaluationService;

    public function __construct(AutoEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    /**
     * عرض التقييم النهائي لطالب في فرصة (للطالب فقط)
     */
    public function showStudentEvaluation(Request $request, int $opportunityId): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير مكتملة'], 400);
        }

        $evaluation = $this->evaluationService->calculateFinalGrade(
            $student->id,
            $opportunityId
        );

        return response()->json([
            'success' => true,
            'data' => $evaluation,
        ]);
    }

    /**
     * حساب التقييم النهائي وإنشاء السجل (للمدير فقط)
     */
    public function calculateAndCreateRecord(Request $request, int $studentId, int $opportunityId): JsonResponse
    {
         try {
        // التحقق من وجود الطالب
        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'الطالب غير موجود',
            ], 404);
        }

        // التحقق من وجود الفرصة
        $opportunity = InternshipOpportunity::find($opportunityId);
        if (!$opportunity) {
            return response()->json([
                'success' => false,
                'message' => 'الفرصة غير موجودة',
            ], 404);
        }

        // التحقق من وجود التقديم المقبول
        $application = Application::where('student_id', $studentId)
            ->where('opportunity_id', $opportunityId)
            ->where('status', 'accepted')
            ->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم قبول هذا الطالب في هذه الفرصة بعد. يجب أن يكون التقديم بحالة accepted.',
            ], 400);
        }

        // حساب التقييم النهائي
        $evaluation = $this->evaluationService->calculateFinalGrade($studentId, $opportunityId);

        if (!$evaluation['is_complete']) {
            return response()->json([
                'success' => false,
                'message' => 'لم يكتمل التقييم بعد. يجب أن يكون هناك تقييم نهائي من المزود والمشرف.',
                'data' => $evaluation,
            ], 400);
        }

        // إنشاء السجل
        $record = $this->evaluationService->createInternshipRecord($studentId, $opportunityId);
        // ✅ إضافة: إرسال إشعار للمدير
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\EvaluationReady($record));
        }
        return response()->json([
            'success' => true,
            'message' => 'تم حساب التقييم النهائي وإنشاء السجل بنجاح',
            'data' => [
                'evaluation' => $evaluation,
                'record' => $record->load('student.user', 'opportunity', 'supervisor.user'),
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage(),
        ], 500);
    }
    }

    /**
     * عرض جميع التقييمات النهائية (للمدير)
     */
    public function indexAll(Request $request): JsonResponse
    {try {
        $records = InternshipRecord::with([
            'student.user:id,name,email',
            'opportunity:id,title,provider_id',
            'opportunity.provider.user:id,name',
            'supervisor.user:id,name',
            'providerEvaluation:id,overall_grade,is_final',
            'supervisorEvaluation:id,overall_grade,is_final',
        ])
            ->orderBy('final_grade', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $records,
            'meta' => [
                'total' => $records->total(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
    }

    /**
     * عرض إحصائيات التقييمات (للمدير)
     */
    public function statistics(): JsonResponse
    {
       try {
        $records = InternshipRecord::all();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'لا توجد سجلات تقييم بعد',
                'data' => [
                    'total_records' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'rejected' => 0,
                    'approved' => 0,
                    'average_grade' => 0,
                    'highest_grade' => 0,
                    'lowest_grade' => 0,
                    'excellent_count' => 0,
                    'very_good_count' => 0,
                    'good_count' => 0,
                    'pass_count' => 0,
                    'fail_count' => 0,
                ],
            ]);
        }

        $statistics = [
            'total_records' => $records->count(),
            'completed' => $records->where('status', 'completed')->count(),
            'in_progress' => $records->where('status', 'in_progress')->count(),
            'rejected' => $records->where('status', 'rejected')->count(),
            'approved' => $records->where('status', 'approved')->count(),
            'average_grade' => round($records->avg('final_grade') ?? 0, 2),
            'highest_grade' => round($records->max('final_grade') ?? 0, 2),
            'lowest_grade' => round($records->min('final_grade') ?? 0, 2),
            'excellent_count' => $records->where('final_grade', '>=', 90)->count(),
            'very_good_count' => $records->whereBetween('final_grade', [80, 89.99])->count(),
            'good_count' => $records->whereBetween('final_grade', [70, 79.99])->count(),
            'pass_count' => $records->whereBetween('final_grade', [60, 69.99])->count(),
            'fail_count' => $records->where('final_grade', '<', 60)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
    catch (\Exception $e) {
        // ✅ عرض الخطأ الفعلي للمساعدة في التشخيص
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }

}}
