<x-mail::message>
# Order Refunded

Your order **#{{ $order->order_number }}** has been refunded. The amount will reflect in your checking account shortly.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>