<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Orchid\Platform\Models\Role;

/**
 * Admin role assignment command
 */
class AssignAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orchid:admin-access {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign admin role and all permissions to a user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return 1;
        }

        // Get or create admin role
        /** @var Role|null $adminRole */
        $adminRole = Role::query()->where('slug', 'admin')->first();

        if (! $adminRole) {
            /** @var Role $adminRole */
            $adminRole = new Role([
                'slug' => 'admin',
                'name' => 'Administrator',
            ]);

            // Define admin permissions
            $permissions = [
                'platform.index' => 1,
                'platform.systems.roles' => 1,
                'platform.systems.users' => 1,
                'platform.content.reddit' => 1,
                'platform.content.youtube' => 1,
                'platform.subscriptions' => 1,
                'platform.playlists' => 1,
            ];

            // Using reflection to set permissions directly
            $reflection = new \ReflectionClass($adminRole);
            $property = $reflection->getProperty('attributes');
            $property->setAccessible(true);
            $attributes = $property->getValue($adminRole);
            $attributes['permissions'] = $permissions;
            $property->setValue($adminRole, $attributes);

            $adminRole->save();
            $this->info('Admin role created.');
        } else {
            // Update admin role permissions
            $permissions = [
                'platform.index' => 1,
                'platform.systems.roles' => 1,
                'platform.systems.users' => 1,
                'platform.content.reddit' => 1,
                'platform.content.youtube' => 1,
                'platform.subscriptions' => 1,
                'platform.playlists' => 1,
            ];

            // Set permissions directly using reflection
            $reflection = new \ReflectionClass($adminRole);
            $property = $reflection->getProperty('attributes');
            $property->setAccessible(true);
            $attributes = $property->getValue($adminRole);
            $attributes['permissions'] = $permissions;
            $property->setValue($adminRole, $attributes);

            $adminRole->save();
            $this->info('Admin role updated.');
        }

        // Assign admin role to user
        $user->roles()->sync([$adminRole->getKey()]);

        // Add admin permissions directly to user
        $permissions = [
            'platform.index' => 1,
            'platform.systems.roles' => 1,
            'platform.systems.users' => 1,
            'platform.content.reddit' => 1,
            'platform.content.youtube' => 1,
            'platform.subscriptions' => 1,
            'platform.playlists' => 1,
        ];

        // Using reflection to set user permissions directly
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue($user);
        $attributes['permissions'] = $permissions;
        $property->setValue($user, $attributes);

        $user->save();

        $this->info("Admin role and permissions assigned to {$email}.");

        return 0;
    }
}
