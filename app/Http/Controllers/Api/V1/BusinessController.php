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

    /**
     * POST /api/v1/businesses/{id}/books
     */
    public function createBook(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()
            ->businesses()
            ->findOrFail($id);

        // Must be owner or editor
        $role = \Illuminate\Support\Facades\DB::table('business_user')
            ->where('business_id', $business->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        abort_unless($role && $role !== 'viewer', 403, 'You do not have permission to create books.');

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'openingBalance' => ['nullable', 'numeric', 'min:0'],
            'periodStartsAt' => ['nullable', 'date'],
            'periodEndsAt'   => ['nullable', 'date', 'after_or_equal:periodStartsAt'],
        ]);

        $book = $business->books()->create([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'opening_balance'  => $validated['openingBalance'] ?? 0,
            'period_starts_at' => $validated['periodStartsAt'] ?? null,
            'period_ends_at'   => $validated['periodEndsAt'] ?? null,
        ]);

        return response()->json([
            'id'   => $book->id,
            'name' => $book->name,
        ], 201);
    }

    /**
     * GET /api/v1/businesses/{id}/members
     */
    public function members(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()
            ->businesses()
            ->findOrFail($id);

        $members = $business->members()
            ->get()
            ->map(fn ($m) => [
                'id'    => $m->id,
                'name'  => $m->name,
                'email' => $m->email,
                'role'  => $m->pivot->role,
            ]);

        return response()->json(['data' => $members]);
    }
}
