<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification
{
    use Queueable;

    protected $application;
    protected $status;

    public function __construct(Application $application, string $status)
    {
        $this->application = $application;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $statusText = $this->status === 'accepted' ? 'مقبول' : 'مرفوض';

        return (new MailMessage)
            ->subject("تحديث حالة التقديم - {$statusText}")
            ->line("تم {$statusText} طلبك للتدريب في: {$this->application->opportunity->title}")
            ->action('عرض التفاصيل', url('/api/student/applications'))
            ->line('شكراً لاستخدامك منصة Trinova');
    }

    public function toArray($notifiable)
    {
        $statusText = $this->status === 'accepted' ? 'مقبول' : 'مرفوض';
        $statusColor = $this->status === 'accepted' ? 'success' : 'error';

        return [
            'type' => 'application_status',
            'title' => "تم {$statusText} طلبك",
            'message' => "طلبك للتدريب في {$this->application->opportunity->title} تم {$statusText}",
            'status' => $this->status,
            'status_color' => $statusColor,
            'application_id' => $this->application->id,
            'opportunity_id' => $this->application->opportunity_id,
            'link' => "/api/student/applications/{$this->application->id}",
        ];
    }
}
