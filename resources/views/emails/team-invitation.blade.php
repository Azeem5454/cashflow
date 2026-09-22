@php
    $acceptUrl = route('invitations.accept', $invitation->token);
    $isEditor  = $invitation->role === 'editor';
    $perms = $isEditor
        ? ['Create and manage books', 'Add, edit, and delete cash entries', 'View the full balance and history']
        : ['View books and cash entries', 'See the balance and history'];
@endphp
@component('emails.partials.layout', [
    'emailTitle' => 'Team Invitation — ' . $invitation->business->name,
    'badge' => 'Invitation',
    'preheader' => $invitation->business->name . ' has invited you to join their team on ' . config('app.name'),
    'footerText' => 'This invitation was sent to <strong style="color:#334155;">' . e($invitation->email) . '</strong>. If you weren\'t expecting it, you can safely ignore this email.',
])

    {{-- Heading --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">You've been invited to join a team</div>
        <div style="font-size:15px;color:#334155;line-height:1.6;margin-top:8px;">
            You've been invited to join <strong style="color:#0f172a;">{{ $invitation->business->name }}</strong> on {{ config('app.name') }} to track cash in and cash out together.
        </div>
    </td></tr>

    {{-- Invitation details card --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8fafc" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;border-collapse:separate;">
            <tr>
                <td style="padding:16px 20px 12px;">
                    <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Business</div>
                    <div style="font-size:16px;font-weight:600;color:#0f172a;margin-top:4px;">{{ $invitation->business->name }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 20px 16px;border-top:1px solid #e2e8f0;">
                    <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Your role</div>
                    <div style="margin-top:6px;">
                        <span style="display:inline-block;padding:4px 12px;background-color:{{ $isEditor ? '#dbeafe' : '#e2e8f0' }};color:{{ $isEditor ? '#1e40af' : '#334155' }};font-size:12px;font-weight:600;border-radius:20px;text-transform:capitalize;">{{ $invitation->role }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </td></tr>

    {{-- Permissions --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:20px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:8px;">What you'll be able to do:</div>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            @foreach($perms as $perm)
            <tr>
                <td width="20" style="padding:4px 0;vertical-align:top;font-size:14px;line-height:20px;color:#15803d;font-weight:bold;">&#10003;</td>
                <td style="padding:4px 0 4px 6px;font-size:14px;line-height:20px;color:#334155;">{{ $perm }}</td>
            </tr>
            @endforeach
        </table>
    </td></tr>

    {{-- CTA + fallback link --}}
    @include('emails.partials.button', ['url' => $acceptUrl, 'label' => 'Accept Invitation'])

    {{-- Expiry note --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:16px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;color:#64748b;text-align:center;line-height:1.6;">
            This invitation expires in <strong style="color:#334155;">72 hours</strong>. If you don't have an account yet, you'll create one when you click the link.
        </div>
    </td></tr>

@endcomponent
