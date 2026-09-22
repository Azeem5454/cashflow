@component('emails.partials.layout', [
    'emailTitle' => 'Verify Your Email — ' . config('app.name'),
    'badge' => 'Verify',
    'preheader' => 'Please verify your email address to get started with ' . config('app.name'),
    'footerText' => 'This email was sent to <strong style="color:#334155;">' . e($email) . '</strong> because an account was created on ' . e(config('app.name')) . '. If you did not create an account, no further action is required.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Verify your email address</div>
        <div style="font-size:15px;color:#334155;line-height:1.6;margin-top:8px;">
            Hi {{ $name }}, thanks for signing up. Click the button below to verify <strong style="color:#0f172a;">{{ $email }}</strong> and activate your {{ config('app.name') }} account.
        </div>
    </td></tr>

    {{-- CTA + fallback link --}}
    @include('emails.partials.button', ['url' => $url, 'label' => 'Verify Email Address'])

    {{-- Expiry --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:16px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;color:#64748b;text-align:center;line-height:1.6;">
            This link expires in <strong style="color:#334155;">60 minutes</strong>.
        </div>
    </td></tr>

@endcomponent
