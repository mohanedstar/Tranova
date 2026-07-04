<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervisor_id',
        'student_id',
        'assigned_by',
        'assigned_at',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // المشرف
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }

    // الطالب
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // المعين (المدير)
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
