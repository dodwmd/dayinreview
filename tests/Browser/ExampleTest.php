<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * Test the homepage loads correctly.
     */
    public function test_homepage_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('Laravel')
                ->assertSee('Welcome')
                ->assertTitle(function ($title) {
                    return str_contains($title, 'Laravel');
                });
        });
    }

    /**
     * Test that a future login page will load correctly.
     * This is a placeholder for future authentication functionality.
     */
    public function test_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Login')
                ->assertSee('E-Mail Address')
                ->assertSee('Password');
        });
    }
}
