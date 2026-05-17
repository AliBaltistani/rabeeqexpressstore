<x-mail::message>
# Order Status Updated

Your order **#{{ $order->order_number }}** status has been updated to: **{{ ucfirst($order->status) }}**.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>