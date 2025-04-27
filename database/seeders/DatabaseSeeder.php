<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Creating users...');

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create regular test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create demo user
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        // Seed all other data
        $this->call([
            RedditPostSeeder::class,
            YoutubeVideoSeeder::class,
            SubscriptionSeeder::class,
            PlaylistSeeder::class,
        ]);

        // Assign admin role to the admin user
        $this->command->call('orchid:grant-admin', [
            'email' => 'admin@example.com',
        ]);
    }
}
