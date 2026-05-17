<x-mail::message>
# Welcome to {{ config('app.name') }}!

Hi {{ $user->name }},

Thank you for joining us! We're excited to have you on board.

<x-mail::button :url="url('/')">
Start Shopping
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>