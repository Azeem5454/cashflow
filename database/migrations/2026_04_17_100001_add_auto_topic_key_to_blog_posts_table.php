<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Set by BlogAutopilot when a post is generated from a topic in
            // config/blog_topics.php. Null for human-written posts. Lets the
            // autopilot skip topics used in the last 90 days to avoid repeats.
            $table->string('auto_topic_key', 120)->nullable()->after('seo_description');
            $table->index(['auto_topic_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['auto_topic_key', 'created_at']);
            $table->dropColumn('auto_topic_key');
        });
    }
};
