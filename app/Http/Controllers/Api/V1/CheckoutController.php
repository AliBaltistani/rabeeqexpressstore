<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Traits\ApiResponse;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ShippingRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/checkout/shipping-rates
     */
    public function shippingRates(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string'],
            'state' => ['nullable', 'string'],
        ]);

        $rates = ShippingRate::where('is_active', true)
            ->with('zone')
            ->get()
            ->map(fn($rate) => [
                'id' => $rate->id,
                'name' => $rate->getTranslation('name', app()->getLocale()),
                'method' => $rate->method,
                'price' => [
                    'raw' => (float) $rate->price,
                    'formatted' => currency_symbol() . ' ' . number_format($rate->price, 2),
                ],
                'freeAbove' => $rate->min_order_for_free ? (float) $rate->min_order_for_free : null,
            ]);

        return $this->success($rates);
    }

    /**
     * POST /api/v1/checkout/place-order
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shippingAddress' => ['required', 'array'],
            'shippingAddress.firstName' => ['required', 'string'],
            'shippingAddress.lastName' => ['required', 'string'],
            'shippingAddress.phone' => ['required', 'string'],
            'shippingAddress.addressLine1' => ['required', 'string'],
            'shippingAddress.city' => ['required', 'string'],
            'shippingAddress.country' => ['required', 'string'],
            'billingAddress' => ['nullable', 'array'],
            'paymentMethod' => ['required', 'string', 'in:cod,bank_transfer,stripe,paypal'],
            'shippingRateId' => ['nullable', 'exists:shipping_rates,id'],
            'couponCode' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string'],
            'guestEmail' => ['required_without:user', 'nullable', 'email'],
            'guestName' => ['nullable', 'string'],
            'guestPhone' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        // Check guest checkout enabled
        if (!$user && !setting('general.enable_guest_checkout', true)) {
            return $this->error('Guest checkout is not enabled. Please log in.', 403);
        }

        // Get cart items
        $cartItems = $this->getCartItems($request);
        if ($cartItems->isEmpty()) {
            return $this->error('Your cart is empty.', 422);
        }

        $currencyCode = $validated['currency'] ?? currency_code();

        try {
            $order = DB::transaction(function () use ($validated, $user, $cartItems, $currencyCode, $request) {
                // Calculate totals
                $subtotal = 0;
                $orderItems = [];

                foreach ($cartItems as $cartItem) {
                    $product = $cartItem->product;
                    if (!$product) continue;

                    $price = (float) ($cartItem->variant?->price ?? $product->price);
                    $qty = $cartItem->quantity;
                    $lineTotal = $price * $qty;
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'variant_id' => $cartItem->variant_id,
                        'product_name' => $product->getTranslation('name', 'en'),
                        'sku' => $cartItem->variant?->sku ?? $product->sku,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total' => $lineTotal,
                        'variant_name' => $cartItem->variant?->name,
                    ];

                    // Reduce stock
                    if ($product->track_stock) {
                        $product->decrement('stock_quantity', $qty);
                    }
                }

                // Coupon discount
                $discount = 0;
                $couponId = null;
                $couponCode = null;
                if (!empty($validated['couponCode'])) {
                    $coupon = Coupon::where('code', strtoupper($validated['couponCode']))->first();
                    if ($coupon && $coupon->isValid()) {
                        $discount = $coupon->calculateDiscount($subtotal);
                        $couponId = $coupon->id;
                        $couponCode = $coupon->code;
                        $coupon->increment('usage_count');
                        if ($user) {
                            CouponUsage::create([
                                'coupon_id' => $coupon->id,
                                'user_id' => $user->id,
                                'order_id' => 0, // Updated below
                                'discount_amount' => $discount,
                            ]);
                        }
                    }
                }

                // Shipping
                $shippingAmount = 0;
                if (!empty($validated['shippingRateId'])) {
                    $rate = ShippingRate::find($validated['shippingRateId']);
                    if ($rate) {
                        $shippingAmount = (float) $rate->price;
                        if ($rate->min_order_for_free && $subtotal >= $rate->min_order_for_free) {
                            $shippingAmount = 0;
                        }
                    }
                }

                // COD fee
                $codFee = 0;
                if ($validated['paymentMethod'] === 'cod') {
                    $codFee = (float) setting('payment.cod_fee', 0);
                }

                $total = $subtotal - $discount + $shippingAmount + $codFee;

                // Create order
                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                    'user_id' => $user?->id,
                    'guest_email' => $validated['guestEmail'] ?? null,
                    'guest_name' => $validated['guestName'] ?? null,
                    'guest_phone' => $validated['guestPhone'] ?? null,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $validated['paymentMethod'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'shipping_amount' => $shippingAmount,
                    'tax_amount' => 0,
                    'total' => $total,
                    'currency_code' => $currencyCode,
                    'currency_rate' => 1,
                    'coupon_id' => $couponId,
                    'coupon_code' => $couponCode,
                    'notes' => $validated['notes'] ?? null,
                    'ip_address' => $request->ip(),
                ]);

                // Create order items
                foreach ($orderItems as $item) {
                    $order->items()->create($item);
                }

                // Create addresses
                $shippingAddr = $validated['shippingAddress'];
                OrderAddress::create([
                    'order_id' => $order->id,
                    'type' => 'shipping',
                    'first_name' => $shippingAddr['firstName'],
                    'last_name' => $shippingAddr['lastName'],
                    'phone' => $shippingAddr['phone'],
                    'address_line_1' => $shippingAddr['addressLine1'],
                    'address_line_2' => $shippingAddr['addressLine2'] ?? null,
                    'city' => $shippingAddr['city'],
                    'state' => $shippingAddr['state'] ?? null,
                    'country' => $shippingAddr['country'],
                    'postal_code' => $shippingAddr['postalCode'] ?? null,
                ]);

                $billingAddr = $validated['billingAddress'] ?? $validated['shippingAddress'];
                OrderAddress::create([
                    'order_id' => $order->id,
                    'type' => 'billing',
                    'first_name' => $billingAddr['firstName'],
                    'last_name' => $billingAddr['lastName'],
                    'phone' => $billingAddr['phone'],
                    'address_line_1' => $billingAddr['addressLine1'],
                    'address_line_2' => $billingAddr['addressLine2'] ?? null,
                    'city' => $billingAddr['city'],
                    'state' => $billingAddr['state'] ?? null,
                    'country' => $billingAddr['country'],
                    'postal_code' => $billingAddr['postalCode'] ?? null,
                ]);

                // Status history
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'note' => 'Order placed.',
                ]);

                // Clear cart
                $userId = $user?->id;
                $sessionId = $userId ? null : $request->session()->getId();
                CartItem::when($userId, fn($q) => $q->where('user_id', $userId))
                    ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                    ->delete();
                session()->forget(['cart_coupon', 'cart_discount']);

                return $order;
            });

            $order->load(['items', 'shippingAddress', 'billingAddress']);

            // Payment method responses
            $response = [
                'orderId' => $order->id,
                'orderNumber' => $order->order_number,
                'status' => $order->status,
                'total' => (float) $order->total,
            ];

            if ($validated['paymentMethod'] === 'stripe') {
                $response['requiresAction'] = true;
                $response['clientSecret'] = 'stripe_client_secret_placeholder';
            } elseif ($validated['paymentMethod'] === 'paypal') {
                $response['redirectUrl'] = 'paypal_redirect_placeholder';
            }

            return $this->success($response, 'Order placed successfully.', 201);

        } catch (\Throwable $e) {
            return $this->error('Failed to place order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/checkout/order-success/{orderNumber}
     */
    public function orderSuccess(string $orderNumber): JsonResponse
    {
        $order = Order::withoutGlobalScopes()
            ->where('order_number', $orderNumber)
            ->with(['items', 'shippingAddress', 'billingAddress'])
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        return $this->success(new OrderResource($order));
    }

    protected function getCartItems(Request $request)
    {
        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        return CartItem::with(['product' => fn($q) => $q->withoutGlobalScopes(), 'variant'])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->get();
    }
}
