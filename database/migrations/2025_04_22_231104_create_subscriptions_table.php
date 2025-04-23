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
        // Create subscriptions table - polymorphic to handle both Reddit and YouTube
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subscribable_type'); // 'reddit' or 'youtube'
            $table->string('subscribable_id');   // subreddit name or YouTube channel ID
            $table->string('name');              // subreddit name or channel name
            $table->string('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->integer('priority')->default(0); // For custom ordering
            $table->timestamps();

            // Add indexes
            $table->unique(['user_id', 'subscribable_type', 'subscribable_id']);
            $table->index(['subscribable_type', 'subscribable_id']);
            $table->index('is_favorite');
        });

        // Create subscription categories for organization
        Schema::create('subscription_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // Create pivot table for subscription-category relationship
        Schema::create('category_subscription', function (Blueprint $table) {
            $table->uuid('subscription_id');
            $table->uuid('subscription_category_id');

            $table->primary(['subscription_id', 'subscription_category_id']);

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->onDelete('cascade');

            $table->foreign('subscription_category_id')
                ->references('id')
                ->on('subscription_categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_subscription');
        Schema::dropIfExists('subscription_categories');
        Schema::dropIfExists('subscriptions');
    }
};
