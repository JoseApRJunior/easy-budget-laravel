<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;
    protected $userRepository;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        // Mock do repositório e serviço de notificação
        $this->userRepository      = $this->mock( UserRepository::class);
        $this->notificationService = $this->mock( \App\Services\NotificationService::class);

        // Instanciar o serviço com os mocks
        $this->userService = new UserRegistrationService( $this->userRepository, $this->notificationService );
    }

    /** @test */
    public function it_registers_provider_successfully()
    {
        $providerData = [ 
            'name'      => 'João Silva',
            'email'     => 'joao@example.com',
            'password'  => 'password123',
            'tenant_id' => $this->tenant->id,
            'document'  => '12345678901',
            'phone'     => '(11) 99999-9999',
        ];

        $this->userRepository
            ->shouldReceive( 'create' )
            ->once()
            ->andReturn( new User( [ 
                'id'    => 1,
                'name'  => 'João Silva',
                'email' => 'joao@example.com',
            ] ) );

        $this->userRepository
            ->shouldReceive( 'saveVerificationToken' )
            ->once();

        $this->notificationService
            ->shouldReceive( 'sendAccountConfirmation' )
            ->once()
            ->andReturn( true );

        $user = $this->userService->registerProvider( $providerData );

        $this->assertInstanceOf( User::class, $user );
        $this->assertEquals( 'João Silva', $user->name );
    }

    /** @test */
    public function it_confirms_account_with_valid_token()
    {
        $userId = 1;
        $token  = 'valid-token';

        $this->userRepository
            ->shouldReceive( 'getUserIdByToken' )
            ->once()
            ->with( $token )
            ->andReturn( $userId );

        $result = $this->userService->confirmAccount( $token );

        $this->assertTrue( $result );
    }

    /** @test */
    public function it_fails_to_confirm_account_with_invalid_token()
    {
        $token = 'invalid-token';

        $this->userRepository
            ->shouldReceive( 'getUserIdByToken' )
            ->once()
            ->with( $token )
            ->andReturn( null );

        $this->expectException( \Illuminate\Validation\ValidationException::class);

        $this->userService->confirmAccount( $token );
    }

    /** @test */
    public function it_resets_password_successfully()
    {
        $email       = 'user@example.com';
        $token       = 'reset-token';
        $newPassword = 'newpassword123';
        $userId      = 1;

        $this->userRepository
            ->shouldReceive( 'getUserIdByResetToken' )
            ->once()
            ->with( $email, $token )
            ->andReturn( $userId );

        $result = $this->userService->resetPassword( $email, $token, $newPassword );

        $this->assertTrue( $result );
    }

    /** @test */
    public function it_blocks_account_with_reason()
    {
        $userId = 1;
        $reason = 'Violação de termos';

        $this->notificationService
            ->shouldReceive( 'sendStatusUpdate' )
            ->once()
            ->with( $userId, 'blocked', $reason )
            ->andReturn( true );

        $result = $this->userService->blockAccount( $userId, $reason );

        $this->assertTrue( $result );
    }

    /** @test */
    public function it_initiates_password_reset()
    {
        $email = 'user@example.com';

        $this->userRepository
            ->shouldReceive( 'findByEmail' )
            ->once()
            ->with( $email )
            ->andReturn( new User( [ 'id' => 1, 'email' => $email ] ) );

        $this->userRepository
            ->shouldReceive( 'saveResetToken' )
            ->once();

        $this->notificationService
            ->shouldReceive( 'sendPasswordReset' )
            ->once()
            ->andReturn( true );

        $result = $this->userService->initiatePasswordReset( $email );

        $this->assertTrue( $result );
    }

    /** @test */
    public function it_handles_password_reset_for_nonexistent_user()
    {
        $email = 'nonexistent@example.com';

        $this->userRepository
            ->shouldReceive( 'findByEmail' )
            ->once()
            ->with( $email )
            ->andReturn( null );

        // Should still return true to prevent email enumeration
        $result = $this->userService->initiatePasswordReset( $email );

        $this->assertTrue( $result );
    }

}
