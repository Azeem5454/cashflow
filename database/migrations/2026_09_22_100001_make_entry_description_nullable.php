<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Description is optional on entries (and on the recurring rules created
     * from them). Blank descriptions are stored as NULL; clients show the
     * category or "Cash in" / "Cash out" instead.
     *
     * Laravel 11 alters nullability natively on both Postgres (ALTER COLUMN
     * ... DROP NOT NULL) and SQLite (table rebuild), so one code path works.
     */
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });

        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('entries')->whereNull('description')->update(['description' => '']);
        DB::table('recurring_entries')->whereNull('description')->update(['description' => '']);

        Schema::table('entries', function (Blueprint $table) {
            $table->string('description')->nullable(false)->change();
        });

        Schema::table('recurring_entries', function (Blueprint $table) {
            $table->string('description')->nullable(false)->change();
        });
    }
};
