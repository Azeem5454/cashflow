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
     * Finds a book that belongs to a business the user is a member of.
     */
    private function findAuthorizedBook(Request $request, string $bookId)
    {
        $businessIds = $request->user()->businesses()->pluck('businesses.id');

        return \App\Models\Book::whereIn('business_id', $businessIds)
            ->findOrFail($bookId);
    }
}
