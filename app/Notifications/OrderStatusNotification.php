<?php

namespace App\Notifications;

use App\Models\Order;
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

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst($this->newStatus);

        $mail = (new MailMessage)
            ->subject("Order #{$this->order->order_number} — Status Updated to {$statusLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your order **#{$this->order->order_number}** has been updated.")
            ->line("**New Status:** {$statusLabel}");

        if ($this->comment) {
            $mail->line("**Note:** {$this->comment}");
        }

        $mail->line("**Order Total:** " . number_format($this->order->total, 2) . ' ' . $this->order->currency_code);

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

        $mail->line('Thank you for shopping with Eseven Store!');

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
