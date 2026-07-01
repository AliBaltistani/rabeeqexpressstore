<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TamaraWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $gateway
    ) {}

    /**
     * POST /webhooks/tamara
     *
     * Tamara sends an HTTP POST with a JSON body. The notification token is
     * sent in the Authorization header as a Bearer token.
     *
     * Supported event types:
     *   - order_approved   → authorise → mark paid
     *   - order_declined   → cancel/fail order
     *   - order_expired    → cancel/fail order
     */
    public function handle(Request $request): Response
    {
        // ── 1. Signature Verification ────────────────────────────────────────
        $config            = $this->gateway->getTamaraConfig();
        $notificationToken = $config['notification_token'] ?? '';

        if (empty($notificationToken)) {
            Log::warning('[Tamara Webhook] Notification token not configured — rejecting request.');
            return response('Webhook not configured.', 400);
        }

        $authHeader = $request->header('Authorization', '');
        $bearer     = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';

        if (!hash_equals($notificationToken, $bearer)) {
            Log::warning('[Tamara Webhook] Invalid notification token.', [
                'ip' => $request->ip(),
            ]);
            return response('Unauthorized.', 401);
        }

        // ── 2. Parse Payload ─────────────────────────────────────────────────
        $payload = $request->all();
        $event   = $payload['event_type'] ?? $payload['type'] ?? null;

        // Tamara may send order_id directly or inside an order object
        $tamaraOrderId = $payload['order_id']
            ?? $payload['order']['order_id']
            ?? $payload['order']['id']
            ?? null;

        $orderRef = $payload['order_reference_id']
            ?? $payload['order']['order_reference_id']
            ?? $payload['merchant_order_reference_id']
            ?? null;

        Log::info('[Tamara Webhook] Received', [
            'event'           => $event,
            'tamara_order_id' => $tamaraOrderId,
            'order_ref'       => $orderRef,
        ]);

        // ── 3. Locate local order (idempotency key) ──────────────────────────
        $order = null;
        if ($tamaraOrderId) {
            $order = Order::where('gateway_order_id', $tamaraOrderId)->first();
        }
        if (!$order && $orderRef) {
            $order = Order::where('order_number', $orderRef)->first();
        }

        if (!$order) {
            Log::warning('[Tamara Webhook] Order not found', [
                'tamara_order_id' => $tamaraOrderId,
                'order_ref'       => $orderRef,
            ]);
            // Return 200 so Tamara stops retrying for unknown orders
            return response('Order not found.', 200);
        }

        // ── 4. Idempotency Guard ─────────────────────────────────────────────
        // Always persist the latest payload + gateway_status
        $order->update([
            'gateway_payload' => $payload,
            'gateway_status'  => $event,
        ]);
        \App\Models\Setting::set('payment.tamara_last_webhook_at', now()->toISOString());

        // ── 5. Route Event ───────────────────────────────────────────────────
        match ($event) {
            'order_approved', 'ORDER_APPROVED'
                => $this->handleApproved($order, $tamaraOrderId ?? $order->gateway_order_id, $payload),
            'order_declined', 'ORDER_DECLINED', 'order_expired', 'ORDER_EXPIRED'
                => $this->handleDeclined($order, $event),
            default => Log::info('[Tamara Webhook] Unhandled event', ['event' => $event]),
        };

        return response('OK', 200);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function handleApproved(Order $order, string $tamaraOrderId, array $payload): void
    {
        // Idempotency: skip if already paid
        if ($order->payment_status === 'paid') {
            Log::info('[Tamara Webhook] Order already paid — skipping.', ['order' => $order->order_number]);
            return;
        }

        // Authorise — the mandatory step that completes the transaction
        $auth = $this->gateway->authoriseTamaraOrder($tamaraOrderId);

        if ($auth['success']) {
            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
                'transaction_id' => $tamaraOrderId,
                'gateway_status' => 'AUTHORIZED',
            ]);
            Log::info('[Tamara Webhook] Order authorised and marked paid.', ['order' => $order->order_number]);
        } else {
            Log::error('[Tamara Webhook] Authorise call failed after approval.', [
                'order' => $order->order_number,
                'error' => $auth['error'],
            ]);
            // Leave as unpaid; can be retried via admin "Sync Status" action
        }
    }

    private function handleDeclined(Order $order, string $event): void
    {
        if (in_array($order->status, ['cancelled', 'failed'])) {
            return; // Already handled
        }

        $order->update([
            'status'         => 'cancelled',
            'payment_status' => 'failed',
            'gateway_status' => $event,
        ]);

        Log::info('[Tamara Webhook] Order declined/expired — cancelled.', ['order' => $order->order_number]);
    }
}
