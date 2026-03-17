@component('mail::message')
# Verify Your New Email Address

Hi **{{ $adminName }}**,

You requested an email change on your CashFlow Admin account. Enter the code below to confirm your new address. The code expires in **10 minutes**.

@component('mail::panel')
<div style="text-align:center; font-size:32px; font-weight:800; letter-spacing:8px; padding:8px 0;">{{ $otp }}</div>
@endcomponent

If you did not request this change, your account is still secure — simply ignore this email and your current email address will remain unchanged.

Thanks,
**The CashFlow Team**
@endcomponent
