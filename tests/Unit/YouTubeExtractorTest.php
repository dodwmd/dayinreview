<?php

namespace Tests\Unit;

use App\Services\YouTube\YouTubeService;
use Tests\TestCase;

class YouTubeExtractorTest extends TestCase
{
    /**
     * Test YouTube URL video ID extraction.
     */
    public function test_extract_video_id_from_standard_url(): void
    {
        $youtubeService = app(YouTubeService::class);

        // Standard format
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));
    }

    /**
     * Test YouTube shortened URL extraction.
     */
    public function test_extract_video_id_from_shortened_url(): void
    {
        $youtubeService = app(YouTubeService::class);

        // Shortened youtu.be format
        $url = 'https://youtu.be/dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));
    }

    /**
     * Test YouTube URL with additional parameters.
     */
    public function test_extract_video_id_with_additional_params(): void
    {
        $youtubeService = app(YouTubeService::class);

        // URL with timestamp and other parameters
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s&feature=youtu.be';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));
    }

    /**
     * Test YouTube embedded URL format.
     */
    public function test_extract_video_id_from_embed_url(): void
    {
        $youtubeService = app(YouTubeService::class);

        // Embedded format
        $url = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));
    }

    /**
     * Test invalid YouTube URLs.
     */
    public function test_extract_video_id_from_invalid_url(): void
    {
        $youtubeService = app(YouTubeService::class);

        // Non-YouTube URL
        $url = 'https://www.example.com/video';
        $this->assertNull($youtubeService->extractVideoId($url));

        // YouTube URL without video ID
        $url = 'https://www.youtube.com/channel/UC_x5XG1OV2P6uZZ5FSM9Ttw';
        $this->assertNull($youtubeService->extractVideoId($url));
    }

    /**
     * Test YouTube URL with playlist and video ID.
     */
    public function test_extract_video_id_from_playlist_url(): void
    {
        $youtubeService = app(YouTubeService::class);

        // URL with playlist and video ID
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLdlFfrVsmlvA12SJj0itYY61RxZ-mXPei';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));
    }

    /**
     * Test YouTube URL with weird formatting.
     */
    public function test_extract_video_id_from_unusual_url(): void
    {
        $youtubeService = app(YouTubeService::class);

        // URL with capitals in domain and path
        $url = 'https://www.YouTube.com/WATCH?v=dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));

        // URL with extra spaces (which might have been encoded)
        $url = 'https://www.youtube.com/watch?v= dQw4w9WgXcQ ';
        $this->assertEquals('dQw4w9WgXcQ', $youtubeService->extractVideoId($url));
    }
}
