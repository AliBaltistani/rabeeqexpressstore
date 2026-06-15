<?php

namespace App\Channels;

use App\Services\TwilioService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function __construct(protected TwilioService $twilio) {}

    /**
     * Send the given notification via SMS.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get the phone number to route to
        $phone = method_exists($notifiable, 'routeNotificationForSms')
            ? $notifiable->routeNotificationForSms($notification)
            : ($notifiable->phone ?? null);

        if (!$phone) {
            return; // No phone number — gracefully skip
        }

        // Get the SMS message body from the notification
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (!$message) {
            return;
        }

        try {
            $this->twilio->sendSms($phone, $message);
        } catch (\Throwable $e) {
            Log::error('SmsChannel: Failed to send SMS.', [
                'to'    => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
