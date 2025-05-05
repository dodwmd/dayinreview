<?php

namespace App\Repositories;

use App\Models\YoutubeVideo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContentRepository
{
    /**
     * Cache TTL in seconds (default: 1 hour).
     */
    protected int $cacheTtl = 3600;

    /**
     * Get trending videos across all sources.
     *
     * @param  int  $limit  Maximum number of videos to return
     * @return Collection Collection of trending videos
     */
    public function getTrendingVideos(int $limit = 10): Collection
    {
        $cacheKey = "trending_videos_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            return DB::table('reddit_posts')
                ->join('youtube_videos', 'reddit_posts.youtube_video_id', '=', 'youtube_videos.id')
                ->orderBy('reddit_posts.score', 'desc')
                ->select('youtube_videos.*', 'reddit_posts.score', 'reddit_posts.subreddit')
                ->limit($limit)
                ->get()
                ->map(function ($video) {
                    return $this->formatVideoData($video);
                });
        });
    }

    /**
     * Get trending videos from specific subreddits.
     *
     * @param  array  $subreddits  Array of subreddit names
     * @param  int  $limit  Maximum number of videos to return
     * @return Collection Collection of trending videos
     */
    public function getTrendingVideosFromSubreddits(array $subreddits, int $limit = 10): Collection
    {
        $subredditKey = implode('_', $subreddits);
        $cacheKey = "trending_videos_subreddits_{$subredditKey}_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($subreddits, $limit) {
            return DB::table('reddit_posts')
                ->join('youtube_videos', 'reddit_posts.youtube_video_id', '=', 'youtube_videos.id')
                ->whereIn('reddit_posts.subreddit', $subreddits)
                ->orderBy('reddit_posts.score', 'desc')
                ->select('youtube_videos.*', 'reddit_posts.score', 'reddit_posts.subreddit')
                ->limit($limit)
                ->get()
                ->map(function ($video) {
                    return $this->formatVideoData($video);
                });
        });
    }

    /**
     * Get videos based on a search query.
     *
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum number of videos to return
     * @return Collection Collection of videos matching the query
     */
    public function searchVideos(string $query, int $limit = 10): Collection
    {
        $cacheKey = 'search_videos_'.md5($query)."_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query, $limit) {
            return YoutubeVideo::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('channel_title', 'like', "%{$query}%")
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($video) {
                    return $this->formatModelData($video);
                });
        });
    }

    /**
     * Get recent videos from a specific time period.
     *
     * @param  int  $daysAgo  Number of days to look back
     * @param  int  $limit  Maximum number of videos to return
     * @return Collection Collection of recent videos
     */
    public function getRecentVideos(int $daysAgo = 7, int $limit = 10): Collection
    {
        $cacheKey = "recent_videos_{$daysAgo}_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($daysAgo, $limit) {
            $date = now()->subDays($daysAgo);

            return YoutubeVideo::where('created_at', '>=', $date)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($video) {
                    return $this->formatModelData($video);
                });
        });
    }

    /**
     * Get videos from a specific subreddit.
     *
     * @param  string  $subreddit  Subreddit name
     * @param  int  $limit  Maximum number of videos to return
     * @return Collection Collection of videos from the subreddit
     */
    public function getVideosFromSubreddit(string $subreddit, int $limit = 10): Collection
    {
        $cacheKey = "subreddit_videos_{$subreddit}_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($subreddit, $limit) {
            return DB::table('reddit_posts')
                ->join('youtube_videos', 'reddit_posts.youtube_video_id', '=', 'youtube_videos.id')
                ->where('reddit_posts.subreddit', $subreddit)
                ->orderBy('reddit_posts.score', 'desc')
                ->select('youtube_videos.*', 'reddit_posts.score', 'reddit_posts.subreddit')
                ->limit($limit)
                ->get()
                ->map(function ($video) {
                    return $this->formatVideoData($video);
                });
        });
    }

    /**
     * Format video data from a database result.
     *
     * @param  object  $video  Video data from database
     * @return array Formatted video data
     */
    protected function formatVideoData(object $video): array
    {
        return [
            'id' => $video->youtube_id,
            'title' => $video->title,
            'description' => $video->description,
            'thumbnail' => $video->thumbnail_url,
            'channel_id' => $video->channel_id,
            'channel_title' => $video->channel_title,
            'duration_seconds' => $video->duration_seconds,
            'view_count' => $video->view_count,
            'subreddit' => $video->subreddit ?? null,
            'score' => $video->score ?? null,
        ];
    }

    /**
     * Format video data from a model.
     *
     * @param  Model|object  $video  Video model or object
     * @return array Formatted video data
     */
    public function formatModelData($video): array
    {
        return [
            'id' => $video->id,
            'youtube_id' => $video->youtube_id,
            'title' => $video->title,
            'description' => $video->description,
            'thumbnail' => $video->thumbnail_url,
            'channel_id' => $video->channel_id,
            'channel_title' => $video->channel_title,
            'duration_seconds' => $video->duration_seconds,
            'view_count' => $video->view_count,
        ];
    }

    /**
     * Store a collection of videos.
     *
     * @param  Collection  $videos  Collection of video data
     * @return bool Whether the operation succeeded
     */
    public function storeVideos(Collection $videos): bool
    {
        try {
            foreach ($videos as $videoData) {
                $video = YoutubeVideo::firstOrNew(['youtube_id' => $videoData['id']]);
                
                /** @var \App\Models\YoutubeVideo $video */
                $video->title = $videoData['title'] ?? '';
                $video->description = $videoData['description'] ?? '';
                $video->thumbnail_url = $videoData['thumbnail'] ?? null;
                $video->channel_id = $videoData['channel_id'] ?? '';
                $video->channel_title = $videoData['channel_title'] ?? '';
                $video->duration_seconds = $videoData['duration_seconds'] ?? 0;
                $video->view_count = $videoData['view_count'] ?? 0;

                $video->save();
            }

            // Clear relevant caches
            Cache::forget('trending_videos_10');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
