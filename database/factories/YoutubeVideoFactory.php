<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YoutubeVideo>
 */
class YoutubeVideoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\YoutubeVideo>
     */
    protected $model = \App\Models\YoutubeVideo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'youtube_id' => $this->faker->regexify('[a-zA-Z0-9_-]{11}'),
            'reddit_post_id' => null, // Set to null by default to avoid foreign key constraint issues
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'published_at' => $this->faker->dateTimeBetween('-3 months'),
            'channel_id' => $this->faker->regexify('[a-zA-Z0-9_-]{24}'),
            'channel_title' => $this->faker->company(),
            'thumbnail_url' => $this->faker->imageUrl(),
            'duration_seconds' => $this->faker->numberBetween(60, 1800),
            'view_count' => $this->faker->numberBetween(100, 1000000),
            'like_count' => $this->faker->numberBetween(10, 50000),
            'is_trending' => $this->faker->boolean(20),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set the video as trending.
     */
    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'view_count' => $this->faker->numberBetween(500000, 10000000),
            'like_count' => $this->faker->numberBetween(50000, 1000000),
            'is_trending' => true,
        ]);
    }

    /**
     * Set the video with a specific YouTube ID.
     */
    public function withYoutubeId(string $youtubeId): static
    {
        return $this->state(fn (array $attributes) => [
            'youtube_id' => $youtubeId,
        ]);
    }
}
