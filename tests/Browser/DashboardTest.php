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

class DashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test the dashboard page loads with user data.
     */
    public function test_dashboard_displays_user_data(): void
    {
        $user = User::factory()->create();
        
        // Create some playlists for the user
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
            'name' => 'Recent Playlist',
            'type' => 'auto',
            'visibility' => 'private',
            'last_generated_at' => Carbon::today(),
            'view_count' => 5,
        ]);
        
        // Add some videos to the playlist
        $video = YoutubeVideo::factory()->create([
            'title' => 'Test Dashboard Video',
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

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('Recent Activity')
                    ->assertSee('Recent Playlist')
                    ->assertSee('Test Dashboard Video');
        });
    }

    /**
     * Test navigation from dashboard to playlists.
     */
    public function test_navigate_to_playlists(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->clickLink('Playlists')
                    ->assertPathIs('/playlists')
                    ->assertSee('Your Playlists');
        });
    }

    /**
     * Test navigation from dashboard to subscriptions.
     */
    public function test_navigate_to_subscriptions(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->clickLink('Subscriptions')
                    ->assertPathIs('/subscriptions')
                    ->assertSee('Your Subscriptions');
        });
    }

    /**
     * Test generating a new playlist from dashboard.
     */
    public function test_generate_playlist_from_dashboard(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->press('Generate New Playlist')
                    ->waitForText('Playlist generated successfully!')
                    ->assertPathBeginsWith('/playlists/')
                    ->assertSee('Daily Playlist');
        });
    }

    /**
     * Test viewing account stats on dashboard.
     */
    public function test_view_account_stats(): void
    {
        $user = User::factory()->create();
        
        // Create multiple playlists with different stats
        Playlist::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'auto',
            'visibility' => 'private',
        ]);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->assertSee('Account Statistics')
                    ->assertSee('Total Playlists: 3')
                    ->assertSee('Videos Watched');
        });
    }
}
