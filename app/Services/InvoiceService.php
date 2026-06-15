<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Generate and download a PDF invoice for the given order.
     */
    public function download(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'billingAddress', 'shippingAddress', 'user', 'coupon']);

        $pdf = Pdf::loadView('invoices.invoice', $this->getInvoiceData($order));

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

        $pdf = Pdf::loadView('invoices.invoice', $this->getInvoiceData($order));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Build the data array passed to the invoice view, injecting admin settings
     * like store name, logo, address, phone, and email.
     */
    protected function getInvoiceData(Order $order): array
    {
        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';

        return [
            'order' => $order,
            'store_name'    => $isArabic
                ? Setting::get('general.store_name_ar', Setting::get('general.store_name_en', 'Rabeq Express Store'))
                : Setting::get('general.store_name_en', 'Rabeq Express Store'),
            'store_logo'    => Setting::get('general.store_logo'),
            'store_email'   => Setting::get('general.store_email'),
            'store_phone'   => Setting::get('general.store_phone'),
            'store_address' => $isArabic
                ? Setting::get('general.store_address_ar', Setting::get('general.store_address_en'))
                : Setting::get('general.store_address_en'),
            'currency_symbol' => currency_symbol(),
            'date_format'     => admin_date_format(),
        ];
    }
}
