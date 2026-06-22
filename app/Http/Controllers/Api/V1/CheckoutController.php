<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlaceOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Traits\ApiResponse;
use App\Services\OrderLifecycleService;
use App\Services\PaymentGatewayService;
use App\Services\ShippingEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ShippingEngineService $shippingEngine,
        private readonly PaymentGatewayService $paymentGateway,
        private readonly OrderLifecycleService $orderLifecycle,
    ) {}

    /**
     * GET /api/v1/checkout/payment-methods
     * Returns all available payment gateways.
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        $gateways = $this->paymentGateway->getAvailableGateways();

        // Add wallet info if enabled
        $walletEnabled = (bool) setting('wallet.enabled', false);
        $user = $request->user('sanctum');
        $walletBalance = $user ? round((float) $user->wallet_balance, 2) : 0;

        return $this->success([
            'gateways'             => $gateways,
            'stripePublishableKey' => $this->paymentGateway->getStripePublishableKey(),
            'wallet' => [
                'enabled'  => $walletEnabled,
                'balance'  => $walletBalance,
                'currency' => currency_code(),
            ],
        ]);
    }

    /**
     * POST /api/v1/checkout/stripe/create-intent
     * Payment-first flow: create a Stripe PaymentIntent from cart totals BEFORE the order.
     * Frontend confirms the card, then calls place-order with the paymentIntentId.
     */
    public function createStripeIntent(Request $request): JsonResponse
    {
        $request->validate([
            'shippingMethodId' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'couponCode'       => ['nullable', 'string', 'max:50'],
            'currency'         => ['nullable', 'string', 'max:3'],
        ]);

        $user = $request->user('sanctum');

        // Compute the exact total the customer will be charged
        try {
            $totals = $this->orderLifecycle->computeTotals($request->all(), $user, $request);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $currency    = $request->input('currency') ?: currency_code();
        $amountCents = (int) round($totals['total'] * 100);

        if ($amountCents <= 0) {
            return $this->error('Order total must be greater than zero.', 422);
        }

        try {
            $result = $this->paymentGateway->createPaymentIntentForAmount($amountCents, $currency);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (!$result['success']) {
            return $this->error($result['error'] ?? 'Failed to initialise Stripe payment.', 422);
        }

        return $this->success([
            'clientSecret'    => $result['client_secret'],
            'paymentIntentId' => $result['payment_intent_id'],
            'amount'          => $amountCents,
            'currency'        => $currency,
        ]);
    }

    /**
     * POST /api/v1/checkout/shipping-methods
     * Dynamic shipping engine — resolves methods by country/city.
     */
    public function shippingMethods(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string'],
            'city'    => ['nullable', 'string'],
        ]);

        $methods = $this->shippingEngine->resolveForDestination(
            $request->input('country'),
            $request->input('city'),
        );

        return $this->success($methods->values()->all());
    }

    /**
     * POST /api/v1/checkout/shipping-rates
     * Backward-compatible endpoint — delegates to the unified ShippingMethod system.
     */
    public function shippingRates(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string'],
            'state'   => ['nullable', 'string'],
        ]);

        $methods = $this->shippingEngine->resolveForDestination(
            $request->input('country'),
            $request->input('city', $request->input('state')),
        );

        return $this->success($methods->values()->all());
    }

    /**
     * POST /api/v1/checkout/place-order
     * Atomic order placement via OrderLifecycleService.
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $validated = $request->validated();

        // Check guest checkout enabled
        if (!$user && !setting('general.enable_guest_checkout', true)) {
            return $this->error('Guest checkout is not enabled. Please log in.', 403);
        }

        try {
            $result = $this->orderLifecycle->placeOrder($validated, $user, $request);

            /** @var \App\Models\Order $order */
            $order = $result['order'];
            $payment = $result['payment'];

            $order->load(['items', 'shippingAddress', 'billingAddress', 'user']);

            // ── Email Notifications ──
            $this->sendOrderNotifications($order, $validated, $user);

            // ── Build response ──
            $response = [
                'orderId'        => $order->id,
                'orderNumber'    => $order->order_number,
                'status'         => $order->status,
                'paymentStatus'  => $order->payment_status,
                'total'          => (float) $order->total,
            ];

            if ($validated['paymentMethod'] === 'stripe') {
                $response['clientSecret']    = $payment['client_secret'] ?? null;
                $response['paymentIntentId'] = $payment['payment_intent_id'] ?? null;
                $response['requiresAction']  = $payment['requires_action'] ?? false;
            } elseif ($validated['paymentMethod'] === 'paypal') {
                $response['redirectUrl'] = url('/api/v1/checkout/paypal/' . $order->order_number);
            }

            return $this->success($response, 'Order placed successfully.', 201);

        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Failed to place order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/checkout/stripe/confirm
     * Confirm Stripe payment after frontend-side 3DS/card authentication.
     */
    public function confirmStripe(Request $request): JsonResponse
    {
        $request->validate([
            'orderNumber'     => ['required', 'string'],
            'paymentIntentId' => ['required', 'string'],
        ]);

        $user            = $request->user('sanctum');
        $requestedIntentId = $request->input('paymentIntentId');

        /** @var \App\Models\Order|null $order */
        $order = \App\Models\Order::withoutGlobalScopes()
            ->where('order_number', $request->input('orderNumber'))
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        // ── Security: verify the PaymentIntent ID matches what was attached to this order ──
        if ($order->payment_intent_id && $order->payment_intent_id !== $requestedIntentId) {
            \Illuminate\Support\Facades\Log::warning('Stripe confirm: PaymentIntent mismatch', [
                'order'            => $order->order_number,
                'stored_intent'    => $order->payment_intent_id,
                'requested_intent' => $requestedIntentId,
            ]);
            return $this->error('Payment intent mismatch. This request cannot be processed.', 422);
        }

        // ── Idempotency: already paid — just return current state ──
        if ($order->payment_status === 'paid') {
            return $this->success([
                'orderNumber'   => $order->order_number,
                'status'        => $order->status,
                'paymentStatus' => $order->payment_status,
            ], 'Order is already paid.');
        }

        $result = $this->paymentGateway->confirmStripePayment($order, $requestedIntentId);

        if ($result['success']) {
            // ── Clear cart now that payment is fully confirmed ──
            $this->orderLifecycle->clearCartForUser($user, $request);

            // Log status transition
            $this->orderLifecycle->transitionStatus($order, 'processing');

            $order->refresh();

            return $this->success([
                'orderNumber'   => $order->order_number,
                'status'        => $order->status,
                'paymentStatus' => $order->payment_status,
            ], 'Payment confirmed.');
        }

        // ── Failure: return actual Stripe status + message so frontend can display it ──
        $stripeStatus = $result['stripeStatus'] ?? null;
        $errorMessage = $result['error'] ?? 'Payment confirmation failed.';
        if ($stripeStatus) {
            $errorMessage .= " Stripe status: {$stripeStatus}.";
        }

        return $this->error($errorMessage, 422);
    }

    /**
     * GET /api/v1/checkout/order-success/{orderNumber}
     */
    public function orderSuccess(string $orderNumber): JsonResponse
    {
        $order = \App\Models\Order::withoutGlobalScopes()
            ->where('order_number', $orderNumber)
            ->with(['items', 'shippingAddress', 'billingAddress'])
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        return $this->success(new OrderResource($order));
    }

    /**
     * POST /api/v1/checkout/stripe/cancel
     * Called by the frontend when Stripe confirmCardPayment fails after an order was created.
     * Cancels the pending unpaid order and restores stock so the customer can retry.
     */
    public function cancelStripeOrder(Request $request): JsonResponse
    {
        $request->validate([
            'orderNumber' => ['required', 'string'],
        ]);

        $user  = $request->user('sanctum');
        $order = \App\Models\Order::withoutGlobalScopes()
            ->where('order_number', $request->input('orderNumber'))
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$order) {
            // Return 200 — idempotent: if already gone, treat as success
            return $this->success(null, 'Order not found or already removed.');
        }

        // Only cancel orders that are still pending & unpaid — never touch paid orders
        if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
            return $this->success(null, 'Order is already processed.');
        }

        if ($order->payment_method === 'stripe' && $order->payment_status === 'unpaid') {
            $this->orderLifecycle->cancelOrder($order);
            return $this->success(null, 'Pending order cancelled.');
        }

        return $this->success(null, 'No action taken.');
    }

    /**
     * Send order placement email notifications.
     */
    private function sendOrderNotifications(\App\Models\Order $order, array $validated, ?\App\Models\User $user): void
    {
        $recipient = $user?->email ?? ($validated['guestEmail'] ?? null);
        if ($recipient && setting('email.notify_order_placed', true)) {
            \Illuminate\Support\Facades\Mail::to($recipient)->queue(new \App\Mail\Customer\OrderPlacedMail($order));
        }

        $adminEmails = setting('email.admin_email');
        if ($adminEmails) {
            $admins = array_filter(array_map('trim', explode(',', $adminEmails)));
            if (!empty($admins)) {
                if (setting('email.admin_notify_new_order', true)) {
                    \Illuminate\Support\Facades\Mail::to($admins)->queue(new \App\Mail\Admin\NewOrderAdminMail($order));
                }
                if (setting('email.admin_notify_low_stock', true)) {
                    foreach ($order->items as $item) {
                        $product = $item->product;
                        if ($product && $product->track_stock && $product->stock_quantity <= setting('general.low_stock_threshold', 5)) {
                            \Illuminate\Support\Facades\Mail::to($admins)->queue(new \App\Mail\Admin\LowStockAdminMail($product));
                        }
                    }
                }
            }
        }
    }
}
