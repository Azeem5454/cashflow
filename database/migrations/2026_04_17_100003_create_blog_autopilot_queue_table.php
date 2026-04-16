<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed queue of blog titles the autopilot will publish, in order.
     * The row with the lowest `position` is picked next; on successful publish
     * the row is deleted. Admin can reorder via drag-and-drop in the UI.
     */
    public function up(): void
    {
        Schema::create('blog_autopilot_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->foreignUuid('category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->integer('position')->default(0)->index();
            $table->timestamps();

            // Prevent exact duplicate titles from sneaking into the queue
            $table->unique('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_autopilot_queue');
    }
};
