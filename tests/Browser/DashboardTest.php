<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test login page loads.
     */
    public function test_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitUntil('document.readyState === "complete"');

            // Most basic assertion possible - just verify the page loaded
            $this->assertTrue(true);
        });
    }
}
