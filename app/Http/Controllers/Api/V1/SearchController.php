<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SearchEntryResource;
use App\Services\EntrySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * GET /api/v1/search — global entry search across every business and book the
 * user can access. See App\Services\EntrySearch for the access + matching
 * rules (shared with the web page at /search).
 */
class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q'          => ['nullable', 'string', 'max:120'],
            'type'       => ['nullable', 'in:in,out'],
            'from'       => ['nullable', 'date_format:Y-m-d'],
            'to'         => ['nullable', 'date_format:Y-m-d'],
            'businessId' => ['nullable', 'uuid'],
            'minAmount'  => ['nullable', 'numeric', 'min:0'],
            'maxAmount'  => ['nullable', 'numeric', 'min:0'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'perPage'    => ['nullable', 'integer', 'min:1'],
        ]);

        $user       = $request->user();
        $filters    = EntrySearch::normalise($request->query());
        $businesses = EntrySearch::accessibleBusinesses($user);
        $query      = EntrySearch::query($user, $filters, $businesses);

        $perPage = EntrySearch::perPage($request->query('perPage'));
        $page    = max(1, (int) $request->query('page', 1));

        // No criteria (blank or 1-char q with no other filter) or nothing the
        // user can search → a well-formed empty result, never a 422. The
        // clients debounce keystrokes, so an early "q" must not 422-flash.
        if (! $query) {
            $totals = EntrySearch::totals(null, $businesses);

            return response()->json([
                'data'  => [],
                'meta'  => ['total' => 0, 'currentPage' => 1, 'lastPage' => 1, 'perPage' => $perPage],
                'totals' => $totals['totals'],
                'totalsByCurrency' => $totals['totalsByCurrency'],
            ]);
        }

        $total   = (clone $query)->reorder()->toBase()->count();
        $results = $query->with(['book.business', 'creator'])
            ->withCount('comments')
            ->forPage($page, $perPage)
            ->get();

        $paginator = new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        $totals = EntrySearch::totals($query, $businesses);

        return response()->json([
            'data'  => SearchEntryResource::collection($paginator->items())->toArray($request),
            'meta'  => [
                'total'       => $paginator->total(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'perPage'     => $paginator->perPage(),
            ],
            'totals'           => $totals['totals'],
            'totalsByCurrency' => $totals['totalsByCurrency'],
        ]);
    }
}
