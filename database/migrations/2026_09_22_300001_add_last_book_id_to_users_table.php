<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.last_book_id — the book the dashboard's "Continue in" card points at.
 * Set only by the dashboard's "Change" picker (never mass-assigned). Access is
 * re-checked on every read, so a stale id (removed from the business, business
 * locked) simply falls back to the most recent editable book.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'last_book_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('last_book_id')->nullable()
                ->constrained('books')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_book_id');
        });
    }
};
