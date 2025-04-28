<?php

namespace Tests\Browser\Admin;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DebugLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Debug the login page structure.
     */
    public function test_debug_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->waitUntil('document.readyState === "complete"', 10)
                ->screenshot('admin-login');

            // Print the page source to the console for debugging
            echo $browser->driver->getPageSource();
        });
    }
}
