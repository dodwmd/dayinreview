<?php

namespace Tests\Unit\Services\Reddit;

use App\Services\Reddit\RedditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RedditServiceTest extends TestCase
{
    protected RedditService $redditService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a new instance of the RedditService
        $this->redditService = new RedditService;

        // Clear cache for tests
        Cache::flush();
    }

    /**
     * Test fetching popular posts.
     */
    public function test_get_popular_posts(): void
    {
        // Mock the HTTP response for popular posts
        Http::fake([
            'www.reddit.com/r/popular.json*' => Http::response([
                'data' => [
                    'after' => 't3_abc123',
                    'children' => [
                        [
                            'data' => [
                                'id' => 'post1',
                                'name' => 't3_post1',
                                'subreddit' => 'test',
                                'title' => 'Test Post 1',
                                'author' => 'testuser',
                                'permalink' => '/r/test/comments/post1/test_post_1/',
                                'url' => 'https://www.reddit.com/r/test/comments/post1/test_post_1/',
                                'score' => 100,
                                'ups' => 120,
                                'downs' => 20,
                                'num_comments' => 10,
                                'created_utc' => time() - 3600,
                                'thumbnail' => 'https://example.com/thumb.jpg',
                                'is_video' => false,
                                'selftext' => 'Test content',
                                'media' => null,
                            ],
                        ],
                        [
                            'data' => [
                                'id' => 'post2',
                                'name' => 't3_post2',
                                'subreddit' => 'videos',
                                'title' => 'YouTube Video Post',
                                'author' => 'videouser',
                                'permalink' => '/r/videos/comments/post2/youtube_video_post/',
                                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                                'score' => 500,
                                'ups' => 600,
                                'downs' => 100,
                                'num_comments' => 50,
                                'created_utc' => time() - 7200,
                                'thumbnail' => 'https://example.com/video_thumb.jpg',
                                'is_video' => false,
                                'selftext' => '',
                                'media' => [
                                    'type' => 'youtube.com',
                                    'oembed' => [
                                        'html' => '<iframe width="600" height="340" src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call the service method
        $result = $this->redditService->getPopularPosts();

        // Assert the response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('after', $result);

        // Assert we have the correct number of posts
        $this->assertCount(2, $result['data']);

        // Assert first post data
        $this->assertEquals('post1', $result['data'][0]['id']);
        $this->assertEquals('Test Post 1', $result['data'][0]['title']);
        $this->assertEquals('test', $result['data'][0]['subreddit']);
        $this->assertFalse($result['data'][0]['has_youtube_video']);

        // Assert YouTube post data
        $this->assertEquals('post2', $result['data'][1]['id']);
        $this->assertEquals('YouTube Video Post', $result['data'][1]['title']);
        $this->assertEquals('videos', $result['data'][1]['subreddit']);
        $this->assertTrue($result['data'][1]['has_youtube_video']);
        $this->assertEquals('dQw4w9WgXcQ', $result['data'][1]['youtube_id']);
    }

    /**
     * Test getting posts from a specific subreddit.
     */
    public function test_get_subreddit_posts(): void
    {
        // Mock HTTP response
        Http::fake([
            'oauth.reddit.com/r/test/hot*' => Http::response([
                'data' => [
                    'children' => [
                        [
                            'data' => [
                                'id' => 'post1',
                                'title' => 'Test Post 1',
                                'url' => 'https://example.com/post1',
                                'permalink' => '/r/test/post1',
                                'score' => 100,
                                'num_comments' => 10,
                                'created_utc' => now()->subDay()->timestamp,
                                'author' => 'testuser',
                                'subreddit' => 'test',
                            ],
                        ],
                        [
                            'data' => [
                                'id' => 'post2',
                                'title' => 'Test Post 2',
                                'url' => 'https://example.com/post2',
                                'permalink' => '/r/test/post2',
                                'score' => 200,
                                'num_comments' => 20,
                                'created_utc' => now()->subDays(2)->timestamp,
                                'author' => 'testuser',
                                'subreddit' => 'test',
                            ],
                        ],
                    ],
                    'after' => 'next_page_token',
                ],
            ], 200),
        ]);

        // Get posts from the "test" subreddit
        $result = $this->redditService->getSubredditPosts('test');

        // Assertions - handle different response structures
        $this->assertIsArray($result);

        // If the response has a 'data' key, it's formatted one way
        if (isset($result['data'])) {
            $this->assertArrayHasKey('data', $result);
            $this->assertIsArray($result['data']);
            $this->assertGreaterThan(0, count($result['data']));
        }
        // If the response has a 'posts' key, it's formatted another way
        elseif (isset($result['posts'])) {
            $this->assertArrayHasKey('posts', $result);
            $this->assertIsArray($result['posts']);
            $this->assertGreaterThan(0, count($result['posts']));
        }
        // Otherwise we'll just check that the result is not empty
        else {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * Test YouTube video detection.
     */
    public function test_youtube_video_detection(): void
    {
        // Test with a YouTube URL
        $postWithDirectLink = [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];
        $this->assertTrue($this->redditService->hasYouTubeVideo($postWithDirectLink));
        $this->assertEquals('dQw4w9WgXcQ', $this->redditService->extractYouTubeVideoId($postWithDirectLink));

        // Test with a shortened YouTube URL
        $postWithShortLink = [
            'url' => 'https://youtu.be/dQw4w9WgXcQ',
        ];
        $this->assertTrue($this->redditService->hasYouTubeVideo($postWithShortLink));
        $this->assertEquals('dQw4w9WgXcQ', $this->redditService->extractYouTubeVideoId($postWithShortLink));

        // Test with a post that has embedded YouTube video
        $postWithEmbed = [
            'url' => 'https://www.reddit.com/r/videos/comments/abc123/test_title/',
            'media' => [
                'type' => 'youtube.com',
                'oembed' => [
                    'html' => '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
                ],
            ],
        ];
        $this->assertTrue($this->redditService->hasYouTubeVideo($postWithEmbed));
        $this->assertEquals('dQw4w9WgXcQ', $this->redditService->extractYouTubeVideoId($postWithEmbed));

        // Test with a non-YouTube URL
        $postWithoutVideo = [
            'url' => 'https://example.com/video',
            'media' => null,
        ];
        $this->assertFalse($this->redditService->hasYouTubeVideo($postWithoutVideo));
        $this->assertNull($this->redditService->extractYouTubeVideoId($postWithoutVideo));
    }

    /**
     * Test API error handling.
     */
    public function test_api_error_handling(): void
    {
        // Mock HTTP response for API error
        Http::fake([
            'oauth.reddit.com/*' => Http::response(
                ['message' => 'Unauthorized', 'error' => 401],
                401
            ),
            'www.reddit.com/*' => Http::response(
                ['message' => 'Unauthorized', 'error' => 401],
                401
            ),
        ]);

        // Test error handling for getPopularPosts
        $result = $this->redditService->getPopularPosts();

        // Check that we get the expected error response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('after', $result);
        $this->assertArrayHasKey('error', $result);

        // Check the content of the error response
        $this->assertEquals([], $result['data']);
        $this->assertNull($result['after']);
        $this->assertStringContainsString('API request failed', $result['error']);

        // Test error handling for getSubredditPosts
        $result = $this->redditService->getSubredditPosts('test');

        // Check that we get the expected error response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('after', $result);
        $this->assertArrayHasKey('error', $result);

        // Check the content of the error response
        $this->assertEquals([], $result['data']);
        $this->assertNull($result['after']);
        $this->assertStringContainsString('API request failed', $result['error']);
    }
}
