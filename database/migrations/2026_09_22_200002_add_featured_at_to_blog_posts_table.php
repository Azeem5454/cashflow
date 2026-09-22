<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `featured_at` — when an admin pinned the post as the blog hero.
 *
 * The blog index hero is the NEWEST published post by default. A pin
 * (is_featured + featured_at) only overrides that for
 * BlogPost::FEATURE_PIN_DAYS days, so a stale pin can never bury new posts.
 *
 * Existing rows are deliberately left with featured_at = NULL: the April
 * "featured" flag therefore no longer wins, and the newest post becomes
 * the hero immediately after deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->timestamp('featured_at')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('featured_at');
        });
    }
};
