<?php

namespace Tests\Browser\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ContentDashboardTest extends DuskTestCase
{
    // Use database transactions to avoid affecting other tests
    use DatabaseTransactions;

    /**
     * Test that the admin content dashboard loads correctly.
     */
    public function test_content_dashboard_loads(): void
    {
        $this->browse(function (Browser $browser) {
            // Create admin user for testing
            $email = 'admin@example.com';
            $password = 'password';

            $admin = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Admin User',
                    'email' => $email,
                    'password' => bcrypt($password),
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
                ]
            );

            // Visit login page and authenticate
            $browser->visit('/admin/login')
                ->waitForText('Sign in to your account')
                ->waitFor('input[name="email"]')
                ->type('email', $email)
                ->type('password', $password)
                ->press('Login')
                ->waitForLocation('/admin')
                ->screenshot('admin-login-success');

            // Now visit the content dashboard
            $browser->visit('/admin/content/dashboard')
                ->waitUntil('document.readyState === "complete"', 30)
                ->screenshot('content-dashboard');

            // Success if we reach this point
            $this->assertTrue(true, 'Successfully loaded the admin content dashboard');
        });
    }
}
