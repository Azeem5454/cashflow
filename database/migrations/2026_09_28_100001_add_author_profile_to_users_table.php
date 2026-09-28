<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Author profile for blog bylines.
 *
 * Posts were attributed to the organisation, which is the weakest possible
 * signal under Google's helpful-content guidance: it wants to see who wrote
 * something and why they would know. A named author with a bio, linked from
 * every post, is the lever.
 *
 * `author_slug` is the public URL segment, kept separate from the name so
 * renaming a person never breaks a published link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('author_slug', 120)->nullable()->unique()->after('name');
            $table->text('author_bio')->nullable()->after('author_slug');
            $table->string('author_role', 160)->nullable()->after('author_bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['author_slug']);
            $table->dropColumn(['author_slug', 'author_bio', 'author_role']);
        });
    }
};
