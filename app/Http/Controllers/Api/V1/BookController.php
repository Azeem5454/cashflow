<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\EntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * GET /api/v1/books/{id}
     */
    public function show(Request $request, string $id): BookResource
    {
        $book = $this->findAuthorizedBook($request, $id);

        $book->total_in  = $book->totalIn();
        $book->total_out = $book->totalOut();
        $book->balance   = $book->balance();
        $book->loadCount('entries');

        return new BookResource($book);
    }

    /**
     * GET /api/v1/books/{id}/entries
     */
    public function entries(Request $request, string $id): AnonymousResourceCollection
    {
        $book = $this->findAuthorizedBook($request, $id);

        $query = $book->entries()
            ->with('creator')
            ->withCount('comments');

        // Optional filters
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($paymentMode = $request->query('paymentMode')) {
            $query->where('payment_mode', $paymentMode);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ilike', "%{$search}%")
                  ->orWhere('reference', 'ilike', "%{$search}%");
            });
        }

        if ($from = $request->query('from')) {
            $query->where('date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('date', '<=', $to);
        }

        // Fetch all matching entries in chronological order for running balance calc
        $allFiltered = $query
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Compute running balance on chronological order
        $runningBalance = (string) $book->opening_balance;

        $allFiltered->each(function ($entry) use (&$runningBalance) {
            if ($entry->type === 'in') {
                $runningBalance = bcadd($runningBalance, (string) $entry->amount, 2);
            } else {
                $runningBalance = bcsub($runningBalance, (string) $entry->amount, 2);
            }
            $entry->running_balance = $runningBalance;
        });

        // Reverse for display (newest first) then paginate
        $reversed = $allFiltered->reverse()->values();
        $perPage  = (int) $request->query('perPage', 50);
        $page     = (int) $request->query('page', 1);
        $sliced   = $reversed->forPage($page, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced, $reversed->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return EntryResource::collection($paginator);
    }

    /**
     * GET /api/v1/books/{id}/summary
     */
    public function summary(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        $totalIn  = $book->totalIn();
        $totalOut = $book->totalOut();
        $balance  = $book->balance();

        $entryCount = $book->entries()->count();
        $daySpan    = $book->period_starts_at && $book->period_ends_at
            ? $book->period_starts_at->diffInDays($book->period_ends_at) + 1
            : ($entryCount > 0 ? $book->entries()->min('date') ? now()->parse($book->entries()->min('date'))->diffInDays(now()->parse($book->entries()->max('date'))) + 1 : 1 : 0);

        return response()->json([
            'totalIn'        => $totalIn,
            'totalOut'       => $totalOut,
            'balance'        => $balance,
            'openingBalance' => $book->opening_balance,
            'entryCount'     => $entryCount,
            'inCount'        => $book->entries()->where('type', 'in')->count(),
            'outCount'       => $book->entries()->where('type', 'out')->count(),
            'currency'       => $book->business->currency,
            'currencySymbol' => $book->business->currencySymbol(),
            'daySpan'        => $daySpan,
        ]);
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
        $businessIds = $request->user()->businesses()->pluck('businesses.id');

        $recentBooks = \App\Models\Book::whereIn('business_id', $businessIds)
            ->with('business:id,name,currency')
            ->withCount('entries')
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(function ($book) {
                $totalIn  = (float) $book->totalIn();
                $totalOut = (float) $book->totalOut();
                $net      = $totalIn - $totalOut + (float) $book->opening_balance;

                return [
                    'id'             => $book->id,
                    'name'           => $book->name,
                    'businessId'     => $book->business_id,
                    'businessName'   => $book->business->name,
                    'currency'       => $book->business->currency,
                    'currencySymbol' => $book->business->currencySymbol(),
                    'netBalance'     => number_format($net, 2, '.', ''),
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
        $book = $this->findAuthorizedBook($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['category' => null]);
        }

        $request->validate(['description' => ['required', 'string', 'min:3']]);

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
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'type'        => $r->type,
                'amount'      => $r->amount,
                'description' => $r->description,
                'category'    => $r->category,
                'paymentMode' => $r->payment_mode,
                'frequency'   => $r->frequency,
                'status'      => $r->status,
                'nextRunAt'   => $r->next_run_at?->toDateString(),
                'endsAt'      => $r->ends_at?->toDateString(),
                'createdAt'   => $r->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $entries]);
    }

    /**
     * PUT /api/v1/recurring/{id}/toggle
     */
    public function toggleRecurring(Request $request, string $id): JsonResponse
    {
        $recurring = \App\Models\RecurringEntry::findOrFail($id);
        $book = $this->findAuthorizedBook($request, $recurring->book_id);

        $newStatus = $recurring->status === 'active' ? 'paused' : 'active';
        $recurring->update(['status' => $newStatus]);

        return response()->json(['status' => $newStatus]);
    }

    /**
     * DELETE /api/v1/recurring/{id}
     */
    public function deleteRecurring(Request $request, string $id): JsonResponse
    {
        $recurring = \App\Models\RecurringEntry::findOrFail($id);
        $book = $this->findAuthorizedBook($request, $recurring->book_id);

        $recurring->delete();

        return response()->json(['message' => 'Recurring entry deleted.']);
    }

    /**
     * GET /api/v1/books/{id}/insights
     */
    public function aiInsights(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required.'], 403);
        }

        // Return cached insights if fresh (< 24h)
        if ($book->ai_insights_cache && $book->ai_insights_generated_at?->diffInHours(now()) < 24) {
            return response()->json(json_decode($book->ai_insights_cache, true));
        }

        // Generate new insights
        $entryCount = $book->entries()->count();
        if ($entryCount < 3) {
            return response()->json(['status' => 'not_enough_data', 'message' => 'Add at least 3 entries to generate insights.']);
        }

        try {
            $totalIn  = (float) $book->totalIn();
            $totalOut = (float) $book->totalOut();
            $net      = $totalIn - $totalOut + (float) $book->opening_balance;

            $topCategories = $book->entries()
                ->whereNotNull('category')
                ->selectRaw("category, type, sum(amount) as total")
                ->groupBy('category', 'type')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $result = app(\App\Services\AiService::class)->generateInsights([
                'totalIn'        => $totalIn,
                'totalOut'       => $totalOut,
                'netBalance'     => $net,
                'entryCount'     => $entryCount,
                'topCategories'  => $topCategories->toArray(),
                'currency'       => $book->business->currency,
            ]);

            $book->update([
                'ai_insights_cache'        => json_encode($result),
                'ai_insights_generated_at' => now(),
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to generate insights.'], 500);
        }
    }

    /**
     * GET /api/v1/books/{id}/export/{format}
     */
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

    /** Shared helper for export — entries ordered with running balance */
    private function entriesWithRunningBalance(\App\Models\Book $book)
    {
        $entries = $book->entries()->orderBy('date', 'asc')->orderBy('created_at', 'asc')->get();
        $running = '0.00';
        foreach ($entries as $entry) {
            $running = $entry->type === 'in'
                ? bcadd($running, (string) $entry->amount, 2)
                : bcsub($running, (string) $entry->amount, 2);
            $entry->running_balance = $running;
        }
        return $entries;
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
            'openingBalance' => ['sometimes', 'numeric', 'min:0'],
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
        $this->ensureEditor($request, $book);

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
        $this->ensureEditor($request, $book);

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
        $this->ensureEditor($request, $book);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required.'], 403);
        }

        $validated = $request->validate([
            'frequency'  => ['required', 'in:weekly,monthly'],
            'recipients' => ['required', 'array', 'min:1', 'max:10'],
            'recipients.*' => ['required', 'string', 'email', 'max:255'],
            'isActive'   => ['boolean'],
        ]);

        $schedule = \App\Models\ReportSchedule::updateOrCreate(
            ['book_id' => $book->id],
            [
                'frequency'  => $validated['frequency'],
                'recipients' => $validated['recipients'],
                'is_active'  => $validated['isActive'] ?? true,
            ]
        );

        return response()->json(['message' => 'Report schedule saved.', 'id' => $schedule->id]);
    }

    /**
     * DELETE /api/v1/books/{id}/report-schedule
     */
    public function deleteReportSchedule(Request $request, string $id): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);
        $this->ensureEditor($request, $book);

        \App\Models\ReportSchedule::where('book_id', $book->id)->delete();

        return response()->json(['message' => 'Report schedule deleted.']);
    }

    /**
     * Finds a book that belongs to a business the user is a member of.
     */
    private function findAuthorizedBook(Request $request, string $bookId)
    {
        $businessIds = $request->user()->businesses()->pluck('businesses.id');

        return \App\Models\Book::whereIn('business_id', $businessIds)
            ->findOrFail($bookId);
    }

    private function ensureEditor(Request $request, \App\Models\Book $book): void
    {
        $role = \Illuminate\Support\Facades\DB::table('business_user')
            ->where('business_id', $book->business_id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        abort_unless($role && $role !== 'viewer', 403, 'Editor or owner role required.');
    }
}
