<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class AdminLoginPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/admin/login';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@email' => 'input[name="email"]',
            '@password' => 'input[name="password"]',
            '@login-button' => 'button[type="submit"]',
            '@remember' => 'input[name="remember"]',
        ];
    }

    /**
     * Login with the given credentials.
     */
    public function login(Browser $browser, string $email, string $password): void
    {
        $browser->waitFor('@email', 10)
            ->type('@email', $email)
            ->waitFor('@password', 10)
            ->type('@password', $password)
            ->waitFor('@login-button', 10)
            ->screenshot('before-login')
            ->press('@login-button')
            ->waitForLocation('/admin', 30)
            ->screenshot('after-login');
    }
}
