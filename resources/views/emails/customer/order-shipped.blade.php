<x-mail::message>
# Order Shipped

Great news! Your order **#{{ $order->order_number }}** has been shipped.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>