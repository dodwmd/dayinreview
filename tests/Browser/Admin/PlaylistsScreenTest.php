<?php

namespace Tests\Browser\Admin;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\AdminTestCase;

class PlaylistsScreenTest extends AdminTestCase
{
    use DatabaseMigrations;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Create a custom playlist
        Playlist::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => 'Test Custom Playlist',
            'description' => 'A test custom playlist',
            'is_auto' => false,
            'is_public' => true,
            'external_id' => null,
            'image_url' => null,
        ]);

        // Create an auto playlist
        Playlist::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => 'Test Auto Playlist',
            'description' => 'A test auto-generated playlist',
            'is_auto' => true,
            'is_public' => false,
            'external_id' => null,
            'image_url' => null,
        ]);
    }

    /**
     * Test the Playlists screen loads correctly.
     */
    public function test_playlists_screen_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit('/admin/playlists')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Playlists')
                ->assertSee('Filter Playlists')
                ->assertSee('Test Custom Playlist')
                ->assertSee('Test Auto Playlist')
                ->assertSee('Test User'); // owner
        });
    }

    /**
     * Test playlist filtering functionality.
     */
    public function test_playlist_filtering(): void
    {
        $this->browse(function (Browser $browser) {
            // Test filtering by title
            $this->loginAsAdmin($browser)
                ->visit('/admin/playlists')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'Custom')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Custom Playlist')
                ->assertDontSee('Test Auto Playlist');

            // Test filtering by playlist type
            $browser->visit('/admin/playlists')
                ->waitUntil('document.readyState === "complete"', 10)
                ->select('filter[is_auto]', '1')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Auto Playlist')
                ->assertDontSee('Test Custom Playlist');

            // Test filtering by visibility
            $browser->visit('/admin/playlists')
                ->waitUntil('document.readyState === "complete"', 10)
                ->select('filter[is_public]', '1')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Custom Playlist')
                ->assertDontSee('Test Auto Playlist');
        });
    }

    /**
     * Test playlist view items functionality.
     */
    public function test_playlist_view_items_link(): void
    {
        $this->browse(function (Browser $browser) {
            $playlist = Playlist::where('title', 'Test Custom Playlist')->first();

            $this->loginAsAdmin($browser)
                ->visit('/admin/playlists')
                ->waitUntil('document.readyState === "complete"', 10)
                ->click('.dropdown-toggle') // Open the dropdown
                ->waitFor('.dropdown-menu')
                ->clickLink('View Items')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertPathIs("/admin/playlists/{$playlist->id}/items")
                ->assertSee("{$playlist->title} - Videos")
                ->assertSee('Manage videos in this playlist');
        });
    }
}
