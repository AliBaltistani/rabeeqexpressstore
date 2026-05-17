<x-mail::message>
# New Order Placed

A new order **#{{ $order->order_number }}** was just placed.

### Total: {{ $order->currency_code }} {{ $order->total }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>