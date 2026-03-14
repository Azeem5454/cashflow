<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('book_id')->index();
            $table->string('name', 100);
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_categories');
    }
};
