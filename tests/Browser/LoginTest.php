<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test the login page and authentication.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->press('Log in')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    /**
     * Test login validation errors.
     */
    public function test_login_validation_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->press('Log in')
                    ->assertSee('The email field is required')
                    ->assertSee('The password field is required');
        });
    }

    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('name', 'Test User')
                    ->type('email', 'new-user@example.com')
                    ->type('password', 'password')
                    ->type('password_confirmation', 'password')
                    ->press('Register')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });

        // Verify the user was created in the database
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Test password reset request form.
     */
    public function test_password_reset_request(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->clickLink('Forgot your password?')
                    ->assertPathIs('/forgot-password')
                    ->type('email', 'test@example.com')
                    ->press('Email Password Reset Link')
                    ->assertSee('Password reset link sent');
        });
    }
}
