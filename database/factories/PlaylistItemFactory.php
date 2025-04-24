<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\YoutubeVideo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlaylistItem>
 */
class PlaylistItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PlaylistItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'playlist_id' => Playlist::factory(),
            'source_type' => 'App\\Models\\YoutubeVideo',
            'source_id' => YoutubeVideo::factory(),
            'position' => $this->faker->numberBetween(1, 20),
            'is_watched' => $this->faker->boolean(),
            'notes' => $this->faker->optional()->sentence(),
            'added_at' => Carbon::now(),
            'watched_at' => function (array $attributes) {
                return $attributes['is_watched'] ? Carbon::now() : null;
            },
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Set the item as watched.
     *
     * @return static
     */
    public function watched(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_watched' => true,
            'watched_at' => Carbon::now(),
        ]);
    }

    /**
     * Set the item as unwatched.
     *
     * @return static
     */
    public function unwatched(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_watched' => false,
            'watched_at' => null,
        ]);
    }
}
