<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_name',
        'organization_type',
        'address',
        'city',
        'country',
        'website',
        'description',
        'logo_path',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    // العلاقة مع User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // فرص التدريب
    public function opportunities()
    {
        return $this->hasMany(InternshipOpportunity::class);
    }

    // التقديمات التي يراجعها
    public function reviewedApplications()
    {
        return $this->hasMany(Application::class, 'reviewed_by');
    }
}
