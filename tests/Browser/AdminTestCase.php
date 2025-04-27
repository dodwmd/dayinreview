<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

abstract class AdminTestCase extends DuskTestCase
{
    /**
     * Create and authenticate an admin user for testing.
     */
    protected function loginAsAdmin(Browser $browser): Browser
    {
        // For testing, we'll create a direct login session instead of
        // going through the full Orchid authentication flow
        $email = 'admin@example.com';

        // Create admin user if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin User',
                'email' => $email,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'permissions' => json_encode([
                    'platform.index' => true,
                    'platform.systems.roles' => true,
                    'platform.systems.users' => true,
                    'platform.content.reddit' => true,
                    'platform.content.youtube' => true,
                    'platform.subscriptions' => true,
                    'platform.playlists' => true,
                ]),
                'id' => 1,
            ]
        );

        // For testing, directly verify the dashboard page
        $browser->visit('/admin')
            ->waitUntil('document.readyState === "complete"', 10)
            ->screenshot('admin-dashboard');

        // If we were redirected to login, handle the login
        if ($browser->currentUrlIs('/admin/login')) {
            $browser->type('email', $email)
                ->type('password', 'password')
                ->press('Login')
                ->waitUntil('document.readyState === "complete"', 10)
                ->screenshot('post-login');
        }

        return $browser;
    }

    /**
     * Skip permission checks for the specified screen in tests.
     */
    protected function permitScreenAccess(string $screenClass): void
    {
        // This is a test helper that bypasses permission checks
        // It keeps tests simpler by focusing on UI rather than permissions
        app('orchid.screens')->get($screenClass)->auth = false;
    }
}
