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

    // Recurring entry form (in slide-over)
    public bool   $entryRecurring  = false;
    public string $entryFrequency  = 'weekly';
    public string $entryEndsAt     = '';
    public bool   $entryRunForever = false;

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

        // Build report data before reversing (reports need chronological order)
        $reportData = [];
        if ($this->activeTab === 'reports' && $this->business->isPro()) {
            $reportData = $this->buildReportData($entries);
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

        $activityLog = $this->activeTab === 'activity'
            ? BookActivityLog::where('book_id', $this->book->id)
                ->with('user')
                ->latest()
                ->limit(100)
                ->get()
            : collect();

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
            'entries', 'totalIn', 'totalOut', 'balance', 'categories', 'paymentModes', 'reportData', 'activityLog', 'recurringEntries',
            'commentThread', 'commentMembers', 'comparisonData'
        ));
    }
}
