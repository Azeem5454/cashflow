<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'description', 'color'];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Keep the denormalised post_count column in sync. Called from BlogPost
     * lifecycle hooks rather than a full count() query on every hit.
     */
    public function refreshPostCount(): void
    {
        $this->post_count = $this->posts()->where('status', 'published')->count();
        $this->saveQuietly();
    }

    /**
     * Auto-slugify name if slug not provided.
     */
    protected static function booted(): void
    {
        static::saving(function (self $c) {
            if (empty($c->slug)) {
                $c->slug = Str::slug($c->name);
            }
        });
    }
}
