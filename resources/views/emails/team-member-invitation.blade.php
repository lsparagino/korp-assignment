<x-mail::message>
# Welcome to the Team, {{ $user->name }}!

You have been invited to join the team on {{ config('app.name') }}.

To set up your account and create a password, please click the button below:

<x-mail::button :url="route('invitation.show', ['token' => $user->invitation_token])">
Set Up Your Account
</x-mail::button>

If you were not expecting this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
