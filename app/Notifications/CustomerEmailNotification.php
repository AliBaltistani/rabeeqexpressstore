<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $emailSubject,
        protected string $emailMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->emailSubject)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->emailMessage)
            ->line('Thank you for being a valued customer at Rabeq Express Store!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->emailSubject,
            'message' => $this->emailMessage,
        ];
    }
}
