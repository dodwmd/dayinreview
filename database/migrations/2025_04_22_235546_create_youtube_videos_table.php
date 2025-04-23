<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('youtube_id')->unique()->index();
            $table->uuid('reddit_post_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('channel_id')->index();
            $table->string('channel_title');
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->bigInteger('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->boolean('is_trending')->default(false);
            $table->timestamp('published_at');
            $table->timestamps();
            
            // Add indexes for common query patterns
            $table->index('published_at');
            $table->index('is_trending');
            
            // Add foreign key
            $table->foreign('reddit_post_id')
                  ->references('id')
                  ->on('reddit_posts')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_videos');
    }
};
