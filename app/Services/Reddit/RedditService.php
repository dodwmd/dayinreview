<?php

namespace App\Services\Reddit;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RedditService
{
    /**
     * The base URL for Reddit API.
     */
    protected string $baseUrl = 'https://www.reddit.com';

    /**
     * The User Agent to use for API requests.
     */
    protected string $userAgent = 'Day in Review App/1.0';

    /**
     * Cache TTL in seconds (default: 30 minutes).
     */
    protected int $cacheTtl = 1800;

    /**
     * Rate limit settings.
     */
    protected int $maxRequestsPerMinute = 60;

    protected string $rateLimitKey = 'reddit_api_rate_limit';

    /**
     * Create a new RedditService instance.
     */
    public function __construct()
    {
        // Override settings from config if available
        if (config('services.reddit.base_url')) {
            $this->baseUrl = config('services.reddit.base_url');
        }

        if (config('services.reddit.user_agent')) {
            $this->userAgent = config('services.reddit.user_agent');
        }

        if (config('services.reddit.cache_ttl')) {
            $this->cacheTtl = config('services.reddit.cache_ttl');
        }

        if (config('services.reddit.max_requests_per_minute')) {
            $this->maxRequestsPerMinute = config('services.reddit.max_requests_per_minute');
        }
    }

