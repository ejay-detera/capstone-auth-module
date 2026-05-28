<x-mail::message>
# Welcome to {{ config('app.name') }}

Your account has been successfully created by an administrator.

Here are your temporary login credentials:
**Email:** {{ $email }}
**Temporary Password:** {{ $password }}

<x-mail::button :url="$url">
Login to your account
</x-mail::button>

For security reasons, you will be required to change your password upon your first login.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
