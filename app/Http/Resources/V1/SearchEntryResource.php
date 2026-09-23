<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A global-search hit: the normal entry payload plus enough context to show
 * "business › book" on the row and to deep-link into the right ledger.
 *
 * `description` stays raw (may be null); `label` is the human fallback
 * (Entry::displayLabel) so clients never have to render an empty row.
 */
class SearchEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book     = $this->book;
        $business = $book?->business;

        return array_merge((new EntryResource($this->resource))->toArray($request), [
            'label'    => $this->displayLabel(),
            'book'     => $book ? [
                'id'   => $book->id,
                'name' => $book->name,
            ] : null,
            'business' => $business ? [
                'id'             => $business->id,
                'name'           => $business->name,
                'currency'       => $business->currency,
                'currencySymbol' => $business->currencySymbol(),
            ] : null,
        ]);
    }
}
