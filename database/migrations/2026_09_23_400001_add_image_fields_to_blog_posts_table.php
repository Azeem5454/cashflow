<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Featured images can now sit on a real photograph.
 *
 * `image_query` holds the search phrase Claude chose for the post, so
 * regenerating an image later finds the same kind of picture instead of
 * guessing from the title. `featured_image_credit` holds the photographer,
 * so the post can credit them — the Pexels licence doesn't require it, but
 * crediting costs nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('image_query', 120)->nullable()->after('featured_image_alt');
            $table->string('featured_image_credit', 160)->nullable()->after('image_query');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['image_query', 'featured_image_credit']);
        });
    }
};
