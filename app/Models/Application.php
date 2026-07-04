<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'opportunity_id',
        'cover_letter',
        'cv_path',
        'status',
        'applied_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'provider_notes',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    // المراجع (المزود)
    public function reviewer()
    {
        return $this->belongsTo(Provider::class, 'reviewed_by');
    }
}
