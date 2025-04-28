<?php

namespace Database\Seeders;

use App\Models\RedditPost;
use App\Models\YoutubeVideo;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class YoutubeVideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $this->command->info('Creating standalone YouTube videos...');

        // Create 50 standalone YouTube videos
        YoutubeVideo::factory()->count(50)->create([
            'id' => function () {
                return (string) Str::uuid();
            },
        ]);

        // Create YouTube videos linked to Reddit posts that have YouTube videos
        $redditPostsWithVideos = RedditPost::query()->where('has_youtube_video', '=', 1)->get();

        if ($redditPostsWithVideos->isEmpty()) {
            $this->command->warn('No Reddit posts with YouTube videos found. You might want to run RedditPostSeeder first.');
        } else {
            $this->command->info("Creating YouTube videos for {$redditPostsWithVideos->count()} Reddit posts...");

            foreach ($redditPostsWithVideos as $post) {
                // Access the ID property safely using the getKey() method
                $postId = $post->getKey();

                YoutubeVideo::factory()->create([
                    'id' => (string) Str::uuid(),
                    'reddit_post_id' => $postId,
                    'is_trending' => rand(0, 100) < 30, // 30% chance of being trending
                ]);
            }
        }

        $this->command->info('Creating trending YouTube videos...');

        // Create some trending videos
        YoutubeVideo::factory()->count(15)->create([
            'id' => function () {
                return (string) Str::uuid();
            },
            'is_trending' => true,
            'view_count' => function () use ($faker) {
                return $faker->numberBetween(500000, 10000000);
            },
        ]);

        $this->command->info('Finished seeding YouTube videos');
    }
}
