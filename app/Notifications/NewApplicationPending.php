<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewApplicationPending extends Notification
{
    use Queueable;

    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'application_pending',
            'title' => 'تقديم جديد بانتظار المراجعة',
            'message' => "{$this->application->student->user->name} تقدم لفرصة {$this->application->opportunity->title}",
            'application_id' => $this->application->id,
            'student_id' => $this->application->student_id,
            'opportunity_id' => $this->application->opportunity_id,
            'link' => "/api/admin/evaluations/final",
        ];
    }
}
