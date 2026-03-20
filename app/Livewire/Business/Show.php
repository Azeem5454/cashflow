<?php

namespace App\Livewire\Business;

use App\Models\Book;
use App\Models\Business;
use Livewire\Component;

class Show extends Component
{
    public Business $business;
    public string $userRole = '';
    public string $search   = '';
    public string $sortBy   = 'updated_at';

    // ── Create book modal ──────────────────────────────────────
    public bool    $showCreateBook      = false;
    public string  $bookName            = '';
    public ?string $bookDescription     = null;
    public string  $bookOpeningBalance  = '';
    public string  $bookPeriodStartsAt  = '';
    public string  $bookPeriodEndsAt    = '';

    // ── Edit book modal ────────────────────────────────────────
    public bool    $showEditBook        = false;
    public string  $editingBookId       = '';
    public string  $editBookName        = '';
    public ?string $editBookDescription = null;
    public string  $editBookOpeningBalance = '';
    public string  $editBookPeriodStartsAt = '';
    public string  $editBookPeriodEndsAt   = '';

    // ── Delete book modal ──────────────────────────────────────
    public bool   $showDeleteBook    = false;
    public string $deletingBookId    = '';
    public string $deletingBookName  = '';
    public string $deleteConfirmName = '';

    // ── Duplicate book modal ───────────────────────────────────
    public bool    $showDuplicateBook        = false;
    public string  $duplicatingBookId        = '';
    public string  $duplicateBookName        = '';
    public string  $duplicateBookPeriodStartsAt = '';
    public string  $duplicateBookPeriodEndsAt   = '';
    public bool    $duplicateKeepCategories   = true;
    public bool    $duplicateKeepPaymentModes = true;
    public bool    $duplicateKeepEntries      = false;

    public function mount(Business $business): void
    {
        $this->business = $business;
        $this->userRole = $business->userRole(auth()->user()) ?? 'viewer';
    }

    // ── Create ─────────────────────────────────────────────────

    public function openCreateBook(): void
    {
        $this->bookName            = '';
        $this->bookDescription     = null;
        $this->bookOpeningBalance  = '';
        $this->bookPeriodStartsAt  = '';
        $this->bookPeriodEndsAt    = '';
        $this->resetErrorBag();
        $this->showCreateBook      = true;
    }

    public function createBook(string $periodStart = '', string $periodEnd = ''): void
    {
        if ($this->userRole === 'viewer') return;

        $this->validate([
            'bookName'           => 'required|string|max:100',
            'bookDescription'    => 'nullable|string|max:500',
            'bookOpeningBalance' => 'nullable|numeric|min:0|max:999999999.99',
        ]);

        $book = $this->business->books()->create([
            'name'             => $this->bookName,
            'description'      => $this->bookDescription ?: null,
            'opening_balance'  => $this->bookOpeningBalance ?: 0,
            'period_starts_at' => $periodStart ?: null,
            'period_ends_at'   => $periodEnd ?: null,
        ]);

        $this->redirect(route('businesses.books.show', [$this->business, $book]));
    }

    // ── Edit ───────────────────────────────────────────────────

    public function openEditBook(string $bookId): void
    {
        if ($this->userRole === 'viewer') return;

        $book = $this->business->books()->findOrFail($bookId);

        $this->editingBookId            = $bookId;
        $this->editBookName             = $book->name;
        $this->editBookDescription      = $book->description;
        $this->editBookOpeningBalance   = $book->opening_balance ? (string) $book->opening_balance : '';
        $this->editBookPeriodStartsAt   = $book->period_starts_at?->format('Y-m-d') ?? '';
        $this->editBookPeriodEndsAt     = $book->period_ends_at?->format('Y-m-d') ?? '';
        $this->resetErrorBag();
        $this->showEditBook             = true;
    }

