<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('تحقق من بريدك الإلكتروني')
            ->greeting('مرحباً ' . $notifiable->name . '!')
            ->line('يرجى النقر على الزر أدناه للتحقق من بريدك الإلكتروني.')
            ->action('تحقق من البريد', $verificationUrl)
            ->line('إذا لم تقم بإنشاء حساب، لا حاجة لاتخاذ أي إجراء.')
            ->line('شكراً لاستخدامك منصة Trinova!');
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
