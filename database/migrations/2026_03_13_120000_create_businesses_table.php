<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
