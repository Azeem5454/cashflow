<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\BusinessResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends Controller
{
    /**
     * GET /api/v1/businesses
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $businesses = $request->user()
            ->businesses()
            ->withCount(['books', 'members'])
            ->orderBy('name')
            ->get();

        return BusinessResource::collection($businesses);
    }

    /**
     * GET /api/v1/businesses/{id}
     */
    public function show(Request $request, string $id): BusinessResource
    {
        $business = $request->user()
            ->businesses()
            ->withCount(['books', 'members'])
            ->findOrFail($id);

        return new BusinessResource($business);
    }

    /**
     * GET /api/v1/businesses/{id}/books
     */
    public function books(Request $request, string $id): AnonymousResourceCollection
    {
        $business = $request->user()
            ->businesses()
            ->findOrFail($id);

        $books = $business->books()
            ->withCount('entries')
            ->orderByDesc('period_starts_at')
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($book) {
                $book->total_in  = $book->totalIn();
                $book->total_out = $book->totalOut();
                $book->balance   = $book->balance();
            });

        return BookResource::collection($books);
    }
}
