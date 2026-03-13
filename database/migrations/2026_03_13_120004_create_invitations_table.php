<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('email');
            $table->string('role')->default('viewer'); // owner | editor | viewer
            $table->string('token')->unique()->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
