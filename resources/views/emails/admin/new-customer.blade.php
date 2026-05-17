<x-mail::message>
# New Customer Registration

A new customer **{{ $user->name }}** ({{ $user->email }}) just registered on your marketplace!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>