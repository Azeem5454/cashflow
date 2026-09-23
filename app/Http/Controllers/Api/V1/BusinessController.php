<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesApiAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\BusinessResource;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends Controller
{
    use AuthorizesApiAccess;

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

        $ids      = $businesses->pluck('id')->all();
        $balances = Business::netBalances($ids);
        $trends   = Business::trends($ids);

        $businesses->each(function ($b) use ($balances, $trends) {
            $b->net_balance      = $balances[$b->id] ?? '0.00';
            $b->trend            = $trends[$b->id]['trend'] ?? null;
            $b->month_change_pct = $trends[$b->id]['monthChangePct'] ?? null;
        });

        return BusinessResource::collection($businesses);
    }

    /**
     * GET /api/v1/businesses/{id}
     */
    public function show(Request $request, string $id): BusinessResource
    {
        $business = $this->findAuthorizedBusiness($request, $id);
        $business->loadCount(['books', 'members']);

        return new BusinessResource($business);
    }

    /**
     * GET /api/v1/businesses/{id}/books
     */
    public function books(Request $request, string $id): AnonymousResourceCollection
    {
        $business = $this->findAuthorizedBusiness($request, $id);

        $books = $business->books()
            ->withCount('entries')
            // Portable NULLS LAST: Postgres sorts NULLs first on DESC.
            ->orderByRaw('CASE WHEN period_starts_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('period_starts_at')
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($book) {
                $book->total_in  = \App\Services\BookLedger::money($book->totalIn());
                $book->total_out = \App\Services\BookLedger::money($book->totalOut());
                $book->balance   = $book->balance();
            });

        return BookResource::collection($books);
    }

    /**
     * POST /api/v1/businesses/{id}/books
     */
    public function createBook(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);

        // Must be owner or editor
        $role = \Illuminate\Support\Facades\DB::table('business_user')
            ->where('business_id', $business->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        abort_unless($role && $role !== 'viewer', 403, 'You do not have permission to create books.');

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'openingBalance' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'periodStartsAt' => ['nullable', 'date'],
            'periodEndsAt'   => ['nullable', 'date', 'after_or_equal:periodStartsAt'],
            'carryForward'   => ['nullable', 'boolean'],
        ]);

        // Carry-forward is computed server-side from the previous book's closing
        // balance — a client-sent figure is never trusted. An explicit
        // openingBalance always wins (the user edited the prefilled number).
        $opening = $validated['openingBalance'] ?? null;
        if ($opening === null && $request->boolean('carryForward')) {
            $opening = $business->previousBookForCarryForward()?->closingBalance() ?? '0';
        }

        $book = $business->books()->create([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'opening_balance'  => $opening ?? 0,
            'period_starts_at' => $validated['periodStartsAt'] ?? null,
            'period_ends_at'   => $validated['periodEndsAt'] ?? null,
        ]);

        return response()->json([
            'id'   => $book->id,
            'name' => $book->name,
        ], 201);
    }

    /**
     * GET /api/v1/businesses/{id}/suggested-opening
     *
     * What the create-book screen should offer as a carry-forward opening
     * balance: the closing balance of the business's most recent book.
     * Returns nulls when the business has no books yet (first book — no offer).
     */
    public function suggestedOpening(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);

        $previous = $business->previousBookForCarryForward();

        return response()->json([
            'suggestedOpeningBalance' => $previous?->closingBalance(),
            'previousBookId'          => $previous?->id,
            'previousBookName'        => $previous?->name,
            'currencySymbol'          => $business->currencySymbol(),
        ]);
    }

    /**
     * GET /api/v1/businesses/{id}/members
     */
    public function members(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);

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
                'message' => 'The Free plan includes one business. More businesses are available on the Pro plan.',
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
        $business = $this->findAuthorizedBusiness($request, $id);

        $this->ensureOwner($request, $business);

        $validated = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'currency'     => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'contactPhone' => ['nullable', 'string', 'max:40'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
        ]);

        $attrs = collect($validated)->only(['name', 'description', 'currency'])->all();
        foreach (['contactPhone' => 'contact_phone', 'contactEmail' => 'contact_email'] as $in => $col) {
            if ($request->has($in)) {
                $attrs[$col] = ($validated[$in] ?? null) ?: null;
            }
        }

        $business->update($attrs);

        return response()->json(['message' => 'Business updated.']);
    }

    /**
     * POST /api/v1/businesses/{id}/logo — upload/replace the export logo (owner only).
     * multipart/form-data, field `logo`.
     */
    public function uploadLogo(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);
        $this->ensureOwner($request, $business);

        $request->validate([
            'logo' => [
                'required', 'image',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/png,image/jpeg',
                'max:1024', // KB
            ],
        ]);

        $bytes = file_get_contents($request->file('logo')->getRealPath());
        abort_if($bytes === false, 422, 'That file could not be read.');

        $business->storeLogo($bytes);

        return response()->json([
            'message' => 'Logo updated.',
            'logoUrl' => $business->logoUrl(absolute: true),
        ]);
    }

    /**
     * DELETE /api/v1/businesses/{id}/logo — remove the export logo (owner only).
     */
    public function deleteLogo(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);
        $this->ensureOwner($request, $business);

        $business->removeLogo();

        return response()->json(['message' => 'Logo removed.']);
    }

    /**
     * DELETE /api/v1/businesses/{id} — delete business (owner only)
     */
    public function destroy(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        // No lock check: deleting an extra (locked) business is how a Free owner frees it up.
        $business = $this->findAuthorizedBusiness($request, $id, checkLock: false);

        $this->ensureOwner($request, $business);

        $business->delete();

        return response()->json(['message' => 'Business deleted.']);
    }

    /**
     * POST /api/v1/businesses/{id}/invitations — invite a team member
     */
    public function invite(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);

        $this->ensureOwner($request, $business);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'role'  => ['required', 'in:editor,viewer'],
        ]);

        // Plan seat limit: members + open invitations (re-sending an open invite is fine)
        if (! $business->canInvite($validated['email'])) {
            $limit = $business->memberLimit();
            return response()->json([
                'message' => "Your plan allows up to {$limit} members, including pending invitations.",
                'code'    => 'seat_limit',
            ], 403);
        }

        $inviter = app(\App\Services\TeamInviter::class);
        if ($inviter->isMember($business, $validated['email'])) {
            return response()->json(['message' => 'This person is already a team member.'], 422);
        }

        // Rate limit: 5 invites/hour per user (counted only for valid invites)
        $key = 'invite:' . $request->user()->id;
        if (! \Illuminate\Support\Facades\RateLimiter::attempt($key, 5, fn () => true, 3600)) {
            return response()->json(['message' => 'Too many invitations. Try again later.'], 429);
        }

        [$invitation, $refreshed] = $inviter->invite($business, $validated['email'], $validated['role']);

        // Send the invitation email
        try {
            \Illuminate\Support\Facades\Mail::to($invitation->email)
                ->queue(new \App\Mail\TeamInvitation($invitation));
        } catch (\Throwable) {
            // Mail failure shouldn't block the API response
        }

        return response()->json([
            'id'        => $invitation->id,
            'email'     => $invitation->email,
            'role'      => $invitation->role,
            'resent'    => $refreshed,
            'message'   => $refreshed ? 'Invitation re-sent.' : 'Invitation sent.',
        ], $refreshed ? 200 : 201);
    }

    /**
     * GET /api/v1/businesses/{id}/invitations — list pending invites
     */
    public function invitations(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $business = $this->findAuthorizedBusiness($request, $id);

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
        $this->abortUnlessUuid($id);
        $invitation = \App\Models\Invitation::findOrFail($id);
        $business = $this->findAuthorizedBusiness($request, $invitation->business_id);

        $this->ensureOwner($request, $business);

        $invitation->delete();

        return response()->json(['message' => 'Invitation cancelled.']);
    }

    /**
     * PUT /api/v1/businesses/{businessId}/members/{userId} — change role
     */
    public function updateMemberRole(Request $request, string $businessId, string $userId): \Illuminate\Http\JsonResponse
    {
        $this->abortUnlessUuid($userId);
        $business = $this->findAuthorizedBusiness($request, $businessId);

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
        $this->abortUnlessUuid($userId);
        $business = $this->findAuthorizedBusiness($request, $businessId);

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
