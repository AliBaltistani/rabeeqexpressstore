<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderStatusHistory;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrderLifecycleService
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGateway,
    ) {}

    /**
     * Atomically place a new order inside a DB transaction.
     *
     * @param  array<string, mixed>  $data  Validated checkout data
     * @return array{order: Order, payment: array}
     *
     * @throws \Throwable
     */
    public function placeOrder(array $data, ?User $user, Request $request): array
    {
        return DB::transaction(function () use ($data, $user, $request) {
            $cartItems = $this->getCartItems($user, $request);

            if ($cartItems->isEmpty()) {
                throw new \RuntimeException('Your cart is empty.');
            }

            $currencyCode = $data['currency'] ?? currency_code();

            // ── Calculate line totals ──
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                if (!$product) {
                    continue;
                }

                $price = (float) ($cartItem->variant?->price ?? $product->price);
                $qty   = $cartItem->quantity;
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;

                // Check stock availability before committing
                if ($product->track_stock && $product->stock_quantity < $qty) {
                    throw new \RuntimeException(
                        "Insufficient stock for \"{$product->getTranslation('name', 'en')}\". Available: {$product->stock_quantity}"
                    );
                }

                $orderItemsData[] = [
                    'product_id'               => $product->id,
                    'variant_id'               => $cartItem->variant_id,
                    'selected_attribute_values' => $cartItem->selected_attribute_values,
                    'product_name'             => $cartItem->variant?->name
                        ? $product->getTranslation('name', 'en') . ' – ' . $cartItem->variant->name
                        : $product->getTranslation('name', 'en'),
                    'product_sku'              => $cartItem->variant?->sku ?? $product->sku,
                    'product_image'            => $product->images->where('is_primary', true)->first()?->image_path ?? $product->images->first()?->image_path ?? null,
                    'quantity'                 => $qty,
                    'unit_price'               => $price,
                    'total'                    => $lineTotal,
                ];

                // Decrement stock atomically
                if ($product->track_stock) {
                    $product->decrement('stock_quantity', $qty);
                }
            }

            // ── Coupon discount ──
            $discount   = 0;
            $couponId   = null;
            $couponCode = null;

            if (!empty($data['couponCode'])) {
                $coupon = Coupon::where('code', strtoupper($data['couponCode']))->first();
                if ($coupon && $coupon->isValid()) {
                    $discount   = $coupon->calculateDiscount($subtotal);
                    $couponId   = $coupon->id;
                    $couponCode = $coupon->code;
                    $coupon->increment('usage_count');
                }
            }

            // ── Shipping cost ──
            $shippingAmount = 0;
            $shippingMethodName = null;

            if (!empty($data['shippingMethodId'])) {
                $method = ShippingMethod::find($data['shippingMethodId']);
                if ($method) {
                    $shippingAmount = $method->getEffectiveCost($subtotal);
                    $shippingMethodName = $method->getTranslation('name', 'en') . ' (' . ucfirst($method->carrier_type) . ')';
                }
            }

            // ── COD fee ──
            $codFee = 0;
            if (($data['paymentMethod'] ?? '') === 'cod') {
                $codFee = (float) setting('payment.cod_extra_fee', 0);
            }

            $total = $subtotal - $discount + $shippingAmount + $codFee;

            // ── Wallet payment ──
            $paymentStatus = 'unpaid';
            if (($data['paymentMethod'] ?? '') === 'wallet') {
                if (!$user) {
                    throw new \RuntimeException('Wallet payment requires a logged-in account.');
                }
                if ((float) $user->wallet_balance < $total) {
                    throw new \RuntimeException('Insufficient wallet balance. You have ' . number_format((float) $user->wallet_balance, 2) . ' but need ' . number_format($total, 2) . '.');
                }
                $user->debitWallet(
                    $total,
                    ['en' => 'Payment for order', 'ar' => 'دفع للطلب'],
                    Order::class,
                    null, // will be set after order creation
                );
                $paymentStatus = 'paid';
            }

            // ── Create order ──
            $order = Order::create([
                'order_number'      => 'ORD-' . strtoupper(Str::random(8)),
                'user_id'           => $user?->id,
                'guest_email'       => $data['guestEmail'] ?? null,
                'guest_name'        => $data['guestName'] ?? null,
                'guest_phone'       => $data['guestPhone'] ?? null,
                'status'            => 'pending',
                'payment_status'    => $paymentStatus,
                'payment_method'    => $data['paymentMethod'],
                'payment_gateway'   => $data['paymentMethod'] === 'stripe' ? 'stripe' : null,
                'shipping_rate_id'  => null,
                'shipping_method'   => $shippingMethodName,
                'shipping_status'   => 'pending',
                'subtotal'          => $subtotal,
                'discount_amount'   => $discount,
                'shipping_amount'   => $shippingAmount,
                'tax_amount'        => 0,
                'total'             => $total,
                'currency_code'     => $currencyCode,
                'currency_rate'     => 1,
                'coupon_id'         => $couponId,
                'coupon_code'       => $couponCode,
                'notes'             => $data['notes'] ?? null,
                'ip_address'        => $request->ip(),
            ]);

            // ── Create order items ──
            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }

            // ── Create addresses ──
            $shippingAddr = $data['shippingAddress'];
            OrderAddress::create([
                'order_id'       => $order->id,
                'type'           => 'shipping',
                'first_name'     => $shippingAddr['firstName'],
                'last_name'      => $shippingAddr['lastName'],
                'phone'          => $shippingAddr['phone'],
                'address_line_1' => $shippingAddr['addressLine1'],
                'address_line_2' => $shippingAddr['addressLine2'] ?? null,
                'city'           => $shippingAddr['city'],
                'state'          => $shippingAddr['state'] ?? '',
                'country'        => $shippingAddr['country'],
                'postal_code'    => $shippingAddr['postalCode'] ?? '',
            ]);

            $billingAddr = $data['billingAddress'] ?? $data['shippingAddress'];
            OrderAddress::create([
                'order_id'       => $order->id,
                'type'           => 'billing',
                'first_name'     => $billingAddr['firstName'],
                'last_name'      => $billingAddr['lastName'],
                'phone'          => $billingAddr['phone'],
                'address_line_1' => $billingAddr['addressLine1'],
                'address_line_2' => $billingAddr['addressLine2'] ?? null,
                'city'           => $billingAddr['city'],
                'state'          => $billingAddr['state'] ?? '',
                'country'        => $billingAddr['country'],
                'postal_code'    => $billingAddr['postalCode'] ?? '',
            ]);

            // ── Initial status history ──
            OrderStatusHistory::create([
                'order_id'    => $order->id,
                'status'      => 'pending',
                'status_from' => null,
                'status_to'   => 'pending',
                'comment'     => 'Order placed.',
            ]);

            // ── Coupon usage ──
            if ($couponId && $user) {
                CouponUsage::create([
                    'coupon_id' => $couponId,
                    'user_id'   => $user->id,
                    'order_id'  => $order->id,
                ]);
            }

            // ── Clear cart ──
            $userId    = $user?->id;
            $sessionId = $userId ? null : $request->session()->getId();

            CartItem::when($userId, fn($q) => $q->where('user_id', $userId))
                ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                ->delete();

            session()->forget(['cart_coupon', 'cart_discount']);

            // ── Handle payment gateway ──
            $paymentResult = [];

            if ($data['paymentMethod'] === 'stripe') {
                $paymentResult = $this->paymentGateway->createStripeIntent($order);
            } elseif ($data['paymentMethod'] === 'cod') {
                // COD: order stays pending/unpaid until delivery
                $paymentResult = ['success' => true, 'error' => null];
            } else {
                $paymentResult = ['success' => true, 'error' => null];
            }

            return [
                'order'   => $order,
                'payment' => $paymentResult,
            ];
        });
    }

    /**
     * Transition an order's status with full audit logging.
     */
    public function transitionStatus(Order $order, string $newStatus, ?int $adminId = null, ?string $comment = null): void
    {
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $order->update(['status' => $newStatus]);

        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'status'      => $newStatus,
            'status_from' => $oldStatus,
            'status_to'   => $newStatus,
            'comment'     => $comment ?? "Status changed from {$oldStatus} to {$newStatus}.",
            'changed_by'  => $adminId,
        ]);

        // ── Auto-award loyalty points on delivery ──
        if ($newStatus === 'delivered' && $order->user_id) {
            $this->awardLoyaltyPoints($order);
        }
    }

    /**
     * Award loyalty points to a user when their order is delivered.
     */
    private function awardLoyaltyPoints(Order $order): void
    {
        if (!setting('loyalty.enabled', false)) {
            return;
        }

        $user = $order->user;
        if (!$user) {
            return;
        }

        $earnRate = (float) setting('loyalty.earn_rate', 1);
        $points = (int) floor((float) $order->total * $earnRate);

        if ($points <= 0) {
            return;
        }

        $user->addLoyaltyPoints(
            $points,
            'earned',
            [
                'en' => "Earned from order #{$order->order_number}",
                'ar' => "مكتسبة من الطلب #{$order->order_number}",
            ],
            Order::class,
            $order->id
        );
    }

    /**
     * Cancel an order and restore stock.
     */
    public function cancelOrder(Order $order, ?int $adminId = null): void
    {
        if ($order->status === 'cancelled') {
            return;
        }

        // Restore stock
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product && $product->track_stock) {
                $product->increment('stock_quantity', $item->quantity);
            }
        }

        $this->transitionStatus($order, 'cancelled', $adminId, 'Order cancelled. Stock restored.');
    }

    /**
     * Get cart items for a user or session.
     */
    private function getCartItems(?User $user, Request $request): Collection
    {
        $userId    = $user?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        return CartItem::with(['product' => fn($q) => $q->withoutGlobalScopes(), 'variant'])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->get();
    }
}
