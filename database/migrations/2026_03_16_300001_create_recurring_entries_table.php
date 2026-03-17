<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->string('type');          // 'in' | 'out'
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('category')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('reference')->nullable();
            $table->string('frequency');     // 'daily' | 'weekly' | 'monthly' | 'yearly'
            $table->date('starts_at');
            $table->date('next_run_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_entries');
    }
};
