<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anomaly detection columns for entries (AI Phase 2).
 *
 * is_flagged — fast boolean for list-view badge rendering
 * flag_reason — short human-readable explanation ("3.2× your typical
 *               Electricity bill") shown in tooltip
 * flagged_at — when the flag was last computed; lets us re-run detection
 *              only when an entry is updated
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('recurring_entry_id');
            $table->string('flag_reason', 255)->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');

            $table->index(['book_id', 'is_flagged']);
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'is_flagged']);
            $table->dropColumn(['is_flagged', 'flag_reason', 'flagged_at']);
        });
    }
};
