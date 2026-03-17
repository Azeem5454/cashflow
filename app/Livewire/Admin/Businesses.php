<?php

namespace App\Livewire\Admin;

use App\Models\Business;
use Livewire\Component;
use Livewire\WithPagination;

class Businesses extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $expandedId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->expandedId = null;
    }

    public function toggleExpand(string $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function getExpandedBusinessProperty()
    {
        if (! $this->expandedId) {
            return null;
        }

        return Business::with(['members', 'books' => fn ($q) => $q->withCount('entries')->latest()])
            ->find($this->expandedId);
    }

    public function render()
    {
        $businesses = Business::query()
            ->with('owner')
            ->withCount(['members', 'books', 'entries'])
            ->when($this->search, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name', 'ilike', '%' . $this->search . '%')
                       ->orWhereHas('owner', fn ($q3) =>
                           $q3->where('email', 'ilike', '%' . $this->search . '%')
                       )
                ))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.businesses', [
            'businesses' => $businesses,
            'expandedBusiness' => $this->expandedBusiness,
        ])->layout('layouts.admin');
    }
}
