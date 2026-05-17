<x-mail::message>
# Order Placed Successfully!

Thank you for placing order **#{{ $order->order_number }}**.

### Total: {{ $order->currency_code }} {{ $order->total }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>