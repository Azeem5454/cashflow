@component('emails.partials.layout', [
    'emailTitle' => 'Team Invitation — ' . $invitation->business->name,
    'badge' => 'Invitation',
    'preheader' => $invitation->business->name . ' has invited you to join their team on ' . config('app.name'),
    'footerText' => 'This invitation was sent to <strong style="color:#94a3b8;">' . e($invitation->email) . '</strong>. If you weren\'t expecting it, you can safely ignore this email.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <div style="font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">You've been invited to join a team</div>
        <div style="font-size:13px;color:#64748b;margin-top:6px;">{{ $invitation->business->name }} on {{ config('app.name') }}</div>
    </td></tr>

    {{-- Divider --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>

    {{-- Invitation details card --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <div style="background-color:#0a0f1e;border-radius:12px;padding:20px;border:1px solid #1e293b;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="padding-bottom:12px;">
                        <div style="font-size:10px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Business</div>
                        <div style="font-size:15px;font-weight:600;color:#ffffff;margin-top:4px;">{{ $invitation->business->name }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:1px solid #1e293b;padding-top:12px;">
                        <div style="font-size:10px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Your Role</div>
                        <div style="margin-top:6px;">
                            <span style="display:inline-block;padding:4px 12px;background-color:{{ $invitation->role === 'editor' ? 'rgba(59,130,246,0.15)' : 'rgba(100,116,139,0.15)' }};color:{{ $invitation->role === 'editor' ? '#93c5fd' : '#94a3b8' }};font-size:12px;font-weight:600;border-radius:20px;text-transform:capitalize;">{{ $invitation->role }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </td></tr>

    {{-- Permissions --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <div style="font-size:12px;font-weight:600;color:#94a3b8;margin-bottom:10px;">What you'll be able to do:</div>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            @php
                $perms = $invitation->role === 'editor'
                    ? ['Create and manage books', 'Add, edit, and delete cash entries', 'View the full balance and history']
                    : ['View books and cash entries', 'See the balance and history'];
            @endphp
            @foreach($perms as $perm)
            <tr>
                <td style="padding:4px 0;vertical-align:middle;" width="20">
                    <div style="width:16px;height:16px;border-radius:50%;background-color:rgba(34,197,94,0.12);text-align:center;line-height:16px;">
                        <span style="color:#22c55e;font-size:10px;font-weight:bold;">&#10003;</span>
                    </div>
                </td>
                <td style="padding:4px 0 4px 8px;font-size:13px;color:#e2e8f0;">{{ $perm }}</td>
            </tr>
            @endforeach
        </table>
    </td></tr>

    {{-- CTA --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td align="center">
                <a href="{{ route('invitations.accept', $invitation->token) }}" class="cta-btn"
                   style="display:inline-block;padding:14px 36px;background-color:#1a56db;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:10px;letter-spacing:0.2px;">
                    Accept Invitation &rarr;
                </a>
            </td></tr>
        </table>
    </td></tr>

    {{-- Fallback plain link (for email clients that strip styled buttons) --}}
    <tr><td class="section-pad" style="padding:12px 32px 0;">
        <div style="font-size:11px;color:#64748b;text-align:center;line-height:1.5;word-break:break-all;">
            If the button doesn't work, copy this link: <a href="{{ route('invitations.accept', $invitation->token) }}" style="color:#3b82f6;text-decoration:underline;">{{ route('invitations.accept', $invitation->token) }}</a>
        </div>
    </td></tr>

    {{-- Expiry note --}}
    <tr><td class="section-pad" style="padding:16px 32px 0;">
        <div style="font-size:12px;color:#64748b;text-align:center;line-height:1.5;">
            This invitation expires in <strong style="color:#94a3b8;">72 hours</strong>. If you don't have an account yet, you'll create one when you click the link.
        </div>
    </td></tr>

@endcomponent
