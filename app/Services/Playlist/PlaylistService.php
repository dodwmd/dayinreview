<?php

namespace App\Services\Playlist;

use App\Models\Playlist;
use App\Models\User;
use App\Models\YoutubeVideo;
use App\Repositories\ContentRepository;
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
        private readonly YouTubeService $youTubeService
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
            $existingPlaylist = $user->playlists()
                ->where('date', $date->format('Y-m-d'))
                ->first();

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
            $playlist->date = $date;
            $playlist->is_public = false; // Default to private
            $playlist->save();

            // Associate videos with the playlist
            $this->addVideosToPlaylist($playlist, $trendingVideos, $subscriptionVideos);

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
        // Get user's Reddit subscriptions (subreddits)
        $subreddits = $user->subscriptions()
            ->reddit()
            ->pluck('subscribable_id')
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
        // Get user's YouTube channel subscriptions
        $channels = $user->subscriptions()
            ->youtube()
            ->pluck('subscribable_id')
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

        // Add subscription videos (priority content)
        foreach ($subscriptionVideos as $video) {
            $youtubeVideo = $this->findOrCreateYoutubeVideo($video);

            if ($youtubeVideo) {
                $playlist->videos()->attach($youtubeVideo, [
                    'position' => $position++,
                    'watched' => false,
                    'source' => 'subscription',
                ]);
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
                $playlist->videos()->attach($youtubeVideo, [
                    'position' => $position++,
                    'watched' => false,
                    'source' => 'trending',
                ]);
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
            $video = YoutubeVideo::firstOrNew(['youtube_id' => $videoId]);

            // Update video data
            $video->title = $videoData['title'] ?? '';
            $video->description = $videoData['description'] ?? '';
            $video->thumbnail_url = $videoData['thumbnail'] ?? null;
            $video->channel_id = $videoData['channel_id'] ?? '';
            $video->channel_title = $videoData['channel_title'] ?? '';
            $video->duration_seconds = $videoData['duration_seconds'] ?? 0;

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

            // If the playlist already has a YouTube ID, it's already synced
            if (! empty($playlist->youtube_playlist_id)) {
                return;
            }

            // Create a new YouTube playlist
            $title = 'Day in Review - '.$playlist->date->format('Y-m-d');
            $description = 'Generated playlist by Day in Review application';

            $ytPlaylist = $this->youTubeService->createPlaylist(
                $title,
                $description,
                'private',
                $user->youtube_token
            );

            if (! empty($ytPlaylist['id'])) {
                // Save the YouTube playlist ID
                $playlist->youtube_playlist_id = $ytPlaylist['id'];
                $playlist->save();

                // Add videos to the YouTube playlist
                $playlistVideos = $playlist->videos;

                foreach ($playlistVideos as $video) {
                    $this->youTubeService->addVideoToPlaylist(
                        $ytPlaylist['id'],
                        $video->youtube_id,
                        $user->youtube_token
                    );
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
        return $user->playlists()
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->with('videos')
            ->get();
    }

    /**
     * Get a specific playlist.
     */
    public function getPlaylist(User $user, string $playlistId): ?Playlist
    {
        return $user->playlists()
            ->where('id', $playlistId)
            ->with('videos')
            ->first();
    }

    /**
     * Update playlist visibility.
     */
    public function updateVisibility(User $user, string $playlistId, bool $isPublic): ?Playlist
    {
        $playlist = $user->playlists()->find($playlistId);

        if (! $playlist) {
            return null;
        }

        $playlist->is_public = $isPublic;
        $playlist->save();

        return $playlist;
    }

    /**
     * Mark a video as watched in a playlist.
     */
    public function markVideoAsWatched(User $user, string $playlistId, string $videoId): bool
    {
        try {
            $playlist = $user->playlists()->find($playlistId);

            if (! $playlist) {
                return false;
            }

            $playlist->videos()
                ->wherePivot('youtube_video_id', $videoId)
                ->updateExistingPivot($videoId, ['watched' => true]);

            return true;
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
