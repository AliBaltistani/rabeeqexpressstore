<x-mail::message>
# Order Delivered

Your order **#{{ $order->order_number }}** has been successfully delivered! We hope you enjoy your purchase.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>