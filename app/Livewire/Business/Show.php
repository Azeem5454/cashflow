<?php

namespace App\Livewire\Business;

use App\Models\Book;
use App\Models\Business;
use App\Support\BusinessLock;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    public Business $business;
    // Display-only; writes re-check the role from the DB (guardEditor/guardOwner).
    #[Locked]
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

    // ── Carry-forward opening balance (from the previous book's closing) ──
    // Display-only; createBook() recomputes the amount from the DB.
    #[Locked]
    public ?string $carryForwardAmount   = null;
    #[Locked]
    public ?string $carryForwardBookName = null;
    public bool    $carryForwardEnabled  = false;

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

    // ── Authorization ──────────────────────────────────────────

    /**
     * Role re-read from the pivot on every call (never trusted from Livewire
     * state). A Free owner's locked extra business yields null — Livewire
     * updates bypass the route-level lock gate.
     */
    private function currentRole(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $role = DB::table('business_user')
            ->where('business_id', $this->business->id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($role && BusinessLock::isLocked($user, $this->business, $role)) {
            return null;
        }

        return $role;
    }

    private function canEdit(): bool
    {
        $role = $this->currentRole();

        return $role !== null && $role !== 'viewer';
    }

    private function isOwner(): bool
    {
        return $this->currentRole() === 'owner';
    }

    private function guardEditor(): void
    {
        abort_unless($this->canEdit(), 403);
    }

    private function guardOwner(): void
    {
        abort_unless($this->isOwner(), 403);
    }

    // ── Create ─────────────────────────────────────────────────

    public function openCreateBook(): void
    {
        if (! $this->canEdit()) return;

        $this->bookName            = '';
        $this->bookDescription     = null;
        $this->bookOpeningBalance  = '';
        $this->bookPeriodStartsAt  = '';
        $this->bookPeriodEndsAt    = '';

        // Offer the previous book's closing balance, on by default.
        $previous = $this->business->previousBookForCarryForward();
        $this->carryForwardBookName = $previous?->name;
        $this->carryForwardAmount   = $previous?->closingBalance();
        $this->carryForwardEnabled  = $previous !== null;
        if ($this->carryForwardEnabled) {
            $this->bookOpeningBalance = $this->carryForwardAmount;
        }

        $this->resetErrorBag();
        $this->showCreateBook      = true;
    }

    /** Checkbox toggled: fill the opening balance field, or clear it back to 0. */
    public function updatedCarryForwardEnabled(bool $value): void
    {
        if ($this->carryForwardAmount === null) {
            $this->carryForwardEnabled = false;
            return;
        }

        $this->bookOpeningBalance = $value ? $this->carryForwardAmount : '';
    }

    public function createBook(string $periodStart = '', string $periodEnd = ''): void
    {
        $this->guardEditor();

        $this->validate([
            'bookName'           => 'required|string|max:100',
            'bookDescription'    => 'nullable|string|max:500',
            'bookOpeningBalance' => 'nullable|numeric|min:-999999999.99|max:999999999.99',
        ]);

        // Carry-forward is re-read from the DB here; the number the browser
        // holds is only ever a display value.
        if ($this->carryForwardEnabled && $this->bookOpeningBalance === '') {
            $this->bookOpeningBalance = $this->business
                ->previousBookForCarryForward()?->closingBalance() ?? '';
        }

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
        if (! $this->canEdit()) return;

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
        $this->guardEditor();

        $this->validate([
            'editBookName'           => 'required|string|max:100',
            'editBookDescription'    => 'nullable|string|max:500',
            'editBookOpeningBalance' => 'nullable|numeric|min:-999999999.99|max:999999999.99',
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
        if (! $this->canEdit()) return;

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
        $this->guardEditor();

        $this->validate([
            'duplicateBookName' => 'required|string|max:100',
        ]);

        $source = $this->business->books()->findOrFail($this->duplicatingBookId);

        $newBook = $this->business->books()->create([
            'name'             => $this->duplicateBookName,
            'description'      => $source->description,
            'opening_balance'  => $source->opening_balance ?? 0,
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
        // Deleting a whole book is owner-only.
        if (! $this->isOwner()) return;

        $book = $this->business->books()->findOrFail($bookId);

        $this->deletingBookId    = $bookId;
        $this->deletingBookName  = $book->name;
        $this->deleteConfirmName = '';
        $this->resetErrorBag();
        $this->showDeleteBook    = true;
    }

    public function deleteBook(): void
    {
        $this->guardOwner();

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
        $this->guardEditor();

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
            // LOWER(...) LIKE rather than Postgres-only ILIKE, with the user's
            // % and _ escaped so a search term can't act as a wildcard.
            ->when($this->search, function ($q) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($this->search)) . '%';
                $q->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]);
            })
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
            'books'      => $books,
            'bookGroups' => $this->groupByYear($books),
        ]);
    }

    /**
     * Books bucketed into collapsible year sections, newest year first.
     *
     * A book's year is the year of its period_starts_at, falling back to
     * created_at when it has no period. While searching, grouping is skipped:
     * results come back as one flat section (year => null) so a match in 2024
     * isn't hidden inside a collapsed header.
     *
     * The current year's section opens expanded; older years start collapsed.
     *
     * @return array<int, array{key:string, year:?int, count:int, net:string, open:bool, books:\Illuminate\Support\Collection}>
     */
    private function groupByYear(\Illuminate\Support\Collection $books): array
    {
        if (trim($this->search) !== '') {
            return $books->isEmpty() ? [] : [[
                'key'   => 'search',
                'year'  => null,
                'count' => $books->count(),
                'net'   => $books->reduce(fn ($c, $b) => bcadd($c, (string) $b->balance_calculated, 2), '0.00'),
                'open'  => true,
                'books' => $books,
            ]];
        }

        $thisYear = (int) now()->format('Y');

        $groups = $books
            ->groupBy(fn ($book) => (int) ($book->period_starts_at ?? $book->created_at)->format('Y'))
            ->sortKeysDesc()
            ->map(fn ($group, $year) => [
                'key'   => (string) $year,
                'year'  => (int) $year,
                'count' => $group->count(),
                'net'   => $group->reduce(fn ($c, $b) => bcadd($c, (string) $b->balance_calculated, 2), '0.00'),
                'open'  => (int) $year === $thisYear,
                'books' => $group->values(),
            ])
            ->values()
            ->all();

        // Nothing from the current year? Open the newest section anyway, so the
        // page never loads as a stack of closed headers.
        if ($groups !== [] && ! collect($groups)->contains('open', true)) {
            $groups[0]['open'] = true;
        }

        return $groups;
    }
}
