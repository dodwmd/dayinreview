<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Services\YouTube\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake HTTP responses for YouTube API calls
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
                            'tags' => ['test', 'video'],
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
            'www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
                        'snippet' => [
                            'title' => 'Test Channel',
                            'description' => 'Test channel description',
                        ],
                        'statistics' => [
                            'subscriberCount' => '1000000',
                            'videoCount' => '500',
                        ],
                    ],
                ],
            ], 200),
            'www.googleapis.com/youtube/v3/subscriptions*' => Http::response([
                'items' => [
                    [
                        'id' => 'subscription123',
                        'snippet' => [
                            'resourceId' => [
                                'channelId' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
                            ],
                            'title' => 'Test Channel',
                            'description' => 'Test channel description',
                            'thumbnails' => [
                                'default' => ['url' => 'https://example.com/channel-thumb.jpg'],
                            ],
                        ],
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-youtube-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'fake-refresh-token',
            ], 200),
        ]);
    }

    /**
     * Test fetching video details from YouTube.
     */
    public function test_fetch_video_details(): void
    {
        $youtubeService = app(YouTubeService::class);
        $videoDetails = $youtubeService->getVideoDetails('dQw4w9WgXcQ');

        $this->assertNotNull($videoDetails);
        $this->assertEquals('dQw4w9WgXcQ', $videoDetails['id']);
        $this->assertEquals('Test Video', $videoDetails['snippet']['title']);
        $this->assertEquals('Test Channel', $videoDetails['snippet']['channelTitle']);
    }

    /**
     * Test fetching channel details from YouTube.
     */
    public function test_fetch_channel_details(): void
    {
        $youtubeService = app(YouTubeService::class);
        $channelDetails = $youtubeService->getChannelDetails('UC_x5XG1OV2P6uZZ5FSM9Ttw');

        $this->assertNotNull($channelDetails);
        $this->assertEquals('UC_x5XG1OV2P6uZZ5FSM9Ttw', $channelDetails['id']);
        $this->assertEquals('Test Channel', $channelDetails['snippet']['title']);
    }

    /**
     * Test fetching user's YouTube subscriptions.
     */
    public function test_fetch_user_subscriptions(): void
    {
        // Create a user with YouTube tokens
        $user = User::factory()->create([
            'youtube_access_token' => 'fake-access-token',
            'youtube_refresh_token' => 'fake-refresh-token',
            'youtube_token_expires_at' => now()->addHour(),
        ]);

        $youtubeService = app(YouTubeService::class);
        $subscriptions = $youtubeService->getUserSubscriptions($user);

        $this->assertNotEmpty($subscriptions);
        $this->assertCount(1, $subscriptions);
        $this->assertEquals('UC_x5XG1OV2P6uZZ5FSM9Ttw', $subscriptions[0]['snippet']['resourceId']['channelId']);
    }

    /**
     * Test token refresh mechanism when token is expired.
     */
    public function test_token_refresh_when_expired(): void
    {
        // Create a user with expired YouTube tokens
        $user = User::factory()->create([
            'youtube_access_token' => 'expired-token',
            'youtube_refresh_token' => 'fake-refresh-token',
            'youtube_token_expires_at' => now()->subHour(),
        ]);

        $youtubeService = app(YouTubeService::class);
        
        // This should trigger a token refresh
        $youtubeService->getUserSubscriptions($user);
        
        // Verify that the token refresh request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://oauth2.googleapis.com/token' &&
                   $request->data()['refresh_token'] == 'fake-refresh-token';
        });
    }

    /**
     * Test YouTube API caching behavior.
     */
    public function test_youtube_api_caching(): void
    {
        $youtubeService = app(YouTubeService::class);
        
        // First call should make an HTTP request
        $firstCallDetails = $youtubeService->getVideoDetails('dQw4w9WgXcQ');
        
        // Second call should be cached
        $secondCallDetails = $youtubeService->getVideoDetails('dQw4w9WgXcQ');
        
        // Assert that both calls returned the same data
        $this->assertEquals($firstCallDetails, $secondCallDetails);
        
        // Assert that only one HTTP request was made
        Http::assertSentCount(1);
    }

    /**
     * Test extracting video ID from YouTube URLs.
     */
    public function test_extract_video_id_from_url(): void
    {
        $youtubeService = app(YouTubeService::class);
        
        // Standard YouTube URL
        $url1 = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url1));
        
        // Short YouTube URL
        $url2 = 'https://youtu.be/dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url2));
        
        // YouTube URL with additional parameters
        $url3 = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url3));
        
        // Invalid URL
        $url4 = 'https://www.example.com';
        $this->assertNull($youtubeService->extractVideoId($url4));
    }
}