    /**
     * Get popular posts from Reddit.
     *
     * @param  string  $timeframe  The timeframe for popular posts (hour, day, week, month, year, all)
     * @param  int  $limit  The number of posts to retrieve
     * @param  string|null  $after  Pagination token
     */
    public function getPopularPosts(string $timeframe = 'day', int $limit = 25, ?string $after = null): array
    {
        $cacheKey = "reddit_popular_{$timeframe}_{$limit}".($after ? "_{$after}" : '');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($timeframe, $limit, $after) {
            try {
                $this->checkRateLimit();

                $endpoint = '/r/popular.json';
                $query = [
                    't' => $timeframe,
                    'limit' => $limit,
                ];

                if ($after) {
                    $query['after'] = $after;
                }

                $response = $this->makeRequest()->get($endpoint, $query);

                if ($response->successful()) {
                    return $this->formatPopularPosts($response->json());
                }

                Log::error('Reddit API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return ['data' => [], 'after' => null, 'error' => 'API request failed: '.$response->status()];
            } catch (\Exception $e) {
                Log::error('Reddit API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ['data' => [], 'after' => null, 'error' => 'Exception: '.$e->getMessage()];
            }
        });
    }

    /**
     * Get posts from a specific subreddit.
     *
     * @param  string  $subreddit  The subreddit name
     * @param  string  $sort  The sort method (hot, new, top, rising)
     * @param  string  $timeframe  The timeframe for top posts (hour, day, week, month, year, all)
     * @param  int  $limit  The number of posts to retrieve
     * @param  string|null  $after  Pagination token
     */
    public function getSubredditPosts(
        string $subreddit,
        string $sort = 'hot',
        string $timeframe = 'day',
        int $limit = 25,
        ?string $after = null
    ): array {
        $cacheKey = "reddit_sub_{$subreddit}_{$sort}_{$timeframe}_{$limit}".($after ? "_{$after}" : '');

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($subreddit, $sort, $timeframe, $limit, $after) {
            try {
                $this->checkRateLimit();

                $endpoint = "/r/{$subreddit}/{$sort}.json";
                $query = [
                    'limit' => $limit,
                ];

                if ($sort === 'top') {
                    $query['t'] = $timeframe;
                }

                if ($after) {
                    $query['after'] = $after;
                }

                $response = $this->makeRequest()->get($endpoint, $query);

                if ($response->successful()) {
                    return $this->formatSubredditPosts($response->json());
                }

                Log::error('Reddit API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return ['data' => [], 'after' => null, 'error' => 'API request failed: '.$response->status()];
            } catch (\Exception $e) {
                Log::error('Reddit API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ['data' => [], 'after' => null, 'error' => 'Exception: '.$e->getMessage()];
            }
        });
    }

    /**
     * Get details about a specific Reddit post.
     *
     * @param  string  $postId  The post ID
     */
    public function getPostDetails(string $postId): array
    {
        $cacheKey = "reddit_post_{$postId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($postId) {
            try {
                $this->checkRateLimit();

                // Remove "t3_" prefix if it exists
                $cleanPostId = str_replace('t3_', '', $postId);
                $endpoint = "/comments/{$cleanPostId}.json";

                $response = $this->makeRequest()->get($endpoint);

                if ($response->successful()) {
                    return $this->formatPostDetails($response->json());
                }

                Log::error('Reddit API error', [
                    'status' => $response->status(),
                    'message' => $response->body(),
                ]);

                return ['post' => null, 'comments' => [], 'error' => 'API request failed: '.$response->status()];
            } catch (\Exception $e) {
                Log::error('Reddit API exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ['post' => null, 'comments' => [], 'error' => 'Exception: '.$e->getMessage()];
            }
        });
    }

    /**
     * Check if a post contains a YouTube video.
     */
    public function hasYouTubeVideo(array $post): bool
    {
        // Check if it's a direct YouTube link
        if (isset($post['url']) && $this->isYouTubeUrl($post['url'])) {
            return true;
        }

        // Check if it has a YouTube embed in the media
        if (isset($post['media']['type']) && $post['media']['type'] === 'youtube.com') {
            return true;
        }

        // Check if it has a YouTube embed in secure_media
        if (isset($post['secure_media']['type']) && $post['secure_media']['type'] === 'youtube.com') {
            return true;
        }

        return false;
    }

    /**
     * Extract YouTube video ID from a Reddit post.
     */
    public function extractYouTubeVideoId(array $post): ?string
    {
        // Check if it's a direct YouTube link
        if (isset($post['url']) && $this->isYouTubeUrl($post['url'])) {
            return $this->getYouTubeIdFromUrl($post['url']);
        }

        // Check if it has a YouTube embed in the media
        if (isset($post['media']['type']) && $post['media']['type'] === 'youtube.com') {
            return $post['media']['oembed']['html'] ?
                $this->getYouTubeIdFromEmbedHtml($post['media']['oembed']['html']) : null;
        }

        // Check if it has a YouTube embed in secure_media
        if (isset($post['secure_media']['type']) && $post['secure_media']['type'] === 'youtube.com') {
            return $post['secure_media']['oembed']['html'] ?
                $this->getYouTubeIdFromEmbedHtml($post['secure_media']['oembed']['html']) : null;
        }

        return null;
    }

    /**
     * Format the popular posts response.
     */
    protected function formatPopularPosts(array $response): array
    {
        $posts = [];
        $after = $response['data']['after'] ?? null;

        foreach ($response['data']['children'] ?? [] as $child) {
            $post = $child['data'] ?? [];

            if ($post) {
                $posts[] = $this->formatPostData($post);
            }
        }

        return [
            'data' => $posts,
            'after' => $after,
        ];
    }

    /**
     * Format the subreddit posts response.
     */
    protected function formatSubredditPosts(array $response): array
    {
        // Same format as popular posts
        return $this->formatPopularPosts($response);
    }

    /**
     * Format the post details response.
     */
    protected function formatPostDetails(array $response): array
    {
        if (empty($response[0]['data']['children'])) {
            return ['post' => null, 'comments' => []];
        }

        $post = $this->formatPostData($response[0]['data']['children'][0]['data'] ?? []);
        $comments = [];

        foreach ($response[1]['data']['children'] ?? [] as $child) {
            if (isset($child['data']) && ! empty($child['data'])) {
                $comments[] = $this->formatCommentData($child['data']);
            }
        }

        return [
            'post' => $post,
            'comments' => $comments,
        ];
    }

    /**
     * Format post data into a consistent structure.
     */
    protected function formatPostData(array $post): array
    {
        $hasYouTubeVideo = $this->hasYouTubeVideo($post);
        $youtubeId = $hasYouTubeVideo ? $this->extractYouTubeVideoId($post) : null;

        return [
            'id' => $post['id'] ?? '',
            'fullname' => $post['name'] ?? '',
            'subreddit' => $post['subreddit'] ?? '',
            'title' => $post['title'] ?? '',
            'author' => $post['author'] ?? '',
            'permalink' => $post['permalink'] ? "https://www.reddit.com{$post['permalink']}" : '',
            'url' => $post['url'] ?? '',
            'score' => $post['score'] ?? 0,
            'ups' => $post['ups'] ?? 0,
            'downs' => $post['downs'] ?? 0,
            'num_comments' => $post['num_comments'] ?? 0,
            'created_utc' => $post['created_utc'] ?? 0,
            'thumbnail' => $post['thumbnail'] ?? '',
            'is_video' => $post['is_video'] ?? false,
            'selftext' => $post['selftext'] ?? '',
            'has_youtube_video' => $hasYouTubeVideo,
            'youtube_id' => $youtubeId,
            'media' => [
                'type' => $post['media']['type'] ?? null,
                'embed_html' => $post['media']['oembed']['html'] ?? null,
            ],
        ];
    }

    /**
     * Format comment data into a consistent structure.
     */
    protected function formatCommentData(array $comment): array
    {
        return [
            'id' => $comment['id'] ?? '',
            'fullname' => $comment['name'] ?? '',
            'author' => $comment['author'] ?? '',
            'body' => $comment['body'] ?? '',
            'score' => $comment['score'] ?? 0,
            'created_utc' => $comment['created_utc'] ?? 0,
            'replies' => isset($comment['replies']['data']) ? $this->getChildComments($comment['replies']['data']) : [],
        ];
    }

    /**
     * Recursively format child comments.
     */
    protected function getChildComments(array $data): array
    {
        $comments = [];

        foreach ($data['children'] ?? [] as $child) {
            if (isset($child['data']) && $child['kind'] !== 'more') {
                $comments[] = $this->formatCommentData($child['data']);
            }
        }

        return $comments;
    }

    /**
     * Check if the URL is a YouTube URL.
     */
    protected function isYouTubeUrl(string $url): bool
    {
        return
            str_contains($url, 'youtube.com/watch') ||
            str_contains($url, 'youtu.be/') ||
            str_contains($url, 'youtube.com/embed/');
    }

    /**
     * Extract YouTube ID from a URL.
     */
    protected function getYouTubeIdFromUrl(string $url): ?string
    {
        // youtube.com/watch?v=VIDEO_ID
        if (preg_match('/youtube\.com\/watch\?v=([^&\s]+)/', $url, $matches)) {
            return $matches[1];
        }

        // youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([^&\s]+)/', $url, $matches)) {
            return $matches[1];
        }

        // youtube.com/embed/VIDEO_ID
        if (preg_match('/youtube\.com\/embed\/([^&\s\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract YouTube ID from an embed HTML.
     */
    protected function getYouTubeIdFromEmbedHtml(string $html): ?string
    {
        if (preg_match('/src="https?:\/\/www\.youtube\.com\/embed\/([^"]+)"/', $html, $matches)) {
            // Remove any extra URL parameters
            $id = explode('?', $matches[1])[0];

            return $id;
        }

        return null;
    }

    /**
     * Create a new HTTP client for Reddit API.
     */
    protected function makeRequest(): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
        ])->baseUrl($this->baseUrl);
    }

    /**
     * Check rate limits before making a request.
     *
     * @throws \Exception If rate limit is exceeded
     */
    protected function checkRateLimit(): void
    {
        $rateLimiter = app('redis');
        $currentTime = now();
        $windowKey = $this->rateLimitKey.':'.$currentTime->format('YmdHi'); // Current minute

        // Get current count for this minute
        $currentCount = (int) $rateLimiter->get($windowKey);

        if ($currentCount >= $this->maxRequestsPerMinute) {
            // Rate limit exceeded
            $reset = 60 - $currentTime->second; // Seconds until next minute
            Log::warning('Reddit API rate limit exceeded', [
                'current_count' => $currentCount,
                'max_requests' => $this->maxRequestsPerMinute,
                'reset_in_seconds' => $reset,
            ]);

            throw new \Exception("Reddit API rate limit exceeded. Try again in {$reset} seconds.");
        }

        // Increment counter and set expiry
        $rateLimiter->incr($windowKey);
        $rateLimiter->expire($windowKey, 120); // TTL: 2 minutes (to ensure it expires)
    }
}
