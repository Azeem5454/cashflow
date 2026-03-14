<?php

namespace App\Livewire\Book;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookPaymentMode;
use App\Models\Business;
use Livewire\Component;

class Show extends Component
{
    public Book $book;
    public Business $business;
    public string $userRole   = '';
    public string $search     = '';
    public string $filterType = 'all'; // all | in | out

    // Slide-over state
    public bool $showEntryPanel = false;
    public ?string $editingEntryId = null;

    // Entry form fields
    public string $entryType        = 'in';
    public string $entryAmount      = '';
    public string $entryDescription = '';
    public string $entryDate        = '';
    public string $entryReference   = '';
    public string $entryCategory    = '';
    public string $entryPaymentMode = '';

    // Add new category inline
    public bool   $showAddCategory  = false;
    public string $newCategoryName  = '';

    // Add new payment mode inline
    public bool   $showAddPaymentMode  = false;
    public string $newPaymentModeName  = '';

    // Book management modals
    public bool   $showRenameBook    = false;
    public string $renameBookName    = '';
    public bool   $showDeleteBook    = false;
    public string $deleteConfirmName = '';

    public function mount(Business $business, Book $book): void
    {
        $this->business  = $business;
        $this->book      = $book;
        $this->userRole  = $business->userRole(auth()->user()) ?? 'viewer';
        $this->entryDate = now()->format('Y-m-d');
    }

