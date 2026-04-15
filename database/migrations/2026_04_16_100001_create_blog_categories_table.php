<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 280)->nullable();
            $table->string('color', 7)->default('#1a56db'); // hex
            $table->unsignedInteger('post_count')->default(0);
            $table->timestamps();
        });

        // Seed three default categories so the admin has something to pick from
        // on first run. Slugs are URL-safe and meaningful for SEO.
        $now = now();
        DB::table('blog_categories')->insert([
            [
                'id'          => (string) \Illuminate\Support\Str::uuid(),
                'name'        => 'Growing Your Business',
                'slug'        => 'growing-your-business',
                'description' => 'Playbooks and practical advice for small business owners scaling revenue.',
                'color'       => '#1a56db',
                'post_count'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => (string) \Illuminate\Support\Str::uuid(),
                'name'        => 'Bookkeeping 101',
                'slug'        => 'bookkeeping-101',
                'description' => 'The fundamentals of cash flow, categories, and keeping clean books — explained without accounting jargon.',
                'color'       => '#16a34a',
                'post_count'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => (string) \Illuminate\Support\Str::uuid(),
                'name'        => 'Product Updates',
                'slug'        => 'product-updates',
                'description' => 'New features, improvements, and behind-the-scenes work on TheCashFox.',
                'color'       => '#8b5cf6',
                'post_count'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};
