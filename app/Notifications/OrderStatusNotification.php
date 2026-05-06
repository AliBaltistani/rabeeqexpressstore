<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected string $newStatus,
        protected ?string $comment = null,
    ) {}

    /**
     * Determine which channels should be used, respecting admin email settings.
     */
    public function via(object $notifiable): array
    {
        // Check if the relevant notification toggle is enabled in settings
        if (!$this->isNotificationEnabled()) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Check the email notification settings to see if this status change should trigger a notification.
     */
    protected function isNotificationEnabled(): bool
    {
        $statusToSettingMap = [
            'processing' => 'email.notify_status_changed',
            'shipped'    => 'email.notify_order_shipped',
            'delivered'  => 'email.notify_order_delivered',
            'cancelled'  => 'email.notify_order_cancelled',
            'refunded'   => 'email.notify_order_refunded',
        ];

        $settingKey = $statusToSettingMap[$this->newStatus] ?? 'email.notify_status_changed';

        // Default to true if setting hasn't been saved yet
        return (bool) Setting::get($settingKey, true);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst($this->newStatus);
        $storeName = Setting::get('general.store_name_en', 'Eseven Store') ?? 'Eseven Store';
        $defaultCurrency = $this->order->currency_code ?? currency_symbol();

        $mail = (new MailMessage)
            ->subject("Order #{$this->order->order_number} — Status Updated to {$statusLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your order **#{$this->order->order_number}** has been updated.")
            ->line("**New Status:** {$statusLabel}");

        if ($this->comment) {
            $mail->line("**Note:** {$this->comment}");
        }

        $mail->line("**Order Total:** " . number_format($this->order->total, 2) . ' ' . $defaultCurrency);

        if ($this->newStatus === 'shipped' && $this->order->tracking) {
            if ($this->order->tracking->tracking_number) {
                $mail->line("**Tracking Number:** {$this->order->tracking->tracking_number}");
            }
            if ($this->order->tracking->carrier) {
                $mail->line("**Carrier:** {$this->order->tracking->carrier}");
            }
            if ($this->order->tracking->tracking_url) {
                $mail->action('Track Your Order', $this->order->tracking->tracking_url);
            }
        }

        $mail->line("Thank you for shopping with {$storeName}!");

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->newStatus,
            'comment' => $this->comment,
        ];
    }
}