    public function openAddEntry(string $type = 'in'): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $this->editingEntryId      = null;
        $this->entryType           = $type;
        $this->entryAmount         = '';
        $this->entryDescription    = '';
        $this->entryDate           = now()->format('Y-m-d');
        $this->entryReference      = '';
        $this->entryCategory       = '';
        $this->entryPaymentMode    = '';
        $this->showAddCategory     = false;
        $this->showAddPaymentMode  = false;
        $this->newCategoryName     = '';
        $this->newPaymentModeName  = '';
        $this->resetErrorBag();
        $this->showEntryPanel      = true;
    }

    public function openEditEntry(string $id): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $entry = $this->book->entries()->findOrFail($id);

        $this->editingEntryId      = $id;
        $this->entryType           = $entry->type;
        $this->entryAmount         = rtrim(rtrim((string) $entry->amount, '0'), '.');
        $this->entryDescription    = $entry->description ?? '';
        $this->entryDate           = $entry->date->format('Y-m-d');
        $this->entryReference      = $entry->reference ?? '';
        $this->entryCategory       = $entry->category ?? '';
        $this->entryPaymentMode    = $entry->payment_mode ?? '';
        $this->showAddCategory     = false;
        $this->showAddPaymentMode  = false;
        $this->newCategoryName     = '';
        $this->newPaymentModeName  = '';
        $this->resetErrorBag();
        $this->showEntryPanel      = true;
    }

    private function doSaveEntry(): void
    {
        $this->validate([
            'entryType'        => 'required|in:in,out',
            'entryAmount'      => 'required|numeric|min:0.01|max:999999999.99',
            'entryDescription' => 'required|string|max:255',
            'entryDate'        => 'required|date',
            'entryReference'   => 'nullable|string|max:100',
            'entryCategory'    => 'nullable|string|max:100',
            'entryPaymentMode' => 'nullable|string|max:100',
        ]);

        $data = [
            'type'         => $this->entryType,
            'amount'       => $this->entryAmount,
            'description'  => $this->entryDescription,
            'date'         => $this->entryDate,
            'reference'    => $this->entryReference ?: null,
            'category'     => $this->entryCategory ?: null,
            'payment_mode' => $this->entryPaymentMode ?: null,
        ];

        if ($this->editingEntryId) {
            $this->book->entries()->where('id', $this->editingEntryId)->update($data);
        } else {
            $this->book->entries()->create($data);
        }

        $this->book->touch();
    }

    public function saveEntry(): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $this->doSaveEntry();
        $this->showEntryPanel = false;
    }

    public function saveAndAddNew(): void
    {
        if ($this->userRole === 'viewer' || $this->editingEntryId) {
            return;
        }

        $type = $this->entryType;
        $this->doSaveEntry();

        // Reset form but keep panel open with same type
        $this->editingEntryId     = null;
        $this->entryType          = $type;
        $this->entryAmount        = '';
        $this->entryDescription   = '';
        $this->entryDate          = now()->format('Y-m-d');
        $this->entryReference     = '';
        $this->entryCategory      = '';
        $this->entryPaymentMode   = '';
        $this->resetErrorBag();
    }

    public function deleteEntry(string $id): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $this->book->entries()->where('id', $id)->delete();
        $this->book->touch();
    }

    public function addCategory(): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $name = trim($this->newCategoryName);
        if ($name === '') {
            return;
        }

        // Avoid duplicates (case-insensitive)
        $exists = $this->book->categories()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();

        if (! $exists) {
            $this->book->categories()->create(['name' => $name]);
        }

        $this->entryCategory     = $name;
        $this->newCategoryName   = '';
        $this->showAddCategory   = false;
    }

    public function addPaymentMode(): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $name = trim($this->newPaymentModeName);
        if ($name === '') {
            return;
        }

        $exists = $this->book->paymentModes()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();

        if (! $exists) {
            $this->book->paymentModes()->create(['name' => $name]);
        }

        $this->entryPaymentMode      = $name;
        $this->newPaymentModeName    = '';
        $this->showAddPaymentMode    = false;
    }

    // ── Book management ──────────────────────────────

    public function openRenameBook(): void
    {
        $this->renameBookName = $this->book->name;
        $this->resetErrorBag();
        $this->showRenameBook = true;
    }

    public function renameBook(): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $this->validate([
            'renameBookName' => 'required|string|max:100',
        ]);

        $this->book->update(['name' => $this->renameBookName]);
        $this->showRenameBook = false;
    }

    public function duplicateBook(): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $newBook = $this->business->books()->create([
            'name'        => $this->book->name . ' (Copy)',
            'description' => $this->book->description,
        ]);

        $this->redirect(route('businesses.books.show', [$this->business, $newBook]));
    }

    public function openDeleteBook(): void
    {
        $this->deleteConfirmName = '';
        $this->resetErrorBag();
        $this->showDeleteBook = true;
    }

    public function deleteBook(): void
    {
        if ($this->userRole !== 'owner') {
            return;
        }

        if (trim($this->deleteConfirmName) !== $this->book->name) {
            $this->addError('deleteConfirmName', 'Book name does not match.');
            return;
        }

        $businessId = $this->business->id;
        $this->book->entries()->delete();
        $this->book->categories()->delete();
        $this->book->paymentModes()->delete();
        $this->book->delete();

        $this->redirect(route('businesses.show', $businessId));
    }

    public function render()
    {
        // Fetch ALL entries for accurate running balance computation
        $allEntries = $this->book->entries()
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Compute running balance on the full set (unfiltered)
        $running = '0.00';
        foreach ($allEntries as $entry) {
            $running = $entry->type === 'in'
                ? bcadd($running, (string) $entry->amount, 2)
                : bcsub($running, (string) $entry->amount, 2);
            $entry->running_balance = $running;
        }

        // Apply filters
        $entries = $allEntries;

        if ($this->filterType !== 'all') {
            $entries = $entries->where('type', $this->filterType);
        }

        if ($this->search !== '') {
            $term    = strtolower($this->search);
            $entries = $entries->filter(fn ($e) =>
                str_contains(strtolower($e->description), $term)
                || str_contains(strtolower($e->reference ?? ''), $term)
                || str_contains(strtolower($e->category ?? ''), $term)
                || str_contains((string) $e->amount, $term)
            );
        }

        // Reverse for display: newest first
        $entries = $entries->reverse()->values();

        $totalIn  = $this->book->totalIn();
        $totalOut = $this->book->totalOut();
        $balance  = $this->book->balance();

        $categories   = $this->book->categories()->get();
        $paymentModes = $this->book->paymentModes()->get();

        return view('livewire.book.show', compact(
            'entries', 'totalIn', 'totalOut', 'balance', 'categories', 'paymentModes'
        ));
    }
}
