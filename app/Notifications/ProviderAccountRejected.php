<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProviderAccountRejected extends Notification
{
    use Queueable;

    protected $provider;
    protected $reason;

    public function __construct($provider, string $reason)
    {
        $this->provider = $provider;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تم رفض حسابك في منصة Trinova')
            ->greeting('مرحباً ' . $notifiable->name . ',')
            ->line('نأسف لإعلامك بأنه تم رفض طلب تسجيل مؤسستك في منصة Trinova.')
            ->line('**سبب الرفض:**')
            ->line($this->reason)
            ->line('إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع فريق الدعم.')
            ->line('شكراً لاهتمامك بمنصتنا.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'account_rejected',
            'message' => 'تم رفض حسابك. السبب: ' . $this->reason,
            'reason' => $this->reason,
        ];
    }
}
