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

    // Create book modal
    public bool    $showCreateBook       = false;
    public string  $bookName             = '';
    public ?string $bookDescription      = null;
    public string  $bookOpeningBalance   = '';

    public function mount(Business $business): void
    {
        $this->business = $business;
        $this->userRole = $business->userRole(auth()->user()) ?? 'viewer';
    }

    public function openCreateBook(): void
    {
        $this->bookName             = '';
        $this->bookDescription      = null;
        $this->bookOpeningBalance   = '';
        $this->resetErrorBag();
        $this->showCreateBook       = true;
    }

    public function createBook(): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $this->validate([
            'bookName'           => 'required|string|max:100',
            'bookDescription'    => 'nullable|string|max:500',
            'bookOpeningBalance' => 'nullable|numeric|min:0|max:999999999.99',
        ]);

        $book = $this->business->books()->create([
            'name'            => $this->bookName,
            'description'     => $this->bookDescription ?: null,
            'opening_balance' => $this->bookOpeningBalance ?: 0,
        ]);

        $this->redirect(route('businesses.books.show', [$this->business, $book]));
    }

    public function renameBook(string $bookId, string $name): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $name = trim($name);
        if ($name === '') {
            return;
        }

        $this->business->books()->where('id', $bookId)->update(['name' => $name]);
    }

    public function duplicateBook(string $bookId): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $book = $this->business->books()->findOrFail($bookId);

        $this->business->books()->create([
            'name'        => $book->name . ' (Copy)',
            'description' => $book->description,
        ]);
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
                $opening = (string) ($book->opening_balance ?? '0');
                $in      = (string) ($book->total_in  ?? '0');
                $out     = (string) ($book->total_out ?? '0');
                $book->balance_calculated = bcsub(bcadd($opening, $in, 2), $out, 2);
                return $book;
            });

        return view('livewire.business.show', [
            'books' => $books,
        ]);
    }
}
