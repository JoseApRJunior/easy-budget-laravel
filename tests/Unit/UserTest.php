<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function it_returns_full_name_from_provider_common_data_when_available()
    {
        // Arrange - Create a user instance
        $user = new User( [ 
            'tenant_id' => 1,
            'email'     => 'joao.silva@example.com',
            'password'  => 'hashed_password',
        ] );

        // Mock the provider relationship
        $mockProvider = $this->mock( \App\Models\Provider::class);
        $mockProvider->shouldReceive( 'getAttribute' )
            ->with( 'commonData' )
            ->andReturn( (object) [ 
                'first_name' => 'João',
                'last_name'  => 'Silva'
            ] );

        $user->setRelation( 'provider', $mockProvider );

        // Act
        $name = $user->name;

        // Assert
        /**  @method TestCase $this->assertEquals */
        $this->assertEquals( 'João Silva', $name );
    }

    /** @test */
    public function it_returns_email_when_provider_common_data_is_not_available()
    {
        // Arrange
        $user = new User( [ 
            'tenant_id' => 1,
            'email'     => 'user@example.com',
            'password'  => 'hashed_password',
        ] );

        // Act
        $name = $user->name;

        // Assert
        $this->assertEquals( 'user@example.com', $name );
    }

    /** @test */
    public function it_returns_empty_string_when_email_is_null_and_no_provider()
    {
        // Arrange
        $user = new User( [ 
            'tenant_id' => 1,
            'email'     => null,
            'password'  => 'hashed_password',
        ] );

        // Act
        $name = $user->name;

        // Assert
        $this->assertEquals( '', $name );
    }

    /** @test */
    public function it_handles_user_with_provider_but_no_common_data()
    {
        // Arrange
        $user = new User( [ 
            'tenant_id' => 1,
            'email'     => 'user@example.com',
            'password'  => 'hashed_password',
        ] );

        // Mock provider without commonData
        $mockProvider = $this->mock( \App\Models\Provider::class);
        $mockProvider->shouldReceive( 'getAttribute' )
            ->with( 'commonData' )
            ->andReturn( null );

        $user->setRelation( 'provider', $mockProvider );

        // Act
        $name = $user->name;

        // Assert
        $this->assertEquals( 'user@example.com', $name );
    }

    /** @test */
    public function it_handles_user_with_only_first_name_in_common_data()
    {
        // Arrange
        $user = new User( [ 
            'tenant_id' => 1,
            'email'     => 'joao@example.com',
            'password'  => 'hashed_password',
        ] );

        // Mock provider with only first name
        $mockProvider = $this->mock( \App\Models\Provider::class);
        $mockProvider->shouldReceive( 'getAttribute' )
            ->with( 'commonData' )
            ->andReturn( (object) [ 
                'first_name' => 'João',
                'last_name'  => null
            ] );

        $user->setRelation( 'provider', $mockProvider );

        // Act
        $name = $user->name;

        // Assert
        $this->assertEquals( 'João ', $name );
    }

    /** @test */
    public function it_handles_user_with_only_last_name_in_common_data()
    {
        // Arrange
        $user = new User( [ 
            'tenant_id' => 1,
            'email'     => 'silva@example.com',
            'password'  => 'hashed_password',
        ] );

        // Mock provider with only last name
        $mockProvider = $this->mock( \App\Models\Provider::class);
        $mockProvider->shouldReceive( 'getAttribute' )
            ->with( 'commonData' )
            ->andReturn( (object) [ 
                'first_name' => null,
                'last_name'  => 'Silva'
            ] );

        $user->setRelation( 'provider', $mockProvider );

        // Act
        $name = $user->name;

        // Assert
        $this->assertEquals( ' Silva', $name );
    }

}
