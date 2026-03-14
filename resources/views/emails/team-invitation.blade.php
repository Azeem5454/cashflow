@component('mail::message')
# You've been invited to join a team

**{{ $invitation->business->name }}** has invited you to collaborate on CashFlow as a **{{ ucfirst($invitation->role) }}**.

@component('mail::button', ['url' => route('invitations.accept', $invitation->token), 'color' => 'primary'])
Accept Invitation
@endcomponent

**What you'll be able to do as {{ ucfirst($invitation->role) }}:**
@if($invitation->role === 'editor')
- Create and manage books
- Add, edit, and delete cash entries
- View the full balance and history
@else
- View books and cash entries
- See the balance and history
@endif

This invitation expires in **72 hours**. If you don't have a CashFlow account yet, you'll be able to create one when you click the link.

If you weren't expecting this invitation, you can safely ignore this email.

Thanks,
**The CashFlow Team**
@endcomponent
