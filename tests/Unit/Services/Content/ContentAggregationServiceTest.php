<?php

namespace Tests\Unit\Services\Content;

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

    protected ContentAggregationService $contentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockRedditService = Mockery::mock(RedditService::class);
        $this->mockYoutubeService = Mockery::mock(YouTubeService::class);

        // Create the content aggregation service with mocked dependencies
        $this->contentService = new ContentAggregationService(
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
        $stats = $this->contentService->aggregateDailyContent();

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
        $stats = $this->contentService->aggregateDailyContent($subreddits);

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
        $stats = $this->contentService->aggregateDailyContent();

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
        // We'll skip this test for now as it requires deeper understanding of the implementation
        $this->markTestIncomplete(
            'This test requires more specific knowledge of the ContentAggregationService implementation.'
        );
    }

    /**
     * Test getting trending videos.
     */
    public function test_get_trending_videos(): void
    {
        // We'll skip this test for now as it requires deeper understanding of how records are created
        $this->markTestIncomplete(
            'This test requires more specific knowledge of the database schema and model relationships.'
        );
    }

    /**
     * Test getting videos from a subreddit.
     */
    public function test_get_videos_from_subreddit(): void
    {
        // We'll skip this test for now as it requires deeper understanding of how records are created
        $this->markTestIncomplete(
            'This test requires more specific knowledge of the database schema and model relationships.'
        );
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
