<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * books.deleted_at — the 30-day recycle bin.
 *
 * Deleting a book now only stamps this column. Its entries, categories,
 * payment modes, recurring rules, comments, activity log and report schedule
 * stay exactly where they are; they only go when the book is force-deleted
 * (by the owner, or by `books:purge-deleted` after 30 days), at which point
 * the existing ON DELETE CASCADE foreign keys clear them out.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'deleted_at')) {
            return;
        }

        Schema::table('books', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('books', 'deleted_at')) {
            return;
        }

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
