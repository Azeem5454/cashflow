<?php

namespace App\Livewire\Book;

use App\Models\AiUsageLog;
use App\Models\Book;
use App\Models\BookActivityLog;
use App\Models\BookCategory;
use App\Models\BookPaymentMode;
use App\Models\Business;
use App\Models\EntryComment;
use App\Notifications\MentionedInComment;
use App\Services\AiService;
use App\Services\BookLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use App\Support\BusinessLock;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;
    use \App\Livewire\Concerns\RequiresVerifiedEmail;
    public Book $book;
    public Business $business;
    // Display-only hint for the Blade view. Locked so the client can't tamper
    // with it; every write re-checks the role from the DB via guardEditor()/guardOwner().
    #[Locked]
    public string $userRole   = '';
    public string $search           = '';
    public string $filterType       = 'all'; // all | in | out

    // Ledger paging — rows shown in the Entries list ("Load more" adds a page)
    public const LEDGER_PAGE_SIZE = 50;
    public int    $perPage          = self::LEDGER_PAGE_SIZE;

    // Duration filter
    public string $filterDuration   = 'all_time'; // all_time | today | yesterday | last_7_days | last_30_days | custom
    public string $filterCustomFrom = '';
    public string $filterCustomTo   = '';
    public bool   $showCustomDateModal = false;
    public bool   $compareEnabled   = false; // Pro: compare with previous period
    public string $compareMode      = 'previous_period'; // previous_period | same_period_last_year

    // Multi-select filters
    public array  $filterCategories    = [];
    public array  $filterPaymentModes  = [];

    // Slide-over state
    public bool $showEntryPanel = false;
    // "More details" disclosure in the slide-over (category, payment method, …)
    public bool $showMoreDetails = false;
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
    public bool    $showEditBook             = false;
    public string  $editBookName             = '';
    public ?string $editBookDescription      = null;
    public string  $editBookOpeningBalance   = '';
    public string  $editBookPeriodStartsAt   = '';
    public string  $editBookPeriodEndsAt     = '';

    public bool    $showDuplicateBook           = false;
    public string  $duplicateBookName           = '';
    public string  $duplicateBookPeriodStartsAt = '';
    public string  $duplicateBookPeriodEndsAt   = '';
    public bool    $duplicateKeepCategories     = true;
    public bool    $duplicateKeepPaymentModes   = true;
    public bool    $duplicateKeepEntries        = false;

    public bool   $showDeleteBook    = false;
    public string $deleteConfirmName = '';

    // Upgrade modal — stores which feature triggered it (empty = hidden)
    public string $upgradeModalFeature = '';

    // Single entry delete confirm modal
    public bool   $showDeleteEntryModal  = false;
    public string $pendingDeleteEntryId  = '';
    public string $pendingDeleteType     = '';
    public string $pendingDeleteAmount   = '';
    public string $pendingDeleteDate     = '';
    public string $pendingDeleteDesc     = '';

    // Comment delete confirm modal
    public bool   $showDeleteCommentModal  = false;
    public string $pendingDeleteCommentId  = '';
    public string $pendingDeleteCommentExcerpt = '';

    // Bulk operations
    public bool   $showBulkDeleteConfirm     = false;
    public bool   $showBulkBookPicker        = false;
    public string $bulkAction                = '';       // 'move' | 'copy' | 'copy_opposite'
    public string $bulkTargetBookId          = '';
    public bool   $showBulkChangeCategory    = false;
    public bool   $showBulkChangePaymentMode = false;
    public string $bulkNewCategory           = '';
    public string $bulkNewPaymentMode        = '';

    // AI auto-categorization
    public string $aiCategorySuggestion = '';
    public bool   $showCategoryChip     = false;
    // Last description we already asked the AI about — prevents the same
    // (blocking) Claude call from re-firing on every focus-out/blur, which
    // used to stack 10s requests ahead of the user's Save click.
    public string $aiSuggestedFor       = '';

    // AI cash flow insights
    public bool   $aiInsightsLoading      = false;
    public array  $aiInsightsData         = [];   // decoded JSON from cache
    public string $aiInsightsError        = '';   // 'failed' | 'not_enough_data' | ''
    public bool   $aiInsightsLimitReached = false;
    public string $aiInsightsGeneratedAt  = '';   // human-readable "X hours ago"

    // Reports tab
    public string $activeTab = 'entries'; // 'entries' | 'reports' | 'recurring' | 'activity'

    // Activity log filters & pagination
    public int    $activityPerPage       = 25;
    public string $activityFilterUserId  = '';
    public string $activityFilterAction  = ''; // '' | 'created' | 'updated' | 'deleted' | 'bulk'

    // Recurring entry form (in slide-over)
    public bool   $entryRecurring  = false;
    public string $entryFrequency  = 'weekly';
    public string $entryEndsAt     = '';
    public bool   $entryRunForever = false;

    // ── Email report settings modal ─────────────────────────────────
    public bool   $showEmailReportModal     = false;
    public string $emailReportFrequency     = 'weekly';
    public string $emailReportRecipients    = ''; // comma-separated emails
    public bool   $emailReportActive        = false;
    public bool   $hasExistingSchedule      = false;
    public string $emailReportLastSent      = ''; // human-readable "Last sent: ..."
    public bool   $sendingTestReport        = false;

    // ── Comments panel ────────────────────────────────────────────
    public bool   $showCommentPanel       = false;
    public string $commentingEntryId      = '';
    public string $commentingEntryDesc    = '';
    public string $commentingEntryAmount  = '';
    public string $commentingEntryType    = '';
    public string $commentBody            = '';
    public bool   $showMentionDropdown    = false;
    public string $mentionQuery           = '';

    public function mount(Business $business, Book $book): void
    {
        $this->business  = $business;
        $this->book      = $book;
        $this->userRole  = $business->userRole(auth()->user()) ?? 'viewer';
        $this->entryDate = now()->format('Y-m-d');

        // If arriving directly on the reports tab (e.g. browser refresh), apply limit + load cache
        if ($this->activeTab === 'reports' && $this->business->isPro()) {
            if ($this->insightsDailyLimitReached()) {
                $this->aiInsightsLimitReached = true;
            }
            $this->loadCachedInsights();
        }
    }

    // ─── AI Insights ─────────────────────────────────────────────────────────

    /**
     * Called when the user switches to the Reports tab.
     * Load from cache instantly if fresh; otherwise queue a generation.
     */
    public function updatedActiveTab(string $value): void
    {
        // Reset activity pagination when switching away and back
        if ($value === 'activity') {
            $this->activityPerPage      = 25;
            $this->activityFilterUserId = '';
            $this->activityFilterAction = '';
        }

        if ($value !== 'reports' || ! $this->business->isPro()) {
            return;
        }

        // Always check limit first — it applies across all books, not just the current one
        if ($this->insightsDailyLimitReached()) {
            $this->aiInsightsLimitReached = true;
        }

        $book = $this->book->fresh();

        // Fresh cache (< 24 h) → show immediately (with limit warning if applicable)
        if ($book->ai_insights_generated_at &&
            $book->ai_insights_generated_at->diffInHours(now()) < 24) {
            $this->loadCachedInsights();
            return;
        }

        // No fresh cache + limit reached → show stale cache or limit-only warning
        if ($this->aiInsightsLimitReached) {
            $this->loadCachedInsights();
            return;
        }

        // No cache, no limit — trigger shimmer; x-init fires generateInsights()
        $this->aiInsightsLoading = true;
    }

    /**
     * Fired by wire:init on the shimmer element — performs the actual API call.
     */
    public function generateInsights(): void
    {
        if (! $this->business->isPro()) {
            return;
        }

        // Per-user burst: max 1 call per 60 s
        $burstKey = 'ai_insights_burst:' . auth()->id();
        if (RateLimiter::tooManyAttempts($burstKey, 1)) {
            $this->aiInsightsLoading = false;
            $this->loadCachedInsights();
            return;
        }
        RateLimiter::hit($burstKey, 60);

        // Daily cap: 10 insights/day per user
        if ($this->insightsDailyLimitReached()) {
            $this->aiInsightsLimitReached = true;
            $this->aiInsightsLoading      = false;
            $this->loadCachedInsights();
            return;
        }

        $this->aiInsightsError = '';

        try {
            $allEntries = $this->book->entries()->get();

            if ($allEntries->count() < 3) {
                $this->aiInsightsError   = 'not_enough_data';
                $this->aiInsightsLoading = false;
                return;
            }

            // Shared with the mobile API (GET /api/v1/books/{id}/insights).
            $result = app(\App\Services\BookInsightsService::class)->generate($this->book, $allEntries);

            if ($result) {
                $this->aiInsightsData = $result;
                $this->book->update([
                    'ai_insights_cache'        => json_encode($result),
                    'ai_insights_generated_at' => now(),
                ]);
                $this->aiInsightsGeneratedAt = 'Just now';
            } else {
                $this->aiInsightsError = 'failed';
            }
        } catch (\Exception $e) {
            Log::warning('AI insights error', ['error' => $e->getMessage()]);
            $this->aiInsightsError = 'failed';
        } finally {
            $this->aiInsightsLoading = false;
        }
    }

    private function loadCachedInsights(): void
    {
        $book = $this->book->fresh();

        if ($book->ai_insights_cache) {
            $decoded = json_decode($book->ai_insights_cache, true);
            if (is_array($decoded)) {
                $this->aiInsightsData = $decoded;
            }
        }

        if ($book->ai_insights_generated_at) {
            $diff = $book->ai_insights_generated_at->diffForHumans();
            $this->aiInsightsGeneratedAt = $diff;
        }
    }

    private function insightsDailyLimitReached(): bool
    {
        return app(\App\Services\BookInsightsService::class)->dailyLimitReached((string) auth()->id());
    }

    public function openAddEntry(string $type = 'in'): void
    {
        if (! $this->canEdit()) {
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
        // NLP state resets too — otherwise a previous parse's "Filled 3 fields"
        // confirmation or stale error message persists on the next new entry.
        $this->nlpInput              = '';
        $this->nlpError              = '';
        $this->nlpFilledFields       = [];
        $this->entryRecurring      = false;
        $this->entryFrequency      = 'weekly';
        $this->entryEndsAt         = $this->book->period_ends_at?->format('Y-m-d') ?? '';
        $this->entryRunForever     = false;
        $this->aiCategorySuggestion = '';
        $this->showCategoryChip     = false;
        $this->aiSuggestedFor       = '';
        $this->showMoreDetails      = false;
        $this->resetErrorBag();
        $this->showEntryPanel      = true;
        $this->dispatch('entry-date-updated', date: $this->entryDate);
    }

    public function openEditEntry(string $id): void
    {
        if (! $this->canEdit()) {
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
        $this->showMoreDetails         = $this->entryCategory !== '' || $this->entryPaymentMode !== ''
                                        || $this->entryReference !== '' || $entry->attachment_path !== null;
        $this->resetErrorBag();
        $this->showEntryPanel      = true;
        $this->dispatch('entry-date-updated', date: $this->entryDate);
    }

    /**
     * Re-fetch the user's role from the DB on every call.
     * Prevents stale Livewire state from being exploited if a role was
     * changed by an owner while the user had an active session.
     */
    private function logActivity(string $action, ?string $entryId = null, array $meta = []): void
    {
        try {
            BookActivityLog::create([
                'book_id'  => $this->book->id,
                'user_id'  => auth()->id(),
                'action'   => $action,
                'entry_id' => $entryId,
                'meta'     => $meta ?: null,
            ]);
        } catch (\Throwable) {
            // Never fail a user action because of audit logging
        }
    }

    /**
     * Role re-read from the business_user pivot on every call — never trusted
     * from Livewire state (the client can't change $userRole, it's #[Locked],
     * but a role may also have changed since the page loaded). A Free owner's
     * locked extra business yields null: Livewire updates don't pass through
     * the business.unlocked route middleware, so the lock is re-checked here.
     */
    private function currentRole(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $role = \Illuminate\Support\Facades\DB::table('business_user')
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
            'entryDescription' => 'nullable|string|max:255',
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
            'description'  => trim($this->entryDescription) !== '' ? trim($this->entryDescription) : null,
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
            $entry = $this->book->entries()->find($this->editingEntryId);
        } else {
            if ($attachmentPath) {
                $data['attachment_path'] = $attachmentPath;
            }
            $data['created_by'] = auth()->id();
            $entry = $this->book->entries()->create($data);
        }

        $this->book->touch();
        $this->entryAttachment = null;

        // Auto-save AI-filled category/payment mode to the book's lists
        // so they appear in future entries without the user having to add them manually
        if ($data['category']) {
            $exists = $this->book->categories()
                ->whereRaw('LOWER(name) = ?', [strtolower($data['category'])])
                ->exists();
            if (! $exists) {
                $this->book->categories()->create(['name' => $data['category']]);
            }
        }

        if ($data['payment_mode']) {
            $exists = $this->book->paymentModes()
                ->whereRaw('LOWER(name) = ?', [strtolower($data['payment_mode'])])
                ->exists();
            if (! $exists) {
                $this->book->paymentModes()->create(['name' => $data['payment_mode']]);
            }
        }

        return $entry;
    }

    public function saveEntry(): void
    {
        $this->guardEditor();

        $isNew = ! $this->editingEntryId;
        $entry = $this->doSaveEntry();

        if ($entry) {
            $this->logActivity($isNew ? 'entry_created' : 'entry_updated', $entry->id, [
                'type'        => $entry->type,
                'amount'      => $entry->amount,
                'description' => $entry->description,
            ]);
        }

        // Create recurring entry template if toggled on for new entries
        if ($isNew && $entry && $this->entryRecurring && $this->business->isPro()) {
            $nextRun = Carbon::parse($this->entryDate);
            match ($this->entryFrequency) {
                'daily'    => $nextRun->addDay(),
                'weekly'   => $nextRun->addWeek(),
                'biweekly' => $nextRun->addWeeks(2),
            };

            $recurringEntry = $this->book->recurringEntries()->create([
                'type'         => $this->entryType,
                'amount'       => $this->entryAmount,
                'description'  => trim($this->entryDescription) !== '' ? trim($this->entryDescription) : null,
                'category'     => $this->entryCategory ?: null,
                'payment_mode' => $this->entryPaymentMode ?: null,
                'reference'    => $this->entryReference ?: null,
                'frequency'    => $this->entryFrequency,
                'starts_at'    => $this->entryDate,
                'next_run_at'  => $nextRun->format('Y-m-d'),
                'ends_at'      => (!$this->entryRunForever && $this->entryEndsAt) ? $this->entryEndsAt : null,
                'status'       => 'active',
            ]);

            // Link the initial entry to the recurring rule
            $entry->update(['recurring_entry_id' => $recurringEntry->id]);

            $this->logActivity('recurring_created', $entry->id, [
                'description' => $recurringEntry->description,
                'frequency'   => $recurringEntry->frequency,
            ]);
        }

        // If editing an entry linked to a recurring rule, silently detach it —
        // the user is editing this specific entry only. The rule continues unchanged.
        if (! $isNew && $entry && $entry->recurring_entry_id) {
            $entry->update(['recurring_entry_id' => null]);
        }

        $this->showEntryPanel = false;
        $this->dispatch('entry-saved', message: $isNew ? 'Entry added successfully.' : 'Entry edited successfully.');
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
        $this->guardEditor();

        // Free: 10 AI entries/month (shared with typed entries); Pro: 200 scans.
        if ($this->aiQuotaDenied(\App\Services\AiQuota::TYPE_SCAN)) {
            return;
        }
        // Dispatch browser event so Alpine clicks the hidden file input
        $this->dispatch('open-ocr-picker');
    }

    public function updatedOcrFile(): void
    {
        $this->guardEditor();

        if (!$this->ocrFile) {
            return;
        }

        // Monthly AI allowance (AiQuota) — checked before validation or any
        // (paid) Claude call. Free: 10 AI entries/month; Pro: 200 scans.
        if ($this->aiQuotaDenied(\App\Services\AiQuota::TYPE_SCAN)) {
            $this->ocrFile = null;
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

    /**
     * Apply the AI entry quota (App\Services\AiQuota). Free plan out of
     * entries → upgrade modal ('ai'); Pro fair-use caps → inline error.
     * Returns true when the action must stop.
     */
    private function aiQuotaDenied(string $type): bool
    {
        $denied = \App\Services\AiQuota::check(auth()->user(), $this->business, $type);
        if (! $denied) {
            return false;
        }

        if ($denied->isUpgradeable()) {
            $this->upgradeModalFeature = 'ai';
        } elseif ($type === \App\Services\AiQuota::TYPE_SCAN) {
            $this->scanError = $denied->getMessage();
        } else {
            $this->nlpError = $denied->getMessage();
        }

        return true;
    }

    public function clearOcrScan(): void
    {
        $this->ocrFile            = null;
        $this->aiFilledFields     = [];
        $this->scanError          = null;
        $this->ocrOriginalAmount  = null;
        $this->ocrConvertedAt     = null;
    }

    // ── Activity log ────────────────────────────────────────────────────────

    public function loadMoreActivity(): void
    {
        $this->activityPerPage += 25;
    }

    public function updatedActivityFilterUserId(): void
    {
        $this->activityPerPage = 25; // reset to first page on filter change
    }

    public function updatedActivityFilterAction(): void
    {
        $this->activityPerPage = 25;
    }

    // ── End AI Receipt OCR ──────────────────────────────────────────────────

    public function saveAndAddNew(): void
    {
        $this->guardEditor();

        if ($this->editingEntryId) {
            return;
        }

        $type  = $this->entryType;
        $entry = $this->doSaveEntry();

        if ($entry) {
            $this->logActivity('entry_created', $entry->id, [
                'type'        => $entry->type,
                'amount'      => $entry->amount,
                'description' => $entry->description,
            ]);
        }

        // Reset form but keep panel open with same type
        $this->editingEntryId     = null;
        $this->entryType          = $type;
        $this->entryAmount        = '';
        $this->entryDescription   = '';
        $this->entryDate          = now()->format('Y-m-d');
        $this->entryReference     = '';
        $this->entryCategory      = '';
        $this->entryPaymentMode   = '';
        $this->entryRecurring     = false;
        $this->entryRunForever    = false;
        $this->aiFilledFields     = [];
        $this->ocrOriginalAmount  = null;
        $this->ocrConvertedAt     = null;
        $this->resetErrorBag();

        $this->dispatch('entry-saved', message: 'Saved. Continue adding more entries.');
    }

    public function confirmDeleteEntry(string $id): void
    {
        $this->guardEditor();

        $entry = $this->book->entries()->find($id);
        if (! $entry) return;

        $this->pendingDeleteEntryId = $id;
        $this->pendingDeleteType    = $entry->type === 'in' ? 'Cash In' : 'Cash Out';
        $this->pendingDeleteAmount  = number_format((float) $entry->amount, 2);
        $this->pendingDeleteDate    = $entry->date->format('d M, Y');
        $this->pendingDeleteDesc    = $entry->displayLabel();
        $this->showDeleteEntryModal = true;
    }

    public function deleteEntry(): void
    {
        $this->guardEditor();

        $entry = $this->book->entries()->find($this->pendingDeleteEntryId);
        if ($entry) {
            $this->logActivity('entry_deleted', null, [
                'type'        => $entry->type,
                'amount'      => $entry->amount,
                'description' => $entry->description,
                'category'    => $entry->category,
            ]);
            if ($entry->attachment_path) {
                Storage::disk('local')->delete($entry->attachment_path);
            }
            $entry->delete();
        }
        $this->book->touch();
        $this->showDeleteEntryModal = false;
        $this->pendingDeleteEntryId = '';
        $this->dispatch('entry-saved', message: 'Entry deleted.');
    }

    public function addCategory(): void
    {
        $this->guardEditor();

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
        $this->guardEditor();

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
        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'daterange';
            return;
        }
        $this->filterDuration = 'custom';
        $this->showCustomDateModal = true;
    }

    public function toggleComparison(): void
    {
        if (! $this->business->isPro()) return;
        $this->compareEnabled = ! $this->compareEnabled;
    }

    public function applyCustomDate(): void
    {
        if (! $this->business->isPro()) {
            $this->filterDuration      = 'all_time';
            $this->filterCustomFrom    = '';
            $this->filterCustomTo      = '';
            $this->showCustomDateModal = false;
            $this->upgradeModalFeature = 'daterange';
            return;
        }

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
        $this->compareEnabled      = false;
        $this->compareMode         = 'previous_period';
        $this->search              = '';
        $this->perPage             = self::LEDGER_PAGE_SIZE;
    }

    // ── Export ───────────────────────────────────────

    public function exportPdf(): void
    {
        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'export';
            return;
        }

        $this->redirect(route('businesses.books.export.pdf', [$this->business, $this->book]));
    }

    public function exportCsv(): void
    {
        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'export';
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

        // Capture details before deleting so they can appear in the activity feed
        $entriesForLog = $this->book->entries()
            ->whereIn('id', $ids)
            ->get(['id', 'type', 'amount', 'description']);
        $count = $this->book->entries()->whereIn('id', $ids)->delete();

        $logMeta = ['count' => $count];
        if ($count === 1 && $entriesForLog->isNotEmpty()) {
            $e = $entriesForLog->first();
            $logMeta['type']        = $e->type;
            $logMeta['amount']      = $e->amount;
            $logMeta['description'] = $e->description;
        }
        $this->logActivity('bulk_delete', null, $logMeta);
        $this->book->touch();
        $this->showBulkDeleteConfirm = false;
        $this->dispatch('bulk-operation-complete');
        $this->dispatch('entry-saved', message: "Deleted {$count} " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    public function openBulkBookPicker(string $action): void
    {
        $this->guardEditor();
        if (! in_array($action, ['move', 'copy', 'copy_opposite'])) {
            return;
        }

        $this->bulkAction = $action;
        $this->bulkTargetBookId = '';
        $this->showBulkBookPicker = true;
    }

    public function executeBulkBookAction(array $ids): void
    {
        // Move/copy write to this book AND the target book (same business,
        // enforced in each helper via $this->business->books()) — editor+ only.
        $this->guardEditor();

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

        $this->logActivity('bulk_move', null, ['count' => $count, 'target_book' => $targetBook->name]);
        $this->book->touch();
        $targetBook->touch();
        $this->showBulkBookPicker = false;
        $this->dispatch('bulk-operation-complete');
        $this->dispatch('entry-saved', message: "Moved {$count} " . ($count === 1 ? 'entry' : 'entries') . " to {$targetBook->name}.");
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

        $count = $entries->count();
        $this->logActivity('bulk_copy', null, ['count' => $count, 'target_book' => $targetBook->name]);
        $targetBook->touch();
        $this->showBulkBookPicker = false;
        $this->dispatch('bulk-operation-complete');
        $this->dispatch('entry-saved', message: "Copied {$count} " . ($count === 1 ? 'entry' : 'entries') . " to {$targetBook->name}.");
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

        $count = $entries->count();
        $this->logActivity('bulk_copy_opposite', null, ['count' => $count, 'target_book' => $targetBook->name]);
        $targetBook->touch();
        $this->showBulkBookPicker = false;
        $this->dispatch('bulk-operation-complete');
        $this->dispatch('entry-saved', message: "Copied {$count} opposite " . ($count === 1 ? 'entry' : 'entries') . " to {$targetBook->name}.");
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

        $this->logActivity('bulk_change_category', null, ['count' => $count, 'category' => $category ?? 'None']);
        $this->book->touch();
        $this->showBulkChangeCategory = false;
        $label = $category ?? 'None';
        $this->bulkNewCategory = '';
        $this->dispatch('bulk-operation-complete');
        $this->dispatch('entry-saved', message: "Category updated to \"{$label}\" on {$count} " . ($count === 1 ? 'entry' : 'entries') . '.');
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

        $this->logActivity('bulk_change_payment_mode', null, ['count' => $count, 'payment_mode' => $paymentMode ?? 'None']);
        $this->book->touch();
        $this->showBulkChangePaymentMode = false;
        $label = $paymentMode ?? 'None';
        $this->bulkNewPaymentMode = '';
        $this->dispatch('bulk-operation-complete');
        $this->dispatch('entry-saved', message: "Payment method updated to \"{$label}\" on {$count} " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    // ── Book management ──────────────────────────────

    public function openEditBook(): void
    {
        if (! $this->canEdit()) return;

        $this->editBookName           = $this->book->name;
        $this->editBookDescription    = $this->book->description;
        $this->editBookOpeningBalance = $this->book->opening_balance ? (string) $this->book->opening_balance : '';
        $this->editBookPeriodStartsAt = $this->book->period_starts_at?->format('Y-m-d') ?? '';
        $this->editBookPeriodEndsAt   = $this->book->period_ends_at?->format('Y-m-d') ?? '';
        $this->resetErrorBag();
        $this->showEditBook = true;
    }

    public function saveEditBook(string $periodStart = '', string $periodEnd = ''): void
    {
        $this->guardEditor();

        $this->validate([
            'editBookName'           => 'required|string|max:100',
            'editBookDescription'    => 'nullable|string|max:500',
            'editBookOpeningBalance' => 'nullable|numeric|min:-999999999.99|max:999999999.99',
        ]);

        $this->book->update([
            'name'             => $this->editBookName,
            'description'      => $this->editBookDescription ?: null,
            'opening_balance'  => $this->editBookOpeningBalance ?: 0,
            'period_starts_at' => $periodStart ?: null,
            'period_ends_at'   => $periodEnd ?: null,
        ]);

        $this->showEditBook = false;
        $this->dispatch('entry-saved', message: 'Book updated successfully.');
    }

    public function openDuplicateBook(): void
    {
        if (! $this->canEdit()) return;

        $this->duplicateBookName           = $this->book->name . ' (Copy)';
        $this->duplicateBookPeriodStartsAt = '';
        $this->duplicateBookPeriodEndsAt   = '';
        $this->duplicateKeepCategories     = true;
        $this->duplicateKeepPaymentModes   = true;
        $this->duplicateKeepEntries        = false;
        $this->resetErrorBag();
        $this->showDuplicateBook = true;
    }

    public function executeDuplicate(string $periodStart = '', string $periodEnd = ''): void
    {
        $this->guardEditor();

        $this->validate([
            'duplicateBookName' => 'required|string|max:100',
        ]);

        $newBook = $this->business->books()->create([
            'name'             => $this->duplicateBookName,
            'description'      => $this->book->description,
            'opening_balance'  => $this->book->opening_balance ?? 0,
            'period_starts_at' => $periodStart ?: null,
            'period_ends_at'   => $periodEnd ?: null,
        ]);

        if ($this->duplicateKeepCategories) {
            foreach ($this->book->categories()->get() as $cat) {
                $newBook->categories()->create(['name' => $cat->name]);
            }
        }

        if ($this->duplicateKeepPaymentModes) {
            foreach ($this->book->paymentModes()->get() as $pm) {
                $newBook->paymentModes()->create(['name' => $pm->name]);
            }
        }

        if ($this->duplicateKeepEntries) {
            foreach ($this->book->entries()->get() as $entry) {
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
        $this->redirect(route('businesses.books.show', [$this->business, $newBook]));
    }

    public function openDeleteBook(): void
    {
        // Deleting a whole book is owner-only (editors keep create/edit/duplicate).
        if (! $this->isOwner()) return;

        $this->deleteConfirmName = '';
        $this->resetErrorBag();
        $this->showDeleteBook = true;
    }

    public function deleteBook(): void
    {
        $this->guardOwner();

        if (trim($this->deleteConfirmName) !== $this->book->name) {
            $this->addError('deleteConfirmName', 'Book name does not match.');
            return;
        }

        $businessId = $this->business->id;
        $this->book->entries()->delete();
        $this->book->categories()->delete();
        $this->book->paymentModes()->delete();
        $this->book->reportSchedule?->delete();
        $this->book->delete();

        $this->redirect(route('businesses.show', $businessId));
    }

    // ── Email report settings ────────────────────────────────────────────

    public function openEmailReportModal(): void
    {
        $this->guardEditor();

        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'emailreports';
            return;
        }

        $schedule = $this->book->reportSchedule;

        if ($schedule) {
            $this->emailReportFrequency  = $schedule->frequency;
            $this->emailReportRecipients = implode(', ', $schedule->recipients ?? []);
            $this->emailReportActive     = $schedule->is_active;
            $this->hasExistingSchedule   = true;
            $this->emailReportLastSent   = $schedule->last_sent_at
                ? $schedule->last_sent_at->diffForHumans()
                : '';
        } else {
            $this->emailReportFrequency  = 'weekly';
            $this->emailReportRecipients = auth()->user()->email;
            $this->emailReportActive     = true;
            $this->hasExistingSchedule   = false;
            $this->emailReportLastSent   = '';
        }

        $this->sendingTestReport    = false;
        $this->emailVerificationRequired = false;
        $this->showEmailReportModal = true;
    }

    public function saveEmailReport(): void
    {
        $this->guardEditor();

        if (! $this->business->isPro()) {
            return;
        }

        // Soft verification: reports email other people.
        if (! $this->ensureVerifiedEmail()) {
            return;
        }

        $validated = $this->validate([
            'emailReportFrequency'  => ['required', 'in:weekly,monthly'],
            'emailReportRecipients' => ['required', 'string', 'max:500'],
        ]);

        // Parse and validate each email
        $emails = array_filter(array_map(
            fn ($e) => strtolower(trim($e)),
            explode(',', $this->emailReportRecipients)
        ));

        if (empty($emails)) {
            $this->addError('emailReportRecipients', 'At least one recipient email is required.');
            return;
        }

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError('emailReportRecipients', "Invalid email address: {$email}");
                return;
            }
        }

        if (count($emails) > 10) {
            $this->addError('emailReportRecipients', 'Maximum 10 recipient emails allowed.');
            return;
        }

        $schedule  = $this->book->reportSchedule;
        $isNewSchedule = ! $schedule;

        if ($schedule) {
            $schedule->update([
                'frequency'  => $this->emailReportFrequency,
                'recipients' => array_values($emails),
                'is_active'  => $this->emailReportActive,
            ]);
        } else {
            $schedule = $this->book->reportSchedule()->create([
                'frequency'  => $this->emailReportFrequency,
                'recipients' => array_values($emails),
                'is_active'  => $this->emailReportActive,
            ]);
        }

        // Auto-send the first report immediately on new schedule creation
        if ($isNewSchedule && $this->emailReportActive) {
            try {
                $reportData = $schedule->buildReportData();
                foreach ($schedule->recipients as $recipientEmail) {
                    \Illuminate\Support\Facades\Mail::to($recipientEmail)->queue(
                        new \App\Mail\BookEmailReport($this->book, $reportData, $schedule->frequency)
                    );
                }
                $schedule->update(['last_sent_at' => now()]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('First email report send failed', [
                    'book_id' => $this->book->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->showEmailReportModal = false;
        $message = $this->emailReportActive
            ? "Email reports enabled — {$this->emailReportFrequency} to " . count($emails) . " recipient(s)."
            : 'Email reports paused.';
        if ($isNewSchedule && $this->emailReportActive) {
            $message = "Email reports enabled. First report sent to " . count($emails) . " recipient(s).";
        }
        $this->dispatch('entry-saved', message: $message);
    }

    public function sendTestReport(): void
    {
        $this->guardEditor();

        if (! $this->business->isPro()) {
            return;
        }

        if (! $this->ensureVerifiedEmail()) {
            return;
        }

        $this->sendingTestReport = true;

        try {
            // Build report data using a temporary schedule object (doesn't need to be saved)
            $tempSchedule = $this->book->reportSchedule ?? new \App\Models\ReportSchedule(['book_id' => $this->book->id]);
            $tempSchedule->setRelation('book', $this->book);
            $reportData = $tempSchedule->buildReportData();

            $frequency = $this->emailReportFrequency ?: 'weekly';

            \Illuminate\Support\Facades\Mail::to(auth()->user()->email)->queue(
                new \App\Mail\BookEmailReport($this->book, $reportData, $frequency)
            );

            $this->sendingTestReport = false;
            $this->dispatch('entry-saved', message: 'Test report sent to ' . auth()->user()->email);
        } catch (\Throwable $e) {
            $this->sendingTestReport = false;
            $this->dispatch('entry-saved', message: 'Failed to send test report. Please try again.');
        }
    }

    public function deleteEmailReport(): void
    {
        $this->guardEditor();

        $schedule = $this->book->reportSchedule;
        if ($schedule) {
            $schedule->delete();
        }

        $this->hasExistingSchedule   = false;
        $this->showEmailReportModal  = false;
        $this->dispatch('entry-saved', message: 'Email reports removed.');
    }

    // ── AI auto-categorization ─────────────────────

    public function suggestCategory(): void
    {
        // Free for every plan — not counted against the AI entry quota; only
        // the per-user burst limit below applies.

        // Only people who can write entries get (paid) suggestions — silent.
        if (! $this->canEdit()) {
            return;
        }

        $desc = trim($this->entryDescription);
        if (strlen($desc) < 3) {
            return;
        }

        // Don't suggest if user already picked a category
        if (! empty($this->entryCategory)) {
            return;
        }

        // Already asked the AI about this exact description — don't fire the
        // blocking API call again (blur fires every time focus leaves the
        // field, e.g. when the user clicks Save or the recurring toggle).
        if ($desc === $this->aiSuggestedFor) {
            return;
        }
        $this->aiSuggestedFor = $desc;

        // Per-user burst limit (shared with the API) — silent when exceeded.
        if (! RateLimiter::attempt(self::SUGGEST_RATE_KEY . auth()->id(), self::SUGGEST_RATE_LIMIT, fn () => true, 60)) {
            return;
        }

        $categories = $this->book->categories()->pluck('name')->toArray();

        try {
            $result = app(\App\Services\AiService::class)
                ->suggestCategory($desc, $this->entryType, $categories);

            if ($result && ! empty($result['category'])) {
                $this->aiCategorySuggestion = $result['category'];
                $this->showCategoryChip     = true;
            }
        } catch (\Exception) {
            // Fail silently — never interrupt the user's flow
        }
    }

    public function applyAiCategory(): void
    {
        $this->guardEditor();

        if (empty($this->aiCategorySuggestion)) {
            return;
        }

        $this->entryCategory = $this->aiCategorySuggestion;

        // Auto-save to book's category list if new
        $exists = $this->book->categories()
            ->whereRaw('LOWER(name) = ?', [strtolower($this->aiCategorySuggestion)])
            ->exists();
        if (! $exists) {
            $this->book->categories()->create(['name' => $this->aiCategorySuggestion]);
        }

        $this->showCategoryChip     = false;
        $this->aiCategorySuggestion = '';
    }

    public function dismissCategoryChip(): void
    {
        $this->showCategoryChip     = false;
        $this->aiCategorySuggestion = '';
    }

    // ── Natural Language Entry ───────────────────────────────────────────────

    public string $nlpInput = '';            // user's "Paid 5000 for rent yesterday"
    public bool   $nlpLoading = false;
    public string $nlpError = '';
    public array  $nlpFilledFields = [];     // which fields were auto-filled this round

    /** Per-user burst rate limit on NLP calls (daily/monthly caps live in AiQuota). */
    public const NLP_DAILY_LIMIT  = \App\Services\AiQuota::PRO_DAILY_TYPED;
    public const NLP_BURST_LIMIT  = 10;
    public const NLP_BURST_WINDOW = 60;     // seconds

    /** Per-user AI category suggestions per minute (same key as the API). */
    public const SUGGEST_RATE_LIMIT = 30;
    public const SUGGEST_RATE_KEY   = 'ai-suggest-category:';

    public function parseEntryText(): void
    {
        $this->guardEditor();

        $this->nlpError        = '';
        $this->nlpFilledFields = [];

        // Monthly AI allowance (Free: 10 shared with scans) / Pro daily cap.
        if ($this->aiQuotaDenied(\App\Services\AiQuota::TYPE_TYPED)) {
            return;
        }

        $text = trim($this->nlpInput);
        if (mb_strlen($text) < 4) {
            $this->nlpError = 'Describe the transaction in a few words.';
            return;
        }
        if (mb_strlen($text) > 500) {
            $this->nlpError = 'Keep it under 500 characters.';
            return;
        }

        // Burst rate limit: per-user, per-minute. Increment BEFORE the daily-cap
        // DB query so repeated error-path requests still count against the
        // burst bucket (defends against spam-by-error).
        $burstKey = 'nlp-burst:' . auth()->id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($burstKey, self::NLP_BURST_LIMIT)) {
            $this->nlpError = 'You\'re parsing very quickly — wait a moment.';
            return;
        }
        \Illuminate\Support\Facades\RateLimiter::hit($burstKey, self::NLP_BURST_WINDOW);

        $this->nlpLoading = true;

        try {
            $categories   = $this->book->categories()->pluck('name')->toArray();
            $paymentModes = $this->book->paymentModes()->pluck('name')->toArray();

            $parsed = app(\App\Services\AiService::class)->parseNaturalLanguage(
                $text,
                $this->business->currency ?: 'USD',
                $categories,
                $paymentModes,
            );

            if (! $parsed) {
                $this->nlpError = "Couldn't parse that. Try something like \"Paid 5000 for rent yesterday\" or fill the form manually.";
                return;
            }

            $filled = [];

            if (isset($parsed['type'])) {
                $this->entryType = $parsed['type'];
                $filled[]        = 'type';
            }

            if (isset($parsed['amount'])) {
                $this->entryAmount = (string) $parsed['amount'];
                $filled[]          = 'amount';
            }

            if (! empty($parsed['date'])) {
                $this->entryDate = $parsed['date'];
                $filled[]        = 'date';
            }

            if (! empty($parsed['description'])) {
                $this->entryDescription = $parsed['description'];
                $filled[]               = 'description';
            }

            if (! empty($parsed['category'])) {
                $this->entryCategory = $parsed['category'];
                $filled[]            = 'category';
                // Case-insensitive dedupe + atomic create. Prevents a race
                // where two concurrent parses each think they need to insert
                // the same category.
                $existing = $this->book->categories()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($parsed['category'])])
                    ->first();
                if (! $existing) {
                    try {
                        $this->book->categories()->create(['name' => $parsed['category']]);
                    } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                        // Another save already inserted it — safe to ignore.
                    }
                }
            }

            if (! empty($parsed['payment_mode'])) {
                $this->entryPaymentMode = $parsed['payment_mode'];
                $filled[]               = 'payment_mode';
                $existing = $this->book->paymentModes()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($parsed['payment_mode'])])
                    ->first();
                if (! $existing) {
                    try {
                        $this->book->paymentModes()->create(['name' => $parsed['payment_mode']]);
                    } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                        // Another save already inserted it — safe to ignore.
                    }
                }
            }

            if (! empty($parsed['reference'])) {
                $this->entryReference = $parsed['reference'];
                $filled[]             = 'reference';
            }

            $this->nlpFilledFields = $filled;
            $this->nlpInput        = '';

            // Dispatch an event so the UI can flash the "AI filled" badges.
            $this->dispatch('nlp-parsed');
        } finally {
            $this->nlpLoading = false;
        }
    }

    public function clearNlpError(): void
    {
        $this->nlpError = '';
    }

    // ── Entry comments ───────────────────────────────────────────────────────

    public function openComments(string $entryId): void
    {
        // Reading an entry's thread is allowed on every plan (Free sees it
        // read-only); only posting is Pro — see addComment(). A Free entry
        // with no comments has nothing to read, so go straight to the modal.
        $entry = $this->book->entries()->withCount('comments')->findOrFail($entryId);

        if (! $this->business->isPro() && $entry->comments_count === 0) {
            $this->upgradeModalFeature = 'comments';
            return;
        }

        $this->commentingEntryId     = $entryId;
        $this->commentingEntryDesc   = $entry->displayLabel();
        $this->commentingEntryAmount = (string) $entry->amount;
        $this->commentingEntryType   = $entry->type;
        $this->commentBody           = '';
        $this->showMentionDropdown   = false;
        $this->mentionQuery          = '';
        $this->showCommentPanel      = true;
    }

    public function closeComments(): void
    {
        $this->showCommentPanel    = false;
        $this->commentingEntryId   = '';
        $this->commentBody         = '';
        $this->showMentionDropdown = false;
        $this->mentionQuery        = '';
    }

    public function addComment(): void
    {
        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'comments';
            return;
        }
        $this->guardEditor();

        $this->validate(['commentBody' => 'required|string|max:1000']);

        $entry = $this->book->entries()->findOrFail($this->commentingEntryId);

        $mentionedIds = EntryComment::extractMentionedIds($this->commentBody);

        $comment = $entry->comments()->create([
            'user_id'            => auth()->id(),
            'body'               => $this->commentBody,
            'mentioned_user_ids' => $mentionedIds ?: null,
        ]);

        // Send mention notifications (load fresh for relations)
        $comment->load('user');
        // Only notify members of this business — mention markup is user-supplied text.
        $memberIds = $this->business->members()->pluck('users.id')->all();

        foreach ($mentionedIds as $userId) {
            if ($userId === auth()->id()) continue; // don't notify yourself
            if (! in_array($userId, $memberIds, true)) continue;
            $mentionedUser = \App\Models\User::find($userId);
            if ($mentionedUser) {
                $mentionedUser->notify(new MentionedInComment($comment, $entry));
            }
        }

        $this->logActivity('comment_added', $entry->id, [
            'entry_description' => $entry->description,
            'category'          => $entry->category,
            'type'              => $entry->type,
        ]);

        $this->dispatch('entry-saved', message: 'Comment added.');
        $this->commentBody         = '';
        $this->showMentionDropdown = false;
        $this->mentionQuery        = '';
    }

    /** A comment on an entry of THIS book (never another book/business). */
    private function findBookComment(string $commentId): EntryComment
    {
        abort_unless(\Illuminate\Support\Str::isUuid($commentId), 404);

        return EntryComment::where('id', $commentId)
            ->whereHas('entry', fn ($q) => $q->where('book_id', $this->book->id))
            ->firstOrFail();
    }

    /** Authors can delete their own comments; the business owner can delete any. */
    private function canDeleteComment(EntryComment $comment): bool
    {
        $role = $this->currentRole();
        if ($role === null) {
            return false;
        }

        return $comment->user_id === auth()->id() || $role === 'owner';
    }

    public function confirmDeleteComment(string $commentId): void
    {
        $comment = $this->findBookComment($commentId);

        if (! $this->canDeleteComment($comment)) return;

        $this->pendingDeleteCommentId      = $commentId;
        $this->pendingDeleteCommentExcerpt = \Illuminate\Support\Str::limit($comment->body, 60);
        $this->showDeleteCommentModal      = true;
    }

    public function deleteComment(): void
    {
        if (! $this->pendingDeleteCommentId) return;

        $comment = $this->findBookComment($this->pendingDeleteCommentId);

        if (! $this->canDeleteComment($comment)) return;

        $entryId   = $comment->entry_id;
        $entryDesc = $this->book->entries()->find($entryId)?->displayLabel() ?? 'an entry';

        $comment->delete();

        $this->logActivity('comment_deleted', $entryId, [
            'entry_description' => $entryDesc,
        ]);

        $this->showDeleteCommentModal      = false;
        $this->pendingDeleteCommentId      = '';
        $this->pendingDeleteCommentExcerpt = '';
        $this->dispatch('entry-saved', message: 'Comment deleted.');
    }

    /** Returns business members for @mention autocomplete (called from blade via wire:model) */
    public function getMentionSuggestions(): array
    {
        if (strlen($this->mentionQuery) < 1) return [];

        return $this->business->members()
            ->where('users.id', '!=', auth()->id())
            ->where('users.name', 'ilike', '%' . $this->mentionQuery . '%')
            ->select('users.id', 'users.name')
            ->limit(5)
            ->get()
            ->toArray();
    }

    // ── Recurring entries ────────────────────────────────────────────────────

    public function enableRecurring(): void
    {
        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'recurring';
            return;
        }

        $this->entryRecurring = true;
    }

    /**
     * Single, stable toggle for the "Repeat this entry" switch.
     *
     * The button previously swapped its wire:click expression between
     * enableRecurring / $toggle based on server state, which Livewire's DOM
     * morph did not rebind reliably — making the toggle feel unresponsive
     * (especially when turning it back off). One stable action fixes that.
     */
    public function toggleRecurring(): void
    {
        if ($this->entryRecurring) {
            $this->entryRecurring = false;
            return;
        }

        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'recurring';
            return;
        }

        $this->entryRecurring = true;
    }

    public function toggleRecurringStatus(string $id): void
    {
        $this->guardEditor();

        $rec = $this->book->recurringEntries()->findOrFail($id);

        if ($rec->isCompleted()) {
            return;
        }

        $newStatus = $rec->isActive() ? 'paused' : 'active';

        // Pausing is always allowed; resuming a rule is a Pro feature.
        if ($newStatus === 'active' && ! $this->business->isPro()) {
            $this->upgradeModalFeature = 'recurring';
            return;
        }

        $rec->update(['status' => $newStatus]);

        $this->logActivity($newStatus === 'paused' ? 'recurring_paused' : 'recurring_resumed', null, [
            'description' => $rec->description,
            'category'    => $rec->category,
            'type'        => $rec->type,
        ]);

        $this->dispatch('entry-saved', message: $newStatus === 'paused' ? 'Recurring rule paused.' : 'Recurring rule resumed.');
    }

    public function deleteRecurring(string $id): void
    {
        $this->guardEditor();

        $rec = $this->book->recurringEntries()->findOrFail($id);
        $meta = ['description' => $rec->description, 'category' => $rec->category, 'type' => $rec->type];
        $rec->delete();

        $this->logActivity('recurring_deleted', null, $meta);
        $this->dispatch('entry-saved', message: 'Recurring entry deleted.');
    }

    /**
     * Reports tab data — deliberately small: period summary, cash in vs out
     * over time, and spending by category. (AI insights load separately.)
     */
    private function buildReportData($entries): array
    {
        $inEntries  = $entries->where('type', 'in');
        $outEntries = $entries->where('type', 'out');

        $totalIn    = $inEntries->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00');
        $totalOut   = $outEntries->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00');
        $netBalance = bcsub($totalIn, $totalOut, 2);

        $minDate      = $entries->min('date');
        $maxDate      = $entries->max('date');
        $daySpan      = ($minDate && $maxDate) ? max(1, $minDate->diffInDays($maxDate) + 1) : 1;
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

        $trendChart        = $this->buildTrendChart($entries, $daySpan, $minDate, $maxDate);
        $categoryBreakdown = $this->buildCategoryBreakdown($entries, (float) $totalIn, (float) $totalOut);

        return compact('periodSummary', 'trendChart', 'categoryBreakdown');
    }

    private function buildTrendChart($entries, int $daySpan, $minDate, $maxDate): array
    {
        if ($entries->count() < 3 || !$minDate || !$maxDate) return [];

        [$groupFormat, $labelFormat, $step] = match (true) {
            $daySpan < 60  => ['Y-m-d', 'd M',   'day'],
            $daySpan < 180 => ['oW',    'd M',   'week'],
            default        => ['Y-m',   'M Y',   'month'],
        };

        $grouped = $entries->groupBy(fn ($e) => $e->date->format($groupFormat));
        $cursor  = $minDate->copy()->startOfDay();
        if ($step === 'week')  $cursor = $cursor->startOfWeek();
        if ($step === 'month') $cursor = $cursor->startOfMonth();
        $end = $maxDate->copy()->endOfDay();

        $chart = [];
        while ($cursor->lte($end)) {
            $key   = $cursor->format($groupFormat);
            $group = $grouped->get($key, collect());
            $chart[] = [
                'label' => $cursor->format($labelFormat),
                'in'    => (float) $group->where('type', 'in')->sum('amount'),
                'out'   => (float) $group->where('type', 'out')->sum('amount'),
            ];
            match ($step) {
                'day'   => $cursor->addDay(),
                'week'  => $cursor->addWeek(),
                'month' => $cursor->addMonth(),
            };
        }

        return $chart;
    }

    private function buildCategoryBreakdown($entries, float $totalIn, float $totalOut): array
    {
        $result = [];
        foreach (['in' => $totalIn, 'out' => $totalOut] as $type => $typeTotal) {
            $byCategory = $entries->where('type', $type)
                ->groupBy(fn ($e) => $e->category ?: 'Uncategorized')
                ->map(fn ($g) => (float) $g->sum('amount'))
                ->sortDesc();

            $items      = [];
            $maxVal     = $byCategory->first() ?: 1;
            $count      = 0;
            $otherTotal = 0.0;

            foreach ($byCategory as $name => $total) {
                $count++;
                if ($count <= 6) {
                    $items[] = [
                        'name'   => $name,
                        'total'  => $total,
                        'pct'    => $typeTotal > 0 ? round(($total / $typeTotal) * 100, 1) : 0,
                        'barPct' => ($total / $maxVal) * 100,
                    ];
                } else {
                    $otherTotal += $total;
                }
            }

            if ($otherTotal > 0) {
                $items[] = [
                    'name'   => 'Other',
                    'total'  => $otherTotal,
                    'pct'    => $typeTotal > 0 ? round(($otherTotal / $typeTotal) * 100, 1) : 0,
                    'barPct' => ($otherTotal / $maxVal) * 100,
                ];
            }

            $result[$type] = $items;
        }

        return $result;
    }

    private function buildComparisonData(): array
    {
        $fromDate = $this->filterCustomFrom;
        $toDate   = $this->filterCustomTo;

        $from = Carbon::parse($fromDate);
        $to   = Carbon::parse($toDate);
        // Duration in days (inclusive)
        $days = $from->diffInDays($to) + 1;

        if ($this->compareMode === 'same_period_last_year') {
            $prevFrom = $from->copy()->subYear();
            $prevTo   = $to->copy()->subYear();
        } else {
            // Previous period: same number of days immediately before current from
            $prevTo   = $from->copy()->subDay();
            $prevFrom = $prevTo->copy()->subDays($days - 1);
        }

        // Two aggregate queries over the whole (unfiltered) book.
        $sum = fn (string $f, string $t) => BookLedger::queryTotals(
            BookLedger::applyQueryFilters($this->book->entries(), ['from' => $f, 'to' => $t])
        );
        $curr = $sum($from->format('Y-m-d'), $to->format('Y-m-d'));
        $prev = $sum($prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d'));

        $currIn  = (float) $curr['totalIn'];
        $currOut = (float) $curr['totalOut'];
        $prevIn  = (float) $prev['totalIn'];
        $prevOut = (float) $prev['totalOut'];
        $currNet = $currIn - $currOut;
        $prevNet = $prevIn - $prevOut;

        $pctChange = fn (float $curr, float $prev): ?float => $prev != 0
            ? round((($curr - $prev) / abs($prev)) * 100, 1)
            : ($curr != 0 ? 100.0 : null);

        return [
            'currentLabel'  => $from->format('d M') . ' – ' . $to->format('d M Y'),
            'previousLabel' => $prevFrom->format('d M') . ' – ' . $prevTo->format('d M Y'),
            'current'  => ['in' => $currIn, 'out' => $currOut, 'net' => $currNet],
            'previous' => ['in' => $prevIn, 'out' => $prevOut, 'net' => $prevNet],
            'changes'  => [
                'in'  => $pctChange($currIn, $prevIn),
                'out' => $pctChange($currOut, $prevOut),
                'net' => $pctChange($currNet, $prevNet),
            ],
        ];
    }

    // ── Ledger paging ────────────────────────────────

    /** Show the next page of ledger rows. */
    public function loadMore(): void
    {
        $this->perPage = min($this->perPage + self::LEDGER_PAGE_SIZE, 100000);
    }

    /** Any filter change starts the list again from the first page. */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterType', 'filterDuration', 'filterCustomFrom', 'filterCustomTo'], true)
            || str_starts_with($property, 'filterCategories')
            || str_starts_with($property, 'filterPaymentModes')) {
            $this->perPage = self::LEDGER_PAGE_SIZE;
        }
    }

    /** Resolve the duration filter to a [from, to] pair of Y-m-d strings (or nulls). */
    private function durationRange(): array
    {
        return match ($this->filterDuration) {
            'today'        => [now()->format('Y-m-d'), now()->format('Y-m-d')],
            'yesterday'    => [now()->subDay()->format('Y-m-d'), now()->subDay()->format('Y-m-d')],
            'last_7_days'  => [now()->subDays(6)->format('Y-m-d'), now()->format('Y-m-d')],
            'last_30_days' => [now()->subDays(29)->format('Y-m-d'), now()->format('Y-m-d')],
            // Custom ranges are Pro — a Free business (e.g. after a downgrade,
            // or a tampered request) just sees all time.
            'custom'       => $this->business->isPro()
                ? [$this->filterCustomFrom ?: null, $this->filterCustomTo ?: null]
                : [null, null],
            default        => [null, null],
        };
    }

    /** Current ledger filters in BookLedger's shape. */
    private function ledgerFilters(bool $includeType = true): array
    {
        [$from, $to] = $this->durationRange();

        return [
            'type'        => $includeType && in_array($this->filterType, ['in', 'out'], true) ? $this->filterType : null,
            'from'        => $from,
            'to'          => $to,
            'category'    => $this->filterCategories,
            'paymentMode' => $this->filterPaymentModes,
            'search'      => $this->search,
        ];
    }

    /** Plain-language summary of active filters, e.g. ["Last 7 days", "Food"]. */
    private function activeFilterSummary(): array
    {
        $parts = [];

        if ($this->filterType === 'in')  $parts[] = 'Cash in only';
        if ($this->filterType === 'out') $parts[] = 'Cash out only';

        [$from, $to] = $this->durationRange();
        $parts[] = match ($this->filterDuration) {
            'today'        => 'Today',
            'yesterday'    => 'Yesterday',
            'last_7_days'  => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'custom'       => ($from || $to)
                ? trim(($from ? Carbon::parse($from)->format('j M Y') : '…') . ' – ' . ($to ? Carbon::parse($to)->format('j M Y') : '…'))
                : null,
            default        => null,
        };

        foreach ($this->filterCategories as $c)   $parts[] = (string) $c;
        foreach ($this->filterPaymentModes as $m) $parts[] = (string) $m;

        if (trim($this->search) !== '') {
            $parts[] = '“' . trim($this->search) . '”';
        }

        return array_values(array_filter($parts, fn ($p) => $p !== null && $p !== ''));
    }

    public function render()
    {
        $filters    = $this->ledgerFilters();
        $hasFilters = BookLedger::hasFilters($filters);

        // ── Totals ─────────────────────────────────────────────────────
        // One aggregate query for the whole book (true balance) and, when
        // filters are active, one for the filtered set. Neither hydrates rows.
        $bookTotals = BookLedger::queryTotals($this->book->entries());
        $viewTotals = $hasFilters
            ? BookLedger::queryTotals(BookLedger::applyQueryFilters($this->book->entries(), $filters))
            : $bookTotals;

        $opening     = BookLedger::money($this->book->opening_balance);
        $bookBalance = bcsub(bcadd($opening, $bookTotals['totalIn'], 2), $bookTotals['totalOut'], 2);

        $totalIn       = $viewTotals['totalIn'];
        $totalOut      = $viewTotals['totalOut'];
        $filteredCount = $viewTotals['count'];
        // Unfiltered: the book balance (incl. opening). Filtered: net of what's shown.
        $balance       = $hasFilters ? bcsub($totalIn, $totalOut, 2) : $bookBalance;

        // ── One page of rows, newest first ─────────────────────────────
        // Running balance comes from a SQL window function over the whole
        // book (stable date → created_at → id order), so a filtered row keeps
        // its true ledger balance and we only ever load $perPage rows.
        $entries = collect();
        if ($this->activeTab === 'entries') {
            $entries = BookLedger::applyQueryFilters(BookLedger::runningQuery($this->book), $filters)
                ->with('creator')
                ->withCount('comments')
                ->orderBy('entries.date', 'desc')
                ->orderBy('entries.created_at', 'desc')
                ->orderBy('entries.id', 'desc')
                ->limit($this->perPage)
                ->get();
            BookLedger::withRunningBalance($entries, $this->book);
        }
        $hasMoreEntries = $filteredCount > $entries->count() && $this->activeTab === 'entries';

        // ── Reports (Pro, Reports tab only) ────────────────────────────
        // Reports use every entry type — the type filter is for the list only;
        // date, category, payment method and search still apply.
        $reportData = [];
        if ($this->activeTab === 'reports' && $this->business->isPro()) {
            $reportEntries = BookLedger::applyQueryFilters($this->book->entries(), $this->ledgerFilters(false))
                ->orderBy('date')->orderBy('created_at')->orderBy('id')
                ->get(['id', 'type', 'amount', 'date', 'category']);
            $reportData = $this->buildReportData($reportEntries);
        }

        // Build comparison data (Pro, custom date range only)
        $comparisonData = null;
        if ($this->business->isPro() && $this->compareEnabled && $this->filterDuration === 'custom'
            && $this->filterCustomFrom !== '' && $this->filterCustomTo !== '') {
            $comparisonData = $this->buildComparisonData();
        }

        $activeFilters = $this->activeFilterSummary();

        $categories   = $this->book->categories()->get();
        $paymentModes = $this->book->paymentModes()->get();

        $activityLog     = collect();
        $activityTotal   = 0;
        $activityMembers = collect();

        if ($this->activeTab === 'activity') {
            $activityQuery = BookActivityLog::where('book_id', $this->book->id)
                ->when($this->activityFilterUserId !== '', fn ($q) => $q->where('user_id', $this->activityFilterUserId))
                ->when($this->activityFilterAction !== '', fn ($q) => $q->where('action', 'like', $this->activityFilterAction === 'bulk' ? 'bulk_%' : $this->activityFilterAction . '%'));

            $activityTotal   = $activityQuery->count();
            $activityLog     = $activityQuery->clone()->with('user')->latest()->limit($this->activityPerPage)->get();

            // Distinct members who have activity in this book (for filter dropdown)
            $memberIds       = BookActivityLog::where('book_id', $this->book->id)->distinct()->pluck('user_id');
            $activityMembers = \App\Models\User::whereIn('id', $memberIds)->get(['id', 'name']);
        }

        $recurringEntries = $this->activeTab === 'recurring'
            ? $this->book->recurringEntries()
                ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'paused' THEN 1 ELSE 2 END")
                ->orderByDesc('created_at')
                ->get()
            : collect();

        // Comments panel data
        $commentThread = ($this->showCommentPanel && $this->commentingEntryId)
            ? EntryComment::where('entry_id', $this->commentingEntryId)
                ->whereHas('entry', fn ($q) => $q->where('book_id', $this->book->id))
                ->with('user')
                ->orderBy('created_at')
                ->get()
            : collect();

        $commentMembers = $this->showCommentPanel
            ? $this->business->members()
                ->where('users.id', '!=', auth()->id())
                ->select('users.id', 'users.name')
                ->get()
            : collect();

        // AI entry allowance for the entry slide-over (new entries, editors+).
        $aiQuota = ($this->showEntryPanel && ! $this->editingEntryId && $this->userRole !== 'viewer')
            ? \App\Services\AiQuota::remaining(auth()->user(), $this->business)
            : null;

        return view('livewire.book.show', compact(
            'aiQuota', 'entries', 'totalIn', 'totalOut', 'balance', 'bookBalance', 'hasFilters', 'filteredCount',
            'hasMoreEntries', 'activeFilters', 'categories', 'paymentModes', 'reportData',
            'activityLog', 'activityTotal', 'activityMembers',
            'recurringEntries', 'commentThread', 'commentMembers', 'comparisonData'
        ));
    }
}
