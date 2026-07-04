<?php

namespace App\Services;

use App\Models\InternshipRecord;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate PDF certificate for the student
     */
    public function generateCertificate(InternshipRecord $record): array
    {
        try {
            // Check if the record is completed and approved
            if (!in_array($record->status, ['completed', 'approved'])) {
                throw new \Exception('Certificate cannot be issued - Training is not completed or approved');
            }

            if ($record->final_grade < 60) {
                throw new \Exception('Certificate cannot be issued - Final grade is below 60');
            }

            // Load related data
            $student = $record->student->load('user');
            $opportunity = $record->opportunity->load('provider.user');
            $supervisor = $record->supervisor?->load('user');

            // Verify data existence
            if (!$student || !$student->user) {
                throw new \Exception('Student data is incomplete');
            }

            if (!$opportunity || !$opportunity->provider) {
                throw new \Exception('Opportunity data is incomplete');
            }

            // Generate unique certificate number
            $certificateNumber = $this->generateCertificateNumber($record);

            // Prepare data for the template
            $data = [
                'studentName' => $student->user->name,
                'opportunityTitle' => $opportunity->title,
                'providerName' => $opportunity->provider->organization_name ?? 'Not Specified',
                'supervisorName' => $supervisor?->user->name ?? 'Not Specified',
                'startDate' => $record->start_date?->format('Y/m/d') ?? 'Not Specified',
                'endDate' => $record->end_date?->format('Y/m/d') ?? 'Not Specified',
                'totalHours' => number_format($record->total_hours ?? 0, 0),
                'finalGrade' => number_format($record->final_grade ?? 0, 2),
                'gradeStatus' => $this->getGradeStatus($record->final_grade),
                'certificateNumber' => $certificateNumber,
                'issueDate' => now()->format('Y/m/d'),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('certificates.certificate', $data);

            // PDF settings
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            // Save the certificate
            $fileName = "certificates/{$certificateNumber}.pdf";

            // Ensure directory exists
            if (!Storage::disk('public')->exists('certificates')) {
                Storage::disk('public')->makeDirectory('certificates');
            }

            Storage::disk('public')->put($fileName, $pdf->output());

            // Update record with certificate path
            $record->update([
                'completion_certificate_path' => $fileName,
            ]);

            return [
                'certificate_number' => $certificateNumber,
                'file_path' => $fileName,
                'file_url' => asset('storage/' . $fileName),
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Certificate generation error: ' . $e->getMessage(), [
                'record_id' => $record->id,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Download existing certificate
     */
    public function downloadCertificate(InternshipRecord $record)
    {
        if (!$record->completion_certificate_path) {
            // Generate certificate if not exists
            $this->generateCertificate($record);
        }

        $filePath = storage_path("app/public/{$record->completion_certificate_path}");

        if (!file_exists($filePath)) {
            throw new \Exception('Certificate file not found');
        }

        return response()->download($filePath, "certificate_{$record->id}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Preview certificate in browser
     */
    public function previewCertificate(InternshipRecord $record)
    {
        if (!$record->completion_certificate_path) {
            $this->generateCertificate($record);
        }

        $filePath = storage_path("app/public/{$record->completion_certificate_path}");

        if (!file_exists($filePath)) {
            throw new \Exception('Certificate file not found');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Generate unique certificate number
     */
    private function generateCertificateNumber(InternshipRecord $record): string
    {
        $year = now()->format('Y');
        $sequence = str_pad($record->id, 5, '0', STR_PAD_LEFT);
        $random = strtoupper(Str::random(4));

        return "TRN-{$year}-{$sequence}-{$random}";
    }

    /**
     * Get grade status
     */
    private function getGradeStatus(?float $grade): string
    {
        if ($grade === null) {
            return 'Not Specified';
        }

        return match (true) {
            $grade >= 90 => 'Excellent',
            $grade >= 80 => 'Very Good',
            $grade >= 70 => 'Good',
            $grade >= 60 => 'Pass',
            default => 'Fail',
        };
    }
}
