<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Book;
use App\Models\BookActivityLog;
use App\Models\Business;
use App\Models\Entry;
use App\Models\User;
use App\Support\BusinessLock;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared access rules for /api/v1 controllers.
 *
 *  - Everything is scoped to businesses the user is a member of (404 otherwise).
 *  - The member role is ALWAYS re-read from the business_user pivot (same as
 *    the web's guardEditor()), never trusted from a cached model.
 *  - Free-plan locked businesses return 403 {code: business_locked}.
 */
trait AuthorizesApiAccess
{
    protected function memberRole(User $user, string $businessId): ?string
    {
        return DB::table('business_user')
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->value('role');
    }

    protected function ensureNotLocked(User $user, Business|string $business, ?string $role): void
    {
        if (BusinessLock::isLocked($user, $business, $role)) {
            throw new HttpResponseException(response()->json([
                'message' => 'This business is locked on the Free plan.',
                'code'    => 'business_locked',
            ], 403));
        }
    }

    protected function ensureEditorRole(?string $role, string $message = 'You do not have permission to modify this book.'): void
    {
        if (! $role || $role === 'viewer') {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }
    }

    protected function ensureOwnerRole(?string $role, string $message = 'Only the business owner can do that.'): void
    {
        if ($role !== 'owner') {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }
    }

    /**
     * Business the user belongs to (pivot role loaded), lock-checked.
     */
    protected function findAuthorizedBusiness(Request $request, string $id, bool $checkLock = true): Business
    {
        $this->abortUnlessUuid($id);

        $business = $request->user()->businesses()->findOrFail($id);

        if ($checkLock) {
            $this->ensureNotLocked($request->user(), $business, $business->pivot?->role);
        }

        return $business;
    }

    protected function findAuthorizedBook(Request $request, string $bookId, bool $requireEditor = false): Book
    {
        $this->abortUnlessUuid($bookId);

        $user = $request->user();
        $book = Book::findOrFail($bookId);
        $role = $this->memberRole($user, $book->business_id);

        abort_unless($role !== null, 404);

        $this->ensureNotLocked($user, $book->business_id, $role);

        if ($requireEditor) {
            $this->ensureEditorRole($role);
        }

        return $book;
    }

    protected function findAuthorizedEntry(Request $request, string $entryId, bool $requireEditor = false): Entry
    {
        $this->abortUnlessUuid($entryId);

        $user  = $request->user();
        $entry = Entry::with('book')->findOrFail($entryId);
        $role  = $this->memberRole($user, $entry->book->business_id);

        abort_unless($role !== null, 404);

        $this->ensureNotLocked($user, $entry->book->business_id, $role);

        if ($requireEditor) {
            $this->ensureEditorRole($role, 'You do not have permission to modify this entry.');
        }

        return $entry;
    }

    /**
     * Same as the web's Book\Show::logActivity() — never fails the request.
     */
    protected function logBookActivity(Book $book, Request $request, string $action, ?string $entryId = null, array $meta = []): void
    {
        try {
            BookActivityLog::create([
                'book_id'  => $book->id,
                'user_id'  => $request->user()->id,
                'action'   => $action,
                'entry_id' => $entryId,
                'meta'     => $meta ?: null,
            ]);
        } catch (\Throwable) {
            // Never fail a user action because of audit logging
        }
    }

    /**
     * Non-UUID ids would make Postgres throw "invalid input syntax for type uuid" (500).
     */
    protected function abortUnlessUuid(string $id): void
    {
        abort_unless(Str::isUuid($id), 404);
    }
}
