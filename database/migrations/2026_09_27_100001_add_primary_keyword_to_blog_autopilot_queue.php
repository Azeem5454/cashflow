<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Explicit keyword targeting for autopilot posts.
 *
 * The prompt previously told Claude to "preserve the seed title's primary
 * keyword" — leaving it to infer which phrase mattered from the title alone.
 * That produced readable posts aimed at no particular query.
 *
 * The keyword is now stated per queued title and placed deliberately: title,
 * slug, first paragraph, meta description and one H2. Stored on the post too,
 * so it's possible to see later which query each post was written for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_autopilot_queue', function (Blueprint $table) {
            $table->string('primary_keyword', 120)->nullable()->after('title');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('primary_keyword', 120)->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_autopilot_queue', function (Blueprint $table) {
            $table->dropColumn('primary_keyword');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('primary_keyword');
        });
    }
};
