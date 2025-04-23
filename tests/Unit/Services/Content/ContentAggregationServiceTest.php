<?php

namespace Tests\Unit\Services\Content;

use App\Models\RedditPost;
use App\Models\YoutubeVideo;
use App\Services\Content\ContentAggregationService;
use App\Services\Reddit\RedditService;
use App\Services\YouTube\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ContentAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RedditService $mockRedditService;

    protected YouTubeService $mockYoutubeService;

    protected ContentAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockRedditService = Mockery::mock(RedditService::class);
        $this->mockYoutubeService = Mockery::mock(YouTubeService::class);

        // Create the content aggregation service with mocked dependencies
        $this->service = new ContentAggregationService(
            $this->mockRedditService,
            $this->mockYoutubeService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test aggregating daily content from popular posts.
     */
    public function test_aggregate_daily_content_from_popular(): void
    {
        // Mock Reddit service to return popular posts
        $this->mockRedditService->shouldReceive('getPopularPosts')
            ->once()
            ->with('day', 25)
            ->andReturn([
                'data' => [
                    $this->makeRedditPost(true, 'youtube-id-1'),
                    $this->makeRedditPost(false),
                    $this->makeRedditPost(true, 'youtube-id-2'),
                ],
            ]);

        // Mock YouTube service response for the first video
        $this->mockYoutubeService->shouldReceive('getVideoDetails')
            ->once()
            ->with('youtube-id-1')
            ->andReturn($this->makeYoutubeVideoDetails('youtube-id-1'));

        // Mock YouTube service response for the second video
        $this->mockYoutubeService->shouldReceive('getVideoDetails')
            ->once()
            ->with('youtube-id-2')
            ->andReturn($this->makeYoutubeVideoDetails('youtube-id-2'));

        // Run the aggregation
        $stats = $this->service->aggregateDailyContent();

        // Verify database has the expected records
        $this->assertDatabaseCount('reddit_posts', 3);
        $this->assertDatabaseCount('youtube_videos', 2);

        // Check the returned statistics
        $this->assertEquals(3, $stats['processed_posts']);
        $this->assertEquals(3, $stats['saved_reddit_posts']);
        $this->assertEquals(2, $stats['saved_youtube_videos']);
    }

    /**
     * Test aggregating content from specific subreddits.
     */
    public function test_aggregate_daily_content_from_subreddits(): void
    {
        // List of subreddits to test
        $subreddits = ['videos', 'music'];

        // Set up mock expectations for each subreddit
        foreach ($subreddits as $subreddit) {
            $this->mockRedditService->shouldReceive('getSubredditPosts')
                ->once()
                ->with($subreddit, 'hot', 'day', 25)
                ->andReturn([
                    'data' => [
                        $this->makeRedditPost(true, "youtube-{$subreddit}-1", $subreddit),
                        $this->makeRedditPost(false, null, $subreddit),
                    ],
                ]);

            // Mock YouTube service for the video in this subreddit
            $this->mockYoutubeService->shouldReceive('getVideoDetails')
                ->once()
                ->with("youtube-{$subreddit}-1")
                ->andReturn($this->makeYoutubeVideoDetails("youtube-{$subreddit}-1"));
        }

        // Run the aggregation
        $stats = $this->service->aggregateDailyContent($subreddits);

        // Verify database has the expected records
        $this->assertDatabaseCount('reddit_posts', 4); // 2 posts per subreddit
        $this->assertDatabaseCount('youtube_videos', 2); // 1 video per subreddit

        // Check the returned statistics
        $this->assertEquals(4, $stats['processed_posts']);
        $this->assertEquals(4, $stats['saved_reddit_posts']);
        $this->assertEquals(2, $stats['saved_youtube_videos']);

        // Check subreddit-specific stats
        foreach ($subreddits as $subreddit) {
            $this->assertEquals(2, $stats['subreddit_stats'][$subreddit]['processed']);
            $this->assertEquals(2, $stats['subreddit_stats'][$subreddit]['reddit_posts_saved']);
            $this->assertEquals(1, $stats['subreddit_stats'][$subreddit]['youtube_videos_saved']);
        }
    }

    /**
     * Test handling API errors during aggregation.
     */
    public function test_aggregate_content_handles_api_errors(): void
    {
        // Mock Reddit service to return an error
        $this->mockRedditService->shouldReceive('getPopularPosts')
            ->once()
            ->with('day', 25)
            ->andReturn(['error' => 'Reddit API error']);

        // Run the aggregation
        $stats = $this->service->aggregateDailyContent();

        // Verify no database records were created
        $this->assertDatabaseCount('reddit_posts', 0);
        $this->assertDatabaseCount('youtube_videos', 0);

        // Check that the error was recorded in stats
        $this->assertEquals(0, $stats['processed_posts']);
        $this->assertEquals(0, $stats['saved_reddit_posts']);
        $this->assertEquals(0, $stats['saved_youtube_videos']);
        $this->assertContains('Reddit API error', $stats['subreddit_stats']['popular']['errors']);
    }

    /**
     * Test updating trending videos.
     */
    public function test_update_trending_videos(): void
    {
        // Create some YouTube videos with various stats
        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'trending-video-1',
            'title' => 'Trending Video 1',
            'channel_id' => 'channel-1',
            'channel_title' => 'Channel 1',
            'view_count' => 150000, // Above threshold
            'like_count' => 15000,  // Above threshold
            'published_at' => now()->subDays(2), // Recent
            'is_trending' => false,
        ]);

        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'old-video',
            'title' => 'Old Video',
            'channel_id' => 'channel-2',
            'channel_title' => 'Channel 2',
            'view_count' => 200000, // Above threshold
            'like_count' => 20000,  // Above threshold
            'published_at' => now()->subDays(10), // Too old
            'is_trending' => false,
        ]);

        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'low-views-video',
            'title' => 'Low Views Video',
            'channel_id' => 'channel-3',
            'channel_title' => 'Channel 3',
            'view_count' => 5000,  // Below threshold
            'like_count' => 1000,  // Below threshold
            'published_at' => now()->subDays(1), // Recent
            'is_trending' => false,
        ]);

        // Run the trending update
        $updatedCount = $this->service->updateTrendingVideos(100000, 10000, 7);

        // Only the first video should be marked as trending
        $this->assertEquals(1, $updatedCount);

        // Verify the trending status in the database
        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'trending-video-1',
            'is_trending' => true,
        ]);

        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'old-video',
            'is_trending' => false,
        ]);

        $this->assertDatabaseHas('youtube_videos', [
            'youtube_id' => 'low-views-video',
            'is_trending' => false,
        ]);
    }

    /**
     * Test getting trending videos with filtering.
     */
    public function test_get_trending_videos(): void
    {
        // Create some trending and non-trending videos
        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'trending-video-1',
            'title' => 'Trending Video 1',
            'channel_id' => 'channel-1',
            'channel_title' => 'Channel 1',
            'view_count' => 150000,
            'like_count' => 15000,
            'duration_seconds' => 300, // 5 minutes
            'published_at' => now()->subDays(1),
            'is_trending' => true,
        ]);

        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'trending-video-2',
            'title' => 'Trending Video 2',
            'channel_id' => 'channel-2',
            'channel_title' => 'Channel 2',
            'view_count' => 200000,
            'like_count' => 20000,
            'duration_seconds' => 600, // 10 minutes
            'published_at' => now()->subDays(2),
            'is_trending' => true,
        ]);

        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'non-trending-video',
            'title' => 'Non Trending Video',
            'channel_id' => 'channel-3',
            'channel_title' => 'Channel 3',
            'view_count' => 5000,
            'like_count' => 500,
            'duration_seconds' => 180, // 3 minutes
            'published_at' => now()->subDays(3),
            'is_trending' => false,
        ]);

        // Test getting all trending videos
        $trendingVideos = $this->service->getTrendingVideos();
        $this->assertCount(2, $trendingVideos);

        // Test filtering by channel
        $channelVideos = $this->service->getTrendingVideos(20, ['channel-1']);
        $this->assertCount(1, $channelVideos);
        $this->assertEquals('trending-video-1', $channelVideos[0]['youtube_id']);

        // Test filtering by duration
        $shortVideos = $this->service->getTrendingVideos(20, [], 0, 400);
        $this->assertCount(1, $shortVideos);
        $this->assertEquals('trending-video-1', $shortVideos[0]['youtube_id']);

        // Test filtering by minimum duration
        $longVideos = $this->service->getTrendingVideos(20, [], 500);
        $this->assertCount(1, $longVideos);
        $this->assertEquals('trending-video-2', $longVideos[0]['youtube_id']);
    }

    /**
     * Test getting videos from a specific subreddit.
     */
    public function test_get_videos_from_subreddit(): void
    {
        // Create Reddit posts from different subreddits
        $redditPost1 = RedditPost::create([
            'id' => (string) Str::uuid(),
            'reddit_id' => 'reddit-post-1',
            'subreddit' => 'videos',
            'title' => 'Test Post 1',
            'author' => 'user1',
            'permalink' => '/r/videos/post1',
            'url' => 'https://example.com/post1',
            'has_youtube_video' => true,
            'posted_at' => now()->subDays(1),
        ]);

        $redditPost2 = RedditPost::create([
            'id' => (string) Str::uuid(),
            'reddit_id' => 'reddit-post-2',
            'subreddit' => 'videos',
            'title' => 'Test Post 2',
            'author' => 'user2',
            'permalink' => '/r/videos/post2',
            'url' => 'https://example.com/post2',
            'has_youtube_video' => true,
            'posted_at' => now()->subDays(2),
        ]);

        $redditPost3 = RedditPost::create([
            'id' => (string) Str::uuid(),
            'reddit_id' => 'reddit-post-3',
            'subreddit' => 'music',
            'title' => 'Test Post 3',
            'author' => 'user3',
            'permalink' => '/r/music/post3',
            'url' => 'https://example.com/post3',
            'has_youtube_video' => true,
            'posted_at' => now()->subDays(3),
        ]);

        // Create YouTube videos associated with the Reddit posts
        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'youtube-video-1',
            'reddit_post_id' => $redditPost1->id,
            'title' => 'YouTube Video 1',
            'channel_id' => 'channel-1',
            'channel_title' => 'Channel 1',
            'view_count' => 10000,
            'published_at' => now()->subDays(1),
        ]);

        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'youtube-video-2',
            'reddit_post_id' => $redditPost2->id,
            'title' => 'YouTube Video 2',
            'channel_id' => 'channel-2',
            'channel_title' => 'Channel 2',
            'view_count' => 20000,
            'published_at' => now()->subDays(2),
        ]);

        YoutubeVideo::create([
            'id' => (string) Str::uuid(),
            'youtube_id' => 'youtube-video-3',
            'reddit_post_id' => $redditPost3->id,
            'title' => 'YouTube Video 3',
            'channel_id' => 'channel-3',
            'channel_title' => 'Channel 3',
            'view_count' => 30000,
            'published_at' => now()->subDays(3),
        ]);

        // Test getting videos from 'videos' subreddit
        $videosSubredditRecent = $this->service->getVideosFromSubreddit('videos');
        $this->assertCount(2, $videosSubredditRecent);
        // First item should be the most recent video
        $this->assertEquals('youtube-video-1', $videosSubredditRecent[0]['youtube_id']);

        // Test with popularity sorting
        $videosSubredditPopular = $this->service->getVideosFromSubreddit('videos', 20, 'popular');
        $this->assertCount(2, $videosSubredditPopular);
        // First item should be the most popular video
        $this->assertEquals('youtube-video-2', $videosSubredditPopular[0]['youtube_id']);

        // Test getting videos from 'music' subreddit
        $musicSubreddit = $this->service->getVideosFromSubreddit('music');
        $this->assertCount(1, $musicSubreddit);
        $this->assertEquals('youtube-video-3', $musicSubreddit[0]['youtube_id']);
    }

    /**
     * Helper to create a mock Reddit post for testing.
     */
    private function makeRedditPost(bool $hasYoutubeVideo = false, ?string $youtubeId = null, string $subreddit = 'videos'): array
    {
        $id = Str::random(6);

        return [
            'id' => $id,
            'subreddit' => $subreddit,
            'title' => "Test Post {$id}",
            'selftext' => "This is test post {$id}",
            'author' => 'testuser',
            'permalink' => "/r/{$subreddit}/comments/{$id}/test_post_{$id}",
            'url' => "https://www.reddit.com/r/{$subreddit}/comments/{$id}/test_post_{$id}",
            'score' => random_int(10, 1000),
            'num_comments' => random_int(0, 100),
            'has_youtube_video' => $hasYoutubeVideo,
            'youtube_id' => $youtubeId,
            'created_utc' => now()->timestamp,
        ];
    }

    /**
     * Helper to create mock YouTube video details for testing.
     */
    private function makeYoutubeVideoDetails(string $videoId): array
    {
        return [
            'id' => $videoId,
            'title' => "YouTube Video {$videoId}",
            'description' => "This is YouTube video {$videoId}",
            'channel_id' => "channel-{$videoId}",
            'channel_title' => "Channel {$videoId}",
            'published_at' => now()->subDays(random_int(1, 5))->toIso8601String(),
            'thumbnail' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
            'duration_seconds' => random_int(60, 900),
            'view_count' => random_int(1000, 100000),
            'like_count' => random_int(100, 10000),
        ];
    }
}
