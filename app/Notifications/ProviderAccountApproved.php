<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProviderAccountApproved extends Notification
{
    use Queueable;

    protected $provider;

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تمت الموافقة على حسابك في منصة Trinova')
            ->greeting('مرحباً ' . $notifiable->name . '!')
            ->line('يسعدنا إعلامك بأنه تمت الموافقة على حساب مؤسستك في منصة Trinova.')
            ->line('يمكنك الآن نشر فرص التدريب والبدء في استقبال طلبات الطلاب.')
            ->action('تسجيل الدخول', url('/login'))
            ->line('شكراً لانضمامك إلى منصتنا!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'account_approved',
            'message' => 'تمت الموافقة على حسابك. يمكنك الآن نشر فرص التدريب.',
        ];
    }
}
