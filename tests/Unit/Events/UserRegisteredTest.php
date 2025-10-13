<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\UserRegistered;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários para o evento UserRegistered.
 *
 * Esta classe testa o comportamento do evento UserRegistered,
 * incluindo sua criação e propriedades.
 */
class UserRegisteredTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa criação do evento UserRegistered.
     */
    public function test_user_registered_event_creation(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Act
        $event = new UserRegistered( $user, $tenant );

        // Assert
        $this->assertInstanceOf( UserRegistered::class, $event );
        $this->assertEquals( $user->id, $event->user->id );
        $this->assertEquals( $tenant->id, $event->tenant->id );
    }

    /**
     * Testa criação do evento com tenant nulo.
     */
    public function test_user_registered_event_with_null_tenant(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $event = new UserRegistered( $user, null );

        // Assert
        $this->assertInstanceOf( UserRegistered::class, $event );
        $this->assertEquals( $user->id, $event->user->id );
        $this->assertNull( $event->tenant );
    }

    /**
     * Testa se o evento implementa interfaces corretas.
     */
    public function test_user_registered_event_interfaces(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $event  = new UserRegistered( $user, $tenant );

        // Assert
        $this->assertContains( \Illuminate\Foundation\Events\Dispatchable::class, class_uses( $event ) );
        $this->assertContains( \Illuminate\Broadcasting\InteractsWithSockets::class, class_uses( $event ) );
        $this->assertContains( \Illuminate\Queue\SerializesModels::class, class_uses( $event ) );
    }

    /**
     * Testa serialização do evento.
     */
    public function test_user_registered_event_serialization(): void
    {
        // Arrange
        $user   = User::factory()->create( [
            'email'     => 'test@example.com',
            'is_active' => true,
        ] );
        $tenant = Tenant::factory()->create( [
            'name'      => 'test-tenant',
            'is_active' => true,
        ] );

        $event = new UserRegistered( $user, $tenant );

        // Act - Simular serialização
        $serialized   = serialize( $event );
        $unserialized = unserialize( $serialized );

        // Assert
        $this->assertInstanceOf( UserRegistered::class, $unserialized );
        $this->assertEquals( $user->id, $unserialized->user->id );
        $this->assertEquals( $tenant->id, $unserialized->tenant->id );
        $this->assertEquals( 'test@example.com', $unserialized->user->email );
        $this->assertEquals( 'test-tenant', $unserialized->tenant->name );
    }

    /**
     * Testa evento com dados relacionais complexos.
     */
    public function test_user_registered_event_with_complex_data(): void
    {
        // Arrange
        $user            = User::factory()->create();
        $user->tenant_id = 123;
        $user->save();

        $tenant            = Tenant::factory()->create();
        $tenant->is_active = true;
        $tenant->save();

        // Act
        $event = new UserRegistered( $user, $tenant );

        // Assert
        $this->assertEquals( 123, $event->user->tenant_id );
        $this->assertTrue( $event->tenant->is_active );
        $this->assertNotNull( $event->user->created_at );
        $this->assertNotNull( $event->tenant->created_at );
    }

}
