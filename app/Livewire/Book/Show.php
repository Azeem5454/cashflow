<?php

namespace App\Livewire\Book;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookPaymentMode;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;
    public Book $book;
    public Business $business;
    public string $userRole   = '';
    public string $search           = '';
    public string $filterType       = 'all'; // all | in | out

    // Duration filter
    public string $filterDuration   = 'all_time'; // all_time | today | yesterday | last_7_days | last_30_days | custom
    public string $filterCustomFrom = '';
    public string $filterCustomTo   = '';
    public bool   $showCustomDateModal = false;

    // Multi-select filters
    public array  $filterCategories    = [];
    public array  $filterPaymentModes  = [];

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

    // Attachment
    public $entryAttachment = null; // Livewire temp upload
    public ?string $existingAttachmentPath = null; // when editing, the current attachment
    public bool $removeAttachment = false;

    // AI receipt OCR
    public $ocrFile              = null;
    public array $aiFilledFields = [];
    public ?string $scanError    = null;
    public ?string $ocrOriginalAmount   = null; // e.g. "USD 5.00"
    public ?string $ocrConvertedAt      = null; // e.g. "1 USD = 278.50 PKR"

    // Attachment preview modal
    public bool    $showAttachmentPreview   = false;
    public ?string $previewAttachmentPath   = null;
    public ?string $previewAttachmentName   = null;
    public ?string $previewEntryId          = null;

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

    // Export / upgrade modal
    public bool $showUpgradeModal = false;

    // Bulk operations
    public bool   $showBulkDeleteConfirm     = false;
    public bool   $showBulkBookPicker        = false;
    public string $bulkAction                = '';       // 'move' | 'copy' | 'copy_opposite'
    public string $bulkTargetBookId          = '';
    public bool   $showBulkChangeCategory    = false;
    public bool   $showBulkChangePaymentMode = false;
    public string $bulkNewCategory           = '';
    public string $bulkNewPaymentMode        = '';
    public string $bulkSuccessMessage        = '';

    // Reports tab
    public string $activeTab = 'entries'; // 'entries' | 'reports' | 'recurring'

    // Recurring entry form (in slide-over)
    public bool   $entryRecurring  = false;
    public string $entryFrequency  = 'monthly';
    public string $entryEndsAt     = '';

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
        $this->entryAttachment         = null;
        $this->existingAttachmentPath  = null;
        $this->removeAttachment        = false;
        $this->ocrFile               = null;
        $this->aiFilledFields        = [];
        $this->scanError             = null;
        $this->ocrOriginalAmount     = null;
        $this->ocrConvertedAt        = null;
        $this->entryRecurring      = false;
        $this->entryFrequency      = 'monthly';
        $this->entryEndsAt         = '';
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
        $this->showAddCategory         = false;
        $this->showAddPaymentMode      = false;
        $this->newCategoryName         = '';
        $this->newPaymentModeName      = '';
        $this->entryAttachment         = null;
        $this->existingAttachmentPath  = $entry->attachment_path;
        $this->removeAttachment        = false;
        $this->resetErrorBag();
        $this->showEntryPanel      = true;
    }

    // Recurring edit confirmation
    public bool   $showRecurringUpdateConfirm = false;
    public string $pendingEditEntryId         = '';

    /**
     * Re-fetch the user's role from the DB on every call.
     * Prevents stale Livewire state from being exploited if a role was
     * changed by an owner while the user had an active session.
     */
    private function guardEditor(): void
    {
        $role = \Illuminate\Support\Facades\DB::table('business_user')
            ->where('business_id', $this->business->id)
            ->where('user_id', auth()->id())
            ->value('role');

        abort_unless($role && $role !== 'viewer', 403);
    }

    /**
     * Validate that all given entry IDs belong to this book.
     * Prevents cross-book ID injection in bulk operations.
     */
    private function validateBulkIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        // Return only IDs that actually exist in this book
        return $this->book->entries()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();
    }

    private function doSaveEntry(): ?\App\Models\Entry
    {
        $rules = [
            'entryType'        => 'required|in:in,out',
            'entryAmount'      => 'required|numeric|min:0.01|max:999999999.99',
            'entryDescription' => 'required|string|max:255',
            'entryDate'        => 'required|date',
            'entryReference'   => 'nullable|string|max:100',
            'entryCategory'    => 'nullable|string|max:100',
            'entryPaymentMode' => 'nullable|string|max:100',
        ];

        if ($this->entryAttachment) {
            $rules['entryAttachment'] = 'file|max:2048|mimes:png,jpg,jpeg,pdf|mimetypes:image/png,image/jpeg,application/pdf';
        }

        $this->validate($rules);

        $data = [
            'type'         => $this->entryType,
            'amount'       => $this->entryAmount,
            'description'  => $this->entryDescription,
            'date'         => $this->entryDate,
            'reference'    => $this->entryReference ?: null,
            'category'     => $this->entryCategory ?: null,
            'payment_mode' => $this->entryPaymentMode ?: null,
        ];

        // Handle attachment
        $attachmentPath = null;
        if ($this->entryAttachment) {
            $dir = "attachments/{$this->business->id}/{$this->book->id}";
            $attachmentPath = $this->entryAttachment->store($dir, 'local');
        }

        $entry = null;
        if ($this->editingEntryId) {
            $existing = $this->book->entries()->find($this->editingEntryId);

            // Remove old attachment if new one uploaded or removal requested
            if ($existing && $existing->attachment_path && ($attachmentPath || $this->removeAttachment)) {
                Storage::disk('local')->delete($existing->attachment_path);
            }

            if ($attachmentPath) {
                $data['attachment_path'] = $attachmentPath;
            } elseif ($this->removeAttachment) {
                $data['attachment_path'] = null;
            }

            $this->book->entries()->where('id', $this->editingEntryId)->update($data);
        } else {
            if ($attachmentPath) {
                $data['attachment_path'] = $attachmentPath;
            }
            $entry = $this->book->entries()->create($data);
        }

        $this->book->touch();
        $this->entryAttachment = null;

        return $entry;
    }

    public function saveEntry(): void
    {
        $this->guardEditor();
        if ($this->userRole === 'viewer') {
            return;
        }

        $isNew = ! $this->editingEntryId;
        $entry = $this->doSaveEntry();

        // Create recurring entry template if toggled on for new entries
        if ($isNew && $entry && $this->entryRecurring && $this->business->isPro()) {
            $nextRun = Carbon::parse($this->entryDate);
            match ($this->entryFrequency) {
                'daily'   => $nextRun->addDay(),
                'weekly'  => $nextRun->addWeek(),
                'monthly' => $nextRun->addMonth(),
                'yearly'  => $nextRun->addYear(),
            };

            $recurringEntry = $this->book->recurringEntries()->create([
                'type'         => $this->entryType,
                'amount'       => $this->entryAmount,
                'description'  => $this->entryDescription,
                'category'     => $this->entryCategory ?: null,
                'payment_mode' => $this->entryPaymentMode ?: null,
                'reference'    => $this->entryReference ?: null,
                'frequency'    => $this->entryFrequency,
                'starts_at'    => $this->entryDate,
                'next_run_at'  => $nextRun->format('Y-m-d'),
                'ends_at'      => $this->entryEndsAt ?: null,
                'is_active'    => true,
            ]);

            // Link the initial entry to the recurring rule
            $entry->update(['recurring_entry_id' => $recurringEntry->id]);
        }

        // If editing an entry linked to a recurring rule, ask user (Pro only)
        if (! $isNew && $this->editingEntryId && $this->business->isPro()) {
            $editedEntry = $this->book->entries()->find($this->editingEntryId);
            if ($editedEntry && $editedEntry->recurring_entry_id) {
                $this->pendingEditEntryId = $this->editingEntryId;
                $this->showRecurringUpdateConfirm = true;
                $this->showEntryPanel = false;
                return;
            }
        }

        $this->showEntryPanel = false;
    }

    public function applyToRecurring(): void
    {
        if ($this->pendingEditEntryId) {
            $entry = $this->book->entries()->find($this->pendingEditEntryId);
            if ($entry && $entry->recurring_entry_id) {
                $rec = $this->book->recurringEntries()->find($entry->recurring_entry_id);
                if ($rec) {
                    $rec->update([
                        'amount'       => $entry->amount,
                        'description'  => $entry->description,
                        'category'     => $entry->category,
                        'payment_mode' => $entry->payment_mode,
                        'reference'    => $entry->reference,
                    ]);
                }
            }
        }

        $this->showRecurringUpdateConfirm = false;
        $this->pendingEditEntryId = '';
    }

    public function skipRecurringUpdate(): void
    {
        $this->showRecurringUpdateConfirm = false;
        $this->pendingEditEntryId = '';
    }

    public function removeExistingAttachment(): void
    {
        $this->removeAttachment = true;
        $this->existingAttachmentPath = null;
    }

    public function clearNewAttachment(): void
    {
        $this->entryAttachment = null;
    }

    public function openAttachmentPreview(string $entryId): void
    {
        $entry = $this->book->entries()->find($entryId);
        if (! $entry || ! $entry->attachment_path) {
            return;
        }

        $this->previewEntryId        = $entry->id;
        $this->previewAttachmentPath = $entry->attachment_path;
        $this->previewAttachmentName = basename($entry->attachment_path);
        $this->showAttachmentPreview = true;
    }

    public function closeAttachmentPreview(): void
    {
        $this->showAttachmentPreview = false;
        $this->previewEntryId        = null;
        $this->previewAttachmentPath = null;
        $this->previewAttachmentName = null;
    }

    // ── AI Receipt OCR ──────────────────────────────────────────────────────

    public function prepareScan(): void
    {
        if (!$this->business->isPro()) {
            $this->showUpgradeModal = true;
            return;
        }
        // Dispatch browser event so Alpine clicks the hidden file input
        $this->dispatch('open-ocr-picker');
    }

    public function updatedOcrFile(): void
    {
        if (!$this->ocrFile) {
            return;
        }

        // Validate file before sending to Claude
        $this->validate([
            'ocrFile' => 'required|file|mimes:png,jpg,jpeg|mimetypes:image/png,image/jpeg|max:5120',
        ]);

        // Per-minute rate limit (max 5 scans/minute per user — prevents burst abuse)
        $rateLimitKey = 'ocr-scan:' . auth()->id();
        if (!\Illuminate\Support\Facades\RateLimiter::attempt($rateLimitKey, 5, fn () => true, 60)) {
            $this->scanError = 'Too many scans. Please wait a moment before scanning again.';
            $this->ocrFile   = null;
            return;
        }

        // Monthly limit (200/month per Pro user)
        if (\App\Models\AiUsageLog::monthlyOcrCount(auth()->id()) >= 200) {
            $this->scanError = 'You\'ve used all 200 AI scans for this month. Resets on the 1st.';
            $this->ocrFile   = null;
            return;
        }

        $this->aiFilledFields = [];
        $this->scanError      = null;

        try {
            $categories = $this->book->entries()
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->toArray();

            $result = app(\App\Services\AiService::class)->extractFromReceipt(
                imagePath: $this->ocrFile->getRealPath(),
                mimeType:  $this->ocrFile->getMimeType(),
                currency:  $this->business->currency,
                categories: $categories,
            );

            if ($result) {
                if (isset($result['type'])) {
                    $this->entryType = $result['type'];
                    $this->aiFilledFields[] = 'type';
                }
                if (isset($result['amount'])) {
                    $rawAmount        = (float) $result['amount'];
                    $receiptCurrency  = $result['receipt_currency'] ?? null;
                    $bookCurrency     = strtoupper($this->business->currency);

                    if ($receiptCurrency && strtoupper($receiptCurrency) !== $bookCurrency) {
                        $ai = app(\App\Services\AiService::class);
                        $conversion = $ai->convertCurrency($rawAmount, $receiptCurrency, $bookCurrency);
                        if ($conversion) {
                            $this->ocrOriginalAmount = $receiptCurrency . ' ' . number_format($rawAmount, 2);
                            $this->ocrConvertedAt    = '1 ' . $receiptCurrency . ' = ' . number_format($conversion['rate'], 2) . ' ' . $bookCurrency;
                            $this->entryAmount = (string) $conversion['converted_amount'];
                        } else {
                            // Conversion failed — use original amount
                            $this->entryAmount = (string) $rawAmount;
                        }
                    } else {
                        $this->entryAmount = (string) $rawAmount;
                    }
                    $this->aiFilledFields[] = 'amount';
                }
                if (isset($result['date'])) {
                    $this->entryDate = $result['date'];
                    $this->aiFilledFields[] = 'date';
                }
                if (isset($result['description'])) {
                    $this->entryDescription = $result['description'];
                    $this->aiFilledFields[] = 'description';
                }
                if (isset($result['category'])) {
                    $this->entryCategory = $result['category'];
                    $this->aiFilledFields[] = 'category';
                }
                if (isset($result['payment_mode'])) {
                    $this->entryPaymentMode = $result['payment_mode'];
                    $this->aiFilledFields[] = 'payment_mode';
                }

                // Scanned file becomes the entry attachment automatically
                if (empty($this->aiFilledFields) === false) {
                    $this->entryAttachment = $this->ocrFile;
                }
            } else {
                $this->scanError = 'Could not read this receipt. Please fill in the details manually.';
            }
        } catch (\Exception $e) {
            $this->scanError = 'AI scan failed. Please fill in the details manually.';
            \Illuminate\Support\Facades\Log::error('OCR scan failed', ['error' => $e->getMessage()]);
        }

        $this->ocrFile = null;
    }

    public function clearOcrScan(): void
    {
        $this->ocrFile            = null;
        $this->aiFilledFields     = [];
        $this->scanError          = null;
        $this->ocrOriginalAmount  = null;
        $this->ocrConvertedAt     = null;
    }

    // ── End AI Receipt OCR ──────────────────────────────────────────────────

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
        $this->guardEditor();

        $entry = $this->book->entries()->find($id);
        if ($entry) {
            if ($entry->attachment_path) {
                Storage::disk('local')->delete($entry->attachment_path);
            }
            $entry->delete();
        }
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

    // ── Filters ──────────────────────────────────────

    public function openCustomDateModal(): void
    {
        $this->filterDuration = 'custom';
        $this->showCustomDateModal = true;
    }

    public function applyCustomDate(): void
    {
        $this->showCustomDateModal = false;
    }

    public function cancelCustomDate(): void
    {
        if ($this->filterCustomFrom === '' && $this->filterCustomTo === '') {
            $this->filterDuration = 'all_time';
        }
        $this->showCustomDateModal = false;
    }

    public function clearFilters(): void
    {
        $this->filterType          = 'all';
        $this->filterDuration      = 'all_time';
        $this->filterCustomFrom    = '';
        $this->filterCustomTo      = '';
        $this->filterCategories    = [];
        $this->filterPaymentModes  = [];
    }

    // ── Export ───────────────────────────────────────

    public function exportPdf(): void
    {
        if (! $this->business->isPro()) {
            $this->showUpgradeModal = true;
            return;
        }

        $this->redirect(route('businesses.books.export.pdf', [$this->business, $this->book]));
    }

    public function exportCsv(): void
    {
        if (! $this->business->isPro()) {
            $this->showUpgradeModal = true;
            return;
        }

        $this->redirect(route('businesses.books.export.csv', [$this->business, $this->book]));
    }

    // ── Bulk operations ─────────────────────────────

    public function bulkDelete(array $ids): void
    {
        $this->guardEditor();

        $ids = $this->validateBulkIds($ids);
        if (empty($ids)) {
            return;
        }

        $count = $this->book->entries()->whereIn('id', $ids)->delete();
        $this->book->touch();
        $this->showBulkDeleteConfirm = false;
        $this->bulkSuccessMessage = "Deleted {$count} " . ($count === 1 ? 'entry' : 'entries');
        $this->dispatch('bulk-operation-complete');
    }

    public function openBulkBookPicker(string $action): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }
        if (! in_array($action, ['move', 'copy', 'copy_opposite'])) {
            return;
        }

        $this->bulkAction = $action;
        $this->bulkTargetBookId = '';
        $this->showBulkBookPicker = true;
    }

    public function executeBulkBookAction(array $ids): void
    {
        match ($this->bulkAction) {
            'move'          => $this->bulkMoveEntries($ids),
            'copy'          => $this->bulkCopyEntries($ids),
            'copy_opposite' => $this->bulkCopyOppositeEntries($ids),
            default         => null,
        };
    }

    private function bulkMoveEntries(array $ids): void
    {
        $ids = $this->validateBulkIds($ids);
        if (empty($ids) || $this->bulkTargetBookId === '') {
            return;
        }

        $targetBook = $this->business->books()
            ->where('id', $this->bulkTargetBookId)
            ->first();

        if (! $targetBook) {
            return;
        }

        $count = $this->book->entries()->whereIn('id', $ids)->update([
            'book_id' => $targetBook->id,
        ]);

        $this->book->touch();
        $targetBook->touch();
        $this->showBulkBookPicker = false;
        $this->bulkSuccessMessage = "Moved {$count} " . ($count === 1 ? 'entry' : 'entries') . " to {$targetBook->name}";
        $this->dispatch('bulk-operation-complete');
    }

    private function bulkCopyEntries(array $ids): void
    {
        $ids = $this->validateBulkIds($ids);
        if (empty($ids) || $this->bulkTargetBookId === '') {
            return;
        }

        $targetBook = $this->business->books()
            ->where('id', $this->bulkTargetBookId)
            ->first();

        if (! $targetBook) {
            return;
        }

        $entries = $this->book->entries()->whereIn('id', $ids)->get();

        foreach ($entries as $entry) {
            $targetBook->entries()->create([
                'type'         => $entry->type,
                'amount'       => $entry->amount,
                'description'  => $entry->description,
                'date'         => $entry->date,
                'reference'    => $entry->reference,
                'category'     => $entry->category,
                'payment_mode' => $entry->payment_mode,
            ]);
        }

        $targetBook->touch();
        $this->showBulkBookPicker = false;
        $count = $entries->count();
        $this->bulkSuccessMessage = "Copied {$count} " . ($count === 1 ? 'entry' : 'entries') . " to {$targetBook->name}";
        $this->dispatch('bulk-operation-complete');
    }

    private function bulkCopyOppositeEntries(array $ids): void
    {
        $ids = $this->validateBulkIds($ids);
        if (empty($ids) || $this->bulkTargetBookId === '') {
            return;
        }

        $targetBook = $this->business->books()
            ->where('id', $this->bulkTargetBookId)
            ->first();

        if (! $targetBook) {
            return;
        }

        $entries = $this->book->entries()->whereIn('id', $ids)->get();

        foreach ($entries as $entry) {
            $targetBook->entries()->create([
                'type'         => $entry->type === 'in' ? 'out' : 'in',
                'amount'       => $entry->amount,
                'description'  => $entry->description,
                'date'         => $entry->date,
                'reference'    => $entry->reference,
                'category'     => $entry->category,
                'payment_mode' => $entry->payment_mode,
            ]);
        }

        $targetBook->touch();
        $this->showBulkBookPicker = false;
        $count = $entries->count();
        $this->bulkSuccessMessage = "Copied {$count} opposite " . ($count === 1 ? 'entry' : 'entries') . " to {$targetBook->name}";
        $this->dispatch('bulk-operation-complete');
    }

    public function bulkChangeCategory(array $ids): void
    {
        $this->guardEditor();

        $ids = $this->validateBulkIds($ids);
        if (empty($ids)) {
            return;
        }

        $category = $this->bulkNewCategory ?: null;

        $count = $this->book->entries()->whereIn('id', $ids)->update([
            'category' => $category,
        ]);

        $this->book->touch();
        $this->showBulkChangeCategory = false;
        $label = $category ?? 'None';
        $this->bulkSuccessMessage = "Changed category to \"{$label}\" on {$count} " . ($count === 1 ? 'entry' : 'entries');
        $this->bulkNewCategory = '';
        $this->dispatch('bulk-operation-complete');
    }

    public function bulkChangePaymentMode(array $ids): void
    {
        $this->guardEditor();

        $ids = $this->validateBulkIds($ids);
        if (empty($ids)) {
            return;
        }

        $paymentMode = $this->bulkNewPaymentMode ?: null;

        $count = $this->book->entries()->whereIn('id', $ids)->update([
            'payment_mode' => $paymentMode,
        ]);

        $this->book->touch();
        $this->showBulkChangePaymentMode = false;
        $label = $paymentMode ?? 'None';
        $this->bulkSuccessMessage = "Changed payment mode to \"{$label}\" on {$count} " . ($count === 1 ? 'entry' : 'entries');
        $this->bulkNewPaymentMode = '';
        $this->dispatch('bulk-operation-complete');
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

    // ── Recurring entries ──────────────────────────

    public function enableRecurring(): void
    {
        if (! $this->business->isPro()) {
            $this->showUpgradeModal = true;
            return;
        }

        $this->entryRecurring = true;
    }

    public function toggleRecurring(string $id): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $rec = $this->book->recurringEntries()->findOrFail($id);
        $rec->update(['is_active' => ! $rec->is_active]);
    }

    public function deleteRecurring(string $id): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $this->book->recurringEntries()->where('id', $id)->delete();
    }

    // Edit recurring entry inline
    public bool   $showEditRecurring       = false;
    public string $editingRecurringId      = '';
    public string $editRecAmount           = '';
    public string $editRecDescription      = '';
    public string $editRecCategory         = '';
    public string $editRecPaymentMode      = '';
    public string $editRecReference        = '';
    public string $editRecFrequency        = 'monthly';
    public string $editRecEndsAt           = '';

    public function openEditRecurring(string $id): void
    {
        if ($this->userRole === 'viewer') {
            return;
        }

        $rec = $this->book->recurringEntries()->findOrFail($id);

        $this->editingRecurringId  = $id;
        $this->editRecAmount       = rtrim(rtrim((string) $rec->amount, '0'), '.');
        $this->editRecDescription  = $rec->description;
        $this->editRecCategory     = $rec->category ?? '';
        $this->editRecPaymentMode  = $rec->payment_mode ?? '';
        $this->editRecReference    = $rec->reference ?? '';
        $this->editRecFrequency    = $rec->frequency;
        $this->editRecEndsAt       = $rec->ends_at ? $rec->ends_at->format('Y-m-d') : '';
        $this->resetErrorBag();
        $this->showEditRecurring   = true;
    }

    public function updateRecurring(): void
    {
        if ($this->userRole === 'viewer' || ! $this->editingRecurringId) {
            return;
        }

        $this->validate([
            'editRecAmount'      => 'required|numeric|min:0.01|max:999999999.99',
            'editRecDescription' => 'required|string|max:255',
            'editRecCategory'    => 'nullable|string|max:100',
            'editRecPaymentMode' => 'nullable|string|max:100',
            'editRecReference'   => 'nullable|string|max:100',
            'editRecFrequency'   => 'required|in:daily,weekly,monthly,yearly',
            'editRecEndsAt'      => 'nullable|date',
        ]);

        $rec = $this->book->recurringEntries()->findOrFail($this->editingRecurringId);

        $rec->update([
            'amount'       => $this->editRecAmount,
            'description'  => $this->editRecDescription,
            'category'     => $this->editRecCategory ?: null,
            'payment_mode' => $this->editRecPaymentMode ?: null,
            'reference'    => $this->editRecReference ?: null,
            'frequency'    => $this->editRecFrequency,
            'ends_at'      => $this->editRecEndsAt ?: null,
        ]);

        $this->showEditRecurring = false;
        $this->editingRecurringId = '';
    }

    public function closeEditRecurring(): void
    {
        $this->showEditRecurring = false;
        $this->editingRecurringId = '';
    }

    private function buildReportData($entries): array
    {
        // --- Period Summary ---
        $inEntries  = $entries->where('type', 'in');
        $outEntries = $entries->where('type', 'out');

        $totalIn  = $inEntries->reduce(fn ($carry, $e) => bcadd($carry, (string) $e->amount, 2), '0.00');
        $totalOut = $outEntries->reduce(fn ($carry, $e) => bcadd($carry, (string) $e->amount, 2), '0.00');
        $netBalance = bcsub($totalIn, $totalOut, 2);

        $minDate = $entries->min('date');
        $maxDate = $entries->max('date');
        $daySpan = ($minDate && $maxDate) ? max(1, $minDate->diffInDays($maxDate) + 1) : 1;
        $dailyAverage = bcdiv($netBalance, (string) $daySpan, 2);

        $periodSummary = [
            'totalIn'      => $totalIn,
            'totalOut'     => $totalOut,
            'netBalance'   => $netBalance,
            'inCount'      => $inEntries->count(),
            'outCount'     => $outEntries->count(),
            'dailyAverage' => $dailyAverage,
            'daySpan'      => $daySpan,
        ];

        // --- Trend Chart ---
        $trendChart = [];
        if ($entries->count() >= 3 && $minDate && $maxDate) {
            if ($daySpan < 60) {
                $groupFormat = 'Y-m-d';
                $labelFormat = 'd M';
                $step = 'day';
            } elseif ($daySpan < 180) {
                $groupFormat = 'oW'; // ISO year + week
                $labelFormat = 'd M';
                $step = 'week';
            } else {
                $groupFormat = 'Y-m';
                $labelFormat = 'M Y';
                $step = 'month';
            }

            $grouped = $entries->groupBy(fn ($e) => $e->date->format($groupFormat));

            // Build continuous timeline
            $cursor = $minDate->copy()->startOfDay();
            if ($step === 'week') {
                $cursor = $cursor->startOfWeek();
            } elseif ($step === 'month') {
                $cursor = $cursor->startOfMonth();
            }
            $end = $maxDate->copy()->endOfDay();

            while ($cursor->lte($end)) {
                $key = $cursor->format($groupFormat);
                $label = $cursor->format($labelFormat);
                $periodEntries = $grouped->get($key, collect());

                $trendChart[] = [
                    'label' => $label,
                    'in'    => (float) $periodEntries->where('type', 'in')->sum('amount'),
                    'out'   => (float) $periodEntries->where('type', 'out')->sum('amount'),
                ];

                match ($step) {
                    'day'   => $cursor->addDay(),
                    'week'  => $cursor->addWeek(),
                    'month' => $cursor->addMonth(),
                };
            }
        }

        // --- Category Breakdown ---
        $categoryBreakdown = [];
        foreach (['in', 'out'] as $type) {
            $byCategory = $entries->where('type', $type)
                ->groupBy(fn ($e) => $e->category ?: 'Uncategorized')
                ->map(fn ($group) => (float) $group->sum('amount'))
                ->sortDesc();

            $items = [];
            $maxVal = $byCategory->first() ?: 1;
            $count = 0;
            $otherTotal = 0;

            foreach ($byCategory as $name => $total) {
                $count++;
                if ($count <= 5) {
                    $items[] = ['name' => $name, 'total' => $total, 'percent' => ($total / $maxVal) * 100];
                } else {
                    $otherTotal += $total;
                }
            }

            if ($otherTotal > 0) {
                $items[] = ['name' => 'Other', 'total' => $otherTotal, 'percent' => ($otherTotal / $maxVal) * 100];
            }

            $categoryBreakdown[$type] = $items;
        }

        // --- Payment Mode Breakdown ---
        $byMode = $entries
            ->groupBy(fn ($e) => $e->payment_mode ?: 'Not specified')
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->sortDesc();

        $paymentModeBreakdown = [];
        $maxMode = $byMode->first() ?: 1;
        $count = 0;
        $otherTotal = 0;

        foreach ($byMode as $name => $total) {
            $count++;
            if ($count <= 5) {
                $paymentModeBreakdown[] = ['name' => $name, 'total' => $total, 'percent' => ($total / $maxMode) * 100];
            } else {
                $otherTotal += $total;
            }
        }

        if ($otherTotal > 0) {
            $paymentModeBreakdown[] = ['name' => 'Other', 'total' => $otherTotal, 'percent' => ($otherTotal / $maxMode) * 100];
        }

        return compact('periodSummary', 'trendChart', 'categoryBreakdown', 'paymentModeBreakdown');
    }

    public function render()
    {
        // Fetch ALL entries for accurate running balance computation
        $allEntries = $this->book->entries()
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Compute running balance on the full set (unfiltered), starting from opening balance
        $running = (string) ($this->book->opening_balance ?? '0.00');
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

        // Duration / date filter
        $from = $to = null;
        switch ($this->filterDuration) {
            case 'today':
                $from = $to = now()->format('Y-m-d');
                break;
            case 'yesterday':
                $from = $to = now()->subDay()->format('Y-m-d');
                break;
            case 'last_7_days':
                $from = now()->subDays(6)->format('Y-m-d');
                $to   = now()->format('Y-m-d');
                break;
            case 'last_30_days':
                $from = now()->subDays(29)->format('Y-m-d');
                $to   = now()->format('Y-m-d');
                break;
            case 'custom':
                $from = $this->filterCustomFrom ?: null;
                $to   = $this->filterCustomTo   ?: null;
                break;
        }

        if ($from !== null) {
            $entries = $entries->filter(fn ($e) => $e->date->format('Y-m-d') >= $from);
        }
        if ($to !== null) {
            $entries = $entries->filter(fn ($e) => $e->date->format('Y-m-d') <= $to);
        }

        // Category filter
        if (! empty($this->filterCategories)) {
            $cats    = $this->filterCategories;
            $entries = $entries->filter(fn ($e) => in_array($e->category, $cats));
        }

        // Payment mode filter
        if (! empty($this->filterPaymentModes)) {
            $modes   = $this->filterPaymentModes;
            $entries = $entries->filter(fn ($e) => in_array($e->payment_mode, $modes));
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

        // Build report data before reversing (reports need chronological order)
        $reportData = [];
        if ($this->activeTab === 'reports' && $this->business->isPro()) {
            $reportData = $this->buildReportData($entries);
        }

        // Reverse for display: newest first
        $entries = $entries->reverse()->values();

        $totalIn  = $this->book->totalIn();
        $totalOut = $this->book->totalOut();
        $balance  = $this->book->balance();

        $categories   = $this->book->categories()->get();
        $paymentModes = $this->book->paymentModes()->get();

        $recurringEntries = $this->activeTab === 'recurring'
            ? $this->book->recurringEntries()->orderByDesc('created_at')->get()
            : collect();

        return view('livewire.book.show', compact(
            'entries', 'totalIn', 'totalOut', 'balance', 'categories', 'paymentModes', 'reportData', 'recurringEntries'
        ));
    }
}
