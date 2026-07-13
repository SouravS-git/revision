<x-mail::message>
# Welcome

Thank you, {{ $user->name }} for registering with us.

<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
