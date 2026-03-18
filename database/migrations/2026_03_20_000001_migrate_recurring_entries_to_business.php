<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add business_id as nullable to start (constraint added after data migration)
        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->uuid('business_id')->nullable()->after('id');
        });

        // Step 2: Populate business_id from the book's business
        DB::statement('
            UPDATE recurring_entries re
            SET business_id = b.business_id
            FROM books b
            WHERE re.book_id = b.id
        ');

        // Step 3: Add status column, drop old index
        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->string('status')->default('active')->after('ends_at');
        });

        // Step 4: Migrate is_active → status
        DB::statement("UPDATE recurring_entries SET status = CASE WHEN is_active = true THEN 'active' ELSE 'paused' END");

        // Step 5: Drop old columns + index, add FK, make business_id NOT NULL, add new index
        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'next_run_at']);
            $table->dropColumn(['is_active', 'book_id']);
        });

        DB::statement('ALTER TABLE recurring_entries ALTER COLUMN business_id SET NOT NULL');

        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['status', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->dropIndex(['status', 'next_run_at']);
            $table->dropForeign(['business_id']);
            $table->dropColumn(['business_id', 'status']);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('book_id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['is_active', 'next_run_at']);
        });
    }
};
