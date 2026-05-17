<x-mail::message>
# Review Approved

Hi {{ $review->user->name ?? 'Customer' }},

Your review for product rating **{{ $review->rating }} Stars** has been approved and is now public!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>