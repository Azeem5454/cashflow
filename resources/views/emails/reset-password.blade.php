@component('emails.partials.layout', [
    'emailTitle' => 'Reset Your Password — ' . config('app.name'),
    'badge' => 'Security',
    'preheader' => 'Password reset requested for your ' . config('app.name') . ' account',
    'footerText' => 'This email was sent to <strong style="color:#94a3b8;">' . e($email) . '</strong> because a password reset was requested. If you did not request this, no action is needed — your password will remain unchanged.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <div style="font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">Reset your password</div>
        <div style="font-size:13px;color:#64748b;margin-top:6px;">We received a password reset request for your account.</div>
    </td></tr>

    {{-- Divider --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>

    {{-- Security note --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <div style="background-color:#0a0f1e;border-radius:12px;padding:20px;border:1px solid #1e293b;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="32" style="vertical-align:top;">
                        <div style="width:28px;height:28px;border-radius:8px;background-color:rgba(245,158,11,0.12);text-align:center;line-height:28px;">
                            <span style="font-size:14px;">&#128274;</span>
                        </div>
                    </td>
                    <td style="padding-left:12px;">
                        <div style="font-size:13px;color:#94a3b8;line-height:1.5;">
                            Click the button below to choose a new password for <strong style="color:#ffffff;">{{ $email }}</strong>. If you didn't request this, you can safely ignore this email.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </td></tr>

    {{-- CTA --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td align="center">
                <a href="{{ $url }}" class="cta-btn"
                   style="display:inline-block;padding:14px 36px;background-color:#1a56db;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:10px;letter-spacing:0.2px;">
                    Reset Password &rarr;
                </a>
            </td></tr>
        </table>
    </td></tr>

    {{-- Expiry --}}
    <tr><td class="section-pad" style="padding:16px 32px 0;">
        <div style="font-size:12px;color:#64748b;text-align:center;line-height:1.5;">
            This link expires in <strong style="color:#94a3b8;">60 minutes</strong>.
        </div>
    </td></tr>

    {{-- Fallback link --}}
    <tr><td class="section-pad" style="padding:16px 32px 0;">
        <div style="font-size:11px;color:#475569;text-align:center;line-height:1.6;word-break:break-all;">
            If the button doesn't work, copy and paste this link:<br>
            <a href="{{ $url }}" style="color:#3b82f6;text-decoration:none;">{{ $url }}</a>
        </div>
    </td></tr>

@endcomponent
