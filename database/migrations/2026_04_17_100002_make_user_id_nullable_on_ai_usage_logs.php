<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BlogAutopilot runs are system-generated (no authenticated user) but we
     * still want to log their Claude cost to ai_usage_logs. Make user_id
     * nullable + switch the FK to nullOnDelete so those rows survive without
     * a user.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Drop the existing FK, drop NOT NULL, re-add FK with ON DELETE SET NULL.
            // Laravel names FKs `{table}_{column}_foreign` by default.
            DB::statement('ALTER TABLE ai_usage_logs DROP CONSTRAINT IF EXISTS ai_usage_logs_user_id_foreign');
            DB::statement('ALTER TABLE ai_usage_logs ALTER COLUMN user_id DROP NOT NULL');
            DB::statement('ALTER TABLE ai_usage_logs ADD CONSTRAINT ai_usage_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        } else {
            // SQLite (tests): rebuild the table since it can't ALTER nullability directly.
            // For local test suites — not touching prod data.
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Guard: down migration would fail if any autopilot rows exist (NULL user_id).
            // We purposely don't force-nuke them — leave them for the operator to handle.
            DB::statement('ALTER TABLE ai_usage_logs DROP CONSTRAINT IF EXISTS ai_usage_logs_user_id_foreign');
            DB::statement('ALTER TABLE ai_usage_logs ALTER COLUMN user_id SET NOT NULL');
            DB::statement('ALTER TABLE ai_usage_logs ADD CONSTRAINT ai_usage_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        } else {
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->uuid('user_id')->nullable(false)->change();
            });
        }
    }
};
