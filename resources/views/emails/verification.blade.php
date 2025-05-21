@component('mail::message')
# Email Verification

Your one-time verification code is:

# {{ $code }}

This code will expire at **{{ $expires }}**.

Thanks,  
**CLICKNCART Team**
@endcomponent
