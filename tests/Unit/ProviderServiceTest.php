<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\User;
use App\Repositories\ProviderRepository;
use App\Services\Domain\ProviderService;
use App\Support\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProviderService    $providerService;
    private ProviderRepository $providerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerRepository = app( ProviderRepository::class);
        $this->providerService    = new ProviderService( $this->providerRepository );
    }

    public function test_get_by_user_id_returns_provider_with_relations()
    {
        // Arrange
        $tenant   = \App\Models\Tenant::factory()->create();
        $user     = User::factory()->create( [ 'tenant_id' => $tenant->id ] );
        $provider = Provider::factory()->create( [
            'user_id'   => $user->id,
            'tenant_id' => $tenant->id
        ] );

        // Act
        $result = $this->providerService->getByUserId( $user->id, $tenant->id );

        // Assert
        $this->assertInstanceOf( Provider::class, $result );
        $this->assertEquals( $provider->id, $result->id );
        $this->assertTrue( $result->relationLoaded( 'user' ) );
        $this->assertTrue( $result->relationLoaded( 'commonData' ) );
        $this->assertTrue( $result->relationLoaded( 'contact' ) );
        $this->assertTrue( $result->relationLoaded( 'address' ) );
    }

    public function test_get_by_user_id_returns_null_when_not_found()
    {
        // Act
        $result = $this->providerService->getByUserId( 999, 1 );

        // Assert
        $this->assertNull( $result );
    }

    public function test_is_email_available_returns_true_when_email_is_available()
    {
        // Arrange
        $tenant = \App\Models\Tenant::factory()->create();
        $user   = User::factory()->create( [ 'tenant_id' => $tenant->id, 'email' => 'existing@example.com' ] );

        // Act
        $result = $this->providerService->isEmailAvailable( 'new@example.com', $user->id, $tenant->id );

        // Assert
        $this->assertTrue( $result );
    }

    public function test_is_email_available_returns_false_when_email_is_taken()
    {
        // Arrange
        $tenant = \App\Models\Tenant::factory()->create();
        $user1  = User::factory()->create( [ 'tenant_id' => $tenant->id, 'email' => 'taken@example.com' ] );
        $user2  = User::factory()->create( [ 'tenant_id' => $tenant->id, 'email' => 'other@example.com' ] );

        // Act
        $result = $this->providerService->isEmailAvailable( 'taken@example.com', $user2->id, $tenant->id );

        // Assert
        $this->assertFalse( $result );
    }

    public function test_get_with_relations_returns_success_when_provider_exists()
    {
        // Arrange
        $provider = Provider::factory()->create( [ 'tenant_id' => 1 ] );

        // Act
        $result = $this->providerService->getWithRelations( $provider->id );

        // Assert
        $this->assertInstanceOf( ServiceResult::class, $result );
        $this->assertTrue( $result->isSuccess() );
        $this->assertInstanceOf( Provider::class, $result->getData() );
        $this->assertEquals( $provider->id, $result->getData()->id );
    }

    public function test_get_with_relations_returns_error_when_provider_not_found()
    {
        // Act
        $result = $this->providerService->getWithRelations( 999 );

        // Assert
        $this->assertInstanceOf( ServiceResult::class, $result );
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( 'Provider não encontrado', $result->getMessage() );
    }

}
