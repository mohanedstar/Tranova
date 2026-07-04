<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'major',
        'university',
        'year_of_study',
        'gpa',
        'bio',
        'skills',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'gpa' => 'decimal:2',
        ];
    }

    // العلاقة مع User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // التقديمات
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // التقارير الأسبوعية
    public function weeklyReports()
    {
        return $this->hasMany(WeeklyReport::class);
    }

    // التقييمات
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    // تعيينات المشرفين
    public function supervisorAssignments()
    {
        return $this->hasMany(SupervisorAssignment::class);
    }

    // المشرف الحالي
    public function currentSupervisor()
    {
        return $this->hasOneThrough(
            Supervisor::class,
            SupervisorAssignment::class,
            'student_id',
            'id',
            'id',
            'supervisor_id'
         )->where(function($query) {
        $query->where('supervisor_assignments.is_active', true);
    });
    }

    // سجلات التدريب
    public function internshipRecords()
    {
        return $this->hasMany(InternshipRecord::class);
    }


    /**
 * حساب التقييم النهائي لجميع فرص التدريب
 */
public function getFinalEvaluations()
{
    $evaluationService = app(\App\Services\AutoEvaluationService::class);

    return $this->applications()
        ->where('status', 'accepted')
        ->get()
        ->map(function ($application) use ($evaluationService) {
            return $evaluationService->calculateFinalGrade(
                $this->id,
                $application->opportunity_id
            );
        });
}
}
