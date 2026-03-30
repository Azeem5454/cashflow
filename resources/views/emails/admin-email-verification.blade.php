@component('emails.partials.layout', [
    'emailTitle' => 'Verify Your Email — ' . config('app.name'),
    'badge' => 'Security',
    'preheader' => 'Your verification code is ' . $otp . ' — expires in 10 minutes',
    'footerText' => 'This email was sent to verify an email change on your <strong style="color:#94a3b8;">' . e(config('app.name')) . '</strong> admin account. If you did not request this, your account is still secure — simply ignore this email.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <div style="font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">Verify your new email</div>
        <div style="font-size:13px;color:#64748b;margin-top:6px;">Hi {{ $adminName }}, enter this code to confirm your email change.</div>
    </td></tr>

    {{-- Divider --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>

    {{-- OTP Code --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <div style="background-color:#0a0f1e;border-radius:12px;padding:24px 20px;text-align:center;border:1px solid #1e293b;">
            <div style="font-size:10px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Verification Code</div>
            <div style="font-size:36px;font-weight:800;color:#ffffff;letter-spacing:10px;font-family:'Courier New',Courier,monospace;">{{ $otp }}</div>
        </div>
    </td></tr>

    {{-- Expiry warning --}}
    <tr><td class="section-pad" style="padding:16px 32px 0;">
        <div style="background-color:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:12px 16px;text-align:center;">
            <span style="font-size:12px;color:#f59e0b;font-weight:500;">&#9201; This code expires in <strong>10 minutes</strong></span>
        </div>
    </td></tr>

    {{-- Instructions --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding:3px 0;vertical-align:top;" width="20">
                    <span style="color:#64748b;font-size:12px;">1.</span>
                </td>
                <td style="padding:3px 0 3px 6px;font-size:13px;color:#94a3b8;">Go to your {{ config('app.name') }} Admin profile</td>
            </tr>
            <tr>
                <td style="padding:3px 0;vertical-align:top;" width="20">
                    <span style="color:#64748b;font-size:12px;">2.</span>
                </td>
                <td style="padding:3px 0 3px 6px;font-size:13px;color:#94a3b8;">Enter the 6-digit code above</td>
            </tr>
            <tr>
                <td style="padding:3px 0;vertical-align:top;" width="20">
                    <span style="color:#64748b;font-size:12px;">3.</span>
                </td>
                <td style="padding:3px 0 3px 6px;font-size:13px;color:#94a3b8;">Your email will be updated immediately</td>
            </tr>
        </table>
    </td></tr>

@endcomponent
