<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->foreignUuid('recurring_entry_id')
                ->nullable()
                ->after('payment_mode')
                ->constrained('recurring_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_entry_id');
        });
    }
};
