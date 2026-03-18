<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Truncate — can't recover original book_id since it was dropped in previous migration
        DB::table('recurring_entries')->truncate();

        Schema::table('recurring_entries', function (Blueprint $table) {
            // Drop business_id FK + column
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');

            // Add book_id
            $table->uuid('book_id')->after('id');
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('recurring_entries')->truncate();

        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn('book_id');

            $table->uuid('business_id')->after('id');
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }
};
