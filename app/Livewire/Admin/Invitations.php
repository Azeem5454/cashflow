<?php

namespace App\Livewire\Admin;

use App\Models\Invitation;
use Livewire\Component;
use Livewire\WithPagination;

class Invitations extends Component
{
    use WithPagination;

    public string $statusFilter = ''; // '' | pending | accepted | expired

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resendInvitation(string $id): void
    {
        $old = Invitation::findOrFail($id);

        $old->update([
            'token'       => \Illuminate\Support\Str::random(64),
            'accepted_at' => null,
            'expires_at'  => now()->addHours(72),
        ]);
    }

    public function cancelInvitation(string $id): void
    {
        Invitation::where('id', $id)->delete();
    }

    public function render()
    {
        $query = Invitation::with('business')
            ->when($this->statusFilter === 'pending', fn ($q) =>
                $q->whereNull('accepted_at')->where('expires_at', '>', now())
            )
            ->when($this->statusFilter === 'accepted', fn ($q) =>
                $q->whereNotNull('accepted_at')
            )
            ->when($this->statusFilter === 'expired', fn ($q) =>
                $q->whereNull('accepted_at')->where('expires_at', '<=', now())
            )
            ->latest();

        $invitations = $query->paginate(25);

        return view('livewire.admin.invitations', compact('invitations'))
            ->layout('layouts.admin');
    }
}
