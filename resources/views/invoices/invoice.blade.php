<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 3px solid #1a2234;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-left {
            float: left;
        }
        .header-right {
            float: right;
            text-align: right;
        }
        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: #1a2234;
        }
        .brand-tagline {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #3b82f6;
        }
        .invoice-number {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-grid {
            width: 100%;
        }
        .info-grid td {
            vertical-align: top;
            width: 33.33%;
            padding-right: 20px;
        }
        .info-label {
            font-size: 10px;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 12px;
            color: #333;
            margin-top: 4px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1a2234;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead th {
            background: #1a2234;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table thead th:last-child,
        .items-table thead th:nth-child(3),
        .items-table thead th:nth-child(4) {
            text-align: right;
        }
        .items-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .items-table tbody td:last-child,
        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4) {
            text-align: right;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .totals-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .totals-section td.label {
            text-align: right;
            padding: 6px 12px;
            font-size: 11px;
            color: #666;
            width: 80%;
        }
        .totals-section td.value {
            text-align: right;
            padding: 6px 12px;
            font-size: 11px;
            width: 20%;
        }
        .totals-section tr.grand-total td {
            font-weight: bold;
            font-size: 14px;
            color: #1a2234;
            border-top: 2px solid #1a2234;
            padding-top: 10px;
        }
        .payment-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #059669;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .address-box {
            background: #f9fafb;
            padding: 12px;
            border-radius: 6px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- Header --}}
        <div class="header clearfix">
            <div class="header-left">
                <div class="brand-name">RABEQ EXPRESS STORE</div>
                <div class="brand-tagline">Premium E-Commerce</div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $order->order_number }}</div>
                <div style="font-size: 11px; color: #666; margin-top: 4px;">
                    Date: {{ $order->created_at->format('M d, Y') }}
                </div>
            </div>
        </div>

        {{-- Order & Customer Info --}}
        <div class="info-section">
            <table class="info-grid">
                <tr>
                    <td>
                        <div class="section-title">Bill To</div>
                        <div class="address-box">
                            @if($order->billingAddress)
                                <strong>{{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}</strong><br>
                                {{ $order->billingAddress->address_line_1 }}<br>
                                @if($order->billingAddress->address_line_2)
                                    {{ $order->billingAddress->address_line_2 }}<br>
                                @endif
                                {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }}<br>
                                {{ $order->billingAddress->country }} {{ $order->billingAddress->postal_code }}<br>
                                📞 {{ $order->billingAddress->phone }}
                            @else
                                <span style="color: #999;">No billing address</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="section-title">Ship To</div>
                        <div class="address-box">
                            @if($order->shippingAddress)
                                <strong>{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</strong><br>
                                {{ $order->shippingAddress->address_line_1 }}<br>
                                @if($order->shippingAddress->address_line_2)
                                    {{ $order->shippingAddress->address_line_2 }}<br>
                                @endif
                                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }}<br>
                                {{ $order->shippingAddress->country }} {{ $order->shippingAddress->postal_code }}<br>
                                📞 {{ $order->shippingAddress->phone }}
                            @else
                                <span style="color: #999;">Same as billing</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="section-title">Order Details</div>
                        <div class="address-box">
                            <strong>Order #:</strong> {{ $order->order_number }}<br>
                            <strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}<br>
                            <strong>Payment:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}<br>
                            <strong>Currency:</strong> {{ $order->currency_code }}<br>
                            @if($order->coupon_code)
                                <strong>Coupon:</strong> {{ $order->coupon_code }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product_sku }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-section">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format($order->subtotal, 2) }} {{ $order->currency_code }}</td>
            </tr>
            @if($order->discount_amount > 0)
            <tr>
                <td class="label">Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</td>
                <td class="value" style="color: #dc2626;">-{{ number_format($order->discount_amount, 2) }} {{ $order->currency_code }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Shipping</td>
                <td class="value">{{ number_format($order->shipping_amount, 2) }} {{ $order->currency_code }}</td>
            </tr>
            @if($order->tax_amount > 0)
            <tr>
                <td class="label">Tax</td>
                <td class="value">{{ number_format($order->tax_amount, 2) }} {{ $order->currency_code }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Grand Total</td>
                <td class="value">{{ number_format($order->total, 2) }} {{ $order->currency_code }}</td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="footer">
            <p><strong>Rabeq Express Store</strong></p>
            <p>Thank you for your business!</p>
            <p style="margin-top: 10px;">© {{ date('Y') }} Rabeq Express Store. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
