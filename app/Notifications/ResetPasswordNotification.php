<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected $token;
    protected $email;

    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $resetUrl = url("/password-reset?token={$this->token}&email={$this->email}");

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور - Trinova')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('أنت تتلقى هذا البريد لأننا تلقينا طلب إعادة تعيين كلمة المرور لحسابك.')
            ->action('إعادة تعيين كلمة المرور', $resetUrl)
            ->line('هذا الرابط صالح لمدة 60 دقيقة فقط.')
            ->line('إذا لم تطلب إعادة التعيين، يمكنك تجاهل هذا البريد بأمان.')
            ->salutation('مع تحيات فريق Trinova');
    }
}
