<?php

namespace Tests\Browser\Admin;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\User;
use App\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\AdminTestCase;

class PlaylistItemsScreenTest extends AdminTestCase
{
    use DatabaseMigrations;

    /**
     * @var Playlist
     */
    protected $playlist;

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

        // Create test playlist
        $this->playlist = Playlist::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => 'Test Playlist',
            'description' => 'A test playlist for viewing items',
            'is_auto' => false,
            'is_public' => true,
            'external_id' => null,
            'image_url' => null,
        ]);

        // Create test YouTube videos
        $video1 = YoutubeVideo::create([
            'id' => Str::uuid()->toString(),
            'youtube_id' => 'abc123',
            'title' => 'First Test Video',
            'description' => 'This is the first test video',
            'channel_id' => 'UC123456789',
            'channel_title' => 'Test Channel',
            'thumbnail_url' => 'https://i.ytimg.com/vi/abc123/default.jpg',
            'published_at' => now()->subDays(2),
            'view_count' => 1000,
            'like_count' => 100,
            'comment_count' => 50,
            'duration' => 'PT5M30S',
            'reddit_post_id' => null,
        ]);

        $video2 = YoutubeVideo::create([
            'id' => Str::uuid()->toString(),
            'youtube_id' => 'def456',
            'title' => 'Second Test Video',
            'description' => 'This is the second test video',
            'channel_id' => 'UC123456789',
            'channel_title' => 'Test Channel',
            'thumbnail_url' => 'https://i.ytimg.com/vi/def456/default.jpg',
            'published_at' => now()->subDays(1),
            'view_count' => 2000,
            'like_count' => 200,
            'comment_count' => 100,
            'duration' => 'PT4M20S',
            'reddit_post_id' => null,
        ]);

        // Create playlist items
        PlaylistItem::create([
            'id' => Str::uuid()->toString(),
            'playlist_id' => $this->playlist->id,
            'video_id' => $video1->id,
            'position' => 1,
            'watched' => false,
        ]);

        PlaylistItem::create([
            'id' => Str::uuid()->toString(),
            'playlist_id' => $this->playlist->id,
            'video_id' => $video2->id,
            'position' => 2,
            'watched' => true,
        ]);
    }

    /**
     * Test the Playlist Items screen loads correctly.
     */
    public function test_playlist_items_screen_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit("/admin/playlists/{$this->playlist->id}/items")
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Playlist - Videos')
                ->assertSee('Manage videos in this playlist')
                ->assertSee('Filter Playlist Videos')
                ->assertSee('First Test Video')
                ->assertSee('Second Test Video')
                ->assertSee('Test Channel');
        });
    }

    /**
     * Test playlist item filtering functionality.
     */
    public function test_playlist_item_filtering(): void
    {
        $this->browse(function (Browser $browser) {
            // Test filtering by title
            $this->loginAsAdmin($browser)
                ->visit("/admin/playlists/{$this->playlist->id}/items")
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'First')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('First Test Video')
                ->assertDontSee('Second Test Video');

            // Test filtering by watched status
            $browser->visit("/admin/playlists/{$this->playlist->id}/items")
                ->waitUntil('document.readyState === "complete"', 10)
                ->select('filter[watched]', '1')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Second Test Video')
                ->assertDontSee('First Test Video');

            // Test filtering by unwatched status
            $browser->visit("/admin/playlists/{$this->playlist->id}/items")
                ->waitUntil('document.readyState === "complete"', 10)
                ->select('filter[watched]', '0')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('First Test Video')
                ->assertDontSee('Second Test Video');
        });
    }

    /**
     * Test item action buttons functionality.
     */
    public function test_item_action_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit("/admin/playlists/{$this->playlist->id}/items")
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Back to Playlists')
                ->assertSee('Add Videos')
                ->assertSee('Reorder Videos')
                ->assertSee('Mark All as Watched');

            // Test going back to playlists
            $browser->clickLink('Back to Playlists')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertPathIs('/admin/playlists')
                ->assertSee('Playlists');
        });
    }
}
