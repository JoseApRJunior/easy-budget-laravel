<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get( '/register' );

        $response->assertStatus( 200 );
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post( '/register', [
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => 'test@example.com',
            'phone'                 => '(11) 99999-9999',
            'password'              => 'TestPass123!@#',
            'password_confirmation' => 'TestPass123!@#',
            'terms_accepted'        => true,
        ] );

        $this->assertAuthenticated();
        $response->assertRedirect( route( 'provider.dashboard', absolute: false ) );
    }

}
