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
        Schema::create('reddit_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reddit_id')->unique()->index();
            $table->string('subreddit')->index();
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
            
            // Add indexes for common query patterns
            $table->index('has_youtube_video');
            $table->index('posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reddit_posts');
    }
};
