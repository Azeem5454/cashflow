<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_user', function (Blueprint $table) {
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->string('role')->default('viewer'); // owner | editor | viewer
            $table->timestamps();

            $table->primary(['business_id', 'user_id']);
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_user');
    }
};
