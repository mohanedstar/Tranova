<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewStudentRegistered extends Notification
{
    use Queueable;

    protected $student;

    public function __construct(User $student)
    {
        $this->student = $student;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'new_student',
            'title' => 'طالب جديد مسجل',
            'message' => "طالب جديد {$this->student->name} سجل في المنصة",
            'student_id' => $this->student->student->id ?? null,
            'user_id' => $this->student->id,
            'link' => "/api/admin/students",
        ];
    }
}
