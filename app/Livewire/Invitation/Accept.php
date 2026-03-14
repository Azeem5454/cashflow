<?php

namespace App\Livewire\Invitation;

use App\Models\Invitation;
use Livewire\Component;

class Accept extends Component
{
    public Invitation $invitation;
    public string $status = 'pending'; // pending | accepted | expired | already_member

    public function mount(Invitation $invitation): void
    {
        $this->invitation = $invitation;

        if ($invitation->isAccepted()) {
            $this->status = 'accepted';
            return;
        }

        if ($invitation->isExpired()) {
            $this->status = 'expired';
            return;
        }

        if (auth()->check()) {
            if ($invitation->business->members()->where('users.id', auth()->id())->exists()) {
                $this->status = 'already_member';
            }
        }
    }

    public function accept(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        if (! $this->invitation->isPending()) {
            return;
        }

        if ($this->invitation->business->members()->where('users.id', auth()->id())->exists()) {
            $this->redirect(route('businesses.show', $this->invitation->business));
            return;
        }

        $this->invitation->business->members()->attach(auth()->id(), [
            'role' => $this->invitation->role,
        ]);

        $this->invitation->update(['accepted_at' => now()]);

        $this->redirect(route('businesses.show', $this->invitation->business));
    }

    public function render()
    {
        return view('livewire.invitation.accept')
            ->layout('layouts.guest');
    }
}
