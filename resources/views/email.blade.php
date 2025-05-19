@component('mail::message')
# Verify Your Email Address

Hi {{ $user->full_name }},

Please click the button below to verify your email address.

@component('mail::button', ['url' => $verificationUrl])
Verify Email Address
@endcomponent

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}

<small>This verification link will expire in 60 minutes.</small>
@endcomponent