<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly OrderLifecycleService $orderLifecycle,
    ) {}

    /**
     * POST /stripe/webhook
     *
     * Receives Stripe events. Reads the webhook signing secret dynamically
     * from admin settings (payment.stripe_webhook_secret), with a fallback
     * to the STRIPE_WEBHOOK_SECRET env variable.
     */
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        // ── Resolve signing secret (admin setting takes priority) ──
        $secret = $this->resolveWebhookSecret();

        if (empty($secret)) {
            Log::error('[Stripe Webhook] Webhook secret is not configured. Set it in Admin → Payment Gateways → Stripe → Webhook Secret.');
            return response('Webhook secret not configured.', 500);
        }

        // ── Verify signature (prevents forged events) ──
        try {
            \Stripe\Stripe::setApiKey($this->resolveStripeSecret());
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('[Stripe Webhook] Invalid signature.', ['error' => $e->getMessage()]);
            return response('Invalid signature.', 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('[Stripe Webhook] Invalid payload.', ['error' => $e->getMessage()]);
            return response('Invalid payload.', 400);
        }

        // ── Route event to the correct handler ──
        match ($event->type) {
            'payment_intent.succeeded'       => $this->handlePaymentIntentSucceeded($event->data->object),
            'payment_intent.payment_failed'  => $this->handlePaymentIntentFailed($event->data->object),
            'charge.refunded'                => $this->handleChargeRefunded($event->data->object),
            default                          => null, // Ignore unhandled events
        };

        return response('OK', 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Event Handlers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * payment_intent.succeeded
     * Marks the matching order as paid if not already done by the frontend flow.
     * This acts as a reliable fallback (e.g. browser closed before redirect).
     */
    private function handlePaymentIntentSucceeded(\Stripe\PaymentIntent $intent): void
    {
        $order = $this->findOrderByIntent($intent->id);

        if (!$order) {
            // Intent may belong to a pre-order flow — order not yet created; silently ignore
            Log::info('[Stripe Webhook] payment_intent.succeeded: no matching order found.', ['pi' => $intent->id]);
            return;
        }

        if ($order->payment_status === 'paid') {
            // Already paid (frontend flow completed first) — idempotent, nothing to do
            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'status'         => 'processing',
            'transaction_id' => $intent->id,
        ]);

        $this->orderLifecycle->transitionStatus($order, 'processing');

        Log::info('[Stripe Webhook] Order marked as paid via webhook.', [
            'order'  => $order->order_number,
            'intent' => $intent->id,
        ]);
    }

    /**
     * payment_intent.payment_failed
     * Cancels the pending order so stock is released.
     */
    private function handlePaymentIntentFailed(\Stripe\PaymentIntent $intent): void
    {
        $order = $this->findOrderByIntent($intent->id);

        if (!$order || $order->payment_status === 'paid') {
            return;
        }

        // Only cancel still-pending orders
        if (in_array($order->status, ['pending', 'processing']) && $order->payment_status === 'unpaid') {
            $this->orderLifecycle->cancelOrder($order);

            Log::info('[Stripe Webhook] Pending order cancelled after payment failure.', [
                'order'  => $order->order_number,
                'intent' => $intent->id,
                'reason' => $intent->last_payment_error?->message ?? 'unknown',
            ]);
        }
    }

    /**
     * charge.refunded
     * Updates the order payment status to refunded.
     */
    private function handleChargeRefunded(\Stripe\Charge $charge): void
    {
        $intentId = $charge->payment_intent;
        if (!$intentId) return;

        $order = $this->findOrderByIntent((string) $intentId);
        if (!$order) return;

        $order->update(['payment_status' => 'refunded']);

        Log::info('[Stripe Webhook] Order marked as refunded.', [
            'order'  => $order->order_number,
            'charge' => $charge->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function findOrderByIntent(string $intentId): ?Order
    {
        return Order::withoutGlobalScopes()
            ->where('payment_intent_id', $intentId)
            ->first();
    }

    /**
     * Read webhook secret from admin settings (encrypted), fallback to env.
     */
    private function resolveWebhookSecret(): string
    {
        $stored = \App\Models\Setting::get('payment.stripe_webhook_secret');
        if ($stored) {
            try {
                return decrypt($stored);
            } catch (\Throwable) {
                return $stored; // Already plain-text (legacy)
            }
        }
        return (string) config('services.stripe.webhook_secret', '');
    }

    /**
     * Read Stripe API secret from admin settings, fallback to env.
     */
    private function resolveStripeSecret(): string
    {
        $stored = \App\Models\Setting::get('payment.stripe_secret_key');
        if ($stored) {
            try {
                return decrypt($stored);
            } catch (\Throwable) {
                return $stored;
            }
        }
        return (string) config('services.stripe.secret', '');
    }
}
