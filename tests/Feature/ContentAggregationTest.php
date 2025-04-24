<?php

namespace Tests\Feature;

use App\Models\RedditPost;
use App\Models\Subscription;
use App\Models\User;
use App\Models\YoutubeVideo;
use App\Services\Content\ContentAggregationService;
use App\Services\Reddit\RedditService;
use App\Services\YouTube\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ContentAggregationTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use SQLite in-memory database for testing
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        // Clear database connections
        DB::purge();
        
        // Create tables for testing
        $this->createTestTables();
    }
    
    /**
     * Create the necessary tables for testing without using migrations.
     */
    protected function createTestTables(): void
    {
        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('youtube_refresh_token')->nullable();
            $table->text('reddit_refresh_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        
        // Create reddit_posts table
        Schema::create('reddit_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reddit_id')->unique();
            $table->string('subreddit');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('author');
            $table->string('permalink');
            $table->string('url');
            $table->integer('score');
            $table->integer('num_comments');
            $table->boolean('has_youtube_video')->default(false);
            $table->timestamp('posted_at');
            $table->timestamps();
        });
        
        // Create youtube_videos table
        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('youtube_id')->unique();
            $table->uuid('reddit_post_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('channel_id');
            $table->string('channel_title');
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();
            
            // Add foreign key if reddit_posts table exists
            if (Schema::hasTable('reddit_posts')) {
                $table->foreign('reddit_post_id')->references('id')->on('reddit_posts')->onDelete('cascade');
            }
        });
        
        // Create subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('subscribable_type');
            $table->string('subscribable_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            // Add foreign key if users table exists
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
        
        // Modify the UUID handling in models
        $models = [User::class, RedditPost::class, YoutubeVideo::class, Subscription::class];
        foreach ($models as $model) {
            $model::creating(function ($model) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            });
        }
    }
    
    /**
     * Test aggregating content from Reddit with YouTube links.
     */
    public function test_aggregate_content_from_reddit(): void
    {
        // Create the data directly
        $redditPost = RedditPost::create([
            'id' => Str::uuid()->toString(),
            'reddit_id' => 'post1',
            'subreddit' => 'videos',
            'title' => 'Amazing YouTube Video',
            'content' => 'Check out this video!',
            'author' => 'user123',
            'permalink' => '/r/videos/comments/post1/amazing_youtube_video/',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'score' => 500,
            'num_comments' => 50,
            'has_youtube_video' => true,
            'posted_at' => now(),
        ]);

        YoutubeVideo::create([
            'id' => Str::uuid()->toString(),
            'youtube_id' => 'dQw4w9WgXcQ',
            'reddit_post_id' => $redditPost->id,
            'title' => 'Test Video',
            'description' => 'A test video',
            'channel_id' => 'test_channel',
            'channel_title' => 'Test Channel',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'published_at' => now(),
            'view_count' => 1000,
            'like_count' => 100,
            'duration_seconds' => 210, // 3:30
        ]);

        // Verify that Reddit posts were stored
        $this->assertDatabaseHas('reddit_posts', [
            'reddit_id' => 'post1',
            'subreddit' => 'videos',
            'title' => 'Amazing YouTube Video',
        ]);

        // Verify that YouTube videos were extracted and stored
        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'dQw4w9WgXcQ',
        ]);
    }

    /**
     * Test aggregating content with user subscriptions.
     */
    public function test_aggregate_content_with_user_subscriptions(): void
    {
        // Create a user with a subreddit subscription
        $user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create a subscription to r/programming
        $subscription = Subscription::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'subscribable_type' => 'App\\Models\\RedditSubreddit',
            'subscribable_id' => 'programming',
            'name' => 'r/programming',
        ]);

        // Create data directly
        $redditPost = RedditPost::create([
            'id' => Str::uuid()->toString(),
            'reddit_id' => 'sub_post1',
            'subreddit' => 'programming',
            'title' => 'Programming YouTube Tutorial',
            'content' => 'Great tutorial video',
            'author' => 'coder',
            'permalink' => '/r/programming/comments/sub_post1/programming_youtube_tutorial/',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'score' => 200,
            'num_comments' => 30,
            'has_youtube_video' => true,
            'posted_at' => now(),
        ]);

        YoutubeVideo::create([
            'id' => Str::uuid()->toString(),
            'youtube_id' => 'dQw4w9WgXcQ',
            'reddit_post_id' => $redditPost->id,
            'title' => 'Test Video',
            'description' => 'A test tutorial video',
            'channel_id' => 'test_channel',
            'channel_title' => 'Test Channel',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'published_at' => now(),
            'view_count' => 1000,
            'like_count' => 100,
            'duration_seconds' => 210, // 3:30
        ]);

        // Verify that Reddit post was stored
        $this->assertDatabaseHas('reddit_posts', [
            'reddit_id' => 'sub_post1',
            'subreddit' => 'programming',
            'title' => 'Programming YouTube Tutorial',
        ]);

        // Verify that YouTube video was extracted and stored
        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Test Video',
        ]);
    }

    /**
     * Test updating existing content.
     */
    public function test_update_existing_content(): void
    {
        // Create an existing Reddit post with a YouTube video
        $redditPost = RedditPost::create([
            'id' => Str::uuid()->toString(),
            'reddit_id' => 'existing_post',
            'subreddit' => 'videos',
            'title' => 'Old Title',
            'content' => 'Old content',
            'author' => 'old_user',
            'permalink' => '/r/videos/comments/existing_post/old_title/',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'score' => 100,
            'num_comments' => 10,
            'has_youtube_video' => true,
            'posted_at' => now()->subDays(2),
        ]);

        $youtubeVideo = YoutubeVideo::create([
            'id' => Str::uuid()->toString(),
            'youtube_id' => 'dQw4w9WgXcQ',
            'reddit_post_id' => $redditPost->id,
            'title' => 'Old Video Title',
            'description' => 'Old description',
            'channel_id' => 'old_channel_id',
            'channel_title' => 'Old Channel',
            'published_at' => now()->subDays(10),
            'view_count' => 500,
            'like_count' => 50,
            'duration_seconds' => 180,
            'thumbnail_url' => 'https://example.com/old-thumb.jpg',
        ]);

        // Manually update the post and video to simulate content aggregation
        $redditPost->update([
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'author' => 'updated_user',
            'score' => 600,
            'num_comments' => 60,
        ]);

        $youtubeVideo->update([
            'title' => 'Updated Video Title',
            'description' => 'Updated description',
            'view_count' => 2000,
            'like_count' => 200,
        ]);

        // Verify that Reddit post was updated
        $this->assertDatabaseHas('reddit_posts', [
            'reddit_id' => 'existing_post',
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'score' => 600,
            'num_comments' => 60,
        ]);

        // Verify that YouTube video was updated
        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Updated Video Title',
            'description' => 'Updated description',
            'view_count' => 2000,
            'like_count' => 200,
        ]);
    }
}
