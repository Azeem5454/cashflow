@component('emails.partials.layout', [
    'emailTitle' => 'Verify Your Email — ' . config('app.name'),
    'badge' => 'Security',
    'preheader' => 'Your verification code is ' . $otp . ' — expires in 10 minutes',
    'footerText' => 'This email was sent to verify an email change on your <strong style="color:#334155;">' . e(config('app.name')) . '</strong> admin account. If you did not request this, your account is still secure — simply ignore this email.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Verify your new email</div>
        <div style="font-size:15px;color:#334155;line-height:1.6;margin-top:8px;">Hi {{ $adminName }}, enter this code to confirm your email change.</div>
    </td></tr>

    {{-- OTP Code --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8fafc" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;border-collapse:separate;">
            <tr>
                <td align="center" style="padding:24px 20px;">
                    <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Verification code</div>
                    <div style="font-size:36px;font-weight:700;color:#0f172a;letter-spacing:10px;font-family:'SFMono-Regular',Menlo,Consolas,'Courier New',monospace;">{{ $otp }}</div>
                </td>
            </tr>
        </table>
    </td></tr>

    {{-- Expiry warning --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:16px 32px 0;background-color:#ffffff;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#fffbeb" style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;border-collapse:separate;">
            <tr>
                <td align="center" style="padding:12px 16px;font-size:13px;color:#92400e;">
                    This code expires in <strong style="color:#92400e;">10 minutes</strong>.
                </td>
            </tr>
        </table>
    </td></tr>

    {{-- Instructions --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:20px 32px 0;background-color:#ffffff;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            @foreach(['Go to your ' . config('app.name') . ' Admin profile', 'Enter the 6-digit code above', 'Your email will be updated immediately'] as $i => $step)
            <tr>
                <td width="20" style="padding:3px 0;vertical-align:top;font-size:14px;line-height:20px;color:#64748b;">{{ $i + 1 }}.</td>
                <td style="padding:3px 0 3px 6px;font-size:14px;line-height:20px;color:#334155;">{{ $step }}</td>
            </tr>
            @endforeach
        </table>
    </td></tr>

@endcomponent
