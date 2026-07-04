<?php

namespace App\Http\Controllers;

use App\Models\InternshipRecord;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Download student's own certificate
     */
    public function downloadMyCertificate(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            abort(400, 'Student data is incomplete');
        }

        // Get the latest completed internship record for the student
        $record = InternshipRecord::where('student_id', $student->id)
            ->whereIn('status', ['completed', 'approved'])
            ->where('final_grade', '>=', 60)
            ->orderBy('final_grade', 'desc')
            ->first();

        if (!$record) {
            abort(404, 'No certificate available - You have not successfully completed the training yet');
        }

        return $this->certificateService->downloadCertificate($record);
    }

    /**
     * Preview student's own certificate
     */
    public function previewMyCertificate(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            abort(400, 'Student data is incomplete');
        }

        $record = InternshipRecord::where('student_id', $student->id)
            ->whereIn('status', ['completed', 'approved'])
            ->where('final_grade', '>=', 60)
            ->orderBy('final_grade', 'desc')
            ->first();

        if (!$record) {
            abort(404, 'No certificate available');
        }

        return $this->certificateService->previewCertificate($record);
    }

    /**
     * Download a specific student's certificate (Admin only)
     */
    public function downloadStudentCertificate(Request $request, int $studentId): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $record = InternshipRecord::where('student_id', $studentId)
            ->whereIn('status', ['completed', 'approved'])
            ->where('final_grade', '>=', 60)
            ->orderBy('final_grade', 'desc')
            ->first();

        if (!$record) {
            abort(404, 'No certificate available for this student');
        }

        return $this->certificateService->downloadCertificate($record);
    }

    /**
     * Generate/Regenerate certificate (Admin only)
     */
    public function generateCertificate(Request $request, int $recordId): JsonResponse
    {
        try {
            // Check if record exists
            $record = InternshipRecord::find($recordId);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found',
                ], 404);
            }

            // Check record status
            if (!in_array($record->status, ['completed', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record must be completed or approved',
                ], 400);
            }

            // Check grade
            if ($record->final_grade < 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'Final grade is below 60',
                ], 400);
            }

            // Generate certificate
            $result = $this->certificateService->generateCertificate($record);

            return response()->json([
                'success' => true,
                'message' => 'Certificate issued successfully',
                'data' => $result,
            ], 201);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Error generating certificate: ' . $e->getMessage(), [
                'record_id' => $recordId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'file' => config('app.debug') ? $e->getFile() : null,
                'line' => config('app.debug') ? $e->getLine() : null,
            ], 500);
        }
    }

    /**
     * List student's certificates (Student)
     */
    public function myCertificates(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student data is incomplete'], 400);
        }

        $records = InternshipRecord::where('student_id', $student->id)
            ->whereIn('status', ['completed', 'approved'])
            ->where('final_grade', '>=', 60)
            ->with(['opportunity.provider.user', 'supervisor.user'])
            ->orderBy('final_grade', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'opportunity_title' => $record->opportunity->title,
                    'provider_name' => $record->opportunity->provider->organization_name,
                    'final_grade' => $record->final_grade,
                    'status' => $record->status,
                    'certificate_number' => $record->completion_certificate_path
                        ? basename($record->completion_certificate_path, '.pdf')
                        : null,
                    'has_certificate' => !empty($record->completion_certificate_path),
                    'issue_date' => $record->updated_at?->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * List all certificates (Admin)
     */
    public function allCertificates(Request $request): JsonResponse
    {
        $records = InternshipRecord::with([
            'student.user',
            'opportunity.provider.user',
            'supervisor.user',
        ])
            ->whereIn('status', ['completed', 'approved'])
            ->where('final_grade', '>=', 60)
            ->orderBy('final_grade', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }
}
