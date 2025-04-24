<?php

namespace Database\Factories;

use App\Models\RedditPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RedditPost>
 */
class RedditPostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\RedditPost>
     */
    protected $model = RedditPost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'reddit_id' => 't3_'.$this->faker->regexify('[a-z0-9]{6}'),
            'subreddit' => $this->faker->randomElement(['programming', 'technology', 'webdev', 'datascience', 'javascript', 'python']),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->optional()->paragraph(),
            'author' => $this->faker->userName(),
            'permalink' => '/r/'.$this->faker->word().'/comments/'.$this->faker->regexify('[a-z0-9]{6}'),
            'url' => $this->faker->url(),
            'score' => $this->faker->numberBetween(1, 10000),
            'num_comments' => $this->faker->numberBetween(0, 500),
            'has_youtube_video' => $this->faker->boolean(30),
            'posted_at' => $this->faker->dateTimeBetween('-3 months'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the model factory for a post with a YouTube video.
     *
     * @return static
     */
    public function withYoutubeVideo()
    {
        return $this->state(function (array $attributes) {
            return [
                'has_youtube_video' => true,
                'url' => 'https://www.youtube.com/watch?v='.$this->faker->regexify('[a-zA-Z0-9_-]{11}'),
            ];
        });
    }

    /**
     * Configure the model factory for a high-scoring post.
     *
     * @return static
     */
    public function popular()
    {
        return $this->state(function (array $attributes) {
            return [
                'score' => $this->faker->numberBetween(5000, 50000),
                'num_comments' => $this->faker->numberBetween(200, 2000),
            ];
        });
    }
}
