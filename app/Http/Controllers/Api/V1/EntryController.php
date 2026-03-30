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
            'receipt' => ['required', 'file', 'max:5120', 'mimes:png,jpg,jpeg,pdf', 'mimetypes:image/png,image/jpeg,application/pdf'],
        ]);

        $path = $request->file('receipt')->store(
            "attachments/{$book->business_id}/{$book->id}",
            'local'
        );

        try {
            $result = app(\App\Services\AiService::class)->extractFromReceipt(
                storage_path('app/private/' . $path)
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
