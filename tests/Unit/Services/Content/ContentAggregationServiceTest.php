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
        // Create non-trending test videos
        $nonTrendingVideo1Id = (string) Str::uuid();
        $nonTrendingVideo1 = new YoutubeVideo;
        $nonTrendingVideo1->id = $nonTrendingVideo1Id;
        $nonTrendingVideo1->youtube_id = 'trending-video-1';
        $nonTrendingVideo1->title = 'Trending Video 1';
        $nonTrendingVideo1->description = 'Description for trending video 1';
        $nonTrendingVideo1->channel_id = 'channel-1';
        $nonTrendingVideo1->channel_title = 'Channel 1';
        $nonTrendingVideo1->thumbnail_url = 'https://example.com/thumb1.jpg';
        $nonTrendingVideo1->duration_seconds = 300; // 5 minutes
        $nonTrendingVideo1->view_count = 150000; // Above the default threshold
        $nonTrendingVideo1->like_count = 15000; // Above the default threshold
        $nonTrendingVideo1->published_at = now()->subDays(2); // Within the default time range
        $nonTrendingVideo1->is_trending = false;
        $nonTrendingVideo1->save();

        $nonTrendingVideo2Id = (string) Str::uuid();
        $nonTrendingVideo2 = new YoutubeVideo;
        $nonTrendingVideo2->id = $nonTrendingVideo2Id;
        $nonTrendingVideo2->youtube_id = 'trending-video-2';
        $nonTrendingVideo2->title = 'Trending Video 2';
        $nonTrendingVideo2->description = 'Description for trending video 2';
        $nonTrendingVideo2->channel_id = 'channel-2';
        $nonTrendingVideo2->channel_title = 'Channel 2';
        $nonTrendingVideo2->thumbnail_url = 'https://example.com/thumb2.jpg';
        $nonTrendingVideo2->duration_seconds = 600; // 10 minutes
        $nonTrendingVideo2->view_count = 200000; // Above the default threshold
        $nonTrendingVideo2->like_count = 20000; // Above the default threshold
        $nonTrendingVideo2->published_at = now()->subDays(4); // Within the default time range
        $nonTrendingVideo2->is_trending = false;
        $nonTrendingVideo2->save();

        // Create a video that shouldn't be considered trending (too few views)
        $lowViewVideoId = (string) Str::uuid();
        $lowViewVideo = new YoutubeVideo;
        $lowViewVideo->id = $lowViewVideoId;
        $lowViewVideo->youtube_id = 'low-view-video';
        $lowViewVideo->title = 'Low View Video';
        $lowViewVideo->description = 'Description for low view video';
        $lowViewVideo->channel_id = 'channel-3';
        $lowViewVideo->channel_title = 'Channel 3';
        $lowViewVideo->thumbnail_url = 'https://example.com/thumb3.jpg';
        $lowViewVideo->duration_seconds = 300; // 5 minutes
        $lowViewVideo->view_count = 50000; // Below the default threshold
        $lowViewVideo->like_count = 15000; // Above the default threshold
        $lowViewVideo->published_at = now()->subDays(3); // Within the default time range
        $lowViewVideo->is_trending = false;
        $lowViewVideo->save();

        // Create a video that's already marked as trending
        $existingTrendingVideoId = (string) Str::uuid();
        $existingTrendingVideo = new YoutubeVideo;
        $existingTrendingVideo->id = $existingTrendingVideoId;
        $existingTrendingVideo->youtube_id = 'already-trending';
        $existingTrendingVideo->title = 'Already Trending Video';
        $existingTrendingVideo->description = 'Description for already trending video';
        $existingTrendingVideo->channel_id = 'channel-4';
        $existingTrendingVideo->channel_title = 'Channel 4';
        $existingTrendingVideo->thumbnail_url = 'https://example.com/thumb4.jpg';
        $existingTrendingVideo->duration_seconds = 400; // 6.67 minutes
        $existingTrendingVideo->view_count = 250000; // Above the default threshold
        $existingTrendingVideo->like_count = 25000; // Above the default threshold
        $existingTrendingVideo->published_at = now()->subDays(1); // Within the default time range
        $existingTrendingVideo->is_trending = true; // Already trending
        $existingTrendingVideo->save();

        // Call the service method with specific thresholds
        $result = $this->contentService->updateTrendingVideos(100000, 10000, 7);

        // Assert that the correct number of videos were updated (should be 2)
        $this->assertEquals(2, $result);

        // Refresh the models to get the updated data from the database
        $nonTrendingVideo1->refresh();
        $nonTrendingVideo2->refresh();
        $lowViewVideo->refresh();
        $existingTrendingVideo->refresh();

        // Check that videos are now marked as trending
        $this->assertTrue($nonTrendingVideo1->is_trending);
        $this->assertTrue($nonTrendingVideo2->is_trending);

        // Ensure the low view video remains non-trending
        $this->assertFalse($lowViewVideo->is_trending);

        // Ensure the existing trending video remains trending
        $this->assertTrue($existingTrendingVideo->is_trending);
    }

    /**
     * Test getting trending videos with filters.
     */
    public function test_get_trending_videos(): void
    {
        // Create trending test videos with different properties
        $trendingVideo1Id = (string) Str::uuid();
        $trendingVideo1 = new YoutubeVideo;
        $trendingVideo1->id = $trendingVideo1Id;
        $trendingVideo1->youtube_id = 'trending-video-1';
        $trendingVideo1->title = 'Trending Video 1';
        $trendingVideo1->description = 'Description for trending video 1';
        $trendingVideo1->channel_id = 'channel-1';
        $trendingVideo1->channel_title = 'Channel 1';
        $trendingVideo1->thumbnail_url = 'https://example.com/thumb1.jpg';
        $trendingVideo1->duration_seconds = 300; // 5 minutes
        $trendingVideo1->view_count = 150000;
        $trendingVideo1->like_count = 15000;
        $trendingVideo1->published_at = now()->subDays(1);
        $trendingVideo1->is_trending = true;
        $trendingVideo1->save();

        $trendingVideo2Id = (string) Str::uuid();
        $trendingVideo2 = new YoutubeVideo;
        $trendingVideo2->id = $trendingVideo2Id;
        $trendingVideo2->youtube_id = 'trending-video-2';
        $trendingVideo2->title = 'Trending Video 2';
        $trendingVideo2->description = 'Description for trending video 2';
        $trendingVideo2->channel_id = 'channel-2';
        $trendingVideo2->channel_title = 'Channel 2';
        $trendingVideo2->thumbnail_url = 'https://example.com/thumb2.jpg';
        $trendingVideo2->duration_seconds = 600; // 10 minutes
        $trendingVideo2->view_count = 200000;
        $trendingVideo2->like_count = 20000;
        $trendingVideo2->published_at = now()->subDays(2);
        $trendingVideo2->is_trending = true;
        $trendingVideo2->save();

        $trendingVideo3Id = (string) Str::uuid();
        $trendingVideo3 = new YoutubeVideo;
        $trendingVideo3->id = $trendingVideo3Id;
        $trendingVideo3->youtube_id = 'trending-video-3';
        $trendingVideo3->title = 'Trending Video 3';
        $trendingVideo3->description = 'Description for trending video 3';
        $trendingVideo3->channel_id = 'channel-1'; // Same channel as video 1
        $trendingVideo3->channel_title = 'Channel 1';
        $trendingVideo3->thumbnail_url = 'https://example.com/thumb3.jpg';
        $trendingVideo3->duration_seconds = 1200; // 20 minutes
        $trendingVideo3->view_count = 300000;
        $trendingVideo3->like_count = 30000;
        $trendingVideo3->published_at = now()->subDays(3);
        $trendingVideo3->is_trending = true;
        $trendingVideo3->save();

        // Create a non-trending video
        $nonTrendingVideoId = (string) Str::uuid();
        $nonTrendingVideo = new YoutubeVideo;
        $nonTrendingVideo->id = $nonTrendingVideoId;
        $nonTrendingVideo->youtube_id = 'non-trending-video';
        $nonTrendingVideo->title = 'Non-Trending Video';
        $nonTrendingVideo->description = 'Description for non-trending video';
        $nonTrendingVideo->channel_id = 'channel-3';
        $nonTrendingVideo->channel_title = 'Channel 3';
        $nonTrendingVideo->thumbnail_url = 'https://example.com/thumb4.jpg';
        $nonTrendingVideo->duration_seconds = 180; // 3 minutes
        $nonTrendingVideo->view_count = 50000;
        $nonTrendingVideo->like_count = 5000;
        $nonTrendingVideo->published_at = now()->subDays(1);
        $nonTrendingVideo->is_trending = false;
        $nonTrendingVideo->save();

        // Test 1: Get all trending videos (default limit)
        $result1 = $this->contentService->getTrendingVideos();
        $this->assertCount(3, $result1);

        // Trending videos should be ordered by published_at desc
        $this->assertEquals($trendingVideo1Id, $result1[0]['id']);
        $this->assertEquals($trendingVideo2Id, $result1[1]['id']);
        $this->assertEquals($trendingVideo3Id, $result1[2]['id']);

        // Test 2: Get trending videos with limit
        $result2 = $this->contentService->getTrendingVideos(2);
        $this->assertCount(2, $result2);
        $this->assertEquals($trendingVideo1Id, $result2[0]['id']);
        $this->assertEquals($trendingVideo2Id, $result2[1]['id']);

        // Test 3: Filter by channel ID
        $result3 = $this->contentService->getTrendingVideos(20, ['channel-1']);
        $this->assertCount(2, $result3);
        // Should include trending videos 1 and 3 (same channel)
        $foundVideo1 = false;
        $foundVideo3 = false;
        foreach ($result3 as $video) {
            if ($video['id'] === $trendingVideo1Id) {
                $foundVideo1 = true;
            }
            if ($video['id'] === $trendingVideo3Id) {
                $foundVideo3 = true;
            }
        }
        $this->assertTrue($foundVideo1);
        $this->assertTrue($foundVideo3);

        // Test 4: Filter by minimum duration
        $result4 = $this->contentService->getTrendingVideos(20, [], 500);
        $this->assertCount(2, $result4);
        // Should include trending videos 2 and 3 (longer than 500 seconds)
        $foundVideo2 = false;
        $foundVideo3 = false;
        foreach ($result4 as $video) {
            if ($video['id'] === $trendingVideo2Id) {
                $foundVideo2 = true;
            }
            if ($video['id'] === $trendingVideo3Id) {
                $foundVideo3 = true;
            }
        }
        $this->assertTrue($foundVideo2);
        $this->assertTrue($foundVideo3);

        // Test 5: Filter by maximum duration
        $result5 = $this->contentService->getTrendingVideos(20, [], 0, 700);
        $this->assertCount(2, $result5);
        // Should include trending videos 1 and 2 (shorter than 700 seconds)
        $foundVideo1 = false;
        $foundVideo2 = false;
        foreach ($result5 as $video) {
            if ($video['id'] === $trendingVideo1Id) {
                $foundVideo1 = true;
            }
            if ($video['id'] === $trendingVideo2Id) {
                $foundVideo2 = true;
            }
        }
        $this->assertTrue($foundVideo1);
        $this->assertTrue($foundVideo2);

        // Test 6: Multiple filters combined
        $result6 = $this->contentService->getTrendingVideos(20, ['channel-1'], 0, 500);
        $this->assertCount(1, $result6);
        $this->assertEquals($trendingVideo1Id, $result6[0]['id']);
    }

    /**
     * Test getting videos from a specific subreddit.
     */
    public function test_get_videos_from_subreddit(): void
    {
        // Create Reddit posts from different subreddits
        $redditPost1Id = (string) Str::uuid();
        $redditPost1 = new RedditPost;
        $redditPost1->id = $redditPost1Id;
        $redditPost1->reddit_id = 'reddit-post-1';
        $redditPost1->subreddit = 'videos';
        $redditPost1->title = 'Video Post 1';
        $redditPost1->permalink = 'https://reddit.com/r/videos/post1';
        $redditPost1->url = 'https://youtube.com/watch?v=video1';
        $redditPost1->author = 'user1';
        $redditPost1->posted_at = now()->subDays(1);
        $redditPost1->created_at = now()->subDays(1);
        $redditPost1->save();

        $redditPost2Id = (string) Str::uuid();
        $redditPost2 = new RedditPost;
        $redditPost2->id = $redditPost2Id;
        $redditPost2->reddit_id = 'reddit-post-2';
        $redditPost2->subreddit = 'videos';
        $redditPost2->title = 'Video Post 2';
        $redditPost2->permalink = 'https://reddit.com/r/videos/post2';
        $redditPost2->url = 'https://youtube.com/watch?v=video2';
        $redditPost2->author = 'user2';
        $redditPost2->posted_at = now()->subDays(2);
        $redditPost2->created_at = now()->subDays(2);
        $redditPost2->save();

        $redditPost3Id = (string) Str::uuid();
        $redditPost3 = new RedditPost;
        $redditPost3->id = $redditPost3Id;
        $redditPost3->reddit_id = 'reddit-post-3';
        $redditPost3->subreddit = 'programming';
        $redditPost3->title = 'Programming Video';
        $redditPost3->permalink = 'https://reddit.com/r/programming/post1';
        $redditPost3->url = 'https://youtube.com/watch?v=programming1';
        $redditPost3->author = 'user3';
        $redditPost3->posted_at = now()->subDays(1);
        $redditPost3->created_at = now()->subDays(1);
        $redditPost3->save();

        // Create YouTube videos linked to the Reddit posts
        $videoFromVideos1Id = (string) Str::uuid();
        $videoFromVideos1 = new YoutubeVideo;
        $videoFromVideos1->id = $videoFromVideos1Id;
        $videoFromVideos1->youtube_id = 'video1';
        $videoFromVideos1->reddit_post_id = $redditPost1Id;
        $videoFromVideos1->title = 'YouTube Video 1 from r/videos';
        $videoFromVideos1->description = 'Description for video 1';
        $videoFromVideos1->channel_id = 'channel-1';
        $videoFromVideos1->channel_title = 'Channel 1';
        $videoFromVideos1->thumbnail_url = 'https://example.com/thumb1.jpg';
        $videoFromVideos1->duration_seconds = 300;
        $videoFromVideos1->view_count = 150000;
        $videoFromVideos1->like_count = 15000;
        $videoFromVideos1->published_at = now()->subDays(1);
        $videoFromVideos1->is_trending = true;
        $videoFromVideos1->save();

        $videoFromVideos2Id = (string) Str::uuid();
        $videoFromVideos2 = new YoutubeVideo;
        $videoFromVideos2->id = $videoFromVideos2Id;
        $videoFromVideos2->youtube_id = 'video2';
        $videoFromVideos2->reddit_post_id = $redditPost2Id;
        $videoFromVideos2->title = 'YouTube Video 2 from r/videos';
        $videoFromVideos2->description = 'Description for video 2';
        $videoFromVideos2->channel_id = 'channel-2';
        $videoFromVideos2->channel_title = 'Channel 2';
        $videoFromVideos2->thumbnail_url = 'https://example.com/thumb2.jpg';
        $videoFromVideos2->duration_seconds = 600;
        $videoFromVideos2->view_count = 300000; // More views than video 1
        $videoFromVideos2->like_count = 30000;
        $videoFromVideos2->published_at = now()->subDays(2);
        $videoFromVideos2->is_trending = true;
        $videoFromVideos2->save();

        $videoFromProgrammingId = (string) Str::uuid();
        $videoFromProgramming = new YoutubeVideo;
        $videoFromProgramming->id = $videoFromProgrammingId;
        $videoFromProgramming->youtube_id = 'programming1';
        $videoFromProgramming->reddit_post_id = $redditPost3Id;
        $videoFromProgramming->title = 'Programming Tutorial';
        $videoFromProgramming->description = 'Description for programming video';
        $videoFromProgramming->channel_id = 'channel-3';
        $videoFromProgramming->channel_title = 'Programming Channel';
        $videoFromProgramming->thumbnail_url = 'https://example.com/thumb3.jpg';
        $videoFromProgramming->duration_seconds = 1200;
        $videoFromProgramming->view_count = 100000;
        $videoFromProgramming->like_count = 10000;
        $videoFromProgramming->published_at = now()->subDays(1);
        $videoFromProgramming->is_trending = true;
        $videoFromProgramming->save();

        // Test 1: Get videos from r/videos subreddit with recent sorting (default)
        $result1 = $this->contentService->getVideosFromSubreddit('videos');
        $this->assertCount(2, $result1);
        // Should be sorted by published_at desc (most recent first)
        $this->assertEquals($videoFromVideos1Id, $result1[0]['id']);
        $this->assertEquals($videoFromVideos2Id, $result1[1]['id']);

        // Test 2: Get videos from r/videos with popular sorting
        $result2 = $this->contentService->getVideosFromSubreddit('videos', 20, 'popular');
        $this->assertCount(2, $result2);
        // Should be sorted by view_count desc (most popular first)
        $this->assertEquals($videoFromVideos2Id, $result2[0]['id']);
        $this->assertEquals($videoFromVideos1Id, $result2[1]['id']);

        // Test 3: Get videos from r/programming
        $result3 = $this->contentService->getVideosFromSubreddit('programming');
        $this->assertCount(1, $result3);
        $this->assertEquals($videoFromProgrammingId, $result3[0]['id']);

        // Test 4: Get videos from a subreddit that doesn't exist
        $result4 = $this->contentService->getVideosFromSubreddit('nonexistent');
        $this->assertCount(0, $result4);
        $this->assertEmpty($result4);

        // Test 5: Get videos from r/videos with a limit
        $result5 = $this->contentService->getVideosFromSubreddit('videos', 1);
        $this->assertCount(1, $result5);
        $this->assertEquals($videoFromVideos1Id, $result5[0]['id']);
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
