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
    public function export(Request $request, string $id, string $format): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $id);

        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required for exports.'], 403);
        }

        // Return the web URL for downloading (user opens in browser)
        $business = $book->business;
        $url = url("/businesses/{$business->id}/books/{$book->id}/export/{$format}");

        return response()->json(['url' => $url, 'format' => $format]);
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
}
