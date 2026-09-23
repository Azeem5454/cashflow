<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesApiAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\EntryResource;
use App\Models\RecurringEntry;
use App\Services\AiQuota;
use App\Services\BookInsightsService;
use App\Services\BookLedger;
use App\Support\BusinessLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\RateLimiter;

class BookController extends Controller
{
    use AuthorizesApiAccess;

    /**
     * GET /api/v1/books/{id}
     */
    public function show(Request $request, string $id): BookResource
    {
        $book = $this->findAuthorizedBook($request, $id);

        $book->total_in  = BookLedger::money($book->totalIn());
        $book->total_out = BookLedger::money($book->totalOut());
        $book->balance   = $book->balance();
        $book->loadCount('entries');

        return new BookResource($book);
    }

    /**
     * GET /api/v1/books/{id}/entries
     * Newest first (date desc, created_at desc, id desc). Running balance is
     * computed over the whole book from the opening balance, then filters are
     * applied — identical to the web ledger.
     */
    public function entries(Request $request, string $id): AnonymousResourceCollection
    {
        $book    = $this->findAuthorizedBook($request, $id);
        $filters = $this->validatedFilters($request, $book);

        $request->validate([
            'page'    => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1'],
        ]);

        $all      = BookLedger::chronological($book, ['creator'], withCommentsCount: true);
        $filtered = BookLedger::applyFilters($all, $filters);

        $reversed = $filtered->reverse()->values();
        $perPage  = min(100, max(1, (int) $request->query('perPage', 50)));
        $page     = max(1, (int) $request->query('page', 1));
        $sliced   = $reversed->forPage($page, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced, $reversed->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // camelCase meta keys per the API contract, alongside Laravel's default
        // snake_case keys (kept for backwards compatibility with older app builds).
        // `total` is already present in Laravel's meta — adding it again would be
        // merged recursively into an array.
        return EntryResource::collection($paginator)->additional(['meta' => [
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'perPage'     => $paginator->perPage(),
        ]]);
    }

    /**
     * GET /api/v1/books/{id}/summary
     * Accepts the same filters as /entries; totals cover the filtered set.
     */
    public function summary(Request $request, string $id): JsonResponse
    {
        $book    = $this->findAuthorizedBook($request, $id);
        $filters = $this->validatedFilters($request, $book);

        $all       = BookLedger::chronological($book);
        $isFiltered = BookLedger::hasFilters($filters);
        $entries   = $isFiltered ? BookLedger::applyFilters($all, $filters) : $all;
        $totals    = BookLedger::totals($entries);

        $opening = BookLedger::money($book->opening_balance);
        $net     = bcsub($totals['totalIn'], $totals['totalOut'], 2);
        $balance = bcadd($opening, $net, 2);

        $entryCount = $entries->count();
        if ($book->period_starts_at && $book->period_ends_at && ! $isFiltered) {
            $daySpan = (int) $book->period_starts_at->diffInDays($book->period_ends_at) + 1;
        } elseif ($entryCount > 0) {
            $min     = $entries->min(fn ($e) => $e->date->format('Y-m-d'));
            $max     = $entries->max(fn ($e) => $e->date->format('Y-m-d'));
            $daySpan = (int) \Carbon\Carbon::parse($min)->diffInDays(\Carbon\Carbon::parse($max)) + 1;
        } else {
            $daySpan = 0;
        }

        return response()->json([
            'totalIn'        => $totals['totalIn'],
            'totalOut'       => $totals['totalOut'],
            'net'            => $net,
            'balance'        => $balance,
            'openingBalance' => $opening,
            'entryCount'     => $entryCount,
            'inCount'        => $totals['inCount'],
            'outCount'       => $totals['outCount'],
            'currency'       => $book->business->currency,
            'currencySymbol' => $book->business->currencySymbol(),
            'daySpan'        => $daySpan,
            'filtered'       => $isFiltered,
        ]);
    }

    /**
     * POST /api/v1/books/{id}/categories  {name}
     */
    public function addCategory(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id, requireEditor: true);
        $name = $this->validatedListName($request);

        $exists = $book->categories()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();
        if (! $exists) {
            $book->categories()->create(['name' => $name]);
        }

        return response()->json(['data' => $book->categories()->pluck('name')->toArray()]);
    }

