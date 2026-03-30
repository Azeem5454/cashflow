@component('emails.partials.layout', [
    'emailTitle' => 'Verify Your Email — ' . config('app.name'),
    'badge' => 'Verify',
    'preheader' => 'Please verify your email address to get started with ' . config('app.name'),
    'footerText' => 'This email was sent to <strong style="color:#94a3b8;">' . e($email) . '</strong> because an account was created on ' . config('app.name') . '. If you did not create an account, no further action is required.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <div style="font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">Verify your email address</div>
        <div style="font-size:13px;color:#64748b;margin-top:6px;">Hi {{ $name }}, thanks for signing up. One quick step to get started.</div>
    </td></tr>

    {{-- Divider --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>

    {{-- Message --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <div style="background-color:#0a0f1e;border-radius:12px;padding:20px;border:1px solid #1e293b;text-align:center;">
            <div style="font-size:13px;color:#94a3b8;line-height:1.6;">
                Click the button below to verify <strong style="color:#ffffff;">{{ $email }}</strong> and activate your {{ config('app.name') }} account.
            </div>
        </div>
    </td></tr>

    {{-- CTA --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td align="center">
                <a href="{{ $url }}" class="cta-btn"
                   style="display:inline-block;padding:14px 36px;background-color:#1a56db;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:10px;letter-spacing:0.2px;">
                    Verify Email Address &rarr;
                </a>
            </td></tr>
        </table>
    </td></tr>

    {{-- Expiry + fallback link --}}
    <tr><td class="section-pad" style="padding:16px 32px 0;">
        <div style="font-size:12px;color:#64748b;text-align:center;line-height:1.5;">
            This link expires in <strong style="color:#94a3b8;">60 minutes</strong>.
        </div>
    </td></tr>

    <tr><td class="section-pad" style="padding:16px 32px 0;">
        <div style="font-size:11px;color:#475569;text-align:center;line-height:1.6;word-break:break-all;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $url }}" style="color:#3b82f6;text-decoration:none;">{{ $url }}</a>
        </div>
    </td></tr>

@endcomponent
