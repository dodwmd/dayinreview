<?php

namespace Tests\Browser\Admin;

use App\Models\RedditPost;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\AdminTestCase;

class RedditPostsScreenTest extends AdminTestCase
{
    use DatabaseMigrations;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test Reddit post
        RedditPost::create([
            'id' => Str::uuid()->toString(),
            'reddit_id' => 'test123',
            'title' => 'Test Reddit Post',
            'subreddit' => 'test',
            'author' => 'testuser',
            'permalink' => 'https://reddit.com/r/test/comments/123/test_post',
            'url' => 'https://reddit.com/r/test/comments/123/test_post',
            'score' => 100,
            'num_comments' => 10,
            'created_utc' => now()->timestamp,
            'is_self' => true,
            'selftext' => 'This is a test post',
            'selftext_html' => '<p>This is a test post</p>',
            'thumbnail' => '',
            'has_detected_youtube' => false,
        ]);
    }

    /**
     * Test the Reddit posts screen loads correctly.
     */
    public function test_reddit_posts_screen_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit('/admin/content/reddit')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Reddit Posts')
                ->assertSee('Filter Posts')
                ->assertSee('Test Reddit Post')
                ->assertSee('test')
                ->assertSee('testuser');
        });
    }

    /**
     * Test Reddit post filtering functionality.
     */
    public function test_reddit_post_filtering(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit('/admin/content/reddit')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'Test')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Reddit Post');

            // Test with a term that shouldn't match
            $browser->visit('/admin/content/reddit')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'NonexistentPostTitle')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertDontSee('Test Reddit Post');
        });
    }
}
