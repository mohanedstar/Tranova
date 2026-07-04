<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'email_verified_at',  // ✅ أضفنا هذا
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==================== العلاقات مع الجداول الفرعية ====================

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function provider()
    {
        return $this->hasOne(Provider::class);
    }

    public function supervisor()
    {
        return $this->hasOne(Supervisor::class);
    }

    // ==================== الرسائل ====================

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // ==================== Helper methods للتحقق من الدور ====================

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isProvider(): bool
    {
        return $this->role === 'provider';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ==================== Helper methods للتحقق من البريد ====================

    /**
     * التحقق من أن البريد موثق
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * تحديد البريد الإلكتروني للتحقق
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * تعليم البريد الإلكتروني كمُوثّق
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * إرسال إشعار التحقق من البريد
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification());
    }
}
