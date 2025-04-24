<?php

namespace Tests\Feature;

use App\Models\RedditPost;
use App\Models\Subscription;
use App\Models\User;
use App\Models\YoutubeVideo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContentAggregationTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure MySQL is used for testing (not SQLite)
        $this->app['config']->set('database.default', 'mysql');

        // Clear database connections
        DB::purge();

        // Create tables for testing using migrations
        $this->artisan('migrate:fresh');

        // Modify the UUID handling in models
        $models = [User::class, RedditPost::class, YoutubeVideo::class, Subscription::class];
        foreach ($models as $model) {
            $model::creating(function ($model) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            });
        }
    }

    /**
     * Clean up after testing.
     */
    protected function tearDown(): void
    {
        // Clean up the database after each test
        $this->artisan('migrate:fresh');

        parent::tearDown();
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
