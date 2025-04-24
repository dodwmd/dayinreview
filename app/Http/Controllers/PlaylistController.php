<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Services\Playlist\PlaylistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaylistController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly PlaylistService $playlistService
    ) {}

    /**
     * Display a listing of the user's playlists.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $playlists = $this->playlistService->getUserPlaylists($user);

        return Inertia::render('Playlists/Index', [
            'playlists' => $playlists->map(function ($playlist) {
                return [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                    'date' => $playlist->last_generated_at,
                    'is_public' => $playlist->visibility === 'public',
                    'thumbnail_url' => $playlist->thumbnail_url,
                    'video_count' => $playlist->videos->count(),
                    'created_at' => $playlist->created_at,
                    'updated_at' => $playlist->updated_at,
                ];
            }),
            'youtubeConnected' => ! empty($user->youtube_token),
        ]);
    }

    /**
     * Generate a new daily playlist.
     */
    public function generate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $playlist = $this->playlistService->generateDailyPlaylist($user);

        if (! $playlist) {
            return redirect()->route('playlists.index')
                ->with('error', 'Failed to generate playlist. Please try again later.');
        }

        return redirect()->route('playlists.show', $playlist->getKey())
            ->with('status', 'playlist-generated');
    }

    /**
     * Display the specified playlist.
     */
    public function show(Request $request, string $id): Response|RedirectResponse
    {
        $user = $request->user();
        $playlist = $this->playlistService->getPlaylist($user, $id);

        if (! $playlist) {
            return redirect()->route('playlists.index')
                ->with('error', 'Playlist not found.');
        }

        // Format playlist data for the frontend
        $formattedPlaylist = [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'description' => $playlist->description,
            'thumbnail_url' => $playlist->thumbnail_url,
            'date' => $playlist->last_generated_at,
            'is_public' => $playlist->visibility === 'public',
            'view_count' => $playlist->view_count,
            'created_at' => $playlist->created_at,
            'updated_at' => $playlist->updated_at,
            'videos' => $playlist->videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'youtube_id' => $video->youtube_id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'thumbnail_url' => $video->thumbnail_url,
                    'channel_id' => $video->channel_id,
                    'channel_title' => $video->channel_title,
                    'duration_seconds' => $video->duration_seconds,
                    'pivot' => [
                        'position' => $video->pivot->position,
                        'watched' => $video->pivot->is_watched,
                        'source' => $video->pivot->notes === 'Trending' ? 'trending' : 'subscription',
                    ],
                ];
            })->sortBy('pivot.position')->values(),
        ];

        return Inertia::render('Playlists/Show', [
            'playlist' => $formattedPlaylist,
            'youtubeConnected' => ! empty($user->youtube_token),
        ]);
    }

    /**
     * Update the visibility of a playlist.
     */
    public function updateVisibility(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'is_public' => 'required|boolean',
        ]);

        $user = $request->user();
        $playlist = $this->playlistService->updateVisibility($user, $id, $validated['is_public']);

        if (! $playlist) {
            return redirect()->route('playlists.index')
                ->with('error', 'Failed to update playlist visibility.');
        }

        $status = $validated['is_public'] ? 'playlist-made-public' : 'playlist-made-private';

        return redirect()->route('playlists.show', $id)
            ->with('status', $status);
    }

    /**
     * Mark a video as watched.
     */
    public function markWatched(Request $request, string $id, string $videoId): RedirectResponse
    {
        $user = $request->user();
        $success = $this->playlistService->markVideoAsWatched($user, $id, $videoId);

        if (! $success) {
            return redirect()->route('playlists.show', $id)
                ->with('error', 'Failed to mark video as watched.');
        }

        return redirect()->route('playlists.show', $id)
            ->with('status', 'video-marked-watched');
    }
}
