<?php

namespace App\Livewire\Business;

use App\Mail\TeamInvitation;
use App\Models\Business;
use App\Support\BusinessLock;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class Settings extends Component
{
    use \App\Livewire\Concerns\RequiresVerifiedEmail;
    use \Livewire\WithFileUploads;

    public Business $business;

    // General form
    public string $name        = '';
    public string $description = '';

    // Branding shown on exports and email reports
    public string $contactPhone = '';
    public string $contactEmail = '';
    public $logoUpload = null;

    // Invite form
    public string $inviteEmail = '';
    public string $inviteRole  = 'editor';
    public bool   $inviteSent  = false;
    public string $upgradeModalFeature = '';

    // Danger zone
    public bool   $showDeleteConfirm  = false;
    public string $deleteConfirmInput = '';

    // Member action state
    public ?string $confirmRemoveId = null;

    public function mount(Business $business): void
    {
        abort_unless($business->userRole(auth()->user()) === 'owner', 403);

        $this->business     = $business;
        $this->name         = $business->name;
        $this->description  = $business->description ?? '';
        $this->contactPhone = $business->contact_phone ?? '';
        $this->contactEmail = $business->contact_email ?? '';
    }

    /**
     * Owner re-checked from the DB on every action. Livewire updates skip the
     * route middleware, so a Free owner's locked extra business is re-checked
     * here too (same rule as the API). Deleting a locked business stays allowed.
     */
    private function guardOwner(bool $allowLocked = false): void
    {
        $user = auth()->user();
        $role = \Illuminate\Support\Facades\DB::table('business_user')
            ->where('business_id', $this->business->id)
            ->where('user_id', $user?->id)
            ->value('role');

        abort_unless($role === 'owner', 403);

        if (! $allowLocked && BusinessLock::isLocked($user, $this->business, $role)) {
            abort(403, 'This business is locked on the Free plan.');
        }
    }

    public function saveGeneral(): void
    {
        $this->guardOwner();

        $data = $this->validate([
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
            'contactPhone' => 'nullable|string|max:40',
            'contactEmail' => 'nullable|email|max:255',
        ]);

        $this->business->update([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'contact_phone' => ($data['contactPhone'] ?? '') ?: null,
            'contact_email' => ($data['contactEmail'] ?? '') ?: null,
        ]);

        $this->dispatch('general-saved');
    }

    /** Logo upload — validated on both extension and sniffed MIME. */
    public function updatedLogoUpload(): void
    {
        $this->guardOwner();

        $this->validate([
            'logoUpload' => [
                'required', 'image',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/png,image/jpeg',
                'max:1024', // KB
            ],
        ]);

        $bytes = file_get_contents($this->logoUpload->getRealPath());
        if ($bytes === false) {
            $this->addError('logoUpload', 'That file could not be read. Try again.');
            return;
        }

        $this->business->storeLogo($bytes);
        $this->logoUpload = null;
        $this->dispatch('general-saved');
    }

    public function removeLogo(): void
    {
        $this->guardOwner();

        $this->business->removeLogo();
        $this->logoUpload = null;
        $this->dispatch('general-saved');
    }

    public function sendInvite(): void
    {
        $this->guardOwner();

        $this->inviteSent = false;

        $this->validate([
            'inviteEmail' => 'required|email|max:255',
            'inviteRole'  => 'required|in:editor,viewer',
        ]);

        // Soft verification: invitations email other people.
        if (! $this->ensureVerifiedEmail()) {
            return;
        }

        // Plan seat limit: members (owner included) + open invitations.
        // Re-sending an invitation that's already open doesn't take a new seat.
        if (! $this->business->canInvite($this->inviteEmail)) {
            $this->upgradeModalFeature = 'team';
            return;
        }

        $inviter = app(\App\Services\TeamInviter::class);

        // Already a member? (case-insensitive — also stops inviting yourself)
        if ($inviter->isMember($this->business, $this->inviteEmail)) {
            $this->addError('inviteEmail', 'This person is already a team member.');
            return;
        }

        // Rate limit: max 5 invitations per hour per user (valid invites only)
        $key = 'invite:' . auth()->id();
        if (!\Illuminate\Support\Facades\RateLimiter::attempt($key, 5, fn () => true, 3600)) {
            $this->addError('inviteEmail', 'Too many invitations sent. Please wait before sending more.');
            return;
        }

        [$invitation] = $inviter->invite($this->business, $this->inviteEmail, $this->inviteRole, 'inviteEmail');

        Mail::to($invitation->email)->send(new TeamInvitation($invitation));

        $this->inviteEmail = '';
        $this->inviteSent  = true;
    }

    public function cancelInvitation(string $id): void
    {
        $this->guardOwner();

        $this->business->invitations()->where('id', $id)->delete();
    }

    public function removeMember(string $userId): void
    {
        $this->guardOwner();

        if ($userId === $this->business->owner_id) {
            return;
        }

        $this->business->members()->detach($userId);
        $this->confirmRemoveId = null;
    }

    public function updateMemberRole(string $userId, string $role): void
    {
        $this->guardOwner();

        if ($userId === $this->business->owner_id) {
            return;
        }

        abort_unless(in_array($role, ['editor', 'viewer']), 422);

        $this->business->members()->updateExistingPivot($userId, ['role' => $role]);
    }

    public function deleteBusiness(): void
    {
        $this->guardOwner(allowLocked: true);

        if ($this->deleteConfirmInput !== $this->business->name) {
            $this->addError('deleteConfirmInput', 'Business name does not match.');
            return;
        }

        $this->business->delete();

        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        $members = $this->business->members()->orderByPivot('created_at')->get();

        $pending = $this->business->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.business.settings', [
            'members'     => $members,
            'pending'     => $pending,
            'memberLimit' => $this->business->memberLimit(),
            'seatsUsed'   => $this->business->seatsUsed(),
        ]);
    }
}
