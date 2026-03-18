<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->text('ai_insights_cache')->nullable()->after('description');
            $table->timestamp('ai_insights_generated_at')->nullable()->after('ai_insights_cache');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['ai_insights_cache', 'ai_insights_generated_at']);
        });
    }
};