    public function saveEditBook(string $periodStart = '', string $periodEnd = ''): void
    {
        if ($this->userRole === 'viewer') return;

        $this->validate([
            'editBookName'           => 'required|string|max:100',
            'editBookDescription'    => 'nullable|string|max:500',
            'editBookOpeningBalance' => 'nullable|numeric|min:0|max:999999999.99',
        ]);

        $this->business->books()->where('id', $this->editingBookId)->update([
            'name'             => $this->editBookName,
            'description'      => $this->editBookDescription ?: null,
            'opening_balance'  => $this->editBookOpeningBalance ?: 0,
            'period_starts_at' => $periodStart ?: null,
            'period_ends_at'   => $periodEnd ?: null,
        ]);

        $this->showEditBook = false;
        $this->dispatch('book-saved', message: 'Book updated successfully.');
    }

    // ── Duplicate ──────────────────────────────────────────────

    public function openDuplicateBook(string $bookId): void
    {
        if ($this->userRole === 'viewer') return;

        $book = $this->business->books()->findOrFail($bookId);

        $this->duplicatingBookId            = $bookId;
        $this->duplicateBookName            = $book->name . ' (Copy)';
        $this->duplicateBookPeriodStartsAt  = '';
        $this->duplicateBookPeriodEndsAt    = '';
        $this->duplicateKeepCategories      = true;
        $this->duplicateKeepPaymentModes    = true;
        $this->duplicateKeepEntries         = false;
        $this->resetErrorBag();
        $this->showDuplicateBook            = true;
    }

    public function executeDuplicate(string $periodStart = '', string $periodEnd = ''): void
    {
        if ($this->userRole === 'viewer') return;

        $this->validate([
            'duplicateBookName' => 'required|string|max:100',
        ]);

        $source = $this->business->books()->findOrFail($this->duplicatingBookId);

        $newBook = $this->business->books()->create([
            'name'             => $this->duplicateBookName,
            'description'      => $source->description,
            'opening_balance'  => 0,
            'period_starts_at' => $periodStart ?: null,
            'period_ends_at'   => $periodEnd ?: null,
        ]);

        if ($this->duplicateKeepCategories) {
            foreach ($source->categories()->get() as $cat) {
                $newBook->categories()->create(['name' => $cat->name]);
            }
        }

        if ($this->duplicateKeepPaymentModes) {
            foreach ($source->paymentModes()->get() as $pm) {
                $newBook->paymentModes()->create(['name' => $pm->name]);
            }
        }

        if ($this->duplicateKeepEntries) {
            foreach ($source->entries()->get() as $entry) {
                $newBook->entries()->create([
                    'type'         => $entry->type,
                    'amount'       => $entry->amount,
                    'description'  => $entry->description,
                    'date'         => $entry->date->format('Y-m-d'),
                    'reference'    => $entry->reference,
                    'category'     => $entry->category,
                    'payment_mode' => $entry->payment_mode,
                    'created_by'   => $entry->created_by,
                ]);
            }
        }

        $this->showDuplicateBook = false;
        $this->dispatch('book-saved', message: 'Book duplicated successfully.');
    }

    // ── Delete ─────────────────────────────────────────────────

    public function openDeleteBook(string $bookId): void
    {
        if ($this->userRole === 'viewer') return;

        $book = $this->business->books()->findOrFail($bookId);

        $this->deletingBookId    = $bookId;
        $this->deletingBookName  = $book->name;
        $this->deleteConfirmName = '';
        $this->resetErrorBag();
        $this->showDeleteBook    = true;
    }

    public function deleteBook(): void
    {
        if ($this->userRole === 'viewer') return;

        $this->validate([
            'deleteConfirmName' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (trim($value) !== trim($this->deletingBookName)) {
                        $fail('Book name does not match.');
                    }
                },
            ],
        ]);

        $this->business->books()->where('id', $this->deletingBookId)->delete();

        $this->showDeleteBook = false;
        $this->dispatch('book-saved', message: 'Book deleted.');
    }

    // ── Kept for backward compat (inline rename, if still used) ─
    public function renameBook(string $bookId, string $name): void
    {
        if ($this->userRole === 'viewer') return;

        $name = trim($name);
        if ($name === '') return;

        $this->business->books()->where('id', $bookId)->update(['name' => $name]);
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
            ->withSum(['entries as total_in'  => fn ($q) => $q->where('type', 'in')],  'amount')
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

        return view('livewire.business.show', compact('books'));
    }
}
