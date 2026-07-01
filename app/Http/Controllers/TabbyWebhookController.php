<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TabbyWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $gateway
    ) {}

    /**
     * POST /webhooks/tabby
     *
     * Tabby sends webhook events with a custom verification header.
     * The header name and value are configured in Filament → Payment & Shipping.
     *
     * Supported payment statuses:
     *   - AUTHORIZED  → capture → mark paid
     *   - CLOSED      → already captured (idempotent)
     *   - REJECTED    → cancel order
     *   - EXPIRED     → cancel order
     */
    public function handle(Request $request): Response
    {
        // ── 1. Signature Verification ────────────────────────────────────────
        $config      = $this->gateway->getTabbyConfig();
        $headerName  = $config['webhook_header_name'] ?? '';
        $headerValue = $config['webhook_header_value'] ?? '';

        if (empty($headerName) || empty($headerValue)) {
            Log::warning('[Tabby Webhook] Webhook verification header not configured — rejecting request.');
            return response('Webhook not configured.', 400);
        }

        $receivedValue = $request->header($headerName, '');

        if (!hash_equals($headerValue, $receivedValue)) {
            Log::warning('[Tabby Webhook] Invalid verification header.', [
                'ip'     => $request->ip(),
                'header' => $headerName,
            ]);
            return response('Unauthorized.', 401);
        }

        // ── 2. Parse Payload ─────────────────────────────────────────────────
        $payload = $request->all();

        // Tabby webhook envelope: { id, status, order: { reference_id } }
        $paymentId  = $payload['id'] ?? null;
        $status     = $payload['status'] ?? null;
        $orderRef   = $payload['order']['reference_id']
            ?? $payload['payment']['order']['reference_id']
            ?? null;

        Log::info('[Tabby Webhook] Received', [
            'payment_id' => $paymentId,
            'status'     => $status,
            'order_ref'  => $orderRef,
        ]);

        // ── 3. Locate local order ─────────────────────────────────────────────
        $order = null;
        if ($paymentId) {
            $order = Order::where('gateway_order_id', $paymentId)->first();
        }
        if (!$order && $orderRef) {
            $order = Order::where('order_number', $orderRef)->first();
        }

        if (!$order) {
            Log::warning('[Tabby Webhook] Order not found', [
                'payment_id' => $paymentId,
                'order_ref'  => $orderRef,
            ]);
            return response('Order not found.', 200);
        }

        // ── 4. Persist latest payload ─────────────────────────────────────────
        $order->update([
            'gateway_payload' => $payload,
            'gateway_status'  => $status,
        ]);
        \App\Models\Setting::set('payment.tabby_last_webhook_at', now()->toISOString());

        // ── 5. Route on payment status ────────────────────────────────────────
        match (strtoupper($status ?? '')) {
            'AUTHORIZED'
                => $this->handleAuthorized($order, $paymentId ?? $order->gateway_order_id),
            'CLOSED'
                => $this->handleClosed($order),
            'REJECTED', 'EXPIRED', 'CANCELED'
                => $this->handleRejected($order, $status),
            default => Log::info('[Tabby Webhook] Unhandled status', ['status' => $status]),
        };

        return response('OK', 200);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function handleAuthorized(Order $order, string $paymentId): void
    {
        if ($order->payment_status === 'paid') {
            Log::info('[Tabby Webhook] Order already paid — skipping.', ['order' => $order->order_number]);
            return;
        }

        $capture = $this->gateway->captureTabbyPayment(
            $paymentId,
            (float) $order->total,
            strtoupper($order->currency_code ?? 'SAR')
        );

        if ($capture['success']) {
            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
                'transaction_id' => $paymentId,
                'gateway_status' => 'CLOSED',
            ]);
            Log::info('[Tabby Webhook] Payment captured and order marked paid.', ['order' => $order->order_number]);
        } else {
            Log::error('[Tabby Webhook] Capture failed after authorization.', [
                'order' => $order->order_number,
                'error' => $capture['error'],
            ]);
        }
    }

    private function handleClosed(Order $order): void
    {
        // CLOSED = already captured — idempotently mark paid
        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
                'gateway_status' => 'CLOSED',
            ]);
        }
    }

    private function handleRejected(Order $order, ?string $status): void
    {
        if (in_array($order->status, ['cancelled', 'failed'])) {
            return;
        }

        $order->update([
            'status'         => 'cancelled',
            'payment_status' => 'failed',
            'gateway_status' => $status,
        ]);

        Log::info('[Tabby Webhook] Payment rejected/expired — order cancelled.', ['order' => $order->order_number]);
    }
}
