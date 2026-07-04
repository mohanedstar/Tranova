<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageReceived extends Notification
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'new_message',
            'title' => 'رسالة جديدة',
            'message' => "لديك رسالة جديدة من {$this->message->sender->name}",
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'subject' => $this->message->subject,
            'preview' => substr($this->message->message, 0, 100),
            'link' => "/api/messages/inbox",
        ];
    }
}
