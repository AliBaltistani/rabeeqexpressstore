<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Traits\ApiResponse;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/cart
     */
    public function index(Request $request): JsonResponse
    {
        $items = $this->getCartItems($request);

        return $this->success(new CartResource($this->buildCartData($items, $request)));
    }

    /**
     * POST /api/v1/cart/items
     */
    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productId' => ['required', 'exists:products,id'],
            'variantId' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        // Check if item already in cart
        $existing = CartItem::where('product_id', $validated['productId'])
            ->where('variant_id', $validated['variantId'] ?? null)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + $validated['quantity']]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_id' => $validated['productId'],
                'variant_id' => $validated['variantId'] ?? null,
                'quantity' => $validated['quantity'],
            ]);
        }

        $items = $this->getCartItems($request);
        return $this->success(new CartResource($this->buildCartData($items, $request)), 'Item added to cart.');
    }

    /**
     * PUT /api/v1/cart/items/{id}
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $item = $this->findCartItem($request, $id);
        if (!$item) {
            return $this->notFound('Cart item not found.');
        }

        $item->update(['quantity' => $validated['quantity']]);

        $items = $this->getCartItems($request);
        return $this->success(new CartResource($this->buildCartData($items, $request)));
    }

    /**
     * DELETE /api/v1/cart/items/{id}
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $item = $this->findCartItem($request, $id);
        if (!$item) {
            return $this->notFound('Cart item not found.');
        }

        $item->delete();

        $items = $this->getCartItems($request);
        return $this->success(new CartResource($this->buildCartData($items, $request)), 'Item removed from cart.');
    }

    /**
     * DELETE /api/v1/cart
     */
    public function clear(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        CartItem::when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->delete();

        return $this->success(null, 'Cart cleared.');
    }

    /**
     * POST /api/v1/cart/coupon
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (!$coupon) {
            return $this->error('Coupon not found.', 404);
        }

        if (!$coupon->isValid()) {
            return $this->error('This coupon is no longer valid.', 422);
        }

        $user = $request->user();
        if ($user && !$coupon->isValidForUser($user)) {
            return $this->error('You have already used this coupon the maximum number of times.', 422);
        }

        $items = $this->getCartItems($request);
        $subtotal = $items->sum(fn($item) => ($item->variant?->price ?? $item->product?->price ?? 0) * $item->quantity);

        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return $this->error("Minimum order amount is " . currency_symbol() . " " . number_format($coupon->min_order_amount, 2), 422);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        // Store coupon in session
        session(['cart_coupon' => $coupon->code, 'cart_discount' => $discount]);

        $cartData = $this->buildCartData($items, $request);
        $cartData->couponCode = $coupon->code;
        $cartData->discountAmount = $discount;

        return $this->success(new CartResource($cartData), 'Coupon applied successfully.');
    }

    /**
     * DELETE /api/v1/cart/coupon
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        session()->forget(['cart_coupon', 'cart_discount']);

        $items = $this->getCartItems($request);
        return $this->success(new CartResource($this->buildCartData($items, $request)), 'Coupon removed.');
    }

    // ── Helpers ──

    protected function getCartItems(Request $request)
    {
        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        return CartItem::with(['product' => fn($q) => $q->withoutGlobalScopes(), 'product.images', 'variant'])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->get();
    }

    protected function findCartItem(Request $request, int $id): ?CartItem
    {
        $userId = $request->user()?->id;
        $sessionId = $userId ? null : $request->session()->getId();

        return CartItem::where('id', $id)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->first();
    }

    protected function buildCartData($items, Request $request): object
    {
        $subtotal = $items->sum(fn($item) => ($item->variant?->price ?? $item->product?->price ?? 0) * $item->quantity);

        return (object) [
            'items' => $items,
            'subtotal' => $subtotal,
            'discountAmount' => session('cart_discount', 0),
            'couponCode' => session('cart_coupon'),
            'shippingAmount' => 0,
        ];
    }
}
