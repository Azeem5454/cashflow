<?php

namespace App\Livewire\Business;

use App\Models\Business;
use Livewire\Component;

class Show extends Component
{
    public Business $business;
    public string $userRole = '';
    public string $search = '';
    public string $sortBy = 'updated_at';

    public function mount(Business $business): void
    {
        $this->business = $business;
        $this->userRole = $business->userRole(auth()->user()) ?? 'viewer';
    }

    public function render()
    {
        $sortColumn = match ($this->sortBy) {
            'name'       => ['name', 'asc'],
            'created_at' => ['created_at', 'desc'],
            default      => ['updated_at', 'desc'],
        };

        $books = $this->business->books()
            ->withCount('entries')
            ->withSum(['entries as total_in' => fn ($q) => $q->where('type', 'in')], 'amount')
            ->withSum(['entries as total_out' => fn ($q) => $q->where('type', 'out')], 'amount')
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', '%' . $this->search . '%'))
            ->orderBy($sortColumn[0], $sortColumn[1])
            ->get()
            ->map(function ($book) {
                $in  = (string) ($book->total_in  ?? '0');
                $out = (string) ($book->total_out ?? '0');
                $book->balance_calculated = bcsub($in, $out, 2);
                return $book;
            });

        return view('livewire.business.show', [
            'books' => $books,
        ]);
    }
}
