<?php

namespace Tests\Browser\Admin;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\AdminTestCase;

class SubscriptionsScreenTest extends AdminTestCase
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

        // Create test Reddit subscription
        Subscription::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'subscribable_type' => 'reddit',
            'subscribable_id' => 'test_subreddit',
            'title' => 'Test Subreddit',
            'description' => 'A test subreddit subscription',
            'image_url' => null,
            'external_url' => 'https://reddit.com/r/test_subreddit',
            'external_id' => 'test_subreddit',
            'auto_add' => true,
        ]);

        // Create test YouTube subscription
        Subscription::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'subscribable_type' => 'youtube',
            'subscribable_id' => 'UC123456789',
            'title' => 'Test YouTube Channel',
            'description' => 'A test YouTube channel subscription',
            'image_url' => 'https://example.com/channel_image.jpg',
            'external_url' => 'https://youtube.com/channel/UC123456789',
            'external_id' => 'UC123456789',
            'auto_add' => false,
        ]);
    }

    /**
     * Test the Subscriptions screen loads correctly.
     */
    public function test_subscriptions_screen_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser)
                ->visit('/admin/subscriptions')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Subscriptions')
                ->assertSee('Filter Subscriptions')
                ->assertSee('Test Subreddit')
                ->assertSee('Test YouTube Channel')
                ->assertSee('reddit')
                ->assertSee('youtube');
        });
    }

    /**
     * Test subscription filtering functionality.
     */
    public function test_subscription_filtering(): void
    {
        $this->browse(function (Browser $browser) {
            // Test filtering by title
            $this->loginAsAdmin($browser)
                ->visit('/admin/subscriptions')
                ->waitUntil('document.readyState === "complete"', 10)
                ->type('filter[title]', 'Subreddit')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Subreddit')
                ->assertDontSee('Test YouTube Channel');

            // Test filtering by type
            $browser->visit('/admin/subscriptions')
                ->waitUntil('document.readyState === "complete"', 10)
                ->select('filter[subscribable_type]', 'youtube')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test YouTube Channel')
                ->assertDontSee('Test Subreddit');

            // Test filtering by auto-add status
            $browser->visit('/admin/subscriptions')
                ->waitUntil('document.readyState === "complete"', 10)
                ->select('filter[auto_add]', '1')
                ->press('Filter')
                ->waitUntil('document.readyState === "complete"', 10)
                ->assertSee('Test Subreddit')
                ->assertDontSee('Test YouTube Channel');
        });
    }
}
