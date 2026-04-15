<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 200)->unique();
            $table->string('title', 200);
            $table->string('excerpt', 400)->nullable();
            $table->longText('body_markdown')->nullable();
            // Rendered HTML cached here so public pages never re-parse markdown.
            $table->longText('body_html')->nullable();
            // Key into uploaded_assets table (featured image served via /brand-asset/{key}).
            $table->string('featured_image_key', 120)->nullable();
            $table->string('featured_image_alt', 200)->nullable();

            $table->uuid('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('blog_categories')->nullOnDelete();

            $table->uuid('author_id')->nullable();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();

            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();

            // SEO — falls back to title / excerpt at render time when blank.
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 280)->nullable();

            $table->unsignedSmallInteger('reading_time')->default(1); // minutes
            $table->unsignedInteger('view_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category_id');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