    /**
     * POST /api/v1/books/{id}/payment-modes  {name}
     */
    public function addPaymentMode(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id, requireEditor: true);
        $name = $this->validatedListName($request);

        $exists = $book->paymentModes()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();
        if (! $exists) {
            $book->paymentModes()->create(['name' => $name]);
        }

        return response()->json(['data' => $book->paymentModes()->pluck('name')->toArray()]);
    }

    /**
     * GET /api/v1/books/{id}/categories
     */
    public function categories(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        return response()->json([
            'data' => $book->categories()->pluck('name')->toArray(),
        ]);
    }

    /**
     * GET /api/v1/books/{id}/payment-modes
     */
    public function paymentModes(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        return response()->json([
            'data' => $book->paymentModes()->pluck('name')->toArray(),
        ]);
    }

    /**
     * GET /api/v1/books/{id}/recent-books
     * Returns recently updated books across all user's businesses (for dashboard)
     */
    public function recentBooks(Request $request): JsonResponse
    {
        $user = $request->user();

        // Books of free-plan locked businesses are left out — they'd 403 on open.
        $businessIds = $user->businesses()->get()
            ->reject(fn ($b) => BusinessLock::isLocked($user, $b, $b->pivot?->role))
            ->pluck('id');

        $recentBooks = \App\Models\Book::whereIn('business_id', $businessIds)
            ->with('business:id,name,currency')
            ->withCount('entries')
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(function ($book) {
                $net = $book->balance();

                return [
                    'id'             => $book->id,
                    'name'           => $book->name,
                    'businessId'     => $book->business_id,
                    'businessName'   => $book->business->name,
                    'currency'       => $book->business->currency,
                    'currencySymbol' => $book->business->currencySymbol(),
                    'netBalance'     => $net,
                    'entriesCount'   => $book->entries_count,
                    'periodStartsAt' => $book->period_starts_at?->toDateString(),
                    'periodEndsAt'   => $book->period_ends_at?->toDateString(),
                    'updatedAt'      => $book->updated_at->toIso8601String(),
                    'updatedAgo'     => $book->updated_at->diffForHumans(),
                ];
            });

        return response()->json(['data' => $recentBooks]);
    }

    /**
     * POST /api/v1/books/{id}/suggest-category
     */
    public function suggestCategory(Request $request, string $id): JsonResponse
    {
        // Suggestions are for people writing entries — editor+ only.
        $book = $this->findAuthorizedBook($request, $id, requireEditor: true);

        // Free for every plan (not counted against the AI entry quota) —
        // only the per-user burst limit below applies.
        $request->validate([
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'type'        => ['nullable', 'in:in,out'],
        ]);

        // Per-user burst limit, shared with the web (Book\Show::suggestCategory).
        $key = \App\Livewire\Book\Show::SUGGEST_RATE_KEY . $request->user()->id;
        if (! RateLimiter::attempt($key, \App\Livewire\Book\Show::SUGGEST_RATE_LIMIT, fn () => true, 60)) {
            return response()->json([
                'category' => null,
                'message'  => 'Too many suggestions. Please wait a moment.',
            ], 429);
        }

        try {
            $categories = $book->categories()->pluck('name')->toArray();
            $result = app(\App\Services\AiService::class)->suggestCategory(
                $request->description,
                $request->input('type', 'out'),
                $categories
            );

            return response()->json(['category' => $result['category'] ?? null]);
        } catch (\Exception $e) {
            return response()->json(['category' => null]);
        }
    }

    /**
     * GET /api/v1/books/{id}/ai-quota
     * AI entry allowance that applies inside this book (the business's plan).
     */
    public function aiQuota(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        return response()->json(['quota' => AiQuota::remaining($request->user(), $book->business)]);
    }

    /**
     * POST /api/v1/books/{id}/parse  {text}
     * "Paid 120 for fuel today" → entry fields. Same parsing + sanitisation as
     * the web (Book\Show::parseEntryText → AiService::parseNaturalLanguage).
     * Counts as one AI entry (type 'nlp') against AiQuota.
     *
     * 200 {fields:{type,amount,description,category,paymentMode,date,reference}, quota}
     * 403 {code:'ai_quota_exhausted', message, resetsAt, quota}   (Free, allowance used)
     * 422 {code:'parse_failed', message, quota}                    (not a transaction)
     * 429 {code:'ai_typed_daily_limit'|'rate_limited', message, ...}
     */
    public function parseEntry(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id, requireEditor: true);
        $user = $request->user();

        $validated = $request->validate([
            'text' => ['required', 'string', 'min:3', 'max:300'],
        ]);
        $text = trim($validated['text']);
        if (mb_strlen($text) < 3) {
            return response()->json([
                'message' => 'Describe the transaction in a few words.',
                'errors'  => ['text' => ['Describe the transaction in a few words.']],
            ], 422);
        }

        // Burst limit shared with the web (Book\Show::parseEntryText).
        $burstKey = 'nlp-burst:' . $user->id;
        if (RateLimiter::tooManyAttempts($burstKey, \App\Livewire\Book\Show::NLP_BURST_LIMIT)) {
            return response()->json([
                'code'    => 'rate_limited',
                'message' => "You're going a bit fast. Wait a moment and try again.",
            ], 429);
        }
        RateLimiter::hit($burstKey, \App\Livewire\Book\Show::NLP_BURST_WINDOW);

        $business = $book->business;
        if ($denied = AiQuota::check($user, $business, AiQuota::TYPE_TYPED)) {
            return response()->json($denied->toResponseArray(), $denied->httpStatus());
        }

        $parsed = app(\App\Services\AiService::class)->parseNaturalLanguage(
            $text,
            $business->currency ?: 'USD',
            $book->categories()->pluck('name')->toArray(),
            $book->paymentModes()->pluck('name')->toArray(),
        );

        $quota = AiQuota::remaining($user, $business);

        if (! $parsed) {
            return response()->json([
                'code'    => 'parse_failed',
                'message' => "Couldn't read that as a transaction. Try something like \"Paid 120 for fuel today\".",
                'quota'   => $quota,
            ], 422);
        }

        return response()->json([
            'fields' => [
                'type'        => $parsed['type'] ?? null,
                'amount'      => isset($parsed['amount']) ? number_format((float) $parsed['amount'], 2, '.', '') : null,
                'description' => $parsed['description'] ?? null,
                'category'    => $parsed['category'] ?? null,
                'paymentMode' => $parsed['payment_mode'] ?? null,
                'date'        => $parsed['date'] ?? null,
                'reference'   => $parsed['reference'] ?? null,
            ],
            'quota' => $quota,
        ]);
    }

    /**
     * GET /api/v1/books/{id}/activity
     */
    public function activity(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        $logs = \App\Models\BookActivityLog::where('book_id', $book->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id'          => $log->id,
                'action'      => $log->action,
                'description' => $log->describe(),
                'iconType'    => $log->iconType(),
                'user'        => $log->user ? [
                    'id'   => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'meta'        => $log->meta,
                'createdAt'   => $log->created_at->toIso8601String(),
                'timeAgo'     => $log->created_at->diffForHumans(),
            ]);

        return response()->json(['data' => $logs]);
    }

    /**
     * GET /api/v1/books/{id}/recurring
     */
    public function recurringEntries(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        $entries = $book->recurringEntries()
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'paused' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => $this->recurringPayload($r));

        return response()->json(['data' => $entries]);
    }

    /**
     * PUT /api/v1/recurring/{id} — edit a recurring rule (Pro + editor).
     * Already-generated entries are not touched; next_run_at is kept.
     */
    public function updateRecurring(Request $request, string $id): JsonResponse
    {
        [$recurring, $book] = $this->findAuthorizedRecurring($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Recurring entries require a Pro subscription.'], 403);
        }

        $validated = $request->validate([
            'type'        => ['sometimes', 'required', 'in:in,out'],
            'amount'      => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'paymentMode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reference'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'frequency'   => ['sometimes', 'required', 'in:' . implode(',', EntryController::RECURRING_FREQUENCIES)],
            'endsAt'      => ['sometimes', 'nullable', 'date', 'after_or_equal:' . $recurring->starts_at->format('Y-m-d')],
        ]);

        $map = [
            'type' => 'type', 'amount' => 'amount', 'description' => 'description',
            'category' => 'category', 'paymentMode' => 'payment_mode', 'reference' => 'reference',
            'frequency' => 'frequency', 'endsAt' => 'ends_at',
        ];

        $updates = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $value = $validated[$input];
                $updates[$column] = match (true) {
                    // Description is optional — blank is stored as NULL.
                    $input === 'description' => trim((string) $value) !== '' ? trim((string) $value) : null,
                    in_array($input, ['category', 'paymentMode', 'reference', 'endsAt'], true) => $value ?: null,
                    default => $value,
                };
            }
        }

        if ($updates) {
            $recurring->update($updates);

            $this->logBookActivity($book, $request, 'recurring_updated', null, [
                'description' => $recurring->description,
                'category'    => $recurring->category,
                'type'        => $recurring->type,
            ]);
        }

        return response()->json(['data' => $this->recurringPayload($recurring->fresh())]);
    }

    /**
     * PUT /api/v1/recurring/{id}/toggle — pause / resume (editor+)
     */
    public function toggleRecurring(Request $request, string $id): JsonResponse
    {
        [$recurring, $book] = $this->findAuthorizedRecurring($request, $id);

        if ($recurring->isCompleted()) {
            return response()->json(['message' => 'This recurring rule has already completed.'], 422);
        }

        $newStatus = $recurring->isActive() ? 'paused' : 'active';

        // Pausing is always allowed; resuming a rule is a Pro feature.
        if ($newStatus === 'active' && ! $book->business->isPro()) {
            return response()->json([
                'message' => 'Resuming recurring entries requires a Pro subscription.',
                'code'    => 'pro_required',
            ], 403);
        }

        $recurring->update(['status' => $newStatus]);

        $this->logBookActivity($book, $request, $newStatus === 'paused' ? 'recurring_paused' : 'recurring_resumed', null, [
            'description' => $recurring->description,
            'category'    => $recurring->category,
            'type'        => $recurring->type,
        ]);

        return response()->json([
            'status' => $newStatus,
            'data'   => $this->recurringPayload($recurring->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/recurring/{id} (editor+)
     */
    public function deleteRecurring(Request $request, string $id): JsonResponse
    {
        [$recurring, $book] = $this->findAuthorizedRecurring($request, $id);

        $meta = [
            'description' => $recurring->description,
            'category'    => $recurring->category,
            'type'        => $recurring->type,
        ];
        $recurring->delete();

        $this->logBookActivity($book, $request, 'recurring_deleted', null, $meta);

        return response()->json(['message' => 'Recurring entry deleted.']);
    }

    /**
     * GET /api/v1/books/{id}/insights[?refresh=1]
     *
     * Same generation, 24h cache (books.ai_insights_cache, shared with web),
     * limits (1/min burst + 10/day per user) and ai_usage_logs as the web
     * Reports tab (Book\Show::generateInsights).
     */
    public function aiInsights(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required.'], 403);
        }

        $service = app(BookInsightsService::class);
        $userId  = (string) $request->user()->id;
        $refresh = $request->boolean('refresh');

        $cached  = $this->decodeInsightsCache($book);
        $isFresh = $cached && $book->ai_insights_generated_at
            && $book->ai_insights_generated_at->diffInHours(now()) < BookInsightsService::CACHE_HOURS;

        if ($isFresh && ! $refresh) {
            return response()->json(['data' => $cached]);
        }

        $entries = $book->entries()->get();
        if ($entries->count() < BookInsightsService::MIN_ENTRIES) {
            return response()->json([
                'data'    => null,
                'status'  => 'not_enough_data',
                'message' => 'Add at least 3 entries to generate insights.',
            ]);
        }

        // Per-user burst: max 1 generation per 60 s
        $burstKey = BookInsightsService::BURST_KEY_PREFIX . $userId;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($burstKey, 1)) {
            return $this->insightsLimitResponse($cached, 'Please wait a minute before generating insights again.');
        }

        // Daily cap: 10 insights/day per user
        if ($service->dailyLimitReached($userId)) {
            return $this->insightsLimitResponse($cached, 'You have reached today\'s limit of 10 AI insights. Try again tomorrow.');
        }

        \Illuminate\Support\Facades\RateLimiter::hit($burstKey, 60);

        try {
            $result = $service->generate($book, $entries);
        } catch (\Throwable $e) {
            report($e);
            $result = null;
        }

        if (! $result) {
            return response()->json([
                'data'    => null,
                'status'  => 'failed',
                'message' => 'Could not generate insights right now. Please try again.',
            ]);
        }

        $book->update([
            'ai_insights_cache'        => json_encode($result),
            'ai_insights_generated_at' => now(),
        ]);

        return response()->json(['data' => $this->insightsPayload($result, $book->fresh()->ai_insights_generated_at, false)]);
    }

    /**
     * GET /api/v1/books/{id}/export/{format}
     *
     * Streams the PDF or CSV file directly with Sanctum auth so the mobile
     * app can download it in-app without opening a browser + re-logging in.
     */
    public function export(Request $request, string $id, string $format)
    {
        $book = $this->findAuthorizedBook($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required for exports.'], 403);
        }

        if (! in_array($format, ['pdf', 'csv'], true)) {
            return response()->json(['message' => 'Format must be pdf or csv.'], 422);
        }

        // Same budget as the web ExportController (shared key): 10/min per user.
        if (! RateLimiter::attempt('export:' . $request->user()->id, 10, fn () => true, 60)) {
            return response()->json(['message' => 'Too many exports. Please wait a minute and try again.'], 429);
        }

        $business = $book->business;
        $entries  = $this->entriesWithRunningBalance($book);
        $slug     = str()->slug($business->name) . '-' . str()->slug($book->name);

        if ($format === 'pdf') {
            $totalIn  = $book->totalIn();
            $totalOut = $book->totalOut();
            $balance  = $book->balance();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.book-pdf', compact(
                'business', 'book', 'entries', 'totalIn', 'totalOut', 'balance'
            ))->setPaper('a4', 'landscape');

            return $pdf->download("{$slug}.pdf");
        }

        // CSV
        return response()->streamDownload(function () use ($entries) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($handle, ['Date', 'Description', 'Reference', 'Category', 'Payment Mode', 'Cash In', 'Cash Out', 'Running Balance']);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->date?->format('Y-m-d'),
                    $entry->description,
                    $entry->reference,
                    $entry->category,
                    $entry->payment_mode,
                    $entry->type === 'in' ? $entry->amount : '',
                    $entry->type === 'out' ? $entry->amount : '',
                    $entry->running_balance,
                ]);
            }

            fclose($handle);
        }, "{$slug}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Shared helper for export — chronological entries with running balance from the opening balance */
    private function entriesWithRunningBalance(\App\Models\Book $book)
    {
        return BookLedger::chronological($book);
    }

    /**
     * PUT /api/v1/books/{id} — update book metadata
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        $this->ensureEditor($request, $book);

        $validated = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'openingBalance' => ['sometimes', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'periodStartsAt' => ['nullable', 'date'],
            'periodEndsAt'   => ['nullable', 'date', 'after_or_equal:periodStartsAt'],
        ]);

        $book->update([
            'name'             => $validated['name']             ?? $book->name,
            'description'      => array_key_exists('description', $validated)    ? $validated['description']    : $book->description,
            'opening_balance'  => $validated['openingBalance']  ?? $book->opening_balance,
            'period_starts_at' => array_key_exists('periodStartsAt', $validated) ? $validated['periodStartsAt'] : $book->period_starts_at,
            'period_ends_at'   => array_key_exists('periodEndsAt', $validated)   ? $validated['periodEndsAt']   : $book->period_ends_at,
        ]);

        return response()->json(['message' => 'Book updated.']);
    }

    /**
     * DELETE /api/v1/books/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        // Deleting a whole book is owner-only (editors keep create/edit/duplicate).
        $this->ensureOwnerRole(
            $this->memberRole($request->user(), $book->business_id),
            'Only the business owner can delete a book.'
        );

        $book->reportSchedule()->delete();
        $book->delete();

        return response()->json(['message' => 'Book deleted.']);
    }

    /**
     * POST /api/v1/books/{id}/duplicate — duplicate book with options
     */
    public function duplicate(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        $this->ensureEditor($request, $book);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'periodStartsAt'    => ['nullable', 'date'],
            'periodEndsAt'      => ['nullable', 'date', 'after_or_equal:periodStartsAt'],
            'copyCategories'    => ['boolean'],
            'copyPaymentModes'  => ['boolean'],
            'copyEntries'       => ['boolean'],
        ]);

        $newBook = $book->business->books()->create([
            'name'             => $validated['name'],
            'description'      => $book->description,
            'opening_balance'  => $book->opening_balance,
            'period_starts_at' => $validated['periodStartsAt'] ?? null,
            'period_ends_at'   => $validated['periodEndsAt']   ?? null,
        ]);

        if (! empty($validated['copyCategories'])) {
            foreach ($book->categories as $cat) {
                $newBook->categories()->create(['name' => $cat->name]);
            }
        }
        if (! empty($validated['copyPaymentModes'])) {
            foreach ($book->paymentModes as $pm) {
                $newBook->paymentModes()->create(['name' => $pm->name]);
            }
        }
        if (! empty($validated['copyEntries'])) {
            foreach ($book->entries as $entry) {
                $newBook->entries()->create([
                    'type'         => $entry->type,
                    'amount'       => $entry->amount,
                    'description'  => $entry->description,
                    'date'         => $entry->date,
                    'category'     => $entry->category,
                    'payment_mode' => $entry->payment_mode,
                    'reference'    => $entry->reference,
                    'created_by'   => $request->user()->id,
                ]);
            }
        }

        return response()->json([
            'id'   => $newBook->id,
            'name' => $newBook->name,
        ], 201);
    }

    /**
     * GET /api/v1/books/{id}/report-data — full report data for charts
     */
    public function reportData(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required.'], 403);
        }

        $entries = $book->entries()->orderBy('date')->get();
        $totalIn  = (float) $book->totalIn();
        $totalOut = (float) $book->totalOut();
        $netBalance = $totalIn - $totalOut + (float) $book->opening_balance;

        // Trend buckets — by day if <60 entries, week if <180, else month
        $bucketBy = $entries->count() < 60 ? 'day' : ($entries->count() < 180 ? 'week' : 'month');
        $trend = [];
        foreach ($entries as $e) {
            $key = match ($bucketBy) {
                'day'   => $e->date->format('Y-m-d'),
                'week'  => $e->date->format('Y-\WW'),
                'month' => $e->date->format('Y-m'),
            };
            if (! isset($trend[$key])) $trend[$key] = ['label' => $key, 'in' => 0.0, 'out' => 0.0];
            $trend[$key][$e->type] += (float) $e->amount;
        }
        $trend = array_values($trend);

        // Category breakdown
        $byCategoryOut = [];
        $byCategoryIn  = [];
        foreach ($entries as $e) {
            $cat = $e->category ?: 'Uncategorized';
            if ($e->type === 'in') {
                $byCategoryIn[$cat] = ($byCategoryIn[$cat] ?? 0) + (float) $e->amount;
            } else {
                $byCategoryOut[$cat] = ($byCategoryOut[$cat] ?? 0) + (float) $e->amount;
            }
        }
        arsort($byCategoryOut);
        arsort($byCategoryIn);

        // Payment mode breakdown
        $byPaymentMode = [];
        foreach ($entries as $e) {
            $mode = $e->payment_mode ?: 'Unspecified';
            $byPaymentMode[$mode] = ($byPaymentMode[$mode] ?? 0) + (float) $e->amount;
        }
        arsort($byPaymentMode);

        return response()->json([
            'periodSummary' => [
                'totalIn'      => $totalIn,
                'totalOut'     => $totalOut,
                'netBalance'   => $netBalance,
                'entryCount'   => $entries->count(),
                'inCount'      => $entries->where('type', 'in')->count(),
                'outCount'     => $entries->where('type', 'out')->count(),
            ],
            'trend'         => $trend,
            'bucketBy'      => $bucketBy,
            'byCategoryOut' => array_map(fn ($k, $v) => ['name' => $k, 'total' => $v], array_keys($byCategoryOut), $byCategoryOut),
            'byCategoryIn'  => array_map(fn ($k, $v) => ['name' => $k, 'total' => $v], array_keys($byCategoryIn), $byCategoryIn),
            'byPaymentMode' => array_map(fn ($k, $v) => ['name' => $k, 'total' => $v], array_keys($byPaymentMode), $byPaymentMode),
            'currencySymbol' => $book->business->currencySymbol(),
        ]);
    }

    /**
     * GET /api/v1/books/{id}/report-schedule
     */
    public function reportSchedule(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        $this->ensureEditor($request, $book, 'Only owners and editors can manage email reports.');

        $schedule = \App\Models\ReportSchedule::where('book_id', $book->id)->first();

        if (! $schedule) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'id'         => $schedule->id,
            'frequency'  => $schedule->frequency,
            'recipients' => $schedule->recipients,
            'isActive'   => $schedule->is_active,
            'lastSentAt' => $schedule->last_sent_at?->toIso8601String(),
        ]]);
    }

    /**
     * PUT /api/v1/books/{id}/report-schedule — create or update schedule
     */
    public function saveReportSchedule(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        $this->ensureEditor($request, $book, 'Only owners and editors can manage email reports.');

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required.'], 403);
        }

        $validated = $request->validate([
            'frequency'  => ['required', 'in:weekly,monthly'],
            'recipients' => ['required', 'array', 'min:1', 'max:10'],
            'recipients.*' => ['required', 'string', 'email', 'max:255'],
            'isActive'   => ['boolean'],
        ]);

        $recipients = array_values(array_unique(array_map(
            fn ($e) => strtolower(trim($e)),
            $validated['recipients']
        )));

        $schedule = \App\Models\ReportSchedule::updateOrCreate(
            ['book_id' => $book->id],
            [
                'frequency'  => $validated['frequency'],
                'recipients' => $recipients,
                'is_active'  => $validated['isActive'] ?? true,
            ]
        );

        // Like the web (Book\Show::saveEmailReport): a newly created, active
        // schedule sends its first report right away.
        $firstSent = false;
        if ($schedule->wasRecentlyCreated && $schedule->is_active) {
            try {
                $schedule->setRelation('book', $book);
                $reportData = $schedule->buildReportData();
                foreach ($schedule->recipients as $recipientEmail) {
                    \Illuminate\Support\Facades\Mail::to($recipientEmail)->queue(
                        new \App\Mail\BookEmailReport($book, $reportData, $schedule->frequency)
                    );
                }
                $schedule->update(['last_sent_at' => now()]);
                $firstSent = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('First email report send failed', [
                    'book_id' => $book->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message'   => $firstSent ? 'Report schedule saved. First report sent.' : 'Report schedule saved.',
            'id'        => $schedule->id,
            'firstSent' => $firstSent,
        ]);
    }

    /**
     * DELETE /api/v1/books/{id}/report-schedule
     */
    public function deleteReportSchedule(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        $this->ensureEditor($request, $book, 'Only owners and editors can manage email reports.');

        \App\Models\ReportSchedule::where('book_id', $book->id)->delete();

        return response()->json(['message' => 'Report schedule deleted.']);
    }

    private function ensureEditor(Request $request, \App\Models\Book $book, string $message = 'Editor or owner role required.'): void
    {
        $this->ensureEditorRole($this->memberRole($request->user(), $book->business_id), $message);
    }

    /**
     * @return array{0: RecurringEntry, 1: \App\Models\Book}
     */
    private function findAuthorizedRecurring(Request $request, string $id): array
    {
        $this->abortUnlessUuid($id);

        $recurring = RecurringEntry::findOrFail($id);
        $book      = $this->findAuthorizedBook($request, $recurring->book_id);
        $this->ensureEditor($request, $book, 'Only owners and editors can manage recurring entries.');

        return [$recurring, $book];
    }

    private function recurringPayload(RecurringEntry $r): array
    {
        return [
            'id'          => $r->id,
            'type'        => $r->type,
            'amount'      => $r->amount,
            'description' => $r->description,
            'category'    => $r->category,
            'paymentMode' => $r->payment_mode,
            'reference'   => $r->reference,
            'frequency'   => $r->frequency,
            'startsAt'    => $r->starts_at?->toDateString(),
            'nextRunAt'   => $r->next_run_at?->toDateString(),
            'endsAt'      => $r->ends_at?->toDateString(),
            'status'      => $r->status,
            'createdAt'   => $r->created_at?->toIso8601String(),
        ];
    }

    /**
     * Free businesses keep the preset windows the apps send (Today, Yesterday,
     * Last 7 / 30 days — each a ≤30-day window ending around today; a couple
     * of days' slack covers client time zones). Any other from/to is a custom
     * range, which is Pro: it's ignored, and the response is all-time.
     */
    public const FREE_RANGE_MAX_DAYS = 30;

    private function validatedFilters(Request $request, \App\Models\Book $book): array
    {
        $request->validate([
            'type'          => ['nullable', 'in:in,out,all'],
            'from'          => ['nullable', 'date_format:Y-m-d'],
            'to'            => ['nullable', 'date_format:Y-m-d'],
            'search'        => ['nullable', 'string', 'max:255'],
            'category'      => ['nullable'],
            'category.*'    => ['nullable', 'string', 'max:100'],
            'paymentMode'   => ['nullable'],
            'paymentMode.*' => ['nullable', 'string', 'max:100'],
        ]);

        $filters = [];
        foreach (BookLedger::FILTER_KEYS as $key) {
            $value = $request->query($key);
            if (is_string($value) && mb_strlen($value) > 255) {
                $value = mb_substr($value, 0, 255);
            }
            $filters[$key] = $value;
        }

        if (! $book->business->isPro() && ! $this->isPresetRange($filters['from'] ?? null, $filters['to'] ?? null)) {
            $filters['from'] = null;
            $filters['to']   = null;
        }

        return $filters;
    }

    private function isPresetRange(?string $from, ?string $to): bool
    {
        if (($from === null || $from === '') && ($to === null || $to === '')) {
            return true; // no date filter at all
        }
        if (! $from || ! $to) {
            return false; // open-ended ranges are custom
        }

        try {
            $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
            $toDate   = \Carbon\Carbon::createFromFormat('Y-m-d', $to)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        $today = now()->startOfDay();

        return $fromDate->lte($toDate)
            && $fromDate->diffInDays($toDate) < self::FREE_RANGE_MAX_DAYS
            && $toDate->between($today->copy()->subDays(2), $today->copy()->addDay());
    }

    private function validatedListName(Request $request): string
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($validated['name']);
        if ($name === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'The name field is required.']);
        }

        return $name;
    }

    /**
     * Cached insights are stored by the web in snake_case with a lowercase
     * sentiment; map to the API contract shape. Returns null for missing or
     * unrecognised cache content.
     */
    private function decodeInsightsCache(\App\Models\Book $book): ?array
    {
        if (! $book->ai_insights_cache) {
            return null;
        }

        $decoded = json_decode($book->ai_insights_cache, true);
        if (! is_array($decoded) || empty($decoded['sentiment']) || ! isset($decoded['bullets']) || ! is_array($decoded['bullets'])) {
            return null;
        }

        return $this->insightsPayload($decoded, $book->ai_insights_generated_at, true);
    }

    private function insightsPayload(array $data, $generatedAt, bool $cached): array
    {
        $sentiment = strtolower((string) ($data['sentiment'] ?? ''));
        $sentiment = in_array($sentiment, ['healthy', 'watch', 'concern'], true) ? $sentiment : 'watch';

        return [
            'sentiment'       => ucfirst($sentiment),
            'sentimentReason' => (string) ($data['sentiment_reason'] ?? ''),
            'bullets'         => array_values(array_map('strval', (array) ($data['bullets'] ?? []))),
            'tip'             => isset($data['tip']) && $data['tip'] !== '' ? (string) $data['tip'] : null,
            'generatedAt'     => $generatedAt?->toIso8601String(),
            'cached'          => $cached,
        ];
    }

    /**
     * Limit hit: like the web, fall back to the last cached insights if any.
     */
    private function insightsLimitResponse(?array $cached, string $message): JsonResponse
    {
        return response()->json([
            'data'    => $cached,
            'status'  => 'limit_reached',
            'message' => $message,
        ]);
    }
}
