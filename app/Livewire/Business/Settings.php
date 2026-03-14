<?php

namespace App\Livewire\Business;

use App\Mail\TeamInvitation;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class Settings extends Component
{
    public Business $business;

    // General form
    public string $name        = '';
    public string $description = '';

    // Invite form
    public string $inviteEmail = '';
    public string $inviteRole  = 'editor';
    public bool   $inviteSent  = false;
    public bool   $showUpgradeModal = false;

    // Danger zone
    public bool   $showDeleteConfirm  = false;
    public string $deleteConfirmInput = '';

    // Member action state
    public ?string $confirmRemoveId = null;

    public function mount(Business $business): void
    {
        abort_unless($business->userRole(auth()->user()) === 'owner', 403);

        $this->business    = $business;
        $this->name        = $business->name;
        $this->description = $business->description ?? '';
    }

    public function saveGeneral(): void
    {
        $data = $this->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $this->business->update($data);
        $this->dispatch('general-saved');
    }

    public function sendInvite(): void
    {
        $this->inviteSent = false;

        $this->validate([
            'inviteEmail' => 'required|email|max:255',
            'inviteRole'  => 'required|in:editor,viewer',
        ]);

        // Free plan: max 2 members
        if (! auth()->user()->isPro() && $this->business->members()->count() >= 2) {
            $this->showUpgradeModal = true;
            return;
        }

        // Already a member?
        if ($this->business->members()->where('users.email', $this->inviteEmail)->exists()) {
            $this->addError('inviteEmail', 'This person is already a team member.');
            return;
        }

        $invitation = $this->business->invitations()->updateOrCreate(
            ['email' => $this->inviteEmail],
            [
                'role'        => $this->inviteRole,
                'token'       => Str::random(64),
                'accepted_at' => null,
                'expires_at'  => now()->addHours(72),
            ]
        );

        Mail::to($this->inviteEmail)->send(new TeamInvitation($invitation));

        $this->inviteEmail = '';
        $this->inviteSent  = true;
    }

    public function cancelInvitation(string $id): void
    {
        $this->business->invitations()->where('id', $id)->delete();
    }

    public function removeMember(string $userId): void
    {
        if ($userId === $this->business->owner_id) {
            return;
        }

        $this->business->members()->detach($userId);
        $this->confirmRemoveId = null;
    }

    public function updateMemberRole(string $userId, string $role): void
    {
        if ($userId === $this->business->owner_id) {
            return;
        }

        abort_unless(in_array($role, ['editor', 'viewer']), 422);

        $this->business->members()->updateExistingPivot($userId, ['role' => $role]);
    }

    public function deleteBusiness(): void
    {
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
            'members' => $members,
            'pending' => $pending,
        ]);
    }
}
