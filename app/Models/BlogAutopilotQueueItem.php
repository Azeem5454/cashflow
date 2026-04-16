<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single queued blog title. Admin manages these from /admin/blog/autopilot.
 * BlogAutopilot picks the row with the lowest `position` value.
 */
class BlogAutopilotQueueItem extends Model
{
    use HasUuids;

    protected $table = 'blog_autopilot_queue';

    protected $fillable = [
        'title',
        'category_id',
        'position',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
}
