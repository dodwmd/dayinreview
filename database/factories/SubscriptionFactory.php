<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscribable_type' => $this->faker->randomElement(['App\\Models\\RedditSubreddit', 'App\\Models\\YoutubeChannel']),
            'subscribable_id' => $this->faker->uuid(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'thumbnail_url' => $this->faker->optional()->imageUrl(),
            'is_favorite' => $this->faker->boolean(20), // 20% chance of being favorite
            'priority' => $this->faker->numberBetween(0, 5),
        ];
    }

    /**
     * Configure the factory for a Reddit subscription.
     *
     * @return $this
     */
    public function reddit()
    {
        return $this->state(function (array $attributes) {
            return [
                'subscribable_type' => 'App\\Models\\RedditSubreddit',
                'subscribable_id' => $this->faker->word(), // Subreddit name
                'name' => 'r/' . $this->faker->word(),
            ];
        });
    }

    /**
     * Configure the factory for a YouTube subscription.
     *
     * @return $this
     */
    public function youtube()
    {
        return $this->state(function (array $attributes) {
            return [
                'subscribable_type' => 'App\\Models\\YoutubeChannel',
                'subscribable_id' => 'UC' . $this->faker->regexify('[A-Za-z0-9]{22}'), // YouTube channel ID format
                'name' => $this->faker->company() . ' Channel',
            ];
        });
    }
}
