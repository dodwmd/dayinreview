<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Playlist>
 */
class PlaylistFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Playlist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'thumbnail_url' => $this->faker->imageUrl(),
            'type' => $this->faker->randomElement(['auto', 'custom']),
            'visibility' => $this->faker->randomElement(['private', 'unlisted', 'public']),
            'is_favorite' => $this->faker->boolean(),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'last_viewed_at' => $this->faker->optional()->dateTimeBetween('-1 month'),
            'last_generated_at' => $this->faker->dateTimeBetween('-1 month'),
            'generation_algorithm' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Create a new private playlist.
     *
     * @return static
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    /**
     * Create a new public playlist.
     *
     * @return static
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    /**
     * Create a new unlisted playlist.
     *
     * @return static
     */
    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'unlisted',
        ]);
    }

    /**
     * Create a new auto-generated playlist.
     *
     * @return static
     */
    public function auto(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'auto',
        ]);
    }

    /**
     * Create a new custom playlist.
     *
     * @return static
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'custom',
        ]);
    }
}
