<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    public function __construct(
        protected Order $order,
        protected string $newStatus,
        protected ?string $comment = null,
    ) {}

    /**
     * Always send via mail — the admin already opted in by checking "Notify Customer".
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst($this->newStatus);
        $storeName = Setting::get('general.store_name_en', 'Eseven Store') ?? 'Eseven Store';
        $defaultCurrency = $this->order->currency_code ?? currency_symbol();

        // Determine customer name
        $customerName = 'Valued Customer';
        if (method_exists($notifiable, 'getKey')) {
            // It's a User model
            $customerName = $notifiable->name ?? 'Valued Customer';
        } elseif ($this->order->guest_name) {
            $customerName = $this->order->guest_name;
        }

        $mail = (new MailMessage)
            ->subject("Order #{$this->order->order_number} — {$statusLabel}")
            ->greeting("Hello {$customerName},")
            ->line("Your order **#{$this->order->order_number}** has been updated.")
            ->line("**New Status:** {$statusLabel}");

        if ($this->comment) {
            $mail->line("**Note:** {$this->comment}");
        }

        // Order total
        $mail->line("**Order Total:** " . number_format($this->order->total, 2) . ' ' . $defaultCurrency);

        // Tracking info for shipped orders
        if ($this->newStatus === 'shipped') {
            $this->order->load('tracking');
            $tracking = $this->order->tracking;

            if ($tracking) {
                if ($tracking->carrier) {
                    $mail->line("**Carrier:** {$tracking->carrier}");
                }
                if ($tracking->tracking_number) {
                    $mail->line("**Tracking Number:** {$tracking->tracking_number}");
                }
                if ($tracking->estimated_delivery) {
                    $mail->line("**Estimated Delivery:** " . $tracking->estimated_delivery->format('M d, Y'));
                }
                if ($tracking->tracking_url) {
                    $mail->action('Track Your Order', $tracking->tracking_url);
                }
            }
        }

        // Order items summary
        $this->order->load('items');
        if ($this->order->items->isNotEmpty()) {
            $itemLines = $this->order->items->map(function ($item) {
                return "• {$item->product_name} × {$item->quantity} — " . number_format($item->total, 2) . " {$this->order->currency_code}";
            })->implode("\n");

            $mail->line("**Order Items:**")
                 ->line($itemLines);
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
