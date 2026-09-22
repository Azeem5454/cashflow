@component('emails.partials.layout', [
    'emailTitle' => 'Reset Your Password — ' . config('app.name'),
    'badge' => 'Security',
    'preheader' => 'Password reset requested for your ' . config('app.name') . ' account',
    'footerText' => 'This email was sent to <strong style="color:#334155;">' . e($email) . '</strong> because a password reset was requested. If you did not request this, no action is needed — your password will remain unchanged.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">Reset your password</div>
        <div style="font-size:15px;color:#334155;line-height:1.6;margin-top:8px;">
            We received a request to reset the password for <strong style="color:#0f172a;">{{ $email }}</strong>. Click the button below to choose a new one.
        </div>
    </td></tr>

    {{-- Security note --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:20px 32px 0;background-color:#ffffff;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#fffbeb" style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;border-collapse:separate;">
            <tr>
                <td style="padding:12px 16px;font-size:13px;color:#92400e;line-height:1.6;">
                    <strong style="color:#92400e;">Didn't request this?</strong> You can safely ignore this email — your password won't change.
                </td>
            </tr>
        </table>
    </td></tr>

    {{-- CTA + fallback link --}}
    @include('emails.partials.button', ['url' => $url, 'label' => 'Reset Password'])

    {{-- Expiry --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:16px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;color:#64748b;text-align:center;line-height:1.6;">
            This link expires in <strong style="color:#334155;">60 minutes</strong>.
        </div>
    </td></tr>

@endcomponent
