<?php

namespace Tests\Unit\Services\Playlist;

use App\Models\Playlist;
use App\Models\User;
use App\Repositories\ContentRepository;
use App\Repositories\PlaylistRepository;
use App\Services\Playlist\PlaylistService;
use App\Services\YouTube\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PlaylistServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContentRepository $mockContentRepository;

    protected YouTubeService $mockYoutubeService;

    protected PlaylistRepository $mockPlaylistRepository;

    protected PlaylistService $playlistService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services and repositories
        $this->mockContentRepository = Mockery::mock(ContentRepository::class);
        $this->mockYoutubeService = Mockery::mock(YouTubeService::class);
        $this->mockPlaylistRepository = Mockery::mock(PlaylistRepository::class);

        // Create the playlist service with mocked dependencies
        $this->playlistService = new PlaylistService(
            $this->mockContentRepository,
            $this->mockYoutubeService,
            $this->mockPlaylistRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test generating a daily playlist when one doesn't exist.
     */
    public function test_generate_daily_playlist_new(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Set test date
        $date = Carbon::create(2025, 4, 20);

        // Create a playlist for the test result
        $newPlaylist = new Playlist;
        $newPlaylist->id = (string) Str::uuid();
        $newPlaylist->user_id = $user->id;
        $newPlaylist->last_generated_at = $date;
        $newPlaylist->visibility = 'private';

        // Mock the PlaylistRepository to return null (no existing playlist)
        $this->mockPlaylistRepository->shouldReceive('getUserPlaylistForDate')
            ->once()
            ->with(Mockery::type(User::class), Mockery::type(Carbon::class))
            ->andReturn(null);

        // Mock trending videos
        $trendingVideos = collect([
            [
                'id' => 'trending-video-1',
                'title' => 'Trending Video 1',
                'description' => 'Description for trending video 1',
                'thumbnail' => 'https://example.com/thumb1.jpg',
                'channel_id' => 'channel-1',
                'channel_title' => 'Channel 1',
                'duration_seconds' => 300,
            ],
            [
                'id' => 'trending-video-2',
                'title' => 'Trending Video 2',
                'description' => 'Description for trending video 2',
                'thumbnail' => 'https://example.com/thumb2.jpg',
                'channel_id' => 'channel-2',
                'channel_title' => 'Channel 2',
                'duration_seconds' => 600,
            ],
        ]);

        // Mock subscription videos
        $subscriptionVideos = collect([
            [
                'id' => 'subscription-video-1',
                'title' => 'Subscription Video 1',
                'description' => 'Description for subscription video 1',
                'thumbnail' => 'https://example.com/sub-thumb1.jpg',
                'channel_id' => 'sub-channel-1',
                'channel_title' => 'Subscription Channel 1',
                'duration_seconds' => 450,
            ],
        ]);

        // Mock the ContentRepository to return trending videos
        $this->mockContentRepository->shouldReceive('getTrendingVideos')
            ->once()
            ->with(10)
            ->andReturn($trendingVideos);

        // Mock the YouTube service to return subscription videos
        $this->mockYoutubeService->shouldReceive('getChannelVideos')
            ->andReturn(['videos' => $subscriptionVideos]);

        // Mock the PlaylistRepository storePlaylist method to save and return the playlist
        $this->mockPlaylistRepository->shouldReceive('storePlaylist')
            ->once()
            ->with(Mockery::type(Playlist::class))
            ->andReturnUsing(function ($playlist) use ($newPlaylist) {
                // Return the pre-created playlist instead of the input
                return $newPlaylist;
            });

        // Run the playlist generation
        $playlist = $this->playlistService->generateDailyPlaylist($user, $date);

        // Verify the playlist was created
        $this->assertNotNull($playlist);
        $this->assertEquals($user->id, $playlist->user_id);
        $this->assertEquals($date->format('Y-m-d'), $playlist->last_generated_at->format('Y-m-d'));
        $this->assertEquals('private', $playlist->visibility);
    }

    /**
     * Test generating a daily playlist when one already exists.
     */
    public function test_generate_daily_playlist_existing(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Set test date
        $date = Carbon::create(2025, 4, 20);

        // Create an existing playlist
        $existingPlaylist = new Playlist;
        $existingPlaylist->id = (string) Str::uuid();
        $existingPlaylist->user_id = $user->id;
        $existingPlaylist->last_generated_at = $date;
        $existingPlaylist->visibility = 'private';

        // Mock the PlaylistRepository to return the existing playlist
        $this->mockPlaylistRepository->shouldReceive('getUserPlaylistForDate')
            ->once()
            ->with(Mockery::type(User::class), Mockery::type(Carbon::class))
            ->andReturn($existingPlaylist);

        // Run the playlist generation
        $playlist = $this->playlistService->generateDailyPlaylist($user, $date);

        // Verify the existing playlist was returned
        $this->assertNotNull($playlist);
        $this->assertEquals($existingPlaylist->id, $playlist->id);
    }

    /**
     * Test generating a daily playlist with no videos available.
     */
    public function test_generate_daily_playlist_no_videos(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Set test date
        $date = Carbon::create(2025, 4, 20);

        // Mock the PlaylistRepository to return null (no existing playlist)
        $this->mockPlaylistRepository->shouldReceive('getUserPlaylistForDate')
            ->once()
            ->with(Mockery::type(User::class), Mockery::type(Carbon::class))
            ->andReturn(null);

        // Mock empty trending and subscription videos
        $this->mockContentRepository->shouldReceive('getTrendingVideos')
            ->once()
            ->with(10)
            ->andReturn(collect());

        // Mock an empty subscription videos response
        $this->mockYoutubeService->shouldReceive('getChannelVideos')
            ->andReturn(['videos' => collect()]);

        // Run the playlist generation
        $playlist = $this->playlistService->generateDailyPlaylist($user, $date);

        // Verify no playlist was created when no videos are available
        $this->assertNull($playlist);
    }

    /**
     * Test getting user playlists.
     */
    public function test_get_user_playlists(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Create mock playlists
        $mockPlaylists = collect([
            new Playlist([
                'user_id' => $user->id,
                'last_generated_at' => Carbon::today(),
                'visibility' => 'private',
            ]),
            new Playlist([
                'user_id' => $user->id,
                'last_generated_at' => Carbon::yesterday(),
                'visibility' => 'public',
            ]),
        ]);

        // Mock the PlaylistRepository
        $this->mockPlaylistRepository->shouldReceive('getUserPlaylists')
            ->once()
            ->with(Mockery::type(User::class), 10)
            ->andReturn($mockPlaylists);

        // Get the user's playlists
        $playlists = $this->playlistService->getUserPlaylists($user);

        // Verify the playlists were returned
        $this->assertCount(2, $playlists);
    }

    /**
     * Test getting a specific playlist.
     */
    public function test_get_playlist(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Create a playlist ID
        $playlistId = (string) Str::uuid();

        // Create a mock playlist
        $mockPlaylist = new Playlist;
        $mockPlaylist->id = $playlistId;
        $mockPlaylist->user_id = $user->id;
        $mockPlaylist->last_generated_at = Carbon::today();
        $mockPlaylist->visibility = 'private';

        // Mock the PlaylistRepository
        $this->mockPlaylistRepository->shouldReceive('getPlaylist')
            ->once()
            ->with($playlistId)
            ->andReturn($mockPlaylist);

        // Get the playlist
        $playlist = $this->playlistService->getPlaylist($user, $playlistId);

        // Verify the playlist was returned
        $this->assertNotNull($playlist);
        $this->assertEquals($playlistId, $playlist->id);
    }

    /**
     * Test getting a playlist that doesn't belong to the user.
     */
    public function test_get_playlist_wrong_user(): void
    {
        // Create users using factory
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create a playlist ID
        $playlistId = (string) Str::uuid();

        // Create a mock playlist belonging to the other user
        $mockPlaylist = new Playlist;
        $mockPlaylist->id = $playlistId;
        $mockPlaylist->user_id = $otherUser->id;
        $mockPlaylist->last_generated_at = Carbon::today();
        $mockPlaylist->visibility = 'private';

        // Mock the PlaylistRepository
        $this->mockPlaylistRepository->shouldReceive('getPlaylist')
            ->once()
            ->with($playlistId)
            ->andReturn($mockPlaylist);

        // Try to get the playlist that belongs to another user
        $playlist = $this->playlistService->getPlaylist($user, $playlistId);

        // Verify null is returned when the playlist doesn't belong to the user
        $this->assertNull($playlist);
    }

    /**
     * Test updating playlist visibility.
     */
    public function test_update_visibility(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Create a playlist ID
        $playlistId = (string) Str::uuid();

        // Create a mock playlist
        $mockPlaylist = new Playlist;
        $mockPlaylist->id = $playlistId;
        $mockPlaylist->user_id = $user->id;
        $mockPlaylist->last_generated_at = Carbon::today();
        $mockPlaylist->visibility = 'private';

        // Mock the PlaylistRepository
        $this->mockPlaylistRepository->shouldReceive('getPlaylist')
            ->once()
            ->with($playlistId)
            ->andReturn($mockPlaylist);

        $this->mockPlaylistRepository->shouldReceive('updateVisibility')
            ->once()
            ->with(Mockery::type(Playlist::class), 'public')
            ->andReturnUsing(function ($playlist, $visibility) {
                $playlist->visibility = $visibility;

                return $playlist;
            });

        // Update the playlist visibility
        $playlist = $this->playlistService->updateVisibility($user, $playlistId, true);

        // Verify the playlist visibility was updated
        $this->assertNotNull($playlist);
        $this->assertEquals('public', $playlist->visibility);
    }

    /**
     * Test marking a video as watched.
     */
    public function test_mark_video_as_watched(): void
    {
        // Create a user using factory
        $user = User::factory()->create();

        // Create a playlist ID
        $playlistId = (string) Str::uuid();
        $videoId = (string) Str::uuid();

        // Create a mock playlist
        $mockPlaylist = new Playlist;
        $mockPlaylist->id = $playlistId;
        $mockPlaylist->user_id = $user->id;
        $mockPlaylist->last_generated_at = Carbon::today();
        $mockPlaylist->visibility = 'private';

        // Mock the PlaylistRepository
        $this->mockPlaylistRepository->shouldReceive('getPlaylist')
            ->once()
            ->with($playlistId)
            ->andReturn($mockPlaylist);

        $this->mockPlaylistRepository->shouldReceive('markVideoAsWatched')
            ->once()
            ->with(Mockery::type(Playlist::class), $videoId)
            ->andReturn(true);

        // Mark the video as watched
        $result = $this->playlistService->markVideoAsWatched($user, $playlistId, $videoId);

        // Verify the operation was successful
        $this->assertTrue($result);
    }
}
