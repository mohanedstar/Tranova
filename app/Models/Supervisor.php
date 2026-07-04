<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'department',
        'academic_title',
        'specialization',
        'office_location',
        'max_students',
    ];

    // العلاقة مع User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // تعيينات الطلاب
    public function assignments()
    {
        return $this->hasMany(SupervisorAssignment::class);
    }

    // الطلاب الحاليون
    public function currentStudents()
    {
        return $this->belongsToMany(
            Student::class,
            'supervisor_assignments',
            'supervisor_id',
            'student_id'
        )->wherePivot('is_active', true);
    }

    // التقارير التي يراجعها
    public function reviewedReports()
    {
        return $this->hasMany(WeeklyReport::class, 'reviewed_by');
    }

    // سجلات التدريب
    public function internshipRecords()
    {
        return $this->hasMany(InternshipRecord::class);
    }
}
