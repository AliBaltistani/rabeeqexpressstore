<x-mail::message>
# New Product Review

A new review with **{{ $review->rating }} Stars** was submitted and is waiting for your attention.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>