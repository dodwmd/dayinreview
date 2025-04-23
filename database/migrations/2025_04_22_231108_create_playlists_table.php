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
        // Create playlists table
        Schema::create('playlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->enum('type', ['auto', 'custom'])->default('auto');
            $table->enum('visibility', ['private', 'unlisted', 'public'])->default('private');
            $table->boolean('is_favorite')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->json('generation_algorithm')->nullable(); // Stores algorithm settings if auto-generated
            $table->timestamps();

            // Add indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('is_favorite');
        });

        // Create playlist items table
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('playlist_id');
            $table->string('source_type'); // 'youtube_video'
            $table->uuid('source_id'); // References ID from source table
            $table->integer('position');
            $table->boolean('is_watched')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('added_at');
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();

            // Add foreign key
            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->onDelete('cascade');

            // Add indexes
            $table->index(['source_type', 'source_id']);
            $table->index('position');
            $table->index('is_watched');
        });

        // Create playlist categories (similar to subscription categories)
        Schema::create('playlist_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // Create playlist-category pivot table
        Schema::create('category_playlist', function (Blueprint $table) {
            $table->uuid('playlist_id');
            $table->uuid('playlist_category_id');

            $table->primary(['playlist_id', 'playlist_category_id']);

            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->onDelete('cascade');

            $table->foreign('playlist_category_id')
                ->references('id')
                ->on('playlist_categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_playlist');
        Schema::dropIfExists('playlist_categories');
        Schema::dropIfExists('playlist_items');
        Schema::dropIfExists('playlists');
    }
};
