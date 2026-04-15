<?php

namespace App\Livewire\Invitation;

use App\Models\Invitation;
use Livewire\Component;

class Accept extends Component
{
    public Invitation $invitation;
    public string $status = 'pending'; // pending | accepted | expired | already_member | seat_limit

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
                return;
            }
        }

        // Enforce Free plan seat cap at accept time, not just at invite time.
        // The owner might have invited people while Pro and downgraded since.
        if ($this->isOverSeatLimit($invitation)) {
            $this->status = 'seat_limit';
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

        // Re-check the seat cap — guards against race conditions and TOCTOU.
        if ($this->isOverSeatLimit($this->invitation)) {
            $this->status = 'seat_limit';
            return;
        }

        $this->invitation->business->members()->attach(auth()->id(), [
            'role' => $this->invitation->role,
        ]);

        $this->invitation->update(['accepted_at' => now()]);

        $this->redirect(route('businesses.show', $this->invitation->business));
    }

    private function isOverSeatLimit(Invitation $invitation): bool
    {
        return ! $invitation->business->isPro()
            && $invitation->business->members()->count() >= 2;
    }

    public function render()
    {
        return view('livewire.invitation.accept')
            ->layout('layouts.guest');
    }
}
