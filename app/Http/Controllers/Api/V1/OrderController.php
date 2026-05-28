<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/orders
     * Supports optional ?status= filter for the order history page.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->with(['items']);

        // Optional status filter
        if ($status = $request->input('status')) {
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
            if (in_array($status, $validStatuses, true)) {
                $query->where('status', $status);
            }
        }

        $orders = $query->orderByDesc('created_at')->paginate(10);

        return $this->paginated($orders, OrderResource::class);
    }

    /**
     * GET /api/v1/orders/{orderNumber}
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::withoutGlobalScopes()
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with([
                'items',
                'shippingAddress',
                'billingAddress',
                'tracking',
                'statusHistories' => fn($q) => $q->orderBy('created_at', 'asc'),
            ])
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        return $this->success(new OrderResource($order));
    }

    /**
     * GET /api/v1/orders/{orderNumber}/invoice
     */
    public function invoice(Request $request, string $orderNumber)
    {
        $order = Order::withoutGlobalScopes()
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['items', 'shippingAddress', 'billingAddress'])
            ->first();

        if (!$order) {
            return $this->notFound('Order not found.');
        }

        $pdf = Pdf::loadView('invoices.invoice', compact('order'));
        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }
}
