<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrantOrchidAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orchid:grant-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant Orchid admin permissions to a user using direct DB access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Get the user
        $user = DB::table('users')->where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found");

            return 1;
        }

        // Update user permissions directly
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'permissions' => json_encode([
                    'platform.index' => true,
                    'platform.systems.roles' => true,
                    'platform.systems.users' => true,
                    'platform.content.dashboard' => true,
                    'platform.content.reddit' => true,
                    'platform.content.youtube' => true,
                    'platform.subscriptions' => true,
                    'platform.playlists' => true,
                ]),
            ]);

        $this->info("Admin permissions granted to {$email}");

        return 0;
    }
}
