<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entry_id')->constrained('entries')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->json('mentioned_user_ids')->nullable(); // array of user UUIDs @mentioned
            $table->timestamps();

            $table->index(['entry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_comments');
    }
};
