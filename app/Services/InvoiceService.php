<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Generate and download a PDF invoice for the given order.
     */
    public function download(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'billingAddress', 'shippingAddress', 'user', 'coupon']);

        $pdf = Pdf::loadView('invoices.invoice', [
            'order' => $order,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "invoice-{$order->order_number}.pdf"
        );
    }

    /**
     * Generate PDF content without downloading (for email attachment).
     */
    public function generate(Order $order): string
    {
        $order->load(['items.product', 'items.variant', 'billingAddress', 'shippingAddress', 'user', 'coupon']);

        $pdf = Pdf::loadView('invoices.invoice', [
            'order' => $order,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}
