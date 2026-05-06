<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->with(['items'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return $this->paginated($orders, OrderResource);
    }

    /**
     * GET /api/v1/orders/{orderNumber}
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::withoutGlobalScopes()
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['items', 'shippingAddress', 'billingAddress', 'tracking', 'statusHistories'])
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        return $this->success(new OrderResource($order));
    }
}
