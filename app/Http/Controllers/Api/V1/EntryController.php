<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesApiAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EntryResource;
use App\Models\Book;
use App\Models\Entry;
use App\Models\EntryComment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EntryController extends Controller
{
    use AuthorizesApiAccess;

    /** Recurring frequencies supported by the web + the entries:generate-recurring cron. */
    public const RECURRING_FREQUENCIES = ['daily', 'weekly', 'biweekly'];

    /** Same limits as the web entry form (Book\Show::doSaveEntry). */
    private const AMOUNT_RULES = ['numeric', 'min:0.01', 'max:999999999.99'];

    /**
     * GET /api/v1/entries/{id}
     */
    public function show(Request $request, string $id): EntryResource
    {
        $entry = $this->findAuthorizedEntry($request, $id);
        $entry->load('creator')->loadCount('comments');

        return new EntryResource($entry);
    }

    /**
     * POST /api/v1/books/{id}/entries
     */
    public function store(Request $request, string $bookId): EntryResource|JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);

        $validated = $request->validate([
            'type'               => ['required', 'in:in,out'],
            'amount'             => ['required', ...self::AMOUNT_RULES],
            'description'        => ['required', 'string', 'max:255'],
            'date'               => ['required', 'date'],
            'category'           => ['nullable', 'string', 'max:100'],
            'paymentMode'        => ['nullable', 'string', 'max:100'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'recurringFrequency' => ['nullable', 'string', 'in:' . implode(',', self::RECURRING_FREQUENCIES)],
            'recurringEndsAt'    => ['nullable', 'date', 'after_or_equal:date'],
            'scanAttachmentPath' => ['nullable', 'string', 'max:255'],
        ]);

        $frequency = $validated['recurringFrequency'] ?? null;

        // Pro gate for recurring — checked BEFORE anything is written.
        if ($frequency && ! $book->business->isPro()) {
            return response()->json(['message' => 'Recurring entries require a Pro subscription.'], 403);
        }

        $attachmentPath = null;
        if (! empty($validated['scanAttachmentPath'])) {
            $attachmentPath = $this->verifiedScanPath($book, $validated['scanAttachmentPath']);
        }

        $data = [
            'type'         => $validated['type'],
            'amount'       => $validated['amount'],
            'description'  => $validated['description'],
            'date'         => $validated['date'],
            'reference'    => ($validated['reference'] ?? null) ?: null,
            'category'     => ($validated['category'] ?? null) ?: null,
            'payment_mode' => ($validated['paymentMode'] ?? null) ?: null,
            'created_by'   => $request->user()->id,
        ];

        if ($attachmentPath) {
            $data['attachment_path'] = $attachmentPath;
        }

        $entry = $book->entries()->create($data);
        $book->touch();

        $this->rememberCategoryAndPaymentMode($book, $data['category'], $data['payment_mode']);

        $this->logBookActivity($book, $request, 'entry_created', $entry->id, [
            'type'        => $entry->type,
            'amount'      => $entry->amount,
            'description' => $entry->description,
        ]);

        if ($attachmentPath) {
            $this->logBookActivity($book, $request, 'attachment_added', $entry->id, [
                'entry_description' => $entry->description,
            ]);
        }

        // Recurring rule — mirrors Book\Show::saveEntry()
        if ($frequency) {
            $nextRun = Carbon::parse($validated['date']);
            match ($frequency) {
                'daily'    => $nextRun->addDay(),
                'weekly'   => $nextRun->addWeek(),
                'biweekly' => $nextRun->addWeeks(2),
            };

            $recurring = $book->recurringEntries()->create([
                'type'         => $entry->type,
                'amount'       => $validated['amount'],
                'description'  => $entry->description,
                'category'     => $data['category'],
                'payment_mode' => $data['payment_mode'],
                'reference'    => $data['reference'],
                'frequency'    => $frequency,
                'starts_at'    => Carbon::parse($validated['date'])->format('Y-m-d'),
                'next_run_at'  => $nextRun->format('Y-m-d'),
                'ends_at'      => $validated['recurringEndsAt'] ?? null,
                'status'       => 'active',
            ]);

            $entry->update(['recurring_entry_id' => $recurring->id]);

            $this->logBookActivity($book, $request, 'recurring_created', $entry->id, [
                'description' => $recurring->description,
                'frequency'   => $recurring->frequency,
            ]);
        }

        return (new EntryResource($entry->fresh()->load('creator')->loadCount('comments')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/v1/entries/{id}
     * Omitted keys are unchanged; nullable keys sent as null are cleared.
     */
    public function update(Request $request, string $id): EntryResource
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);
        $book  = $entry->book;

        $validated = $request->validate([
            'type'        => ['sometimes', 'required', 'in:in,out'],
            'amount'      => ['sometimes', 'required', ...self::AMOUNT_RULES],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'date'        => ['sometimes', 'required', 'date'],
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'paymentMode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reference'   => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $map = [
            'type'        => 'type',
            'amount'      => 'amount',
            'description' => 'description',
            'date'        => 'date',
            'category'    => 'category',
            'paymentMode' => 'payment_mode',
            'reference'   => 'reference',
        ];

        $updates = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $value = $validated[$input];
                $updates[$column] = in_array($input, ['category', 'paymentMode', 'reference'], true)
                    ? ($value ?: null)
                    : $value;
            }
        }

        // Editing a single occurrence detaches it from its recurring rule
        // (same as web saveEntry — the rule itself continues unchanged).
        if ($entry->recurring_entry_id) {
            $updates['recurring_entry_id'] = null;
        }

        if ($updates) {
            $entry->update($updates);
        }
        $book->touch();

        $this->rememberCategoryAndPaymentMode(
            $book,
            $updates['category'] ?? null,
            $updates['payment_mode'] ?? null
        );

        $entry->refresh();

        $this->logBookActivity($book, $request, 'entry_updated', $entry->id, [
            'type'        => $entry->type,
            'amount'      => $entry->amount,
            'description' => $entry->description,
        ]);

        return new EntryResource($entry->load('creator')->loadCount('comments'));
    }

    /**
     * DELETE /api/v1/entries/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);
        $book  = $entry->book;

        $this->logBookActivity($book, $request, 'entry_deleted', null, [
            'type'        => $entry->type,
            'amount'      => $entry->amount,
            'description' => $entry->description,
        ]);

        if ($entry->attachment_path) {
            Storage::disk('local')->delete($entry->attachment_path);
        }

        $entry->delete();
        $book->touch();

        return response()->json(['message' => 'Entry deleted.']);
    }

    /**
     * POST /api/v1/books/{id}/scan
     */
    public function scan(Request $request, string $bookId): JsonResponse
    {
        // Upload + Claude vision + currency conversion can exceed PHP's default
        // 30s limit on slow mobile networks; the app waits up to 90s.
        set_time_limit(90);

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
                Storage::disk('local')->path($path),
                $file->getMimeType() ?: 'image/jpeg',
                $book->business->currency ?? 'USD',
                $book->categories()->pluck('name')->toArray()
            );

            // Same as web: a receipt in another currency is converted to the
            // book's currency, and the original amount + rate are reported.
            $ocrOriginalAmount = null;
            $ocrConvertedAt    = null;
            $receiptCurrency   = isset($result['receipt_currency']) ? strtoupper((string) $result['receipt_currency']) : null;
            $bookCurrency      = strtoupper($book->business->currency ?? 'USD');

            if ($result && isset($result['amount']) && $receiptCurrency && $receiptCurrency !== $bookCurrency) {
                $rawAmount  = (float) $result['amount'];
                $conversion = app(\App\Services\AiService::class)->convertCurrency($rawAmount, $receiptCurrency, $bookCurrency);
                if ($conversion) {
                    $result['amount']  = (string) $conversion['converted_amount'];
                    $ocrOriginalAmount = $receiptCurrency . ' ' . number_format($rawAmount, 2);
                    $ocrConvertedAt    = '1 ' . $receiptCurrency . ' = ' . number_format($conversion['rate'], 2) . ' ' . $bookCurrency;
                }
            }

            // Pass this back as `scanAttachmentPath` on POST /books/{id}/entries
            // to attach the scanned file to the new entry.
            return response()->json([
                'fields'            => $result,
                'attachmentPath'    => $path,
                'ocrOriginalAmount' => $ocrOriginalAmount,
                'ocrConvertedAt'    => $ocrConvertedAt,
            ]);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($path);

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
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($c) => $this->commentPayload($c, $request));

        return response()->json(['data' => $comments]);
    }

    /**
     * POST /api/v1/entries/{id}/comments
     */
    public function addComment(Request $request, string $id): JsonResponse
    {
        $entry = $this->findAuthorizedEntry($request, $id, requireEditor: true);
        $book  = $entry->book;

        // Pro feature — entry comments are gated.
        if (! $book->business->isPro()) {
            return response()->json(['message' => 'Pro subscription required to post comments.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        // Only notify real members of this business (never arbitrary UUIDs).
        $mentionedIds = EntryComment::extractMentionedIds($validated['body']);
        if ($mentionedIds) {
            $mentionedIds = $book->business->members()
                ->whereIn('users.id', $mentionedIds)
                ->pluck('users.id')
                ->all();
        }

        $comment = $entry->comments()->create([
            'user_id'            => $request->user()->id,
            'body'               => $validated['body'],
            'mentioned_user_ids' => $mentionedIds ?: null,
        ]);

        $comment->load('user');
        foreach ($mentionedIds as $userId) {
            if ($userId === $request->user()->id) {
                continue; // don't notify yourself
            }
            $mentionedUser = User::find($userId);
            if ($mentionedUser) {
                try {
                    $mentionedUser->notify(new MentionedInComment($comment, $entry));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->logBookActivity($book, $request, 'comment_added', $entry->id, [
            'entry_description' => $entry->description,
        ]);

        return response()->json($this->commentPayload($comment, $request), 201);
    }

    /**
     * DELETE /api/v1/comments/{id}
     * Author or business owner (same as web).
     */
    public function deleteComment(Request $request, string $id): JsonResponse
    {
        $this->abortUnlessUuid($id);

        $comment = EntryComment::with('entry.book')->findOrFail($id);
        $book    = $comment->entry?->book;
        abort_unless($book, 404);

        $role = $this->memberRole($request->user(), $book->business_id);
        abort_unless($role !== null, 404);
        $this->ensureNotLocked($request->user(), $book->business_id, $role);

        if ($comment->user_id !== $request->user()->id && $role !== 'owner') {
            return response()->json(['message' => 'You can only delete your own comments.'], 403);
        }

        $entryId   = $comment->entry_id;
        $entryDesc = $comment->entry?->description ?? 'an entry';

        $comment->delete();

        $this->logBookActivity($book, $request, 'comment_deleted', $entryId, [
            'entry_description' => $entryDesc,
        ]);

        return response()->json(['message' => 'Comment deleted.']);
    }

    /**
     * POST /api/v1/books/{id}/entries/bulk-delete
     */
    public function bulkDelete(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);
        $ids  = $this->validatedBulkIds($request, $book);

        $entries = $book->entries()->whereIn('id', $ids)->get();
        foreach ($entries as $entry) {
            if ($entry->attachment_path) {
                Storage::disk('local')->delete($entry->attachment_path);
            }
        }

        $count = $book->entries()->whereIn('id', $ids)->delete();

        $logMeta = ['count' => $count];
        if ($count === 1 && $entries->isNotEmpty()) {
            $e = $entries->first();
            $logMeta['type']        = $e->type;
            $logMeta['amount']      = $e->amount;
            $logMeta['description'] = $e->description;
        }
        $this->logBookActivity($book, $request, 'bulk_delete', null, $logMeta);

        $book->touch();

        return response()->json(['message' => "Deleted {$count} entries.", 'count' => $count]);
    }

    /**
     * POST /api/v1/books/{id}/entries/bulk-update
     */
    public function bulkUpdate(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);
        $ids  = $this->validatedBulkIds($request, $book);

        $request->validate([
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'paymentMode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'flipType'    => ['sometimes', 'boolean'],
        ]);

        $count = count($ids);

        if ($request->exists('category')) {
            $category = $request->input('category') ?: null;
            $count = $book->entries()->whereIn('id', $ids)->update(['category' => $category]);
            $this->logBookActivity($book, $request, 'bulk_change_category', null, [
                'count'    => $count,
                'category' => $category ?? 'None',
            ]);
        }

        if ($request->exists('paymentMode')) {
            $paymentMode = $request->input('paymentMode') ?: null;
            $count = $book->entries()->whereIn('id', $ids)->update(['payment_mode' => $paymentMode]);
            $this->logBookActivity($book, $request, 'bulk_change_payment_mode', null, [
                'count'        => $count,
                'payment_mode' => $paymentMode ?? 'None',
            ]);
        }

        if ($request->boolean('flipType')) {
            // Flip in↔out for each entry
            $book->entries()->whereIn('id', $ids)->where('type', 'in')->update(['type' => '__tmp__']);
            $book->entries()->whereIn('id', $ids)->where('type', 'out')->update(['type' => 'in']);
            $book->entries()->whereIn('id', $ids)->where('type', '__tmp__')->update(['type' => 'out']);

            $this->logBookActivity($book, $request, 'bulk_flip_type', null, ['count' => count($ids)]);
        }

        $book->touch();

        return response()->json(['message' => 'Entries updated.', 'count' => count($ids)]);
    }

    /**
     * POST /api/v1/books/{id}/entries/bulk-move — move or copy to another book
     */
    public function bulkMove(Request $request, string $bookId): JsonResponse
    {
        $book = $this->findAuthorizedBook($request, $bookId, requireEditor: true);
        $ids  = $this->validatedBulkIds($request, $book);

        $request->validate([
            'targetBookId' => ['required', 'string'],
            'copy'         => ['sometimes', 'boolean'],
        ]);

        $targetBook = $this->findAuthorizedBook($request, $request->input('targetBookId'), requireEditor: true);

        // Both books must be in the same business (currency must match)
        if ($targetBook->business_id !== $book->business_id) {
            return response()->json(['message' => 'Target book must be in the same business.'], 422);
        }

        if ($targetBook->id === $book->id) {
            return response()->json(['message' => 'Choose a different target book.'], 422);
        }

        if ($request->boolean('copy')) {
            $entries = $book->entries()->whereIn('id', $ids)->get();
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
            $count = $entries->count();

            $this->logBookActivity($book, $request, 'bulk_copy', null, ['count' => $count, 'target_book' => $targetBook->name]);
        } else {
            $count = $book->entries()->whereIn('id', $ids)->update(['book_id' => $targetBook->id]);

            $this->logBookActivity($book, $request, 'bulk_move', null, ['count' => $count, 'target_book' => $targetBook->name]);
            $book->touch();
        }

        $targetBook->touch();

        return response()->json([
            'message' => $request->boolean('copy') ? 'Entries copied.' : 'Entries moved.',
            'count'   => $count,
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
            Storage::disk('local')->delete($entry->attachment_path);
        }

        $path = $request->file('file')->store(
            "attachments/{$entry->book->business_id}/{$entry->book_id}",
            'local'
        );

        $entry->update(['attachment_path' => $path]);
        $entry->book->touch();

        $this->logBookActivity($entry->book, $request, 'attachment_added', $entry->id, [
            'entry_description' => $entry->description,
        ]);

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

        if (! Storage::disk('local')->exists($entry->attachment_path)) {
            abort(404);
        }

        $mimeType = Storage::disk('local')->mimeType($entry->attachment_path);

        // MIME whitelist
        if (! in_array($mimeType, ['image/png', 'image/jpeg', 'image/heic', 'image/heif', 'application/pdf'], true)) {
            abort(403);
        }

        return response()->file(
            Storage::disk('local')->path($entry->attachment_path),
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
            Storage::disk('local')->delete($entry->attachment_path);
            $entry->update(['attachment_path' => null]);
            $entry->book->touch();

            $this->logBookActivity($entry->book, $request, 'attachment_removed', $entry->id, [
                'entry_description' => $entry->description,
            ]);
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
            'ids.*' => ['required', 'string', 'uuid'],
        ]);

        $ids = array_values(array_unique($request->input('ids')));
        $validIds = $book->entries()->whereIn('id', $ids)->pluck('id')->toArray();

        if (count($validIds) !== count($ids)) {
            abort(403, 'Some entries do not belong to this book.');
        }

        return $validIds;
    }

    /**
     * A scan upload path is only accepted if it is exactly a file that
     * POST /books/{id}/scan stored for THIS book: attachments/{business}/{book}/{hash}.{ext},
     * no traversal, exists on disk, and not already attached to another entry.
     */
    private function verifiedScanPath(Book $book, string $path): string
    {
        $dir     = "attachments/{$book->business_id}/{$book->id}/";
        $pattern = '#^' . preg_quote($dir, '#') . '[A-Za-z0-9]{1,100}\.(png|jpe?g|pdf|heic|heif)$#';

        $valid = preg_match($pattern, $path) === 1
            && Storage::disk('local')->exists($path)
            && ! Entry::where('attachment_path', $path)->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'scanAttachmentPath' => 'The scanned receipt could not be attached. Please scan it again.',
            ]);
        }

        return $path;
    }

    /**
     * Same as web doSaveEntry(): categories / payment modes used on an entry
     * are added to the book's lists if missing (case-insensitive).
     */
    private function rememberCategoryAndPaymentMode(Book $book, ?string $category, ?string $paymentMode): void
    {
        if ($category) {
            $exists = $book->categories()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($category)])
                ->exists();
            if (! $exists) {
                $book->categories()->create(['name' => $category]);
            }
        }

        if ($paymentMode) {
            $exists = $book->paymentModes()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($paymentMode)])
                ->exists();
            if (! $exists) {
                $book->paymentModes()->create(['name' => $paymentMode]);
            }
        }
    }

    private function commentPayload(EntryComment $c, Request $request): array
    {
        return [
            'id'        => $c->id,
            'body'      => $c->body,
            'bodyText'  => $c->plainBody(),
            'user'      => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
            'isMine'    => $c->user_id === $request->user()->id,
            'createdAt' => $c->created_at->toIso8601String(),
            'timeAgo'   => $c->created_at->diffForHumans(),
        ];
    }
}
