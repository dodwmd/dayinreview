<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Reddit posts table
        Schema::create('reddit_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reddit_id')->unique();
            $table->string('subreddit');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('author');
            $table->string('permalink');
            $table->string('url');
            $table->integer('score')->default(0);
            $table->integer('num_comments')->default(0);
            $table->boolean('has_youtube_video')->default(false);
            $table->timestamp('posted_at');
            $table->timestamps();

            // Add indexes
            $table->index('subreddit');
            $table->index('has_youtube_video');
            $table->index('posted_at');
            
            // Only add fulltext indexes if not using SQLite (for testing compatibility)
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->fulltext(['title', 'content']);
            }
        });

        // Create YouTube videos table
        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('youtube_id')->unique();
            $table->uuid('reddit_post_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('channel_id');
            $table->string('channel_title');
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->boolean('is_trending')->default(false);
            $table->timestamp('published_at');
            $table->timestamps();

            // Add indexes
            $table->foreign('reddit_post_id')->references('id')->on('reddit_posts')->onDelete('set null');
            $table->index('channel_id');
            $table->index('is_trending');
            $table->index('published_at');
            
            // Only add fulltext indexes if not using SQLite (for testing compatibility)
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->fulltext(['title', 'description']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_videos');
        Schema::dropIfExists('reddit_posts');
    }
};
