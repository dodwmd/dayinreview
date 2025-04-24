<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test viewing subscriptions index page.
     */
    public function test_subscriptions_index_page(): void
    {
        // Create a user with subscriptions
        $user = User::factory()->create();

        // Create some subscription categories
        $category1 = SubscriptionCategory::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Tech',
            'description' => 'Technology related subscriptions',
        ]);

        $category2 = SubscriptionCategory::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Entertainment',
            'description' => 'Entertainment related subscriptions',
        ]);

        // Create subscriptions in different categories
        $subscription1 = Subscription::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Programming',
            'source_type' => 'App\\Models\\RedditSubreddit',
            'source_id' => 'programming',
            'source_data' => json_encode(['type' => 'subreddit']),
            'is_active' => true,
        ]);

        $subscription2 = Subscription::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Google Developers',
            'source_type' => 'App\\Models\\YoutubeChannel',
            'source_id' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
            'source_data' => json_encode(['type' => 'channel']),
            'is_active' => true,
        ]);

        // Attach subscriptions to categories
        $category1->subscriptions()->attach($subscription1->id);
        $category2->subscriptions()->attach($subscription2->id);

        // Visit the subscriptions index page
        $response = $this->actingAs($user)
            ->get(route('subscriptions.index'));

        // Check the response
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Subscriptions/Index')
                ->has('subscriptions')
                ->has('categories')
            );
    }

    /**
     * Test creating a new subscription category.
     */
    public function test_create_subscription_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('subscription-categories.store'), [
                'name' => 'New Category',
                'description' => 'A new category for subscriptions',
            ]);

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Category created successfully!');

        $this->assertDatabaseHas('subscription_categories', [
            'user_id' => $user->id,
            'name' => 'New Category',
            'description' => 'A new category for subscriptions',
        ]);
    }

    /**
     * Test updating a subscription category.
     */
    public function test_update_subscription_category(): void
    {
        $user = User::factory()->create();
        
        $category = SubscriptionCategory::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($user)
            ->put(route('subscription-categories.update', $category->id), [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Category updated successfully!');

        $this->assertDatabaseHas('subscription_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    /**
     * Test deleting a subscription category.
     */
    public function test_delete_subscription_category(): void
    {
        $user = User::factory()->create();
        
        $category = SubscriptionCategory::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Category to Delete',
            'description' => 'This category will be deleted',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('subscription-categories.destroy', $category->id));

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Category deleted successfully!');

        $this->assertDatabaseMissing('subscription_categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * Test subscribing to a Reddit subreddit.
     */
    public function test_subscribe_to_subreddit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('subscriptions.store'), [
                'name' => 'Programming',
                'source_type' => 'App\\Models\\RedditSubreddit',
                'source_id' => 'programming',
                'source_data' => json_encode(['type' => 'subreddit']),
                'categories' => [],
            ]);

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Subscription created successfully!');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'name' => 'Programming',
            'source_type' => 'App\\Models\\RedditSubreddit',
            'source_id' => 'programming',
            'is_active' => 1,
        ]);
    }

    /**
     * Test subscribing to a YouTube channel.
     */
    public function test_subscribe_to_youtube_channel(): void
    {
        $user = User::factory()->create([
            'youtube_access_token' => 'fake-access-token',
            'youtube_refresh_token' => 'fake-refresh-token',
            'youtube_token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('subscriptions.store'), [
                'name' => 'Google Developers',
                'source_type' => 'App\\Models\\YoutubeChannel',
                'source_id' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
                'source_data' => json_encode(['type' => 'channel']),
                'categories' => [],
            ]);

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Subscription created successfully!');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'name' => 'Google Developers',
            'source_type' => 'App\\Models\\YoutubeChannel',
            'source_id' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
            'is_active' => 1,
        ]);
    }

    /**
     * Test updating subscription settings.
     */
    public function test_update_subscription_settings(): void
    {
        $user = User::factory()->create();
        
        $subscription = Subscription::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Original Name',
            'source_type' => 'App\\Models\\RedditSubreddit',
            'source_id' => 'programming',
            'source_data' => json_encode(['type' => 'subreddit']),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->put(route('subscriptions.update', $subscription->id), [
                'name' => 'Updated Name',
                'is_active' => false,
                'categories' => [],
            ]);

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Subscription updated successfully!');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'name' => 'Updated Name',
            'is_active' => 0,
        ]);
    }

    /**
     * Test deleting a subscription.
     */
    public function test_delete_subscription(): void
    {
        $user = User::factory()->create();
        
        $subscription = Subscription::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Subscription to Delete',
            'source_type' => 'App\\Models\\RedditSubreddit',
            'source_id' => 'programming',
            'source_data' => json_encode(['type' => 'subreddit']),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('subscriptions.destroy', $subscription->id));

        $response->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success', 'Subscription deleted successfully!');

        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
        ]);
    }
}
