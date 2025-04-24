<?php

namespace App\Repositories;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaylistRepository
{
    /**
     * Cache time in seconds (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Get a user's playlists with caching.
     */
    public function getUserPlaylists(User $user, int $limit = 10): Collection
    {
        $cacheKey = "playlists:user:{$user->getKey()}:limit:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $limit) {
            return $user->playlists()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->with('videos')
                ->get();
        });
    }

    /**
     * Get a user's playlists with eager loaded items for optimized performance testing.
     */
    public function getUserPlaylistsWithItems(User $user, int $limit = 10): Collection
    {
        $cacheKey = "playlists:user:{$user->getKey()}:withItems:limit:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $limit) {
            return $user->playlists()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->with(['items', 'items.source'])
                ->get();
        });
    }

    /**
     * Get a specific playlist with caching.
     */
    public function getPlaylist(string $playlistId): ?Playlist
    {
        $cacheKey = "playlist:{$playlistId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($playlistId) {
            return Playlist::with(['videos', 'categories'])->find($playlistId);
        });
    }

    /**
     * Get a user's playlist for a specific date with caching.
     */
    public function getUserPlaylistForDate(User $user, Carbon $date): ?Playlist
    {
        $dateString = $date->format('Y-m-d');
        $cacheKey = "playlist:user:{$user->getKey()}:date:{$dateString}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $dateString) {
            return $user->playlists()
                ->where('last_generated_at', 'like', $dateString.'%')
                ->where('type', 'auto')
                ->with('videos')
                ->first();
        });
    }

    /**
     * Store a new playlist.
     */
    public function storePlaylist(Playlist $playlist): void
    {
        try {
            // Save the playlist
            $playlist->save();

            // Clear related cache
            $this->clearUserPlaylistsCache($playlist->user_id);

            if ($playlist->last_generated_at) {
                $dateString = $playlist->last_generated_at->format('Y-m-d');
                $cacheKey = "playlist:user:{$playlist->user_id}:date:{$dateString}";
                Cache::forget($cacheKey);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store playlist', [
                'playlist_id' => $playlist->getKey(),
                'user_id' => $playlist->user_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update playlist visibility.
     */
    public function updateVisibility(Playlist $playlist, string $visibility): void
    {
        try {
            $playlist->setVisibility($visibility);
            $playlist->save();

            // Clear cache for this playlist
            $this->clearPlaylistCache($playlist->getKey());
            $this->clearUserPlaylistsCache($playlist->user_id);
        } catch (\Exception $e) {
            Log::error('Failed to update playlist visibility', [
                'playlist_id' => $playlist->getKey(),
                'user_id' => $playlist->user_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark a video as watched in a playlist.
     */
    public function markVideoAsWatched(Playlist $playlist, string $videoId): bool
    {
        try {
            // Find the playlist item for this video
            /** @var \App\Models\PlaylistItem|null $playlistItem */
            $playlistItem = $playlist->items()
                ->where('source_type', 'youtube_video')
                ->where('source_id', $videoId)
                ->first();

            if (! $playlistItem) {
                return false;
            }

            // Mark as watched
            $playlistItem->is_watched = true;
            $playlistItem->watched_at = now();
            $playlistItem->save();

            // Clear cache for this playlist
            $this->clearPlaylistCache($playlist->getKey());

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark video as watched', [
                'playlist_id' => $playlist->getKey(),
                'video_id' => $videoId,
                'user_id' => $playlist->user_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get trending playlists with caching.
     */
    public function getTrendingPlaylists(int $limit = 10): Collection
    {
        $cacheKey = "playlists:trending:limit:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            // First get IDs with raw query
            $playlistIds = DB::table('playlists')
                ->select('id')
                ->where('visibility', 'public')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->pluck('id')
                ->toArray();

            if (empty($playlistIds)) {
                return collect();
            }

            // Use a different approach to get playlists with relationships
            $playlists = collect();

            // Fetch each playlist individually with relationships
            foreach ($playlistIds as $id) {
                $playlist = (new Playlist)->newQuery()->find($id);
                if ($playlist) {
                    // Load the relationships
                    $playlist->load(['videos', 'user:id,name']);
                    $playlists->push($playlist);
                }
            }

            return $playlists;
        });
    }

    /**
     * Clear playlist cache.
     */
    protected function clearPlaylistCache(string $playlistId): void
    {
        Cache::forget("playlist:{$playlistId}");
    }

    /**
     * Clear user playlists cache.
     */
    protected function clearUserPlaylistsCache(string $userId): void
    {
        // Clear any cached user playlists with different limits
        for ($limit = 5; $limit <= 20; $limit += 5) {
            Cache::forget("playlists:user:{$userId}:limit:{$limit}");
            Cache::forget("playlists:user:{$userId}:withItems:limit:{$limit}");
        }
    }
}
