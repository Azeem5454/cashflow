<?php

namespace App\Livewire\Admin;

use Laravel\Cashier\Subscription;
use Livewire\Component;
use Livewire\WithPagination;

class Subscriptions extends Component
{
    use WithPagination;

    public string $statusFilter = ''; // '' | active | canceled | on_grace_period | ended

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Subscription::with('owner')
            ->when($this->statusFilter === 'active', fn ($q) =>
                $q->where('stripe_status', 'active')->whereNull('ends_at')
            )
            ->when($this->statusFilter === 'canceled', fn ($q) =>
                $q->where('stripe_status', 'canceled')
            )
            ->when($this->statusFilter === 'on_grace_period', fn ($q) =>
                $q->whereNotNull('ends_at')->where('ends_at', '>', now())
            )
            ->when($this->statusFilter === 'ended', fn ($q) =>
                $q->whereNotNull('ends_at')->where('ends_at', '<=', now())
            )
            ->latest();

        $subscriptions = $query->paginate(25);

        $activeSubs = Subscription::where('stripe_status', 'active')->whereNull('ends_at')->count();
        $mrr = $activeSubs * 3;

        return view('livewire.admin.subscriptions', compact('subscriptions', 'activeSubs', 'mrr'))
            ->layout('layouts.admin');
    }
}
