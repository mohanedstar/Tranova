<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'title',
        'description',
        'requirements',
        'required_major',
        'required_skills',
        'available_positions',
        'filled_positions',
        'location',
        'is_remote',
        'duration_months',
        'start_date',
        'end_date',
        'application_deadline',
        'status',
        'salary',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'is_remote' => 'boolean',
            'is_paid' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'application_deadline' => 'datetime',
            'salary' => 'decimal:2',
        ];
    }

    // المزود
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    // التقديمات
    public function applications()
    {
        return $this->hasMany(Application::class, 'opportunity_id');    }

    // التقارير
    public function weeklyReports()
    {
        return $this->hasMany(WeeklyReport::class, 'opportunity_id');
    }

    // التقييمات
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'opportunity_id');
    }

    // السجلات
    public function internshipRecords()
    {
        return $this->hasMany(InternshipRecord::class, 'opportunity_id');
    }

    // Scope للفرص المتاحة فقط
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    // Scope للفرص التي لم ينتهي موعدها
    public function scopeActive($query)
    {
        return $query->where('status', 'open')
                    ->where('application_deadline', '>=', now());
    }
}
