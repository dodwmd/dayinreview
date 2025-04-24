<?php

namespace Tests\Browser;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\User;
use App\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PlaylistTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test viewing the playlist index page.
     */
    public function test_playlist_index_page(): void
    {
        $user = User::factory()->create();
        
        // Create some playlists for the user
        Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'First Playlist',
            'type' => 'auto',
            'visibility' => 'private',
            'last_generated_at' => Carbon::now()->subDays(1),
        ]);
        
        Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Second Playlist',
            'type' => 'custom',
            'visibility' => 'public',
            'last_generated_at' => Carbon::now()->subDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/playlists')
                    ->assertSee('Your Playlists')
                    ->assertSee('First Playlist')
                    ->assertSee('Second Playlist')
                    ->assertSee('Generate New Playlist');
        });
    }

    /**
     * Test generating a new playlist.
     */
    public function test_generate_new_playlist(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/playlists')
                    ->press('Generate New Playlist')
                    ->waitForText('Playlist generated successfully!')
                    ->assertPathBeginsWith('/playlists/')
                    ->assertSee('Daily Playlist');
        });
    }

    /**
     * Test viewing a playlist and its videos.
     */
    public function test_view_playlist_with_videos(): void
    {
        $user = User::factory()->create();
        
        // Create a playlist with videos
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Playlist',
            'type' => 'auto',
            'visibility' => 'private',
            'last_generated_at' => Carbon::now(),
        ]);
        
        // Create some videos
        $video1 = YoutubeVideo::factory()->create([
            'title' => 'First Test Video',
            'channel_title' => 'Test Channel',
            'duration' => 'PT5M30S',
        ]);
        
        $video2 = YoutubeVideo::factory()->create([
            'title' => 'Second Test Video',
            'channel_title' => 'Another Channel',
            'duration' => 'PT3M45S',
        ]);
        
        // Add videos to playlist
        PlaylistItem::create([
            'id' => Str::uuid(),
            'playlist_id' => $playlist->id,
            'source_type' => 'App\\Models\\YoutubeVideo',
            'source_id' => $video1->id,
            'position' => 1,
            'is_watched' => false,
            'added_at' => now(),
        ]);
        
        PlaylistItem::create([
            'id' => Str::uuid(),
            'playlist_id' => $playlist->id,
            'source_type' => 'App\\Models\\YoutubeVideo',
            'source_id' => $video2->id,
            'position' => 2,
            'is_watched' => false,
            'added_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $playlist) {
            $browser->loginAs($user)
                    ->visit('/playlists/' . $playlist->id)
                    ->assertSee('Test Playlist')
                    ->assertSee('First Test Video')
                    ->assertSee('Second Test Video')
                    ->assertSee('Test Channel')
                    ->assertSee('Another Channel');
        });
    }

    /**
     * Test toggling playlist visibility.
     */
    public function test_toggle_playlist_visibility(): void
    {
        $user = User::factory()->create();
        
        // Create a private playlist
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Private Playlist',
            'visibility' => 'private',
            'last_generated_at' => Carbon::now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $playlist) {
            $browser->loginAs($user)
                    ->visit('/playlists/' . $playlist->id)
                    ->assertSee('Private Playlist')
                    ->assertSee('Private') // Current visibility status
                    ->click('@toggle-visibility') // Using a Dusk attribute selector
                    ->waitForText('Playlist visibility updated successfully!')
                    ->assertSee('Public'); // Updated visibility status
            
            // Refresh and check database was updated
            $browser->refresh()
                    ->assertSee('Public');
        });
    }

    /**
     * Test marking a video as watched.
     */
    public function test_mark_video_as_watched(): void
    {
        $user = User::factory()->create();
        
        // Create a playlist with a video
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Watch Test Playlist',
            'visibility' => 'private',
            'last_generated_at' => Carbon::now(),
        ]);
        
        $video = YoutubeVideo::factory()->create([
            'title' => 'Video To Watch',
        ]);
        
        PlaylistItem::create([
            'id' => Str::uuid(),
            'playlist_id' => $playlist->id,
            'source_type' => 'App\\Models\\YoutubeVideo',
            'source_id' => $video->id,
            'position' => 1,
            'is_watched' => false,
            'added_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $playlist) {
            $browser->loginAs($user)
                    ->visit('/playlists/' . $playlist->id)
                    ->assertSee('Video To Watch')
                    ->assertPresent('@unwatched-video') // Using a Dusk attribute for unwatched status
                    ->click('@mark-watched-button') // Using a Dusk attribute selector
                    ->waitForText('Video marked as watched!')
                    ->assertPresent('@watched-video'); // Check for watched status indicator
            
            // Refresh and verify persistence
            $browser->refresh()
                    ->assertPresent('@watched-video');
        });
    }

    /**
     * Test playlist navigation and video player.
     */
    public function test_playlist_video_player(): void
    {
        $user = User::factory()->create();
        
        // Create a playlist with multiple videos
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Player Test Playlist',
            'visibility' => 'private',
            'last_generated_at' => Carbon::now(),
        ]);
        
        // Create some videos
        $video1 = YoutubeVideo::factory()->create([
            'title' => 'First Player Video',
            'youtube_id' => 'dQw4w9WgXcQ', // Use a real YouTube ID
        ]);
        
        $video2 = YoutubeVideo::factory()->create([
            'title' => 'Second Player Video',
            'youtube_id' => 'Zi_XLOBDo_Y', // Another real YouTube ID
        ]);
        
        // Add videos to playlist
        PlaylistItem::create([
            'id' => Str::uuid(),
            'playlist_id' => $playlist->id,
            'source_type' => 'App\\Models\\YoutubeVideo',
            'source_id' => $video1->id,
            'position' => 1,
            'is_watched' => false,
            'added_at' => now(),
        ]);
        
        PlaylistItem::create([
            'id' => Str::uuid(),
            'playlist_id' => $playlist->id,
            'source_type' => 'App\\Models\\YoutubeVideo',
            'source_id' => $video2->id,
            'position' => 2,
            'is_watched' => false,
            'added_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user, $playlist) {
            $browser->loginAs($user)
                    ->visit('/playlists/' . $playlist->id)
                    ->assertSee('Player Test Playlist')
                    ->assertSee('First Player Video')
                    
                    // Check video player is loaded
                    ->assertPresent('#youtube-player-container')
                    
                    // Navigate to next video
                    ->click('@next-video-button')
                    ->waitFor('#youtube-player-container')
                    ->assertSee('Second Player Video')
                    ->assertSee('Currently Playing: Second Player Video');
        });
    }
}
