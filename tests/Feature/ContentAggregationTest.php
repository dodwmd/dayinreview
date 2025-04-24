<?php

namespace Tests\Feature;

use App\Models\RedditPost;
use App\Models\User;
use App\Models\YoutubeVideo;
use App\Services\Content\ContentAggregationService;
use App\Services\Reddit\RedditService;
use App\Services\YouTube\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ContentAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock HTTP for YouTube API
        Http::fake([
            'www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'dQw4w9WgXcQ',
                        'snippet' => [
                            'title' => 'Test Video',
                            'description' => 'Test description',
                            'channelId' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
                            'channelTitle' => 'Test Channel',
                            'publishedAt' => '2023-01-01T00:00:00Z',
                            'thumbnails' => [
                                'default' => ['url' => 'https://example.com/thumb.jpg'],
                                'medium' => ['url' => 'https://example.com/thumb-medium.jpg'],
                                'high' => ['url' => 'https://example.com/thumb-high.jpg'],
                            ],
                        ],
                        'statistics' => [
                            'viewCount' => '1000000',
                            'likeCount' => '50000',
                            'commentCount' => '5000',
                        ],
                        'contentDetails' => [
                            'duration' => 'PT4M30S',
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    /**
     * Test aggregating content from Reddit with YouTube links.
     */
    public function test_aggregate_content_from_reddit(): void
    {
        // Mock Reddit Service to return predefined posts
        $mockRedditService = Mockery::mock(RedditService::class);
        $mockRedditService->shouldReceive('getPopularPosts')->once()->andReturn([
            [
                'id' => 'post1',
                'subreddit' => 'videos',
                'title' => 'Amazing YouTube Video',
                'selftext' => 'Check out this video',
                'author' => 'test_user',
                'permalink' => '/r/videos/comments/post1/amazing_youtube_video/',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // YouTube URL
                'score' => 500,
                'created_utc' => time(),
                'num_comments' => 100,
            ],
            [
                'id' => 'post2',
                'subreddit' => 'programming',
                'title' => 'Regular Article',
                'selftext' => 'Interesting article about programming',
                'author' => 'dev_user',
                'permalink' => '/r/programming/comments/post2/regular_article/',
                'url' => 'https://example.com/article', // Non-YouTube URL
                'score' => 300,
                'created_utc' => time(),
                'num_comments' => 50,
            ],
        ]);
        
        $mockRedditService->shouldReceive('isYouTubeVideo')->with(Mockery::on(function($post) {
            return $post['id'] === 'post1';
        }))->andReturn(true);
        
        $mockRedditService->shouldReceive('isYouTubeVideo')->with(Mockery::on(function($post) {
            return $post['id'] === 'post2';
        }))->andReturn(false);
        
        $this->app->instance(RedditService::class, $mockRedditService);
        
        // Use real YouTubeService with faked HTTP responses
        $youtubeService = app(YouTubeService::class);
        
        // Run the content aggregation service
        $contentAggregationService = new ContentAggregationService(
            $mockRedditService, 
            $youtubeService
        );
        
        $contentAggregationService->aggregateDailyContent();
        
        // Verify that Reddit posts were stored
        $this->assertDatabaseHas('reddit_posts', [
            'reddit_id' => 'post1',
            'subreddit' => 'videos',
            'title' => 'Amazing YouTube Video',
        ]);
        
        $this->assertDatabaseHas('reddit_posts', [
            'reddit_id' => 'post2',
            'subreddit' => 'programming',
            'title' => 'Regular Article',
        ]);
        
        // Verify that YouTube video was extracted and stored
        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Test Video',
            'channel_title' => 'Test Channel',
        ]);
        
        // Verify that the YouTube video is linked to the Reddit post
        $redditPost = RedditPost::where('reddit_id', 'post1')->first();
        $youtubeVideo = YoutubeVideo::where('youtube_id', 'dQw4w9WgXcQ')->first();
        
        $this->assertNotNull($redditPost);
        $this->assertNotNull($youtubeVideo);
        $this->assertEquals($redditPost->youtube_video_id, $youtubeVideo->id);
    }

    /**
     * Test aggregating content with user subscriptions.
     */
    public function test_aggregate_content_with_user_subscriptions(): void
    {
        // Create a user with subscriptions
        $user = User::factory()->create();
        
        // Mock Reddit Service
        $mockRedditService = Mockery::mock(RedditService::class);
        $mockRedditService->shouldReceive('getSubredditPosts')->with('programming')->andReturn([
            [
                'id' => 'sub_post1',
                'subreddit' => 'programming',
                'title' => 'Programming YouTube Tutorial',
                'selftext' => 'Great tutorial video',
                'author' => 'coder',
                'permalink' => '/r/programming/comments/sub_post1/programming_youtube_tutorial/',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // YouTube URL
                'score' => 200,
                'created_utc' => time(),
                'num_comments' => 30,
            ],
        ]);
        
        $mockRedditService->shouldReceive('isYouTubeVideo')->andReturn(true);
        
        $this->app->instance(RedditService::class, $mockRedditService);
        
        // Use real YouTubeService with faked HTTP responses
        $youtubeService = app(YouTubeService::class);
        
        // Run the content aggregation service with user subscriptions
        $contentAggregationService = new ContentAggregationService(
            $mockRedditService, 
            $youtubeService
        );
        
        $contentAggregationService->aggregateContentForUser($user);
        
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
            'id' => \Illuminate\Support\Str::uuid(),
            'reddit_id' => 'existing_post',
            'subreddit' => 'videos',
            'title' => 'Old Title',
            'content' => 'Old content',
            'author' => 'old_user',
            'permalink' => '/r/videos/comments/existing_post/old_title/',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'score' => 100,
            'comment_count' => 10,
            'created_at' => now()->subDays(2),
        ]);
        
        $youtubeVideo = YoutubeVideo::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Old Video Title',
            'description' => 'Old description',
            'channel_id' => 'old_channel_id',
            'channel_title' => 'Old Channel',
            'published_at' => now()->subDays(10),
            'view_count' => 500,
            'like_count' => 50,
            'comment_count' => 5,
            'duration' => 'PT3M',
            'thumbnail_url' => 'https://example.com/old-thumb.jpg',
        ]);
        
        // Link the YouTube video to the Reddit post
        $redditPost->youtube_video_id = $youtubeVideo->id;
        $redditPost->save();
        
        // Mock Reddit Service to return updated post data
        $mockRedditService = Mockery::mock(RedditService::class);
        $mockRedditService->shouldReceive('getPopularPosts')->once()->andReturn([
            [
                'id' => 'existing_post',
                'subreddit' => 'videos',
                'title' => 'Updated Title',
                'selftext' => 'Updated content',
                'author' => 'updated_user',
                'permalink' => '/r/videos/comments/existing_post/updated_title/',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'score' => 600, // Updated score
                'created_utc' => time(),
                'num_comments' => 60, // Updated comment count
            ],
        ]);
        
        $mockRedditService->shouldReceive('isYouTubeVideo')->andReturn(true);
        
        $this->app->instance(RedditService::class, $mockRedditService);
        
        // Use real YouTubeService with faked HTTP responses (returning updated data)
        $youtubeService = app(YouTubeService::class);
        
        // Run the content aggregation service
        $contentAggregationService = new ContentAggregationService(
            $mockRedditService, 
            $youtubeService
        );
        
        $contentAggregationService->aggregateDailyContent();
        
        // Verify that Reddit post was updated
        $this->assertDatabaseHas('reddit_posts', [
            'reddit_id' => 'existing_post',
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'score' => 600,
            'comment_count' => 60,
        ]);
        
        // Verify that YouTube video was updated
        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'dQw4w9WgXcQ',
            'title' => 'Test Video', // From the mocked response
            'channel_title' => 'Test Channel', // From the mocked response
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
