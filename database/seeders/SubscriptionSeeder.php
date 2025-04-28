<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get system users
        $users = User::query()->whereIn('email', [
            'admin@example.com',
            'user@example.com',
        ])->get();

        if ($users->isEmpty()) {
            echo "No users found for subscription seeding.\n";

            return;
        }

        // Common YouTube channels
        $youtubeChannels = [
            [
                'id' => 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
                'title' => 'Google for Developers',
                'description' => 'Official channel for Google Developer content',
                'thumbnail_url' => 'https://yt3.ggpht.com/ytc/AOPolaSNvIYocSV7nQ8LkBgVBj2PH4RgdyDuEKD7wYOELQ=s88-c-k-c0x00ffffff-no-rj',
            ],
            [
                'id' => 'UCsBjURrPoezykLs9EqgamOA',
                'title' => 'Fireship',
                'description' => 'High-intensity ⚡ code tutorials and tech news',
                'thumbnail_url' => 'https://yt3.ggpht.com/ytc/AOPolaR3oqCOmvSM6nKygf-vY18WM3ywQWXHFHyAaJ-DCw=s88-c-k-c0x00ffffff-no-rj',
            ],
            [
                'id' => 'UCP_lo1MFyx5IXDeD9s_6nUw',
                'title' => 'Laravel',
                'description' => 'Official Laravel channel',
                'thumbnail_url' => 'https://yt3.ggpht.com/ytc/AOPolaScLUbE-bLmv7Qc0ZNEay322-jS0oLP_9GYdyG3=s88-c-k-c0x00ffffff-no-rj',
            ],
        ];

        // Common subreddits
        $subreddits = ['programming', 'javascript', 'laravel', 'php', 'react'];

        foreach ($users as $user) {
            // Create YouTube channel subscriptions
            foreach ($youtubeChannels as $channelData) {
                Subscription::query()->create([
                    'id' => Str::uuid(),
                    'user_id' => $user->id,
                    'subscribable_type' => 'App\\Models\\YoutubeChannel',
                    'subscribable_id' => $channelData['id'],
                    'name' => $channelData['title'],
                    'description' => $channelData['description'],
                    'thumbnail_url' => $channelData['thumbnail_url'],
                    'last_fetched_at' => now()->subHours(rand(1, 24)),
                ]);
            }

            // Create Reddit subreddit subscriptions
            foreach ($subreddits as $subreddit) {
                Subscription::query()->create([
                    'id' => Str::uuid(),
                    'user_id' => $user->id,
                    'subscribable_type' => 'App\\Models\\RedditSubreddit',
                    'subscribable_id' => 'r/'.$subreddit,
                    'name' => 'r/'.$subreddit,
                    'description' => 'Subreddit for '.$subreddit,
                    'thumbnail_url' => 'https://picsum.photos/100?random='.rand(1, 1000),
                    'last_fetched_at' => now()->subHours(rand(1, 24)),
                ]);
            }
        }

        echo 'Created subscriptions for '.$users->count()." users.\n";
    }
}
