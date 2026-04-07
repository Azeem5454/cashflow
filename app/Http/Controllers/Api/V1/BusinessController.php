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

    /**
     * POST /api/v1/businesses — create a new business
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Free plan limit: 1 business
        if (! $user->isPro() && $user->ownedBusinesses()->count() >= 1) {
            return response()->json([
                'message' => 'Upgrade to Pro to create more than one business.',
            ], 403);
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'currency'    => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ]);

        $business = \App\Models\Business::create([
            'owner_id'    => $user->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'currency'    => $validated['currency'],
        ]);

        // Owner pivot
        $business->members()->attach($user->id, ['role' => 'owner']);

        return response()->json([
            'id'   => $business->id,
            'name' => $business->name,
        ], 201);
    }

    /**
     * PUT /api/v1/businesses/{id} — update business (owner only)
     */
    public function update(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()->businesses()->findOrFail($id);

        $this->ensureOwner($request, $business);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'currency'    => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ]);

        $business->update($validated);

        return response()->json(['message' => 'Business updated.']);
    }

    /**
     * DELETE /api/v1/businesses/{id} — delete business (owner only)
     */
    public function destroy(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()->businesses()->findOrFail($id);

        $this->ensureOwner($request, $business);

        $business->delete();

        return response()->json(['message' => 'Business deleted.']);
    }

    /**
     * POST /api/v1/businesses/{id}/invitations — invite a team member
     */
    public function invite(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()->businesses()->findOrFail($id);

        $this->ensureOwner($request, $business);

        // Free plan limit: 2 members total
        if (! $business->isPro() && $business->members()->count() >= 2) {
            return response()->json([
                'message' => 'Upgrade to Pro to invite more team members.',
            ], 403);
        }

        // Rate limit: 5 invites/hour per user
        $key = 'invite:' . $request->user()->id;
        if (! \Illuminate\Support\Facades\RateLimiter::attempt($key, 5, fn () => true, 3600)) {
            return response()->json(['message' => 'Too many invitations. Try again later.'], 429);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'role'  => ['required', 'in:editor,viewer'],
        ]);

        // Don't invite existing members
        $existing = $business->members()->where('users.email', $validated['email'])->exists();
        if ($existing) {
            return response()->json(['message' => 'That email is already a member.'], 422);
        }

        $invitation = \App\Models\Invitation::create([
            'business_id' => $business->id,
            'email'       => $validated['email'],
            'role'        => $validated['role'],
        ]);

        // Send the invitation email
        try {
            \Illuminate\Support\Facades\Mail::to($validated['email'])
                ->queue(new \App\Mail\TeamInvitation($invitation));
        } catch (\Throwable) {
            // Mail failure shouldn't block the API response
        }

        return response()->json([
            'id'    => $invitation->id,
            'email' => $invitation->email,
            'role'  => $invitation->role,
        ], 201);
    }

    /**
     * GET /api/v1/businesses/{id}/invitations — list pending invites
     */
    public function invitations(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()->businesses()->findOrFail($id);

        $this->ensureOwner($request, $business);

        $invites = $business->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get()
            ->map(fn ($i) => [
                'id'        => $i->id,
                'email'     => $i->email,
                'role'      => $i->role,
                'expiresAt' => $i->expires_at->toIso8601String(),
                'createdAt' => $i->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $invites]);
    }

    /**
     * DELETE /api/v1/invitations/{id} — cancel a pending invitation
     */
    public function cancelInvitation(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $invitation = \App\Models\Invitation::findOrFail($id);
        $business = $request->user()->businesses()->findOrFail($invitation->business_id);

        $this->ensureOwner($request, $business);

        $invitation->delete();

        return response()->json(['message' => 'Invitation cancelled.']);
    }

    /**
     * PUT /api/v1/businesses/{businessId}/members/{userId} — change role
     */
    public function updateMemberRole(Request $request, string $businessId, string $userId): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()->businesses()->findOrFail($businessId);

        $this->ensureOwner($request, $business);

        // Cannot change owner's role
        if ($userId === $business->owner_id) {
            return response()->json(['message' => 'Cannot change the owner\'s role.'], 422);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:editor,viewer'],
        ]);

        $business->members()->updateExistingPivot($userId, ['role' => $validated['role']]);

        return response()->json(['message' => 'Role updated.']);
    }

    /**
     * DELETE /api/v1/businesses/{businessId}/members/{userId} — remove a member
     */
    public function removeMember(Request $request, string $businessId, string $userId): \Illuminate\Http\JsonResponse
    {
        $business = $request->user()->businesses()->findOrFail($businessId);

        $this->ensureOwner($request, $business);

        if ($userId === $business->owner_id) {
            return response()->json(['message' => 'Cannot remove the owner.'], 422);
        }

        $business->members()->detach($userId);

        return response()->json(['message' => 'Member removed.']);
    }

    private function ensureOwner(Request $request, \App\Models\Business $business): void
    {
        $role = \Illuminate\Support\Facades\DB::table('business_user')
            ->where('business_id', $business->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        abort_unless($role === 'owner', 403, 'Only the business owner can do that.');
    }
}
