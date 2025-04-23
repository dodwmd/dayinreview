<?php

namespace App\Services\Content;

use App\Models\RedditPost;
use App\Models\YoutubeVideo;
use App\Services\Reddit\RedditService;
use App\Services\YouTube\YouTubeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentAggregationService
{
    /**
     * The Reddit Service.
     */
    protected RedditService $redditService;

    /**
     * The YouTube Service.
     */
    protected YouTubeService $youtubeService;

    /**
     * Create a new ContentAggregationService instance.
     */
    public function __construct(RedditService $redditService, YouTubeService $youtubeService)
    {
        $this->redditService = $redditService;
        $this->youtubeService = $youtubeService;
    }

    /**
     * Aggregate daily content from popular subreddits.
     *
     * @param  array  $subreddits  Specific subreddits to fetch from, or empty for r/popular
     * @param  string  $timeframe  The timeframe for popular posts (hour, day, week, month, year, all)
     * @param  int  $limit  The number of posts to retrieve per subreddit
     * @return array Summary of the aggregation operation
     */
    public function aggregateDailyContent(array $subreddits = [], string $timeframe = 'day', int $limit = 25): array
    {
        $stats = [
            'processed_posts' => 0,
            'saved_reddit_posts' => 0,
            'saved_youtube_videos' => 0,
            'errors' => [],
            'subreddit_stats' => [],
        ];

        try {
            // Process r/popular if no specific subreddits are specified
            if (empty($subreddits)) {
                $stats['subreddit_stats']['popular'] = $this->processPopularPosts($timeframe, $limit);

                $stats['processed_posts'] += $stats['subreddit_stats']['popular']['processed'];
                $stats['saved_reddit_posts'] += $stats['subreddit_stats']['popular']['reddit_posts_saved'];
                $stats['saved_youtube_videos'] += $stats['subreddit_stats']['popular']['youtube_videos_saved'];
            } else {
                // Process each specified subreddit
                foreach ($subreddits as $subreddit) {
                    $subredditStats = $this->processSubredditPosts($subreddit, $timeframe, $limit);
                    $stats['subreddit_stats'][$subreddit] = $subredditStats;

                    $stats['processed_posts'] += $subredditStats['processed'];
                    $stats['saved_reddit_posts'] += $subredditStats['reddit_posts_saved'];
                    $stats['saved_youtube_videos'] += $subredditStats['youtube_videos_saved'];
                }
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error aggregating content', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $stats['errors'][] = 'Error aggregating content: '.$e->getMessage();

            return $stats;
        }
    }

    /**
     * Process popular posts from Reddit's front page.
     *
     * @param  string  $timeframe  The timeframe for popular posts
     * @param  int  $limit  The number of posts to retrieve
     * @return array Statistics about the operation
     */
    protected function processPopularPosts(string $timeframe, int $limit): array
    {
        $stats = [
            'processed' => 0,
            'reddit_posts_saved' => 0,
            'youtube_videos_saved' => 0,
            'errors' => [],
        ];

        try {
            // Fetch popular posts from Reddit
            $popularPosts = $this->redditService->getPopularPosts($timeframe, $limit);

            if (isset($popularPosts['error'])) {
                $stats['errors'][] = $popularPosts['error'];

                return $stats;
            }

            // Process each post
            foreach ($popularPosts['data'] as $post) {
                $stats['processed']++;

                try {
                    $savedPost = $this->saveRedditPost($post);

                    if ($savedPost) {
                        $stats['reddit_posts_saved']++;

                        // If the post has a YouTube video, process it
                        if ($post['has_youtube_video'] && ! empty($post['youtube_id'])) {
                            $savedVideo = $this->processYouTubeVideo($post['youtube_id'], $savedPost->id);

                            if ($savedVideo) {
                                $stats['youtube_videos_saved']++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing popular post', [
                        'post_id' => $post['id'] ?? 'unknown',
                        'message' => $e->getMessage(),
                    ]);

                    $stats['errors'][] = 'Error processing post '.($post['id'] ?? 'unknown').': '.$e->getMessage();
                }
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error processing popular posts', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $stats['errors'][] = 'Error processing popular posts: '.$e->getMessage();

            return $stats;
        }
    }

    /**
     * Process posts from a specific subreddit.
     *
     * @param  string  $subreddit  The subreddit name
     * @param  string  $timeframe  The timeframe for posts
     * @param  int  $limit  The number of posts to retrieve
     * @return array Statistics about the operation
     */
    protected function processSubredditPosts(string $subreddit, string $timeframe, int $limit): array
    {
        $stats = [
            'processed' => 0,
            'reddit_posts_saved' => 0,
            'youtube_videos_saved' => 0,
            'errors' => [],
        ];

        try {
            // Fetch posts from the specified subreddit
            $subredditPosts = $this->redditService->getSubredditPosts($subreddit, 'hot', $timeframe, $limit);

            if (isset($subredditPosts['error'])) {
                $stats['errors'][] = $subredditPosts['error'];

                return $stats;
            }

            // Process each post
            foreach ($subredditPosts['data'] as $post) {
                $stats['processed']++;

                try {
                    $savedPost = $this->saveRedditPost($post);

                    if ($savedPost) {
                        $stats['reddit_posts_saved']++;

                        // If the post has a YouTube video, process it
                        if ($post['has_youtube_video'] && ! empty($post['youtube_id'])) {
                            $savedVideo = $this->processYouTubeVideo($post['youtube_id'], $savedPost->id);

                            if ($savedVideo) {
                                $stats['youtube_videos_saved']++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing subreddit post', [
                        'subreddit' => $subreddit,
                        'post_id' => $post['id'] ?? 'unknown',
                        'message' => $e->getMessage(),
                    ]);

                    $stats['errors'][] = 'Error processing post '.($post['id'] ?? 'unknown').': '.$e->getMessage();
                }
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error processing subreddit posts', [
                'subreddit' => $subreddit,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $stats['errors'][] = 'Error processing subreddit '.$subreddit.': '.$e->getMessage();

            return $stats;
        }
    }

    /**
     * Save a Reddit post to the database.
     *
     * @param  array  $postData  The post data from Reddit API
     * @return RedditPost|null The saved post or null if error
     */
    protected function saveRedditPost(array $postData): ?RedditPost
    {
        try {
            // Check if the post already exists
            /** @var RedditPost|null $existingPost */
            $existingPost = RedditPost::where('reddit_id', $postData['id'])->first();

            if ($existingPost) {
                // Update the existing post with new data
                $existingPost->update([
                    'score' => (int) $postData['score'],
                    'num_comments' => (int) $postData['num_comments'],
                    'has_youtube_video' => (bool) $postData['has_youtube_video'],
                ]);

                return $existingPost;
            }

            // Create a new post
            $postedAt = isset($postData['created_utc'])
                ? Carbon::createFromTimestamp($postData['created_utc'])
                : now();

            $post = new RedditPost;
            $post->id = (string) Str::uuid();
            $post->reddit_id = $postData['id'];
            $post->subreddit = $postData['subreddit'];
            $post->title = $postData['title'];
            $post->content = $postData['selftext'] ?? null;
            $post->author = $postData['author'];
            $post->permalink = $postData['permalink'];
            $post->url = $postData['url'];
            $post->score = (int) $postData['score'];
            $post->num_comments = (int) $postData['num_comments'];
            $post->has_youtube_video = (bool) $postData['has_youtube_video'];
            $post->posted_at = $postedAt;
            $post->save();

            return $post;
        } catch (\Exception $e) {
            Log::error('Error saving Reddit post', [
                'post_id' => $postData['id'] ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Process a YouTube video from a Reddit post.
     *
     * @param  string  $youtubeId  The YouTube video ID
     * @param  string  $redditPostId  The UUID of the associated Reddit post
     * @return YoutubeVideo|null The saved video or null if error
     */
    protected function processYouTubeVideo(string $youtubeId, string $redditPostId): ?YoutubeVideo
    {
        try {
            // Check if the video already exists
            /** @var YoutubeVideo|null $existingVideo */
            $existingVideo = YoutubeVideo::where('youtube_id', $youtubeId)->first();

            if ($existingVideo) {
                // Update the relationship with Reddit post if needed
                if ($existingVideo->reddit_post_id === null) {
                    $existingVideo->reddit_post_id = $redditPostId;
                    $existingVideo->save();
                }

                return $existingVideo;
            }

            // Fetch video details from YouTube API
            $videoDetails = $this->youtubeService->getVideoDetails($youtubeId);

            if (isset($videoDetails['error'])) {
                Log::error('Error fetching YouTube video details', [
                    'youtube_id' => $youtubeId,
                    'error' => $videoDetails['error'],
                ]);

                return null;
            }

            // Create a new YouTube video record
            $publishedAt = isset($videoDetails['published_at'])
                ? Carbon::parse($videoDetails['published_at'])
                : now();

            $video = new YoutubeVideo;
            $video->id = (string) Str::uuid();
            $video->youtube_id = $youtubeId;
            $video->reddit_post_id = $redditPostId;
            $video->title = $videoDetails['title'];
            $video->description = $videoDetails['description'];
            $video->channel_id = $videoDetails['channel_id'];
            $video->channel_title = $videoDetails['channel_title'];
            $video->thumbnail_url = $videoDetails['thumbnail'];
            $video->duration_seconds = (int) ($videoDetails['duration_seconds'] ?? 0);
            $video->view_count = (int) ($videoDetails['view_count'] ?? 0);
            $video->like_count = (int) ($videoDetails['like_count'] ?? 0);
            $video->is_trending = false; // Set based on criteria, e.g., high view count
            $video->published_at = $publishedAt;
            $video->save();

            return $video;
        } catch (\Exception $e) {
            Log::error('Error processing YouTube video', [
                'youtube_id' => $youtubeId,
                'reddit_post_id' => $redditPostId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Mark videos as trending based on criteria.
     *
     * @param  int  $viewThreshold  The minimum view count for a video to be considered trending
     * @param  int  $likeThreshold  The minimum like count for a video to be considered trending
     * @param  int  $dayRange  The number of days to consider for trending videos
     * @return int The number of videos marked as trending
     */
    public function updateTrendingVideos(int $viewThreshold = 100000, int $likeThreshold = 10000, int $dayRange = 7): int
    {
        try {
            $dateThreshold = now()->subDays($dayRange);

            // Find videos that meet the trending criteria
            $count = DB::table('youtube_videos')
                ->where('published_at', '>=', $dateThreshold)
                ->where('view_count', '>=', $viewThreshold)
                ->where('like_count', '>=', $likeThreshold)
                ->where('is_trending', '=', false)
                ->update(['is_trending' => true]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Error updating trending videos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }

    /**
     * Get trending videos based on various filters.
     *
     * @param  int  $limit  The maximum number of videos to return
     * @param  array  $channelIds  Filter by specific channel IDs
     * @param  int  $minDuration  Minimum duration in seconds
     * @param  int  $maxDuration  Maximum duration in seconds
     * @return array The trending videos
     */
    public function getTrendingVideos(int $limit = 20, array $channelIds = [], int $minDuration = 0, int $maxDuration = 0): array
    {
        try {
            $query = YoutubeVideo::where('is_trending', '=', true)
                ->orderBy('published_at', 'desc');

            // Apply filters
            if (! empty($channelIds)) {
                $query->whereIn('channel_id', $channelIds);
            }

            if ($minDuration > 0) {
                $query->where('duration_seconds', '>=', $minDuration);
            }

            if ($maxDuration > 0) {
                $query->where('duration_seconds', '<=', $maxDuration);
            }

            return $query->limit($limit)->get()->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting trending videos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get videos from Reddit posts in a specific subreddit.
     *
     * @param  string  $subreddit  The subreddit name
     * @param  int  $limit  The maximum number of videos to return
     * @param  string  $sortBy  How to sort the videos (recent, popular)
     * @return array The videos from the subreddit
     */
    public function getVideosFromSubreddit(string $subreddit, int $limit = 20, string $sortBy = 'recent'): array
    {
        try {
            $query = YoutubeVideo::join('reddit_posts', 'youtube_videos.reddit_post_id', '=', 'reddit_posts.id')
                ->where('reddit_posts.subreddit', $subreddit)
                ->select('youtube_videos.*');

            // Apply sorting
            if ($sortBy === 'popular') {
                $query->orderBy('youtube_videos.view_count', 'desc');
            } else {
                $query->orderBy('youtube_videos.published_at', 'desc');
            }

            return $query->limit($limit)->get()->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting videos from subreddit', [
                'subreddit' => $subreddit,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }
}
