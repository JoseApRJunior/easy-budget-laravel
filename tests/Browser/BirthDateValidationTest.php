<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BirthDateValidationTest extends DuskTestCase
{
    public function test_birth_date_error_appears_after_input(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(5)
                    ->visit('http://localhost/provider/business/edit')
                    ->pause(2000)
                    ->assertSee('Dados Empresariais')
                    ->type('#birth_date', '01/01/2020')
                    ->click('#email_personal')
                    ->pause(1000)
                    ->screenshot('birth_date_error_position')
                    ->assertPresent('#birth_date_js_error');
        });
    }
}
