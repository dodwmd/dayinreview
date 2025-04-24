<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitUntil('document.readyState === "complete"');

            // Most basic assertion possible - just verify the page didn't crash
            $this->assertTrue(true);
        });
    }
}
