<?php

namespace Database\Seeders;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\User;
use App\Models\YoutubeVideo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlaylistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users from database - only use the 3 specific ones we created in DatabaseSeeder
        $users = User::query()->whereIn('email', ['admin@example.com', 'test@example.com', 'demo@example.com'])->get();

        // Get videos from the database
        $videos = YoutubeVideo::query()->get();

        if ($users->isEmpty()) {
            $this->command->error('No users found for creating playlists.');

            return;
        }

        if ($videos->isEmpty()) {
            $this->command->error('No videos found. Please run YoutubeVideoSeeder first.');

            return;
        }

        $this->command->info('Creating playlists for '.$users->count().' users');

        foreach ($users as $user) {
            $this->command->info("Creating playlists for user: {$user->name}");

            // Create an auto-generated 'Daily' playlist for each user
            $dailyPlaylist = Playlist::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'name' => 'Daily Mix',
                'description' => 'Auto-generated playlist of your daily recommendations',
                'type' => 'auto',
                'visibility' => 'public',
                'created_at' => now()->subDays(rand(1, 5)),
                'updated_at' => now(),
            ]);

            // Create 1-3 custom playlists for each user
            $customPlaylistCount = rand(1, 3);
            $playlistNames = ['Favorites', 'Watch Later', 'Programming Tutorials', 'Tech News', 'Learning Resources', 'Must Watch'];

            for ($i = 0; $i < $customPlaylistCount; $i++) {
                $playlistName = $playlistNames[array_rand($playlistNames)];

                $customPlaylist = Playlist::query()->create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'name' => $playlistName.' '.rand(1, 100),
                    'description' => 'A collection of '.strtolower($playlistName),
                    'type' => 'custom',
                    'visibility' => rand(0, 1) == 1 ? 'public' : 'private',
                    'created_at' => now()->subDays(rand(1, 14)),
                    'updated_at' => now(),
                ]);

                // Add 5-15 random videos to each custom playlist
                $playlistVideos = $videos->random(min(rand(5, 15), $videos->count()));
                $position = 1;

                foreach ($playlistVideos as $video) {
                    PlaylistItem::query()->create([
                        'id' => (string) Str::uuid(),
                        'playlist_id' => $customPlaylist->id,
                        'source_type' => YoutubeVideo::class,
                        'source_id' => $video->id,
                        'position' => $position,
                        'is_watched' => rand(0, 1) == 1,
                        'added_at' => $customPlaylist->created_at->addHours(rand(1, 24)),
                        'created_at' => $customPlaylist->created_at->addHours(rand(1, 24)),
                        'updated_at' => now(),
                    ]);
                    $position++;
                }
            }

            // Add 10-20 random videos to the daily playlist
            $dailyVideos = $videos->random(min(rand(10, 20), $videos->count()));
            $position = 1;

            foreach ($dailyVideos as $video) {
                PlaylistItem::query()->create([
                    'id' => (string) Str::uuid(),
                    'playlist_id' => $dailyPlaylist->id,
                    'source_type' => YoutubeVideo::class,
                    'source_id' => $video->id,
                    'position' => $position,
                    'is_watched' => rand(0, 1) == 1,
                    'added_at' => $dailyPlaylist->created_at->addHours(rand(1, 12)),
                    'created_at' => $dailyPlaylist->created_at->addHours(rand(1, 12)),
                    'updated_at' => now(),
                ]);
                $position++;
            }
        }
    }
}
