<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property \Carbon\Carbon|null $approved_at
 */
class InternshipRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'opportunity_id',
        'supervisor_id',
        'start_date',
        'end_date',
        'total_hours',
        'provider_evaluation_id',
        'supervisor_evaluation_id',
        'final_grade',
        'status',
        'approved_by',
        'approved_at',
        'completion_certificate_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_hours' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    // ✅ الطالب
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ✅ الفرصة - مع تحديد foreign key الصحيح
    public function opportunity()
    {
        return $this->belongsTo(InternshipOpportunity::class, 'opportunity_id');
    }

    // ✅ المشرف
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }

    // ✅ تقييم المزود - مع تحديد foreign key الصحيح
    public function providerEvaluation()
    {
        return $this->belongsTo(Evaluation::class, 'provider_evaluation_id');
    }

    // ✅ تقييم المشرف - مع تحديد foreign key الصحيح
    public function supervisorEvaluation()
    {
        return $this->belongsTo(Evaluation::class, 'supervisor_evaluation_id');
    }

    // ✅ المعتمد (المدير)
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
