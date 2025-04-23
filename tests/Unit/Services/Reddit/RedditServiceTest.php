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
        $this->redditService = new RedditService();
        
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
                            ]
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
                                        'html' => '<iframe width="600" height="340" src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ], 200)
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
        
        // Assert second post with YouTube video
        $this->assertEquals('post2', $result['data'][1]['id']);
        $this->assertTrue($result['data'][1]['has_youtube_video']);
        $this->assertEquals('dQw4w9WgXcQ', $result['data'][1]['youtube_id']);
    }

    /**
     * Test fetching posts from a specific subreddit.
     */
    public function test_get_subreddit_posts(): void
    {
        // Mock the HTTP response
        Http::fake([
            'www.reddit.com/r/programming/hot.json*' => Http::response([
                'data' => [
                    'after' => 't3_xyz789',
                    'children' => [
                        [
                            'data' => [
                                'id' => 'pgm1',
                                'name' => 't3_pgm1',
                                'subreddit' => 'programming',
                                'title' => 'Programming Post',
                                'author' => 'coder123',
                                'permalink' => '/r/programming/comments/pgm1/programming_post/',
                                'url' => 'https://github.com/example/repo',
                                'score' => 300,
                                'ups' => 350,
                                'downs' => 50,
                                'num_comments' => 75,
                                'created_utc' => time() - 7200,
                                'thumbnail' => 'https://example.com/code_thumb.jpg',
                                'is_video' => false,
                                'selftext' => 'Check out this cool repo!',
                                'media' => null,
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Call the service method
        $result = $this->redditService->getSubredditPosts('programming');

        // Assert the response
        $this->assertIsArray($result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('pgm1', $result['data'][0]['id']);
        $this->assertEquals('programming', $result['data'][0]['subreddit']);
        $this->assertEquals('t3_xyz789', $result['after']);
    }

    /**
     * Test the YouTube video detection functionality.
     */
    public function test_youtube_video_detection(): void
    {
        // Test direct YouTube URL
        $postWithDirectLink = [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];
        
        $this->assertTrue($this->redditService->hasYouTubeVideo($postWithDirectLink));
        $this->assertEquals('dQw4w9WgXcQ', $this->redditService->extractYouTubeVideoId($postWithDirectLink));

        // Test YouTube short URL
        $postWithShortLink = [
            'url' => 'https://youtu.be/dQw4w9WgXcQ',
        ];
        
        $this->assertTrue($this->redditService->hasYouTubeVideo($postWithShortLink));
        $this->assertEquals('dQw4w9WgXcQ', $this->redditService->extractYouTubeVideoId($postWithShortLink));

        // Test embedded YouTube video
        $postWithEmbed = [
            'url' => 'https://www.reddit.com/r/videos/comments/abc123/test_title/',
            'media' => [
                'type' => 'youtube.com',
                'oembed' => [
                    'html' => '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>'
                ]
            ]
        ];
        
        $this->assertTrue($this->redditService->hasYouTubeVideo($postWithEmbed));
        $this->assertEquals('dQw4w9WgXcQ', $this->redditService->extractYouTubeVideoId($postWithEmbed));

        // Test post without YouTube video
        $postWithoutVideo = [
            'url' => 'https://example.com/some-article',
            'media' => null
        ];
        
        $this->assertFalse($this->redditService->hasYouTubeVideo($postWithoutVideo));
        $this->assertNull($this->redditService->extractYouTubeVideoId($postWithoutVideo));
    }

    /**
     * Test error handling when the Reddit API fails.
     */
    public function test_api_error_handling(): void
    {
        // Mock a failed API response
        Http::fake([
            'www.reddit.com/r/popular.json*' => Http::response('', 500)
        ]);

        // Call the service method
        $result = $this->redditService->getPopularPosts();

        // Assert error state
        $this->assertIsArray($result);
        $this->assertEmpty($result['data']);
        $this->assertNull($result['after']);
        $this->assertArrayHasKey('error', $result);
    }
}
