<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'opportunity_id',
        'evaluator_type',
        'evaluator_id',
        'attendance_grade',
        'commitment_grade',
        'technical_skills_grade',
        'teamwork_grade',
        'communication_grade',
        'overall_grade',
        'evaluation_notes',
        'strengths',
        'areas_for_improvement',
        'evaluation_date',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'attendance_grade' => 'decimal:2',
            'commitment_grade' => 'decimal:2',
            'technical_skills_grade' => 'decimal:2',
            'teamwork_grade' => 'decimal:2',
            'communication_grade' => 'decimal:2',
            'overall_grade' => 'decimal:2',
            'evaluation_date' => 'date',
            'is_final' => 'boolean',
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

    // المقيّم (يمكن أن يكون Provider أو Supervisor)
    public function evaluator()
    {
        if ($this->evaluator_type === 'provider') {
            return $this->belongsTo(Provider::class, 'evaluator_id');
        } else {
            return $this->belongsTo(Supervisor::class, 'evaluator_id');
        }
    }

    // Scope للتقييمات النهائية فقط
public function scopeFinal($query)
{
    return $query->where('is_final', true);
}

// Scope حسب نوع المقيّم
public function scopeByType($query, string $type)
{
    return $query->where('evaluator_type', $type);
}
}
