<?php

namespace App\Services\YouTube;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    /**
     * The base URL for YouTube Data API v3.
     */
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    /**
     * YouTube API key.
     */
    protected ?string $apiKey = null;

    /**
     * Cache TTL in seconds (default: 1 hour).
     */
    protected int $cacheTtl = 3600;

    /**
     * Rate limit settings.
     * YouTube has a default quota of 10,000 units per day.
     * Different operations cost different quota units.
     */
    protected int $maxRequestsPerDay = 10000;

    protected string $rateLimitKey = 'youtube_api_rate_limit';

    /**
     * Create a new YouTubeService instance.
     */
    public function __construct()
    {
        // Override settings from config if available
        $this->apiKey = config('services.youtube.api_key');

        if (config('services.youtube.cache_ttl')) {
            $this->cacheTtl = config('services.youtube.cache_ttl');
        }

        if (config('services.youtube.max_requests_per_day')) {
            $this->maxRequestsPerDay = config('services.youtube.max_requests_per_day');
        }
    }

    /**
     * Get video details by video ID.
     *
     * @param  string  $videoId  The YouTube video ID
     * @param  bool  $includeContentDetails  Whether to include content details (duration, etc.)
     */
    public function getVideoDetails(string $videoId, bool $includeContentDetails = true): array
    {
        $cacheKey = "youtube_video_{$videoId}".($includeContentDetails ? '_full' : '_basic');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($videoId, $includeContentDetails) {
            try {
                $this->checkRateLimit();

                $parts = ['snippet', 'statistics'];
                if ($includeContentDetails) {
                    $parts[] = 'contentDetails';
                }

                $response = $this->makeRequest()->get('/videos', [
                    'part' => implode(',', $parts),
                    'id' => $videoId,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['items'][0])) {
                        return $this->formatVideoData($data['items'][0]);
                    }

                    return ['error' => 'Video not found'];
                }

                Log::error('YouTube API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return ['error' => 'API request failed: '.$response->status()];
            } catch (\Exception $e) {
                Log::error('YouTube API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ['error' => 'Exception: '.$e->getMessage()];
            }
        });
    }

    /**
     * Get multiple videos by their IDs.
     *
     * @param  array  $videoIds  Array of YouTube video IDs
     */
    public function getVideosById(array $videoIds): array
    {
        // YouTube API has a limit of 50 video IDs per request
        $videoIdsChunks = array_chunk($videoIds, 50);
        $allVideos = [];

        foreach ($videoIdsChunks as $chunk) {
            $cacheKey = 'youtube_videos_'.md5(implode(',', $chunk));

            $videos = Cache::remember($cacheKey, $this->cacheTtl, function () use ($chunk) {
                try {
                    $this->checkRateLimit();

                    $response = $this->makeRequest()->get('/videos', [
                        'part' => 'snippet,contentDetails,statistics',
                        'id' => implode(',', $chunk),
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $videos = [];

                        foreach ($data['items'] ?? [] as $item) {
                            $videos[] = $this->formatVideoData($item);
                        }

                        return $videos;
                    }

                    Log::error('YouTube API error', [
                        'status' => $response->status(),
                        'message' => $response->body(),
                    ]);

                    return [];
                } catch (\Exception $e) {
                    Log::error('YouTube API exception', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    return [];
                }
            });

            $allVideos = array_merge($allVideos, $videos);
        }

        return $allVideos;
    }

    /**
     * Search for videos on YouTube.
     *
     * @param  string  $query  The search query
     * @param  int  $maxResults  Maximum number of results to return
     * @param  string  $order  The order of results (date, rating, relevance, title, viewCount)
     */
    public function searchVideos(string $query, int $maxResults = 10, string $order = 'relevance'): array
    {
        $cacheKey = 'youtube_search_'.md5($query.'_'.$maxResults.'_'.$order);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query, $maxResults, $order) {
            try {
                $this->checkRateLimit();

                $response = $this->makeRequest()->get('/search', [
                    'part' => 'snippet',
                    'type' => 'video',
                    'q' => $query,
                    'maxResults' => $maxResults,
                    'order' => $order,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $results = [];
                    $videoIds = [];

                    // Extract basic info and collect video IDs
                    foreach ($data['items'] ?? [] as $item) {
                        if (isset($item['id']['videoId'])) {
                            $videoIds[] = $item['id']['videoId'];

                            // Add basic info from search results
                            $results[$item['id']['videoId']] = [
                                'id' => $item['id']['videoId'],
                                'title' => $item['snippet']['title'] ?? '',
                                'description' => $item['snippet']['description'] ?? '',
                                'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                                'channel_id' => $item['snippet']['channelId'] ?? '',
                                'channel_title' => $item['snippet']['channelTitle'] ?? '',
                                'published_at' => $item['snippet']['publishedAt'] ?? null,
                            ];
                        }
                    }

                    // Get detailed info for all videos in a single request
                    if (! empty($videoIds)) {
                        $videoDetails = $this->getVideosById($videoIds);

                        // Merge detailed info back into results
                        foreach ($videoDetails as $video) {
                            if (isset($results[$video['id']])) {
                                $results[$video['id']] = array_merge($results[$video['id']], $video);
                            }
                        }
                    }

                    return array_values($results);
                }

                Log::error('YouTube API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('YouTube API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get channel information by channel ID.
     *
     * @param  string  $channelId  The YouTube channel ID
     */
    public function getChannelInfo(string $channelId): array
    {
        $cacheKey = "youtube_channel_{$channelId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($channelId) {
            try {
                $this->checkRateLimit();

                $response = $this->makeRequest()->get('/channels', [
                    'part' => 'snippet,statistics,contentDetails',
                    'id' => $channelId,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['items'][0])) {
                        return $this->formatChannelData($data['items'][0]);
                    }

                    return ['error' => 'Channel not found'];
                }

                Log::error('YouTube API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return ['error' => 'API request failed: '.$response->status()];
            } catch (\Exception $e) {
                Log::error('YouTube API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ['error' => 'Exception: '.$e->getMessage()];
            }
        });
    }

    /**
     * Get videos from a channel.
     *
     * @param  string  $channelId  The YouTube channel ID
     * @param  int  $maxResults  Maximum number of results to return
     * @param  string|null  $pageToken  Page token for pagination
     */
    public function getChannelVideos(string $channelId, int $maxResults = 25, ?string $pageToken = null): array
    {
        $cacheKey = "youtube_channel_videos_{$channelId}_{$maxResults}".($pageToken ? "_{$pageToken}" : '');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($channelId, $maxResults, $pageToken) {
            try {
                $this->checkRateLimit();

                // First, get the playlist ID for the channel's uploads
                $channelInfo = $this->getChannelInfo($channelId);

                if (isset($channelInfo['error'])) {
                    return ['videos' => [], 'next_page_token' => null, 'error' => $channelInfo['error']];
                }

                $uploadsPlaylistId = $channelInfo['uploads_playlist_id'] ?? null;

                if (! $uploadsPlaylistId) {
                    return ['videos' => [], 'next_page_token' => null, 'error' => 'Uploads playlist not found'];
                }

                // Then get the videos from the uploads playlist
                $params = [
                    'part' => 'snippet',
                    'playlistId' => $uploadsPlaylistId,
                    'maxResults' => $maxResults,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $response = $this->makeRequest()->get('/playlistItems', $params);

                if ($response->successful()) {
                    $data = $response->json();
                    $videoIds = [];

                    foreach ($data['items'] ?? [] as $item) {
                        if (isset($item['snippet']['resourceId']['videoId'])) {
                            $videoIds[] = $item['snippet']['resourceId']['videoId'];
                        }
                    }

                    $videos = [];

                    if (! empty($videoIds)) {
                        $videos = $this->getVideosById($videoIds);
                    }

                    return [
                        'videos' => $videos,
                        'next_page_token' => $data['nextPageToken'] ?? null,
                    ];
                }

                Log::error('YouTube API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return ['videos' => [], 'next_page_token' => null, 'error' => 'API request failed: '.$response->status()];
            } catch (\Exception $e) {
                Log::error('YouTube API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ['videos' => [], 'next_page_token' => null, 'error' => 'Exception: '.$e->getMessage()];
            }
        });
    }

    /**
     * Create a playlist on YouTube.
     *
     * @param  string  $title  The playlist title
     * @param  string  $description  The playlist description
     * @param  string  $privacy  The privacy status (public, unlisted, private)
     */
    public function createPlaylist(string $title, string $description = '', string $privacy = 'private'): array
    {
        // This requires OAuth authentication and is not implemented in this basic version
        throw new \Exception('User authentication required for this operation');
    }

    /**
     * Add a video to a playlist.
     *
     * @param  string  $playlistId  The playlist ID
     * @param  string  $videoId  The video ID
     */
    public function addVideoToPlaylist(string $playlistId, string $videoId): array
    {
        // This requires OAuth authentication and is not implemented in this basic version
        throw new \Exception('User authentication required for this operation');
    }

    /**
     * Format video data into a consistent structure.
     */
    protected function formatVideoData(array $item): array
    {
        // Extract important data from the video
        $videoId = $item['id'];
        $snippet = $item['snippet'] ?? [];
        $statistics = $item['statistics'] ?? [];
        $contentDetails = $item['contentDetails'] ?? [];

        // Parse duration from ISO 8601 format
        $durationSeconds = 0;
        if (isset($contentDetails['duration'])) {
            $duration = new \DateInterval($contentDetails['duration']);
            $durationSeconds = ($duration->h * 3600) + ($duration->i * 60) + $duration->s;
        }

        return [
            'id' => $videoId,
            'title' => $snippet['title'] ?? '',
            'description' => $snippet['description'] ?? '',
            'published_at' => $snippet['publishedAt'] ?? null,
            'channel_id' => $snippet['channelId'] ?? '',
            'channel_title' => $snippet['channelTitle'] ?? '',
            'thumbnail' => $snippet['thumbnails']['high']['url'] ?? null,
            'tags' => $snippet['tags'] ?? [],
            'category_id' => $snippet['categoryId'] ?? null,
            'live_broadcast_content' => $snippet['liveBroadcastContent'] ?? 'none',
            'duration' => $contentDetails['duration'] ?? null,
            'duration_seconds' => $durationSeconds,
            'dimension' => $contentDetails['dimension'] ?? null,
            'definition' => $contentDetails['definition'] ?? null,
            'caption' => $contentDetails['caption'] === 'true',
            'licensed_content' => $contentDetails['licensedContent'] ?? false,
            'view_count' => (int) ($statistics['viewCount'] ?? 0),
            'like_count' => (int) ($statistics['likeCount'] ?? 0),
            'comment_count' => (int) ($statistics['commentCount'] ?? 0),
        ];
    }

    /**
     * Format channel data into a consistent structure.
     */
    protected function formatChannelData(array $item): array
    {
        $channelId = $item['id'];
        $snippet = $item['snippet'] ?? [];
        $statistics = $item['statistics'] ?? [];
        $contentDetails = $item['contentDetails'] ?? [];

        return [
            'id' => $channelId,
            'title' => $snippet['title'] ?? '',
            'description' => $snippet['description'] ?? '',
            'custom_url' => $snippet['customUrl'] ?? null,
            'published_at' => $snippet['publishedAt'] ?? null,
            'thumbnail' => $snippet['thumbnails']['high']['url'] ?? null,
            'country' => $snippet['country'] ?? null,
            'uploads_playlist_id' => $contentDetails['relatedPlaylists']['uploads'] ?? null,
            'subscriber_count' => (int) ($statistics['subscriberCount'] ?? 0),
            'video_count' => (int) ($statistics['videoCount'] ?? 0),
            'view_count' => (int) ($statistics['viewCount'] ?? 0),
        ];
    }

    /**
     * Create a new HTTP client for YouTube API.
     */
    protected function makeRequest(): PendingRequest
    {
        // Ensure API key is set
        if (! $this->apiKey) {
            throw new \Exception('YouTube API key is not configured.');
        }

        return Http::baseUrl($this->baseUrl)
            ->withQueryParameters(['key' => $this->apiKey]);
    }

    /**
     * Check rate limits before making a request.
     *
     * @throws \Exception If rate limit is exceeded
     */
    protected function checkRateLimit(): void
    {
        $rateLimiter = app('redis');
        $currentDate = now()->format('Ymd');
        $dateKey = $this->rateLimitKey.':'.$currentDate;

        // Get current count for today
        $currentCount = (int) $rateLimiter->get($dateKey);

        if ($currentCount >= $this->maxRequestsPerDay) {
            // Rate limit exceeded
            $resetTime = now()->addDays(1)->startOfDay();
            $resetSeconds = $resetTime->diffInSeconds(now());

            Log::warning('YouTube API rate limit exceeded', [
                'current_count' => $currentCount,
                'max_requests' => $this->maxRequestsPerDay,
                'reset_in_seconds' => $resetSeconds,
            ]);

            throw new \Exception("YouTube API daily quota exceeded. Quota will reset in {$resetSeconds} seconds.");
        }

        // Increment counter and set expiry
        $rateLimiter->incr($dateKey);
        $rateLimiter->expire($dateKey, 86400 * 2); // TTL: 2 days (to ensure it expires)
    }
}
