<?php

namespace App\Services\Playlist;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\User;
use App\Models\YoutubeVideo;
use App\Repositories\ContentRepository;
use App\Repositories\PlaylistRepository;
use App\Services\YouTube\YouTubeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PlaylistService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly YouTubeService $youTubeService,
        private readonly PlaylistRepository $playlistRepository
    ) {}

    /**
     * Generate a daily playlist for a user.
     *
     * @param  User  $user  The user to generate the playlist for
     * @param  Carbon|null  $date  The date to generate the playlist for (defaults to today)
     * @return Playlist|null The generated playlist
     */
    public function generateDailyPlaylist(User $user, ?Carbon $date = null): ?Playlist
    {
        $date = $date ?? Carbon::today();

        try {
            // Check if playlist already exists for this date
            $existingPlaylist = $this->playlistRepository->getUserPlaylistForDate($user, $date);

            if ($existingPlaylist) {
                return $existingPlaylist;
            }

            // Get trending videos
            $trendingVideos = $this->getTrendingVideos($user);

            // Get subscription videos
            $subscriptionVideos = $this->getSubscriptionVideos($user);

            // If both collections are empty, return null
            if ($trendingVideos->isEmpty() && $subscriptionVideos->isEmpty()) {
                Log::info('No videos available for playlist generation', [
                    'user_id' => $user->getKey(),
                    'date' => $date->format('Y-m-d'),
                ]);

                return null;
            }

            // Create new playlist
            $playlist = new Playlist;
            $playlist->user_id = $user->getKey();
            $playlist->name = 'Daily Playlist - '.$date->format('F j, Y');
            $playlist->description = 'Automatically generated daily playlist with trending and subscription content';
            $playlist->type = 'auto';
            $playlist->visibility = 'private';
            $playlist->is_favorite = false;
            $playlist->view_count = 0;
            $playlist->last_generated_at = $date;

            // Add videos to the playlist
            $this->addVideosToPlaylist($playlist, $trendingVideos, $subscriptionVideos);

            // Store the playlist
            $this->playlistRepository->storePlaylist($playlist);

            // Sync with YouTube if user has YouTube integration
            if (! empty($user->youtube_token)) {
                $this->syncWithYouTube($playlist, $user);
            }

            return $playlist;
        } catch (\Exception $e) {
            Log::error('Failed to generate daily playlist', [
                'user_id' => $user->getKey(),
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get trending videos based on user's subscription preferences.
     */
    protected function getTrendingVideos(User $user): Collection
    {
        // Get user's Reddit subscriptions (subreddits) using a where clause instead of scope
        $subreddits = $user->subscriptions()
            ->where('source_type', 'App\\Models\\RedditSubreddit')
            ->pluck('source_id')
            ->toArray();

        // If user has no Reddit subscriptions, get general trending videos
        if (empty($subreddits)) {
            return $this->contentRepository->getTrendingVideos(10);
        }

        // Otherwise, get trending videos from user's subreddits
        return $this->contentRepository->getTrendingVideosFromSubreddits($subreddits, 10);
    }

    /**
     * Get videos from user's YouTube subscriptions.
     */
    protected function getSubscriptionVideos(User $user): Collection
    {
        // Get user's YouTube channel subscriptions using a where clause instead of scope
        $channels = $user->subscriptions()
            ->where('source_type', 'App\\Models\\YouTubeChannel')
            ->pluck('source_id')
            ->toArray();

        if (empty($channels) || empty($user->youtube_token)) {
            return collect();
        }

        // Get recent videos from subscribed channels
        // Limit to 2 videos per channel, maximum 10 total
        $videos = collect();

        foreach ($channels as $channelId) {
            // Get channel videos
            $channelVideos = $this->youTubeService->getChannelVideos($channelId, 2);

            if (! empty($channelVideos['videos'])) {
                $videos = $videos->merge($channelVideos['videos']);
            }

            // Limit to maximum 10 videos
            if ($videos->count() >= 10) {
                $videos = $videos->take(10);
                break;
            }
        }

        return $videos;
    }

    /**
     * Add videos to a playlist with clear separation between trending and subscription videos.
     */
    protected function addVideosToPlaylist(Playlist $playlist, Collection $trendingVideos, Collection $subscriptionVideos): void
    {
        $position = 1;
        $now = now();

        // Add subscription videos (priority content)
        foreach ($subscriptionVideos as $video) {
            $youtubeVideo = $this->findOrCreateYoutubeVideo($video);

            if ($youtubeVideo) {
                $playlistItem = new PlaylistItem;
                $playlistItem->playlist_id = $playlist->id;
                $playlistItem->source_type = 'youtube_video';
                $playlistItem->source_id = $youtubeVideo->id;
                $playlistItem->position = $position++;
                $playlistItem->is_watched = false;
                $playlistItem->added_at = $now;
                $playlistItem->notes = 'From subscriptions';
                $playlistItem->save();
            }
        }

        // Add trending videos
        foreach ($trendingVideos as $video) {
            // Skip videos already added from subscriptions
            if ($subscriptionVideos->contains('id', $video['id'] ?? null)) {
                continue;
            }

            $youtubeVideo = $this->findOrCreateYoutubeVideo($video);

            if ($youtubeVideo) {
                $playlistItem = new PlaylistItem;
                $playlistItem->playlist_id = $playlist->id;
                $playlistItem->source_type = 'youtube_video';
                $playlistItem->source_id = $youtubeVideo->id;
                $playlistItem->position = $position++;
                $playlistItem->is_watched = false;
                $playlistItem->added_at = $now;
                $playlistItem->notes = 'Trending';
                $playlistItem->save();
            }
        }
    }

    /**
     * Find or create a YouTubeVideo model from API data.
     */
    protected function findOrCreateYoutubeVideo(array $videoData): ?YoutubeVideo
    {
        try {
            $videoId = $videoData['id'] ?? null;

            if (! $videoId) {
                return null;
            }

            // Find existing video or create a new one
            /** @var YoutubeVideo $video */
            $video = YoutubeVideo::query()->firstOrNew(['youtube_id' => $videoId]);

            // Update video data
            $video->fill([
                'title' => $videoData['title'] ?? '',
                'description' => $videoData['description'] ?? '',
                'thumbnail_url' => $videoData['thumbnail'] ?? null,
                'channel_id' => $videoData['channel_id'] ?? '',
                'channel_title' => $videoData['channel_title'] ?? '',
                'duration_seconds' => $videoData['duration_seconds'] ?? 0,
            ]);

            // Save if new or changed
            if ($video->isDirty()) {
                $video->save();
            }

            return $video;
        } catch (\Exception $e) {
            Log::error('Failed to find or create YouTube video', [
                'video_id' => $videoData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync a playlist with YouTube.
     */
    protected function syncWithYouTube(Playlist $playlist, User $user): void
    {
        try {
            if (empty($user->youtube_token)) {
                return;
            }

            // Create a new YouTube playlist
            $title = $playlist->name;
            $description = $playlist->description ?? 'Generated playlist by Day in Review application';

            $ytPlaylist = $this->youTubeService->createPlaylist(
                $title,
                $description,
                'private',
                $user->youtube_token
            );

            if (! empty($ytPlaylist['id'])) {
                // Save the YouTube playlist ID
                $playlist->thumbnail_url = $ytPlaylist['thumbnail'] ?? null;
                $playlist->save();

                // Add videos to the YouTube playlist
                $playlistItems = $playlist->items()
                    ->where('source_type', 'youtube_video')
                    ->orderBy('position')
                    ->get();

                foreach ($playlistItems as $playlistItem) {
                    // Get the YouTube video
                    $video = (new YoutubeVideo)->newQuery()->find($playlistItem->source_id);
                    if ($video) {
                        $this->youTubeService->addVideoToPlaylist(
                            $ytPlaylist['id'],
                            $video->youtube_id,
                            $user->youtube_token
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync playlist with YouTube', [
                'playlist_id' => $playlist->getKey(),
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get a user's playlists.
     */
    public function getUserPlaylists(User $user, int $limit = 10): Collection
    {
        return $this->playlistRepository->getUserPlaylists($user, $limit);
    }

    /**
     * Get a specific playlist.
     */
    public function getPlaylist(User $user, string $playlistId): ?Playlist
    {
        $playlist = $this->playlistRepository->getPlaylist($playlistId);

        // Ensure the user owns this playlist
        if (! $playlist || $playlist->user_id !== $user->getKey()) {
            return null;
        }

        return $playlist;
    }

    /**
     * Update playlist visibility.
     */
    public function updateVisibility(User $user, string $playlistId, bool $isPublic): ?Playlist
    {
        $playlist = $this->playlistRepository->getPlaylist($playlistId);

        if (! $playlist || $playlist->user_id !== $user->getKey()) {
            return null;
        }

        $visibility = $isPublic ? 'public' : 'private';
        $this->playlistRepository->updateVisibility($playlist, $visibility);

        return $playlist;
    }

    /**
     * Mark a video as watched in a playlist.
     */
    public function markVideoAsWatched(User $user, string $playlistId, string $videoId): bool
    {
        try {
            $playlist = $this->playlistRepository->getPlaylist($playlistId);

            if (! $playlist || $playlist->user_id !== $user->getKey()) {
                return false;
            }

            return $this->playlistRepository->markVideoAsWatched($playlist, $videoId);
        } catch (\Exception $e) {
            Log::error('Failed to mark video as watched', [
                'playlist_id' => $playlistId,
                'video_id' => $videoId,
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
