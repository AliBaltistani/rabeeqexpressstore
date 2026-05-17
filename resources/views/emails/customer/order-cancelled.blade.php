<x-mail::message>
# Order Cancelled

Your order **#{{ $order->order_number }}** has been cancelled. Please contact support if you need assistance.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>