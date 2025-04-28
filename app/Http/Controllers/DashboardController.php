<?php

namespace App\Http\Controllers;

use App\Repositories\PlaylistRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly PlaylistRepository $playlistRepository
    ) {}

    /**
     * Show the application dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get recent playlists
        $recentPlaylists = $this->playlistRepository->getUserPlaylists($user, 6);

        // Get subscription stats
        $subscriptionStats = [
            'youtube' => $user->subscriptions()->youtube()->count(),
            'reddit' => $user->subscriptions()->reddit()->count(),
        ];

        $playlistsData = [];
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Playlist> $recentPlaylists */
        /** @var \App\Models\Playlist $playlist */
        foreach ($recentPlaylists as $playlist) {
            $playlistsData[] = [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'thumbnail_url' => $playlist->thumbnail_url,
                'video_count' => $playlist->videos->count(),
                'created_at' => $playlist->created_at,
            ];
        }

        return Inertia::render('Dashboard', [
            'recentPlaylists' => $playlistsData,
            'subscriptionStats' => $subscriptionStats,
            'youtubeConnected' => ! empty($user->youtube_token),
        ]);
    }
}
