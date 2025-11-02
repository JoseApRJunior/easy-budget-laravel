<?php

declare(strict_types=1);

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FormularioProviderTest extends DuskTestCase
{
    /**
     * Test form page loads correctly.
     */
    public function test_pagina_formulario_carrega()
    {
        $this->browse( function ( Browser $browser ) {
            $browser->visit( '/login' )
                ->type( 'email', 'juniorklan.ju@gmail.com' )
                ->type( 'password', 'Password1@' )
                ->press( 'Entrar' )
                ->pause( 2000 )
                ->visit( '/provider/business/edit' )
                ->assertSee( 'Dados Empresariais' )
                ->assertPresent( 'input[name="first_name"]' )
                ->assertPresent( 'input[name="last_name"]' )
                ->assertPresent( 'button[type="submit"]' );
        } );
    }

}
