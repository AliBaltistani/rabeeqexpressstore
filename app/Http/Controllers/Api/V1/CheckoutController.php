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
    public function paymentMethods(): JsonResponse
    {
        $gateways = $this->paymentGateway->getAvailableGateways();

        // Return all gateways — frontend handles showing disabled/coming-soon
        return $this->success([
            'gateways'             => $gateways,
            'stripePublishableKey' => $this->paymentGateway->getStripePublishableKey(),
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

        $user = $request->user('sanctum');

        /** @var \App\Models\Order|null $order */
        $order = \App\Models\Order::withoutGlobalScopes()
            ->where('order_number', $request->input('orderNumber'))
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        $result = $this->paymentGateway->confirmStripePayment(
            $order,
            $request->input('paymentIntentId')
        );

        if ($result['success']) {
            // Log status transition
            $this->orderLifecycle->transitionStatus($order, 'processing');

            $order->refresh();

            return $this->success([
                'orderNumber'   => $order->order_number,
                'status'        => $order->status,
                'paymentStatus' => $order->payment_status,
            ], 'Payment confirmed.');
        }

        return $this->error($result['error'] ?? 'Payment confirmation failed.', 422);
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
