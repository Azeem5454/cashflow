<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('book_id');
            $table->uuid('user_id');
            $table->string('action');         // entry_created | entry_updated | entry_deleted | bulk_delete | bulk_move | bulk_copy | bulk_copy_opposite | bulk_change_category | bulk_change_payment_mode
            $table->uuid('entry_id')->nullable();
            $table->json('meta')->nullable();  // action-specific details (amount, description, count, target_book, etc.)
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // entry_id: no FK — entries can be deleted; meta stores the details for display
            $table->index(['book_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_activity_log');
    }
};
