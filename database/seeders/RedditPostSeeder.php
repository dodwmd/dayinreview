<?php

namespace Database\Seeders;

use App\Models\RedditPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RedditPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating regular Reddit posts...');

        // Create 20 Reddit posts with no YouTube videos
        RedditPost::factory()->count(20)->create([
            'id' => function () {
                return (string) Str::uuid();
            },
        ]);

        $this->command->info('Creating Reddit posts with YouTube videos...');

        // Create 10 Reddit posts specifically with YouTube videos
        RedditPost::factory()->count(10)
            ->withYoutubeVideo()
            ->create([
                'id' => function () {
                    return (string) Str::uuid();
                },
            ]);

        $this->command->info('Finished seeding Reddit posts');
    }
}
