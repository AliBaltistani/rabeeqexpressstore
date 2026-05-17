<?php

namespace App\Observers;

use App\Models\Order;

use App\Mail\Customer\OrderStatusUpdatedMail;
use App\Mail\Customer\OrderShippedMail;
use App\Mail\Customer\OrderDeliveredMail;
use App\Mail\Customer\OrderCancelledMail;
use App\Mail\Customer\OrderRefundedMail;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('status')) {
            $status = $order->status;
            $email = $order->user?->email ?? $order->guest_email;

            if (!$email) return;

            if (setting('email.notify_status_changed', true)) {
                Mail::to($email)->queue(new OrderStatusUpdatedMail($order));
            }

            if ($status === 'shipped' && setting('email.notify_order_shipped', true)) {
                Mail::to($email)->queue(new OrderShippedMail($order));
            } elseif ($status === 'delivered' && setting('email.notify_order_delivered', true)) {
                Mail::to($email)->queue(new OrderDeliveredMail($order));
            } elseif ($status === 'cancelled' && setting('email.notify_order_cancelled', true)) {
                Mail::to($email)->queue(new OrderCancelledMail($order));
            } elseif ($status === 'refunded' && setting('email.notify_order_refunded', true)) {
                Mail::to($email)->queue(new OrderRefundedMail($order));
            }
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
