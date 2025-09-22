<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    public function it_displays_login_page()
    {
        $response = $this->get( route( 'login' ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.login.index' );
    }

    /** @test */
    public function it_allows_user_to_login_with_valid_credentials()
    {
        $user = User::factory()->create( [
            'password'  => Hash::make( 'password123' ),
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->post( route( 'login.store' ), [
            'email'    => $user->email,
            'password' => 'password123',
        ] );

        $response->assertRedirect( route( 'home' ) );
        $this->assertAuthenticatedAs( $user );
    }

    /** @test */
    public function it_prevents_login_with_invalid_credentials()
    {
        $user = User::factory()->create( [
            'password'  => Hash::make( 'password123' ),
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->post( route( 'login.store' ), [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ] );

        $response->assertRedirect( route( 'login' ) );
        $response->assertSessionHasErrors( 'email' );
        $this->assertGuest();
    }

    /** @test */
    public function it_prevents_login_with_nonexistent_email()
    {
        $response = $this->post( route( 'login.store' ), [
            'email'    => 'nonexistent@example.com',
            'password' => 'password123',
        ] );

        $response->assertRedirect( route( 'login' ) );
        $response->assertSessionHasErrors( 'email' );
        $this->assertGuest();
    }

    /** @test */
    public function it_validates_login_form()
    {
        $response = $this->post( route( 'login.store' ), [
            'email'    => '',
            'password' => '',
        ] );

        $response->assertRedirect( route( 'login' ) );
        $response->assertSessionHasErrors( [ 'email', 'password' ] );
    }

    /** @test */
    public function it_allows_user_to_logout()
    {
        $user = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $this->actingAs( $user );

        $response = $this->post( route( 'logout' ) );

        $response->assertRedirect( route( 'login' ) );
        $this->assertGuest();
    }

    /** @test */
    public function it_rate_limits_login_attempts()
    {
        $user = User::factory()->create( [
            'password'  => Hash::make( 'password123' ),
            'tenant_id' => $this->tenant->id,
        ] );

        // Tentar login várias vezes com senha incorreta
        for ( $i = 0; $i < 6; $i++ ) {
            $response = $this->post( route( 'login.store' ), [
                'email'    => $user->email,
                'password' => 'wrongpassword',
            ] );
        }

        // A última tentativa deve ser rate limited
        $response->assertStatus( 429 ); // Too Many Requests
    }

    /** @test */
    public function it_remembers_user_when_remember_me_is_checked()
    {
        $user = User::factory()->create( [
            'password'  => Hash::make( 'password123' ),
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->post( route( 'login.store' ), [
            'email'    => $user->email,
            'password' => 'password123',
            'remember' => 'on',
        ] );

        $response->assertRedirect( route( 'home' ) );

        // Verificar se o cookie de remember me foi definido
        $response->assertCookie( 'laravel_remember' );
    }

    /** @test */
    public function it_redirects_authenticated_users_away_from_login_page()
    {
        $user = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $this->actingAs( $user );

        $response = $this->get( route( 'login' ) );

        $response->assertRedirect( route( 'home' ) );
    }

    /** @test */
    public function it_displays_password_reset_request_page()
    {
        $response = $this->get( route( 'password.request' ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'auth.forgot-password' );
    }

    /** @test */
    public function it_sends_password_reset_email()
    {
        $user = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->post( route( 'password.email' ), [
            'email' => $user->email,
        ] );

        $response->assertRedirect();
        $response->assertSessionHas( 'status', 'We have emailed your password reset link.' );
    }

    /** @test */
    public function it_validates_password_reset_email()
    {
        $response = $this->post( route( 'password.email' ), [
            'email' => 'invalid-email',
        ] );

        $response->assertRedirect();
        $response->assertSessionHasErrors( 'email' );
    }

    /** @test */
    public function it_blocks_login_for_blocked_users()
    {
        $user = User::factory()->create( [
            'password'  => Hash::make( 'password123' ),
            'status'    => 'blocked',
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->post( route( 'login.store' ), [
            'email'    => $user->email,
            'password' => 'password123',
        ] );

        $response->assertRedirect( route( 'login' ) );
        $response->assertSessionHasErrors( 'email' );
        $this->assertGuest();
    }

    /** @test */
    public function it_handles_case_insensitive_email_login()
    {
        $user = User::factory()->create( [
            'email'     => 'TestUser@Example.Com',
            'password'  => Hash::make( 'password123' ),
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->post( route( 'login.store' ), [
            'email'    => 'testuser@example.com',
            'password' => 'password123',
        ] );

        $response->assertRedirect( route( 'home' ) );
        $this->assertAuthenticatedAs( $user );
    }

}
