<?php

namespace Tests\Feature\API;

use App\Services\Reddit\RedditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RedditApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake HTTP responses
        Http::fake([
            'oauth.reddit.com/*' => Http::response([
                'data' => [
                    'children' => [
                        [
                            'data' => [
                                'id' => 'abc123',
                                'subreddit' => 'programming',
                                'title' => 'Test Post',
                                'selftext' => 'Test content',
                                'author' => 'test_user',
                                'permalink' => '/r/programming/comments/abc123/test_post/',
                                'url' => 'https://www.example.com',
                                'score' => 100,
                                'created_utc' => time(),
                                'num_comments' => 50,
                            ],
                        ],
                    ],
                ],
            ], 200),
            'www.reddit.com/api/v1/access_token' => Http::response([
                'access_token' => 'fake-token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    /**
     * Test fetching popular posts from Reddit.
     */
    public function test_fetch_popular_posts(): void
    {
        $redditService = app(RedditService::class);
        $posts = $redditService->getPopularPosts();

        $this->assertNotEmpty($posts);
        $this->assertIsArray($posts);
        
        if (!empty($posts)) {
            $firstPost = $posts[0];
            $this->assertArrayHasKey('subreddit', $firstPost);
            $this->assertArrayHasKey('title', $firstPost);
            $this->assertArrayHasKey('url', $firstPost);
        }
    }

    /**
     * Test fetching posts from a specific subreddit.
     */
    public function test_fetch_subreddit_posts(): void
    {
        $redditService = app(RedditService::class);
        $posts = $redditService->getSubredditPosts('programming');

        $this->assertNotEmpty($posts);
        $this->assertIsArray($posts);
        
        if (!empty($posts)) {
            $firstPost = $posts[0];
            $this->assertEquals('programming', $firstPost['subreddit']);
        }
    }

    /**
     * Test caching behavior of Reddit API calls.
     */
    public function test_reddit_api_caching(): void
    {
        $redditService = app(RedditService::class);
        
        // First call should make an HTTP request
        $firstCallPosts = $redditService->getPopularPosts();
        
        // Second call should be cached
        $secondCallPosts = $redditService->getPopularPosts();
        
        // Assert that both calls returned the same data
        $this->assertEquals($firstCallPosts, $secondCallPosts);
        
        // Assert that only one HTTP request was made
        Http::assertSentCount(1);
    }

    /**
     * Test Reddit API authentication.
     */
    public function test_reddit_api_authentication(): void
    {
        $redditService = app(RedditService::class);
        
        // This should trigger the authentication flow
        $redditService->getPopularPosts();
        
        // Verify that the authentication request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://www.reddit.com/api/v1/access_token';
        });
    }

    /**
     * Test detecting YouTube videos in Reddit posts.
     */
    public function test_detect_youtube_videos(): void
    {
        // Mock a Reddit post with a YouTube URL
        $post = [
            'id' => 'abc123',
            'subreddit' => 'videos',
            'title' => 'Amazing YouTube Video',
            'selftext' => 'Check out this video',
            'author' => 'test_user',
            'permalink' => '/r/videos/comments/abc123/amazing_youtube_video/',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'score' => 500,
            'created_utc' => time(),
            'num_comments' => 100,
        ];
        
        $redditService = app(RedditService::class);
        $isYouTubeVideo = $redditService->isYouTubeVideo($post);
        
        $this->assertTrue($isYouTubeVideo);
        
        // Test with a non-YouTube URL
        $nonYouTubePost = array_merge($post, ['url' => 'https://www.example.com']);
        $isYouTubeVideo = $redditService->isYouTubeVideo($nonYouTubePost);
        
        $this->assertFalse($isYouTubeVideo);
    }
}
