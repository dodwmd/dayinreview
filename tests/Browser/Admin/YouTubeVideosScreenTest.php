<?php

namespace Tests\Browser\Admin;

use App\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\AdminTestCase;

class YouTubeVideosScreenTest extends AdminTestCase
{
    use DatabaseMigrations;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test YouTube video
        YoutubeVideo::create([
            'id' => Str::uuid()->toString(),
            'youtube_id' => 'abc123',
            'title' => 'Test YouTube Video',
            'description' => 'This is a test video description',
            'channel_id' => 'UC123456789',
            'channel_title' => 'Test Channel',
            'thumbnail_url' => 'https://i.ytimg.com/vi/abc123/default.jpg',
            'published_at' => now()->subDays(1),
            'view_count' => 1000,
            'like_count' => 100,
            'comment_count' => 50,
            'duration' => 'PT5M30S',
            'reddit_post_id' => null,
        ]);
    }

    /**
     * Test the YouTube videos screen loads correctly.
     */
    public function test_youtube_videos_screen_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit('/admin/content/youtube')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('YouTube Videos')
                ->assertSee('Filter Videos')
                ->assertSee('Test YouTube Video')
                ->assertSee('Test Channel')
                ->assertSee('1,000'); // formatted view count
        });
    }

    /**
     * Test YouTube video filtering functionality.
     */
    public function test_youtube_video_filtering(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit('/admin/content/youtube')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'Test')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test YouTube Video');

            // Test filtering by channel
            $browser->visit('/admin/content/youtube')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[channel_title]', 'Test Channel')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test YouTube Video');

            // Test with a term that shouldn't match
            $browser->visit('/admin/content/youtube')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'NonexistentVideoTitle')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertDontSee('Test YouTube Video');
        });
    }
}
