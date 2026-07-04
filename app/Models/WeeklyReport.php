<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'opportunity_id',
        'report_date',
        'week_number',
        'training_hours',
        'completed_tasks',
        'challenges',
        'achievements',
        'next_week_plan',
        'attachments',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'supervisor_comments',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'attachments' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'training_hours' => 'decimal:2',
            'grade' => 'decimal:2',
        ];
    }

    // الطالب
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // الفرصة
    public function opportunity()
    {
        return $this->belongsTo(InternshipOpportunity::class, 'opportunity_id');
    }

    // المراجع (المشرف)
    public function reviewer()
    {
        return $this->belongsTo(Supervisor::class, 'reviewed_by');
    }
}
