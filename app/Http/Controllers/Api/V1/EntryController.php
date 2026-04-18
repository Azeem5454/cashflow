<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EntryResource;
use App\Models\Book;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntryController extends Controller
{
    /**
     * POST /api/v1/books/{id}/entries
     */
    public function store(Request $request, string $bookId): EntryResource
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);

        $validated = $request->validate([
            'type'        => ['required', 'in:in,out'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'date'        => ['required', 'date'],
            'category'    => ['nullable', 'string', 'max:100'],
            'paymentMode' => ['nullable', 'string', 'max:100'],
            'reference'   => ['nullable', 'string', 'max:100'],
        ]);

        $entry = $book->entries()->create([
            'type'         => $validated['type'],
            'amount'       => $validated['amount'],
            'description'  => $validated['description'] ?? null,
            'date'         => $validated['date'],
            'category'     => $validated['category'] ?? null,
            'payment_mode' => $validated['paymentMode'] ?? null,
            'reference'    => $validated['reference'] ?? null,
            'created_by'   => $request->user()->id,
        ]);

        $book->touch();

        return new EntryResource($entry->load('creator'));
    }

    /**
     * PUT /api/v1/entries/{id}
     */
    public function update(Request $request, string $id): EntryResource
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);

        $validated = $request->validate([
            'type'        => ['sometimes', 'in:in,out'],
            'amount'      => ['sometimes', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'date'        => ['sometimes', 'date'],
            'category'    => ['nullable', 'string', 'max:100'],
            'paymentMode' => ['nullable', 'string', 'max:100'],
            'reference'   => ['nullable', 'string', 'max:100'],
        ]);

        $entry->update([
            'type'         => $validated['type'] ?? $entry->type,
            'amount'       => $validated['amount'] ?? $entry->amount,
            'description'  => array_key_exists('description', $validated) ? $validated['description'] : $entry->description,
            'date'         => $validated['date'] ?? $entry->date,
            'category'     => array_key_exists('category', $validated) ? $validated['category'] : $entry->category,
            'payment_mode' => array_key_exists('paymentMode', $validated) ? $validated['paymentMode'] : $entry->payment_mode,
            'reference'    => array_key_exists('reference', $validated) ? $validated['reference'] : $entry->reference,
        ]);

        $entry->book->touch();

        return new EntryResource($entry->fresh()->load('creator'));
    }

    /**
     * DELETE /api/v1/entries/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);

        // Clean up attachment if present
        if ($entry->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($entry->attachment_path);
        }

        $entry->book->touch();
        $entry->delete();

        return response()->json(['message' => 'Entry deleted.']);
    }

    /**
     * POST /api/v1/entries/{id}/scan
     */
    public function scan(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);

        // Pro gate
        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required for AI receipt scanning.'], 403);
        }

        // Rate limit: 5/minute
        $rateLimitKey = 'ocr:' . $request->user()->id;
        if (! \Illuminate\Support\Facades\RateLimiter::attempt($rateLimitKey, 5, fn () => true, 60)) {
            return response()->json(['message' => 'Too many scan requests. Try again in a minute.'], 429);
        }

        // Monthly limit: 200
        $monthlyCount = \App\Models\AiUsageLog::where('user_id', $request->user()->id)
            ->where('type', 'ocr')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($monthlyCount >= 200) {
            return response()->json(['message' => 'Monthly OCR limit reached (200 scans).'], 429);
        }

        $request->validate([
            'receipt' => ['required', 'file', 'max:5120', 'mimes:png,jpg,jpeg,heic,heif,pdf', 'mimetypes:image/png,image/jpeg,image/heic,image/heif,application/pdf'],
        ]);

        $file = $request->file('receipt');
        $path = $file->store(
            "attachments/{$book->business_id}/{$book->id}",
            'local'
        );

        try {
            $result = app(\App\Services\AiService::class)->extractFromReceipt(
                storage_path('app/private/' . $path),
                $file->getMimeType() ?: 'image/jpeg',
                $book->business->currency ?? 'USD',
                $book->categories()->pluck('name')->toArray()
            );

            return response()->json([
                'fields' => $result,
                'attachmentPath' => $path,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);

            return response()->json(['message' => 'Failed to process receipt. Please try again.'], 500);
        }
    }

    /**
     * GET /api/v1/entries/{id}/comments
     */
    public function comments(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id);

        $comments = $entry->comments()
            ->with('user:id,name,email')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($c) => [
                'id'        => $c->id,
                'body'      => $c->body,
                'user'      => ['id' => $c->user->id, 'name' => $c->user->name],
                'isMine'    => $c->user_id === $request->user()->id,
                'createdAt' => $c->created_at->toIso8601String(),
                'timeAgo'   => $c->created_at->diffForHumans(),
            ]);

        return response()->json(['data' => $comments]);
    }

    /**
     * POST /api/v1/entries/{id}/comments
     */
    public function addComment(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);

        // Pro feature — entry comments are gated.
        if (! $entry->book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required to post comments.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $comment = $entry->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
        ]);

        return response()->json([
            'id'        => $comment->id,
            'body'      => $comment->body,
            'user'      => ['id' => $request->user()->id, 'name' => $request->user()->name],
            'isMine'    => true,
            'createdAt' => $comment->created_at->toIso8601String(),
            'timeAgo'   => 'just now',
        ], 201);
    }

    /**
     * DELETE /api/v1/comments/{id}
     */
    public function deleteComment(Request $request, string $id): JsonResponse
    {
        $comment = \App\Models\EntryComment::findOrFail($id);

        // Only comment author can delete
        abort_unless($comment->user_id === $request->user()->id, 403, 'You can only delete your own comments.');

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    /**
     * POST /api/v1/books/{id}/entries/bulk-delete
     */
    public function bulkDelete(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);
        $ids = $this->validatedBulkIds($request, $book);

        $deleted = $book->entries()->whereIn('id', $ids)->get();
        foreach ($deleted as $entry) {
            if ($entry->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($entry->attachment_path);
            }
        }

        $count = $book->entries()->whereIn('id', $ids)->delete();
        $book->touch();

        return response()->json(['message' => "Deleted {$count} entries.", 'count' => $count]);
    }

    /**
     * POST /api/v1/books/{id}/entries/bulk-update
     */
    public function bulkUpdate(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);
        $ids = $this->validatedBulkIds($request, $book);

        $request->validate([
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'paymentMode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'flipType'    => ['sometimes', 'boolean'],
        ]);

        $updates = [];
        if ($request->has('category'))    $updates['category']     = $request->input('category');
        if ($request->has('paymentMode')) $updates['payment_mode'] = $request->input('paymentMode');

        if (! empty($updates)) {
            $book->entries()->whereIn('id', $ids)->update($updates);
        }

        if ($request->boolean('flipType')) {
            // Flip in↔out for each entry
            $book->entries()->whereIn('id', $ids)->where('type', 'in')->update(['type' => '__tmp__']);
            $book->entries()->whereIn('id', $ids)->where('type', 'out')->update(['type' => 'in']);
            $book->entries()->whereIn('id', $ids)->where('type', '__tmp__')->update(['type' => 'out']);
        }

        $book->touch();

        return response()->json(['message' => 'Entries updated.', 'count' => count($ids)]);
    }

    /**
     * POST /api/v1/books/{id}/entries/bulk-move — move to another book
     */
    public function bulkMove(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);
        $ids = $this->validatedBulkIds($request, $book);

        $request->validate([
            'targetBookId' => ['required', 'string'],
            'copy'         => ['sometimes', 'boolean'],
        ]);

        $targetBook = $this->findAuthorizedBook($request, $request->input('targetBookId'), requireEditor: true);

        // Both books must be in the same business (currency must match)
        if ($targetBook->business_id !== $book->business_id) {
            return response()->json(['message' => 'Target book must be in the same business.'], 422);
        }

        $entries = $book->entries()->whereIn('id', $ids)->get();

        if ($request->boolean('copy')) {
            foreach ($entries as $entry) {
                $targetBook->entries()->create([
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
        } else {
            $book->entries()->whereIn('id', $ids)->update(['book_id' => $targetBook->id]);
        }

        $book->touch();
        $targetBook->touch();

        return response()->json([
            'message' => $request->boolean('copy') ? 'Entries copied.' : 'Entries moved.',
            'count'   => count($ids),
        ]);
    }

    /**
     * POST /api/v1/entries/{id}/attachment — upload attachment
     */
    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);

        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:png,jpg,jpeg,heic,heif,pdf', 'mimetypes:image/png,image/jpeg,image/heic,image/heif,application/pdf'],
        ]);

        // Delete existing attachment if any
        if ($entry->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($entry->attachment_path);
        }

        $path = $request->file('file')->store(
            "attachments/{$entry->book->business_id}/{$entry->book_id}",
            'local'
        );

        $entry->update(['attachment_path' => $path]);
        $entry->book->touch();

        return response()->json([
            'message' => 'Attachment uploaded.',
            'url'     => url("/api/v1/entries/{$entry->id}/attachment"),
        ]);
    }

    /**
     * GET /api/v1/entries/{id}/attachment — fetch the file
     */
    public function getAttachment(Request $request, string $id)
    {
        $entry = $this->findAuthorizedEntry($request, $id);

        if (! $entry->attachment_path) {
            abort(404);
        }

        if (! \Illuminate\Support\Facades\Storage::disk('local')->exists($entry->attachment_path)) {
            abort(404);
        }

        $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($entry->attachment_path);

        // MIME whitelist
        if (! in_array($mimeType, ['image/png', 'image/jpeg', 'application/pdf'], true)) {
            abort(403);
        }

        return response()->file(
            \Illuminate\Support\Facades\Storage::disk('local')->path($entry->attachment_path),
            [
                'Content-Type'           => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * DELETE /api/v1/entries/{id}/attachment
     */
    public function deleteAttachment(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);

        if ($entry->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($entry->attachment_path);
            $entry->update(['attachment_path' => null]);
            $entry->book->touch();
        }

        return response()->json(['message' => 'Attachment removed.']);
    }

    /**
     * Validate that all provided IDs belong to the given book.
     */
    private function validatedBulkIds(Request $request, Book $book): array
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'string'],
        ]);

        $ids = $request->input('ids');
        $validIds = $book->entries()->whereIn('id', $ids)->pluck('id')->toArray();

        if (count($validIds) !== count($ids)) {
            abort(403, 'Some entries do not belong to this book.');
        }

        return $validIds;
    }

    /**
     * Find a book the user has access to.
     */
    private function findAuthorizedBook(Request $request, string $bookId, bool $requireEditor = false): Book
    {
        $user = $request->user();
        $businessIds = $user->businesses()->pluck('businesses.id');
        $book = Book::whereIn('business_id', $businessIds)->findOrFail($bookId);

        if ($requireEditor) {
            $role = DB::table('business_user')
                ->where('business_id', $book->business_id)
                ->where('user_id', $user->id)
                ->value('role');

            abort_unless($role && $role !== 'viewer', 403, 'You do not have permission to modify this book.');
        }

        return $book;
    }

    /**
     * Find an entry the user has access to.
     */
    private function findAuthorizedEntry(Request $request, string $entryId, bool $requireEditor = false): Entry
    {
        $user = $request->user();
        $businessIds = $user->businesses()->pluck('businesses.id');

        $entry = Entry::whereHas('book', fn ($q) => $q->whereIn('business_id', $businessIds))
            ->findOrFail($entryId);

        if ($requireEditor) {
            $role = DB::table('business_user')
                ->where('business_id', $entry->book->business_id)
                ->where('user_id', $user->id)
                ->value('role');

            abort_unless($role && $role !== 'viewer', 403, 'You do not have permission to modify this entry.');
        }

        return $entry;
    }
}
