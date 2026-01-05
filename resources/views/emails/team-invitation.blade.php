@component('mail::message')
You have been invited to join the {{ $invitation->team->name }} team!

@if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::registration()))
If you do not have an account, you may create one by clicking the button below. After creating an account, you may click the invitation acceptance button in this email to accept the team invitation:

@component('mail::button', ['url' => route('register')])
Create account
@endcomponent

If you already have an account, you may accept this invitation by clicking the button below:

@else
You may accept this invitation by clicking the button below:
@endif


@component('mail::button', ['url' => $acceptUrl])
Accept invitation
@endcomponent

If you did not expect to receive an invitation to this team, you may discard this email.
@endcomponent
