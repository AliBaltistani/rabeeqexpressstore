<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $code = $this->currency_code ?? currency_code();
        $currencyObj = Currency::where('code', $code)->first();
        $symbol = $currencyObj?->symbol ?? $code;

        return [
            'id' => $this->id,
            'orderNumber' => $this->order_number,
            'status' => $this->status,
            'statusLabel' => $this->status_label,
            'paymentStatus' => $this->payment_status,
            'paymentMethod' => $this->payment_method,
            'subtotal' => [
                'raw' => (float) $this->subtotal,
                'formatted' => $symbol . ' ' . number_format($this->subtotal, 2),
            ],
            'discountAmount' => [
                'raw' => (float) $this->discount_amount,
                'formatted' => $symbol . ' ' . number_format($this->discount_amount, 2),
            ],
            'shippingAmount' => [
                'raw' => (float) $this->shipping_amount,
                'formatted' => $symbol . ' ' . number_format($this->shipping_amount, 2),
            ],
            'taxAmount' => [
                'raw' => (float) $this->tax_amount,
                'formatted' => $symbol . ' ' . number_format($this->tax_amount, 2),
            ],
            'total' => [
                'raw' => (float) $this->total,
                'formatted' => $symbol . ' ' . number_format($this->total, 2),
            ],
            'currency' => $code,
            'couponCode' => $this->coupon_code,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'productName' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unitPrice' => [
                    'raw' => (float) $item->unit_price,
                    'formatted' => $symbol . ' ' . number_format($item->unit_price, 2),
                ],
                'total' => [
                    'raw' => (float) $item->total,
                    'formatted' => $symbol . ' ' . number_format($item->total, 2),
                ],
                'variantName' => $item->variant_name,
                'productImage' => $item->product_image ? asset('storage/' . $item->product_image) : null,
            ])),
            'shippingAddress' => $this->whenLoaded('shippingAddress', fn() => $this->shippingAddress ? [
                'firstName' => $this->shippingAddress->first_name,
                'lastName' => $this->shippingAddress->last_name,
                'phone' => $this->shippingAddress->phone,
                'addressLine1' => $this->shippingAddress->address_line_1,
                'addressLine2' => $this->shippingAddress->address_line_2,
                'city' => $this->shippingAddress->city,
                'state' => $this->shippingAddress->state,
                'country' => $this->shippingAddress->country,
                'postalCode' => $this->shippingAddress->postal_code,
            ] : null),
            'billingAddress' => $this->whenLoaded('billingAddress', fn() => $this->billingAddress ? [
                'firstName' => $this->billingAddress->first_name,
                'lastName' => $this->billingAddress->last_name,
                'phone' => $this->billingAddress->phone,
                'addressLine1' => $this->billingAddress->address_line_1,
                'addressLine2' => $this->billingAddress->address_line_2,
                'city' => $this->billingAddress->city,
                'state' => $this->billingAddress->state,
                'country' => $this->billingAddress->country,
                'postalCode' => $this->billingAddress->postal_code,
            ] : null),
            'tracking' => $this->whenLoaded('tracking', fn() => $this->tracking ? [
                'carrier' => $this->tracking->carrier,
                'trackingNumber' => $this->tracking->tracking_number,
                'trackingUrl' => $this->tracking->tracking_url,
                'estimatedDelivery' => $this->tracking->estimated_delivery?->toISOString(),
            ] : null),
            'statusHistory' => $this->whenLoaded('statusHistories', fn() =>
                $this->statusHistories->map(fn($h) => [
                    'status' => $h->status,
                    'note' => $h->note,
                    'createdAt' => $h->created_at?->toISOString(),
                ])->sortByDesc('createdAt')->values()
            ),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
