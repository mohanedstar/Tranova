<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'subject',
        'message',
        'attachments',
        'is_read',
        'read_at',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // المرسل
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // المستقبل
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // الرسالة الأصلية (للردود)
    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    // الردود
    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }
}
