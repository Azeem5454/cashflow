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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
    public bool   $compareEnabled   = false; // Pro: compare with previous period
    public string $compareMode      = 'previous_period'; // previous_period | same_period_last_year

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

            $current  = $this->buildBookAggregates($this->book, $allEntries);
            $previous = $this->buildPreviousBookAggregates();
            $recurringCount = $this->book->recurringEntries()->where('status', 'active')->count();

            $result = app(AiService::class)->generateInsights(
                $current,
                $previous,
                $this->business->currency,
                $recurringCount
            );

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
        return AiUsageLog::where('user_id', auth()->id())
            ->where('type', 'insights')
            ->whereDate('created_at', today())
            ->count() >= 10;
    }

    private function buildBookAggregates(Book $book, $entries): array
    {
        $totalIn  = (float) $entries->where('type', 'in')->sum('amount');
        $totalOut = (float) $entries->where('type', 'out')->sum('amount');
        $balance  = $totalIn - $totalOut + (float) ($book->opening_balance ?? 0);

        $topOut = $entries->where('type', 'out')->whereNotNull('category')
            ->groupBy('category')
            ->map(fn ($g) => $g->sum('amount'))
            ->sortDesc()->take(5)
            ->map(fn ($amt, $cat) => "{$cat} (" . number_format($amt, 0) . ")")
            ->values()->toArray();

        $topIn = $entries->where('type', 'in')->whereNotNull('category')
            ->groupBy('category')
            ->map(fn ($g) => $g->sum('amount'))
            ->sortDesc()->take(3)
            ->map(fn ($amt, $cat) => "{$cat} (" . number_format($amt, 0) . ")")
            ->values()->toArray();

        $period = ($book->period_starts_at && $book->period_ends_at)
            ? $book->period_starts_at->format('d M Y') . ' to ' . $book->period_ends_at->format('d M Y')
            : 'Custom period';

        return [
            'name'              => $book->name,
            'period'            => $period,
            'totalIn'           => number_format($totalIn, 2),
            'totalOut'          => number_format($totalOut, 2),
            'balance'           => number_format($balance, 2),
            'entryCount'        => $entries->count(),
            'topCategoriesOut'  => $topOut,
            'topCategoriesIn'   => $topIn,
        ];
    }

    private function buildPreviousBookAggregates(): ?array
    {
        // Require period dates on the current book — without them we cannot
        // reliably determine which other book represents an earlier period.
        // The created_at fallback produced backwards comparisons when books
        // were created out of chronological order (e.g. February created before January).
        if (! $this->book->period_starts_at) {
            return null;
        }

        $prevBook = $this->business->books()
            ->where('id', '!=', $this->book->id)
            ->whereNotNull('period_ends_at')
            ->where('period_ends_at', '<', $this->book->period_starts_at)
            ->orderByDesc('period_ends_at')
            ->first();

        if (! $prevBook) {
            return null;
        }

        $entries = $prevBook->entries()->get();

        if ($entries->count() < 2) {
            return null;
        }

        return $this->buildBookAggregates($prevBook, $entries);
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
        $this->entryFrequency      = 'weekly';
        $this->entryEndsAt         = $this->book->period_ends_at?->format('Y-m-d') ?? '';
        $this->entryRunForever     = false;
        $this->aiCategorySuggestion = '';
        $this->showCategoryChip     = false;
        $this->resetErrorBag();
        $this->showEntryPanel      = true;
        $this->dispatch('entry-date-updated', date: $this->entryDate);
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
        if ($this->userRole === 'viewer') {
            return;
        }

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
                'description'  => $this->entryDescription,
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
        if (!$this->business->isPro()) {
            $this->upgradeModalFeature = 'ai';
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
        if ($this->userRole === 'viewer' || $this->editingEntryId) {
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
        $this->pendingDeleteDesc    = $entry->description;
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
        $this->dispatch('entry-saved', message: "Payment mode updated to \"{$label}\" on {$count} " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    // ── Book management ──────────────────────────────

    public function openEditBook(): void
    {
        if ($this->userRole === 'viewer') return;

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
        if ($this->userRole === 'viewer') return;

        $this->validate([
            'editBookName'           => 'required|string|max:100',
            'editBookDescription'    => 'nullable|string|max:500',
            'editBookOpeningBalance' => 'nullable|numeric|min:0|max:999999999.99',
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
        if ($this->userRole === 'viewer') return;

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
        if ($this->userRole === 'viewer') return;

        $this->validate([
            'duplicateBookName' => 'required|string|max:100',
        ]);

        $newBook = $this->business->books()->create([
            'name'             => $this->duplicateBookName,
            'description'      => $this->book->description,
            'opening_balance'  => 0,
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
        $this->deleteConfirmName = '';
        $this->resetErrorBag();
        $this->showDeleteBook = true;
    }

    public function deleteBook(): void
    {
        if ($this->userRole === 'viewer') {
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
        $this->showEmailReportModal = true;
    }

    public function saveEmailReport(): void
    {
        $this->guardEditor();

        if (! $this->business->isPro()) {
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
        // Pro only — free users: silent no-op (no modal, no error)
        if (! $this->business->isPro()) {
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

    /** Per-user daily + burst rate limits on NLP calls. */
    public const NLP_DAILY_LIMIT  = 30;
    public const NLP_BURST_LIMIT  = 10;
    public const NLP_BURST_WINDOW = 60;     // seconds

    public function parseEntryText(): void
    {
        $this->nlpError        = '';
        $this->nlpFilledFields = [];

        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'ai';
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

        // Burst rate limit: per-user, per-minute
        $burstKey = 'nlp-burst:' . auth()->id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($burstKey, self::NLP_BURST_LIMIT)) {
            $this->nlpError = 'You\'re parsing very quickly — wait a moment.';
            return;
        }

        // Daily cap: count today's nlp calls from ai_usage_logs
        $todayCount = \App\Models\AiUsageLog::where('user_id', auth()->id())
            ->where('type', 'nlp')
            ->whereDate('created_at', today())
            ->count();
        if ($todayCount >= self::NLP_DAILY_LIMIT) {
            $this->nlpError = 'Daily AI parse limit reached. Type the entry manually, or try again tomorrow.';
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
                // Auto-create if new
                $exists = $this->book->categories()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($parsed['category'])])
                    ->exists();
                if (! $exists) {
                    $this->book->categories()->create(['name' => $parsed['category']]);
                }
            }

            if (! empty($parsed['payment_mode'])) {
                $this->entryPaymentMode = $parsed['payment_mode'];
                $filled[]               = 'payment_mode';
                $exists = $this->book->paymentModes()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($parsed['payment_mode'])])
                    ->exists();
                if (! $exists) {
                    $this->book->paymentModes()->create(['name' => $parsed['payment_mode']]);
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
        if (! $this->business->isPro()) {
            $this->upgradeModalFeature = 'comments';
            return;
        }

        $entry = $this->book->entries()->findOrFail($entryId);

        $this->commentingEntryId     = $entryId;
        $this->commentingEntryDesc   = $entry->description;
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
        if (! $this->business->isPro()) return;
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
        foreach ($mentionedIds as $userId) {
            if ($userId === auth()->id()) continue; // don't notify yourself
            $mentionedUser = \App\Models\User::find($userId);
            if ($mentionedUser) {
                $mentionedUser->notify(new MentionedInComment($comment, $entry));
            }
        }

        $this->logActivity('comment_added', $entry->id, [
            'entry_description' => $entry->description,
        ]);

        $this->dispatch('entry-saved', message: 'Comment added.');
        $this->commentBody         = '';
        $this->showMentionDropdown = false;
        $this->mentionQuery        = '';
    }

    public function confirmDeleteComment(string $commentId): void
    {
        $comment = EntryComment::findOrFail($commentId);

        $isOwner = $this->userRole === 'owner';
        if ($comment->user_id !== auth()->id() && ! $isOwner) return;

        $this->pendingDeleteCommentId      = $commentId;
        $this->pendingDeleteCommentExcerpt = \Illuminate\Support\Str::limit($comment->body, 60);
        $this->showDeleteCommentModal      = true;
    }

    public function deleteComment(): void
    {
        if (! $this->pendingDeleteCommentId) return;

        $comment = EntryComment::findOrFail($this->pendingDeleteCommentId);

        $isOwner = $this->userRole === 'owner';
        if ($comment->user_id !== auth()->id() && ! $isOwner) return;

        $entryId   = $comment->entry_id;
        $entryDesc = $this->book->entries()->find($entryId)?->description ?? 'an entry';

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

    public function toggleRecurringStatus(string $id): void
    {
        $this->guardEditor();

        $rec = $this->book->recurringEntries()->findOrFail($id);

        if ($rec->isCompleted()) {
            return;
        }

        $newStatus = $rec->isActive() ? 'paused' : 'active';
        $rec->update(['status' => $newStatus]);

        $this->logActivity($newStatus === 'paused' ? 'recurring_paused' : 'recurring_resumed', null, [
            'description' => $rec->description,
        ]);

        $this->dispatch('entry-saved', message: $newStatus === 'paused' ? 'Recurring rule paused.' : 'Recurring rule resumed.');
    }

    public function deleteRecurring(string $id): void
    {
        $this->guardEditor();

        $rec = $this->book->recurringEntries()->findOrFail($id);
        $desc = $rec->description;
        $rec->delete();

        $this->logActivity('recurring_deleted', null, ['description' => $desc]);
        $this->dispatch('entry-saved', message: 'Recurring entry deleted.');
    }

    private function buildReportData($entries, $allEntries): array
    {
        $inEntries  = $entries->where('type', 'in');
        $outEntries = $entries->where('type', 'out');

        $totalIn    = $inEntries->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00');
        $totalOut   = $outEntries->reduce(fn ($c, $e) => bcadd($c, (string) $e->amount, 2), '0.00');
        $netBalance = bcsub($totalIn, $totalOut, 2);

        $minDate      = $entries->min('date');
        $maxDate      = $entries->max('date');
        $daySpan      = ($minDate && $maxDate) ? max(1, $minDate->diffInDays($maxDate) + 1) : 1;
        $dailyAverage = $daySpan > 0 ? bcdiv($netBalance, (string) $daySpan, 2) : '0.00';

        $totalInF  = (float) $totalIn;
        $totalOutF = (float) $totalOut;
        $netF      = (float) $netBalance;

        $periodSummary = [
            'totalIn'      => $totalIn,
            'totalOut'     => $totalOut,
            'netBalance'   => $netBalance,
            'inCount'      => $inEntries->count(),
            'outCount'     => $outEntries->count(),
            'dailyAverage' => $dailyAverage,
            'daySpan'      => $daySpan,
        ];

        $healthScore        = $this->computeHealthScore($entries, $totalInF, $totalOutF, $netF, $daySpan, $minDate);
        $balanceTimeline    = $this->buildBalanceTimeline($allEntries);
        $trendChart         = $this->buildTrendChart($entries, $daySpan, $minDate, $maxDate);
        $burnMetrics        = $this->computeBurnMetrics($totalInF, $totalOutF, $netF, $daySpan);
        $incomeReliability  = $this->computeIncomeReliability($inEntries, $totalInF);
        $spendConcentration = $this->computeSpendConcentration($outEntries, $totalOutF);
        $spendingVelocity   = $this->computeSpendingVelocity($entries, $minDate, $daySpan);
        $topOutEntries      = $outEntries->sortByDesc(fn ($e) => (float) $e->amount)->take(5)->values();
        $topInEntries       = $inEntries->sortByDesc(fn ($e) => (float) $e->amount)->take(5)->values();
        $categoryBreakdown  = $this->buildCategoryBreakdown($entries, $totalInF, $totalOutF);
        $paymentModeBreakdown = $this->buildPaymentModeBreakdown($entries);

        // ── Previous-period grade comparison ──────────────────────────────
        // Find the most recent book in this business that ended before the
        // current book's period. Compute its health score using the same
        // algorithm so grades are directly comparable.
        // Only runs on the Reports tab (already gated) — one extra query.
        $previousGrade = null;
        $prevBook = $this->business->books()
            ->where('id', '!=', $this->book->id)
            ->where(function ($q) {
                if ($this->book->period_ends_at) {
                    $q->whereNotNull('period_ends_at')
                      ->where('period_ends_at', '<', $this->book->period_ends_at);
                } else {
                    $q->where('created_at', '<', $this->book->created_at);
                }
            })
            ->orderByDesc('period_ends_at')
            ->first();

        if ($prevBook) {
            $prevEntries = $prevBook->entries()
                ->orderBy('date')->orderBy('created_at')->orderBy('id')
                ->get();

            if ($prevEntries->count() >= 3) {
                $pIn    = (float) $prevEntries->where('type', 'in')->sum('amount');
                $pOut   = (float) $prevEntries->where('type', 'out')->sum('amount');
                $pNet   = $pIn - $pOut;
                $pMin   = $prevEntries->min('date');
                $pMax   = $prevEntries->max('date');
                $pSpan  = ($pMin && $pMax) ? max(1, $pMin->diffInDays($pMax) + 1) : 1;
                $pScore = $this->computeHealthScore($prevEntries, $pIn, $pOut, $pNet, $pSpan, $pMin);

                $previousGrade = [
                    'grade'    => $pScore['grade'],
                    'score'    => $pScore['score'],
                    'bookName' => $prevBook->name,
                    'color'    => $pScore['color'],
                ];
            }
        }

        $healthScore['previousGrade'] = $previousGrade;

        return compact(
            'periodSummary', 'healthScore', 'balanceTimeline',
            'trendChart', 'burnMetrics', 'incomeReliability',
            'spendConcentration', 'spendingVelocity',
            'topOutEntries', 'topInEntries',
            'categoryBreakdown', 'paymentModeBreakdown'
        );
    }

    private function computeHealthScore($entries, float $totalIn, float $totalOut, float $net, int $daySpan, $minDate): array
    {
        $entryCount = $entries->count();

        // Confidence level — reflects how much data we have to work with.
        // Shown as a UI badge; does NOT affect the score itself.
        $confidence = match (true) {
            $entryCount <  3  => 'insufficient',
            $entryCount <  8  => 'low',
            $entryCount < 15  => 'moderate',
            default           => 'good',
        };

        // Return early if we don't have enough data to score meaningfully.
        if ($entryCount < 3) {
            return [
                'score' => 0, 'grade' => '—', 'color' => 'slate',
                'status' => 'Insufficient data',
                'headline' => 'Add at least 3 entries to generate a health score.',
                'ratioScore' => 0, 'trendScore' => 0, 'consistencyScore' => 0,
                'confidence' => 'insufficient', 'entryCount' => $entryCount,
                'ratio' => 0.0, 'trendChange' => 0, 'cv' => null,
                'previousGrade' => null,
            ];
        }

        // ── 1. Profitability Ratio  (0–45 pts) ─────────────────────────────
        // Smooth two-segment linear scale — no step cliffs.
        //   ratio 0.0 → 1.0 : maps linearly to 0 → 22 pts  (losing money zone)
        //   ratio 1.0 → 2.0 : maps linearly to 22 → 45 pts (profit zone, capped at 2×)
        // This means a 1-rupee change in expenses causes a proportional change in
        // score rather than a sudden 8-point jump at an arbitrary threshold.
        $ratio = 0.0;
        if ($totalOut <= 0) {
            $ratioScore = $totalIn > 0 ? 45 : 0;
            $ratio      = $totalIn  > 0 ? 99.0 : 0.0;
        } else {
            $ratio = $totalIn / $totalOut;
            if ($ratio < 1.0) {
                $ratioScore = (int) round($ratio * 22);           // 0–21
            } else {
                $ratioScore = (int) round(min(45, 22 + ($ratio - 1.0) * 23)); // 22–45
            }
        }

        // ── 2. Trend Direction  (0–30 pts) ─────────────────────────────────
        // Compare the FIRST third vs LAST third of the period (ignoring the
        // noisy middle). Using halves was too sensitive — one large payment
        // in week 1 made every period look like it was "declining".
        // Requires at least 7-day span and 6 entries to activate.
        // Smooth: change% maps linearly to 0–30 pts (0% change → 15 pts neutral).
        $trendScore  = 15; // neutral default when insufficient data
        $trendChange = 0;
        if ($daySpan >= 7 && $entryCount >= 6 && $minDate) {
            $third     = max(1, (int) ($daySpan / 3));
            $firstEnd  = $minDate->copy()->addDays($third);
            $lastStart = $minDate->copy()->addDays($daySpan - $third);

            $calcNet = fn ($col) => $col->reduce(
                fn ($c, $e) => $c + ($e->type === 'in' ? (float) $e->amount : -(float) $e->amount),
                0.0
            );

            $firstNet = $calcNet($entries->filter(fn ($e) => $e->date->lte($firstEnd)));
            $lastNet  = $calcNet($entries->filter(fn ($e) => $e->date->gte($lastStart)));

            if ($firstNet != 0) {
                $trendChange = (($lastNet - $firstNet) / abs($firstNet)) * 100;
            } elseif ($lastNet > 0) {
                $trendChange = 100;
            } elseif ($lastNet < 0) {
                $trendChange = -100;
            }

            // Smooth map: ±100% change covers the full 0–30 range.
            // +100% (doubling) → 30 pts | 0% (flat) → 15 pts | −100% → 0 pts
            $trendScore = (int) round(min(30, max(0, 15 + ($trendChange / 200) * 30)));
        }

        // ── 3. Income Consistency  (0–25 pts) ──────────────────────────────
        // Coefficient of variation (CV) on Cash In entries.
        // Smooth linear: CV=0 (perfectly consistent) → 25 pts, CV≥3 → 0 pts.
        // Freelancers who get paid once a month won't be unfairly penalised
        // as harshly — the curve is gradual rather than a cliff.
        $consistencyScore = 12; // neutral default when < 2 income entries
        $cv = null;
        $inEntries = $entries->where('type', 'in');
        if ($inEntries->count() >= 2) {
            $amounts = $inEntries->pluck('amount')->map(fn ($a) => (float) $a);
            $mean    = $amounts->average();
            if ($mean > 0) {
                $variance         = $amounts->reduce(fn ($c, $v) => $c + ($v - $mean) ** 2, 0.0) / $amounts->count();
                $cv               = sqrt($variance) / $mean;
                $consistencyScore = (int) round(max(0, min(25, 25 * (1 - $cv / 3))));
            } else {
                $consistencyScore = 0;
            }
        }

        // ── Total score & grade ─────────────────────────────────────────────
        $score = $ratioScore + $trendScore + $consistencyScore; // max 100

        [$grade, $color, $status, $headline] = match (true) {
            $score >= 90 => ['A+', 'emerald', 'Excellent', 'Outstanding — your cash flow is exceptionally strong.'],
            $score >= 80 => ['A',  'emerald', 'Strong',    'Healthy — consistently bringing in more than you spend.'],
            $score >= 65 => ['B',  'blue',    'Good',      'Solid cash flow with room to optimise.'],
            $score >= 50 => ['C',  'amber',   'Fair',      'Expenses need watching — income needs a boost.'],
            $score >= 35 => ['D',  'orange',  'Weak',      'Expenses are outpacing income this period.'],
            default      => ['F',  'red',     'Critical',  'Urgent: cash flow needs immediate attention.'],
        };

        return [
            'score'            => $score,
            'grade'            => $grade,
            'color'            => $color,
            'status'           => $status,
            'headline'         => $headline,
            'ratioScore'       => $ratioScore,
            'trendScore'       => $trendScore,
            'consistencyScore' => $consistencyScore,
            'confidence'       => $confidence,
            'entryCount'       => $entryCount,
            'ratio'            => round($ratio, 2),
            'trendChange'      => (int) round($trendChange),
            'cv'               => $cv !== null ? round($cv, 2) : null,
            'previousGrade'    => null, // filled in buildReportData
        ];
    }

    private function buildBalanceTimeline($allEntries): array
    {
        if ($allEntries->count() < 2) return [];

        $opening = (float) ($this->book->opening_balance ?? 0);
        $start   = $this->book->period_starts_at ?? $allEntries->min('date');
        $end     = $this->book->period_ends_at   ?? $allEntries->max('date');

        if (!$start || !$end || $start->gt($end)) return [];

        $byDate  = $allEntries->groupBy(fn ($e) => $e->date->format('Y-m-d'));
        $cursor  = $start->copy()->startOfDay();
        $running = $opening;
        $points  = [];
        $highIdx = 0;
        $lowIdx  = 0;

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            foreach ($byDate->get($key, collect()) as $entry) {
                $running += $entry->type === 'in' ? (float) $entry->amount : -(float) $entry->amount;
            }
            $points[] = [
                'date'        => $key,
                'label'       => $cursor->format('d M'),
                'balance'     => round($running, 2),
                'entry_count' => $byDate->get($key, collect())->count(),
            ];
            $cursor->addDay();
        }

        if (count($points) < 2) return [];

        // Sample down to ≤ 120 points for SVG performance
        if (count($points) > 120) {
            $step   = (int) ceil(count($points) / 120);
            $points = array_values(array_filter($points, fn ($_, $i) => $i % $step === 0, ARRAY_FILTER_USE_BOTH));
        }

        // Find high/low indices
        $balances = array_column($points, 'balance');
        $highIdx  = (int) array_search(max($balances), $balances);
        $lowIdx   = (int) array_search(min($balances), $balances);

        return [
            'points'  => $points,
            'highIdx' => $highIdx,
            'lowIdx'  => $lowIdx,
            'opening' => $opening,
            'svg'     => $this->buildBalanceSvg($points, $opening),
        ];
    }

    private function buildBalanceSvg(array $points, float $opening): array
    {
        if (count($points) < 2) return [];

        $vw = 1000;
        $vh = 220;
        $py = 20; // vertical padding

        $balances = array_column($points, 'balance');
        $minBal   = min($balances);
        $maxBal   = max($balances);
        $range    = max(1, $maxBal - $minBal);

        // Add breathing room
        $minBal -= $range * 0.12;
        $maxBal += $range * 0.12;
        $range   = $maxBal - $minBal;

        $n      = count($points);
        $coords = [];

        foreach ($points as $i => $p) {
            $x        = round(($i / ($n - 1)) * $vw, 2);
            $y        = round($py + (1 - ($p['balance'] - $minBal) / $range) * ($vh - 2 * $py), 2);
            $coords[] = "{$x},{$y}";
        }

        $polyline = implode(' ', $coords);

        // Area fill path: descend to baseline, trace points, return
        [$fx] = explode(',', $coords[0]);
        [$lx] = explode(',', $coords[$n - 1]);
        $baseY    = $vh - $py;
        $areaPath = "M {$fx},{$baseY} L " . implode(' L ', $coords) . " L {$lx},{$baseY} Z";

        // Zero line Y (only show if zero is within visible range)
        $zeroY = null;
        if ($minBal <= 0 && $maxBal >= 0) {
            $zeroY = round($py + (1 - (0 - $minBal) / $range) * ($vh - 2 * $py), 2);
        }

        // Opening balance reference line Y
        $openingY = null;
        if ($opening >= $minBal && $opening <= $maxBal && $opening !== 0.0) {
            $openingY = round($py + (1 - ($opening - $minBal) / $range) * ($vh - 2 * $py), 2);
        }

        return compact('polyline', 'areaPath', 'coords', 'zeroY', 'openingY', 'vw', 'vh');
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

    private function computeBurnMetrics(float $totalIn, float $totalOut, float $net, int $daySpan): array
    {
        $dailyIn  = $daySpan > 0 ? round($totalIn  / $daySpan, 2) : 0.0;
        $dailyOut = $daySpan > 0 ? round($totalOut / $daySpan, 2) : 0.0;
        $dailyNet = $daySpan > 0 ? round($net       / $daySpan, 2) : 0.0;

        $isBurning = $dailyNet < 0;
        $runway    = null;

        if ($isBurning && $dailyOut > 0) {
            $currentBalance = (float) ($this->book->opening_balance ?? 0) + $net;
            if ($currentBalance > 0) {
                $runway = max(0, (int) ($currentBalance / abs($dailyNet)));
            }
        }

        // Efficiency: what percentage of income is consumed by expenses
        $efficiency = $totalIn > 0
            ? round(min(999, ($totalOut / $totalIn) * 100), 1)
            : ($totalOut > 0 ? 100.0 : 0.0);

        return compact('dailyIn', 'dailyOut', 'dailyNet', 'isBurning', 'runway', 'efficiency');
    }

    private function computeIncomeReliability($inEntries, float $totalIn): array
    {
        $empty = ['label' => 'No data', 'color' => 'slate', 'topPct' => 0,
                  'concentrationLabel' => 'N/A', 'concentrationColor' => 'slate'];

        if ($inEntries->count() < 2) return $empty;

        $amounts  = $inEntries->pluck('amount')->map(fn ($a) => (float) $a);
        $mean     = $amounts->average();
        $cv       = 999.0;

        if ($mean > 0) {
            $variance = $amounts->reduce(fn ($c, $v) => $c + ($v - $mean) ** 2, 0.0) / $amounts->count();
            $cv       = sqrt($variance) / $mean;
        }

        [$label, $color] = match (true) {
            $cv <= 0.4 => ['Consistent', 'emerald'],
            $cv <= 0.8 => ['Moderate',   'blue'],
            $cv <= 1.5 => ['Variable',   'amber'],
            default    => ['Irregular',  'red'],
        };

        // Concentration: top 2 transactions as % of total income
        $top2Amt = (float) $inEntries->sortByDesc(fn ($e) => (float) $e->amount)->take(2)->sum('amount');
        $topPct  = $totalIn > 0 ? round(($top2Amt / $totalIn) * 100) : 0;

        [$concentrationLabel, $concentrationColor] = match (true) {
            $topPct <= 30 => ['Diversified',   'emerald'],
            $topPct <= 60 => ['Moderate',      'amber'],
            default       => ['Concentrated',  'red'],
        };

        return compact('label', 'color', 'concentrationLabel', 'concentrationColor')
            + ['topPct' => $topPct];
    }

    private function computeSpendConcentration($outEntries, float $totalOut): array
    {
        if ($outEntries->isEmpty() || $totalOut <= 0) return [];

        $byCategory = $outEntries
            ->groupBy(fn ($e) => $e->category ?: 'Uncategorized')
            ->map(fn ($g) => (float) $g->sum('amount'))
            ->sortDesc();

        $items     = [];
        $top3Total = 0.0;

        foreach ($byCategory->take(3) as $name => $total) {
            $pct     = round(($total / $totalOut) * 100, 1);
            $items[] = ['name' => $name, 'total' => $total, 'pct' => $pct];
            $top3Total += $total;
        }

        $top3Pct        = round(($top3Total / $totalOut) * 100, 1);
        $highestPct     = $items[0]['pct'] ?? 0;
        $isConcentrated = $highestPct > 40;

        return compact('items', 'top3Pct', 'isConcentrated', 'highestPct');
    }

    private function computeSpendingVelocity($entries, $minDate, int $daySpan): array
    {
        if ($entries->count() < 4 || $daySpan < 2 || !$minDate) return [];

        $mid    = $minDate->copy()->addDays((int) ($daySpan / 2));
        $first  = $entries->filter(fn ($e) => $e->date->lte($mid));
        $second = $entries->filter(fn ($e) => $e->date->gt($mid));

        $fIn  = (float) $first->where('type', 'in')->sum('amount');
        $fOut = (float) $first->where('type', 'out')->sum('amount');
        $sIn  = (float) $second->where('type', 'in')->sum('amount');
        $sOut = (float) $second->where('type', 'out')->sum('amount');

        $outChange = $fOut > 0 ? round((($sOut - $fOut) / $fOut) * 100) : ($sOut > 0 ? 100 : 0);
        $inChange  = $fIn  > 0 ? round((($sIn  - $fIn)  / $fIn)  * 100) : ($sIn  > 0 ? 100 : 0);

        return [
            'first'     => ['in' => $fIn, 'out' => $fOut, 'net' => $fIn - $fOut, 'count' => $first->count()],
            'second'    => ['in' => $sIn, 'out' => $sOut, 'net' => $sIn - $sOut, 'count' => $second->count()],
            'outChange' => $outChange,
            'inChange'  => $inChange,
        ];
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

    private function buildPaymentModeBreakdown($entries): array
    {
        $byMode = $entries
            ->groupBy(fn ($e) => $e->payment_mode ?: 'Not specified')
            ->map(fn ($g) => [
                'total' => (float) $g->sum('amount'),
                'in'    => (float) $g->where('type', 'in')->sum('amount'),
                'out'   => (float) $g->where('type', 'out')->sum('amount'),
                'count' => $g->count(),
            ])
            ->sortByDesc(fn ($v) => $v['total']);

        $max    = $byMode->max(fn ($v) => $v['total']) ?: 1;
        $result = [];
        $count  = 0;
        [$otherIn, $otherOut, $otherTotal, $otherCount] = [0.0, 0.0, 0.0, 0];

        foreach ($byMode as $name => $data) {
            $count++;
            if ($count <= 5) {
                $result[] = array_merge(['name' => $name, 'barPct' => ($data['total'] / $max) * 100], $data);
            } else {
                $otherIn    += $data['in'];
                $otherOut   += $data['out'];
                $otherTotal += $data['total'];
                $otherCount += $data['count'];
            }
        }

        if ($otherTotal > 0) {
            $result[] = [
                'name' => 'Other', 'total' => $otherTotal, 'in' => $otherIn,
                'out'  => $otherOut, 'count' => $otherCount,
                'barPct' => ($otherTotal / $max) * 100,
            ];
        }

        return $result;
    }

    private function buildComparisonData($allEntries): array
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

        // Compute totals for each period from the full (unfiltered) entry collection
        $currIn  = $currOut = $prevIn = $prevOut = 0.0;
        $prevFromStr = $prevFrom->format('Y-m-d');
        $prevToStr   = $prevTo->format('Y-m-d');

        foreach ($allEntries as $entry) {
            $d = $entry->date->format('Y-m-d');

            if ($d >= $fromDate && $d <= $toDate) {
                if ($entry->type === 'in') $currIn  += (float) $entry->amount;
                else                       $currOut += (float) $entry->amount;
            }

            if ($d >= $prevFromStr && $d <= $prevToStr) {
                if ($entry->type === 'in') $prevIn  += (float) $entry->amount;
                else                       $prevOut += (float) $entry->amount;
            }
        }

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

    public function render()
    {
        // Fetch ALL entries for accurate running balance computation.
        // Three-level sort: date → created_at → id ensures a fully stable order
        // even when multiple entries share the same date or the same timestamp
        // (PostgreSQL returns non-deterministic order without a unique tiebreaker).
        $allEntries = $this->book->entries()
            ->with(['creator', 'comments'])
            ->withCount('comments')
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
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

        // Build report data before reversing (reports need chronological order).
        // Reports always use ALL entry types — the type filter is for the ledger view only.
        // Date, category, payment-mode, and search filters still apply so the report
        // reflects the same time window and scope the user is looking at.
        $reportData = [];
        if ($this->activeTab === 'reports' && $this->business->isPro()) {
            // Build a type-agnostic entry set: start from allEntries, re-apply every
            // filter except filterType.
            $reportEntries = $allEntries;
            if ($from !== null) {
                $reportEntries = $reportEntries->filter(fn ($e) => $e->date->format('Y-m-d') >= $from);
            }
            if ($to !== null) {
                $reportEntries = $reportEntries->filter(fn ($e) => $e->date->format('Y-m-d') <= $to);
            }
            if (! empty($this->filterCategories)) {
                $cats          = $this->filterCategories;
                $reportEntries = $reportEntries->filter(fn ($e) => in_array($e->category, $cats));
            }
            if (! empty($this->filterPaymentModes)) {
                $modes         = $this->filterPaymentModes;
                $reportEntries = $reportEntries->filter(fn ($e) => in_array($e->payment_mode, $modes));
            }
            if ($this->search !== '') {
                $term          = strtolower($this->search);
                $reportEntries = $reportEntries->filter(fn ($e) =>
                    str_contains(strtolower($e->description), $term)
                    || str_contains(strtolower($e->reference ?? ''), $term)
                    || str_contains(strtolower($e->category ?? ''), $term)
                    || str_contains((string) $e->amount, $term)
                );
            }
            $reportData = $this->buildReportData($reportEntries, $allEntries);
        }

        // 30-day cash flow forecast (pure statistics, no AI cost).
        // Only computed on the Reports tab + Pro — same gate as the rest of
        // the Reports panel. Uses the Book's own forecast method so the
        // logic stays reusable (tests, API, etc.).
        $forecast = [];
        if ($this->activeTab === 'reports' && $this->business->isPro()) {
            $forecast = $this->book->forecast30Days();
        }

        // Build comparison data (Pro, custom date range only)
        $comparisonData = null;
        if ($this->business->isPro() && $this->compareEnabled && $this->filterDuration === 'custom'
            && $this->filterCustomFrom !== '' && $this->filterCustomTo !== '') {
            $comparisonData = $this->buildComparisonData($allEntries);
        }

        // Reverse for display: newest first
        $entries = $entries->reverse()->values();

        $totalIn  = $this->book->totalIn();
        $totalOut = $this->book->totalOut();
        $balance  = $this->book->balance();

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

        return view('livewire.book.show', compact(
            'entries', 'totalIn', 'totalOut', 'balance', 'categories', 'paymentModes', 'reportData',
            'activityLog', 'activityTotal', 'activityMembers',
            'recurringEntries', 'commentThread', 'commentMembers', 'comparisonData',
            'forecast'
        ));
    }
}
