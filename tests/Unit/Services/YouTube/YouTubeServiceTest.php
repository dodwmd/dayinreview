<?php

namespace Tests\Unit\Services\YouTube;

use App\Services\YouTube\YouTubeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeServiceTest extends TestCase
{
    protected YouTubeService $youtubeService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a dummy API key for testing
        Config::set('services.youtube.api_key', 'test-api-key');

        // Create a new instance of the YouTubeService
        $this->youtubeService = new YouTubeService;

        // Clear cache for tests
        Cache::flush();
    }

    /**
     * Test getting video details.
     */
    public function test_get_video_details(): void
    {
        // Mock the HTTP response
        Http::fake([
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'dQw4w9WgXcQ',
                        'snippet' => [
                            'title' => 'Rick Astley - Never Gonna Give You Up (Official Music Video)',
                            'description' => 'The official music video for "Never Gonna Give You Up"',
                            'publishedAt' => '2009-10-25T06:57:33Z',
                            'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                            'channelTitle' => 'Rick Astley',
                            'tags' => ['Rick Astley', 'Never Gonna Give You Up'],
                            'categoryId' => '10', // Music
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
                                ],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT3M33S', // 3 minutes and 33 seconds
                            'dimension' => '2d',
                            'definition' => 'hd',
                            'caption' => 'false',
                            'licensedContent' => true,
                        ],
                        'statistics' => [
                            'viewCount' => '1292000123',
                            'likeCount' => '15200321',
                            'commentCount' => '899321',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call the service method
        $result = $this->youtubeService->getVideoDetails('dQw4w9WgXcQ');

        // Assert response structure and data
        $this->assertIsArray($result);
        $this->assertEquals('dQw4w9WgXcQ', $result['id']);
        $this->assertEquals('Rick Astley - Never Gonna Give You Up (Official Music Video)', $result['title']);
        $this->assertEquals('Rick Astley', $result['channel_title']);
        $this->assertEquals('PT3M33S', $result['duration']);
        $this->assertEquals(213, $result['duration_seconds']); // 3*60 + 33 = 213 seconds
        $this->assertEquals(1292000123, $result['view_count']);
        $this->assertEquals(15200321, $result['like_count']);
    }

    /**
     * Test getting multiple videos by ID.
     */
    public function test_get_videos_by_id(): void
    {
        // Mock the HTTP response
        Http::fake([
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'video1',
                        'snippet' => [
                            'title' => 'Test Video 1',
                            'description' => 'This is test video 1',
                            'publishedAt' => '2023-01-01T00:00:00Z',
                            'channelId' => 'channel1',
                            'channelTitle' => 'Test Channel 1',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/thumb1.jpg',
                                ],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT2M30S',
                            'dimension' => '2d',
                            'definition' => 'hd',
                            'caption' => 'false',
                            'licensedContent' => true,
                        ],
                        'statistics' => [
                            'viewCount' => '1000',
                            'likeCount' => '100',
                            'commentCount' => '50',
                        ],
                    ],
                    [
                        'id' => 'video2',
                        'snippet' => [
                            'title' => 'Test Video 2',
                            'description' => 'This is test video 2',
                            'publishedAt' => '2023-01-02T00:00:00Z',
                            'channelId' => 'channel2',
                            'channelTitle' => 'Test Channel 2',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/thumb2.jpg',
                                ],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT5M',
                            'dimension' => '2d',
                            'definition' => 'hd',
                            'caption' => 'true',
                            'licensedContent' => true,
                        ],
                        'statistics' => [
                            'viewCount' => '2000',
                            'likeCount' => '200',
                            'commentCount' => '100',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call the service method
        $result = $this->youtubeService->getVideosById(['video1', 'video2']);

        // Assert response
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('video1', $result[0]['id']);
        $this->assertEquals('Test Video 1', $result[0]['title']);
        $this->assertEquals('video2', $result[1]['id']);
        $this->assertEquals('Test Video 2', $result[1]['title']);
    }

    /**
     * Test searching for videos.
     */
    public function test_search_videos(): void
    {
        // Mock the HTTP responses for both search and videos endpoints
        Http::fake([
            'googleapis.com/youtube/v3/search*' => Http::response([
                'items' => [
                    [
                        'id' => [
                            'kind' => 'youtube#video',
                            'videoId' => 'search1',
                        ],
                        'snippet' => [
                            'title' => 'Search Result 1',
                            'description' => 'This is search result 1',
                            'publishedAt' => '2023-01-03T00:00:00Z',
                            'channelId' => 'channel3',
                            'channelTitle' => 'Test Channel 3',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/search1.jpg',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'search1',
                        'snippet' => [
                            'title' => 'Search Result 1',
                            'description' => 'This is search result 1',
                            'publishedAt' => '2023-01-03T00:00:00Z',
                            'channelId' => 'channel3',
                            'channelTitle' => 'Test Channel 3',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/search1.jpg',
                                ],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT3M',
                            'dimension' => '2d',
                            'definition' => 'hd',
                            'caption' => 'false',
                            'licensedContent' => true,
                        ],
                        'statistics' => [
                            'viewCount' => '3000',
                            'likeCount' => '300',
                            'commentCount' => '150',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call the service method
        $result = $this->youtubeService->searchVideos('test search');

        // Assert response
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('search1', $result[0]['id']);
        $this->assertEquals('Search Result 1', $result[0]['title']);
        $this->assertEquals(3000, $result[0]['view_count']);
    }

    /**
     * Test getting channel info.
     */
    public function test_get_channel_info(): void
    {
        // Mock the HTTP response
        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                        'snippet' => [
                            'title' => 'Rick Astley',
                            'description' => 'The official Rick Astley YouTube channel',
                            'customUrl' => '@RickAstleyOfficial',
                            'publishedAt' => '2009-03-07T00:00:00Z',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/channel.jpg',
                                ],
                            ],
                            'country' => 'GB',
                        ],
                        'contentDetails' => [
                            'relatedPlaylists' => [
                                'uploads' => 'UUuAXFkgsw1L7xaCfnd5JJOw',
                            ],
                        ],
                        'statistics' => [
                            'subscriberCount' => '4000000',
                            'videoCount' => '100',
                            'viewCount' => '2500000000',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call the service method
        $result = $this->youtubeService->getChannelInfo('UCuAXFkgsw1L7xaCfnd5JJOw');

        // Assert response
        $this->assertIsArray($result);
        $this->assertEquals('UCuAXFkgsw1L7xaCfnd5JJOw', $result['id']);
        $this->assertEquals('Rick Astley', $result['title']);
        $this->assertEquals('@RickAstleyOfficial', $result['custom_url']);
        $this->assertEquals('UUuAXFkgsw1L7xaCfnd5JJOw', $result['uploads_playlist_id']);
        $this->assertEquals(4000000, $result['subscriber_count']);
        $this->assertEquals(100, $result['video_count']);
    }

    /**
     * Test getting channel videos.
     */
    public function test_get_channel_videos(): void
    {
        // Mock the HTTP responses for multiple endpoints
        Http::fake([
            // First request to get channel info
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'channel1',
                        'contentDetails' => [
                            'relatedPlaylists' => [
                                'uploads' => 'uploads_playlist_1',
                            ],
                        ],
                        'snippet' => [
                            'title' => 'Test Channel',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/channel.jpg',
                                ],
                            ],
                        ],
                        'statistics' => [
                            'subscriberCount' => '1000000',
                            'videoCount' => '200',
                            'viewCount' => '5000000',
                        ],
                    ],
                ],
            ], 200),

            // Second request to get playlist items
            'googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => [
                                'videoId' => 'channel_video_1',
                            ],
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => [
                                'videoId' => 'channel_video_2',
                            ],
                        ],
                    ],
                ],
                'nextPageToken' => 'next_page_token_123',
            ], 200),

            // Third request to get video details
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'channel_video_1',
                        'snippet' => [
                            'title' => 'Channel Video 1',
                            'description' => 'This is a channel video',
                            'publishedAt' => '2023-02-01T00:00:00Z',
                            'channelId' => 'channel1',
                            'channelTitle' => 'Test Channel',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/video1.jpg',
                                ],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT10M',
                            'dimension' => '2d',
                            'definition' => 'hd',
                            'caption' => 'false',
                            'licensedContent' => true,
                        ],
                        'statistics' => [
                            'viewCount' => '50000',
                            'likeCount' => '5000',
                            'commentCount' => '1000',
                        ],
                    ],
                    [
                        'id' => 'channel_video_2',
                        'snippet' => [
                            'title' => 'Channel Video 2',
                            'description' => 'This is another channel video',
                            'publishedAt' => '2023-02-15T00:00:00Z',
                            'channelId' => 'channel1',
                            'channelTitle' => 'Test Channel',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/video2.jpg',
                                ],
                            ],
                        ],
                        'contentDetails' => [
                            'duration' => 'PT8M30S',
                            'dimension' => '2d',
                            'definition' => 'hd',
                            'caption' => 'true',
                            'licensedContent' => true,
                        ],
                        'statistics' => [
                            'viewCount' => '40000',
                            'likeCount' => '4000',
                            'commentCount' => '800',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call the service method
        $result = $this->youtubeService->getChannelVideos('channel1');

        // Assert response
        $this->assertIsArray($result);
        $this->assertArrayHasKey('videos', $result);
        $this->assertArrayHasKey('next_page_token', $result);
        $this->assertCount(2, $result['videos']);
        $this->assertEquals('channel_video_1', $result['videos'][0]['id']);
        $this->assertEquals('Channel Video 1', $result['videos'][0]['title']);
        $this->assertEquals('channel_video_2', $result['videos'][1]['id']);
        $this->assertEquals('Channel Video 2', $result['videos'][1]['title']);
        $this->assertEquals('next_page_token_123', $result['next_page_token']);
    }

    /**
     * Test error handling when the API key is missing.
     */
    public function test_api_key_missing(): void
    {
        // Remove API key
        Config::set('services.youtube.api_key', null);

        // Create service with HTTP fake to prevent real API calls
        Http::fake();
        $service = new YouTubeService;

        // The service should return an error array instead of throwing an exception
        $result = $service->getVideoDetails('some-video-id');

        // Assert that we got an error message about the API key
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('YouTube API key is not configured', $result['error']);
    }

    /**
     * Test error handling when the YouTube API fails.
     */
    public function test_api_error_handling(): void
    {
        // Mock a failed API response
        Http::fake([
            'googleapis.com/youtube/v3/videos*' => Http::response('', 500),
        ]);

        // Call the service method
        $result = $this->youtubeService->getVideoDetails('some-video-id');

        // Assert error state
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('API request failed', $result['error']);
    }
}
