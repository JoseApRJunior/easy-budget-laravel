<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\BudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetStatusSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_status_enum_has_expected_cases(): void
    {
        // Como agora usamos enum ao invés de seeder, testamos diretamente o enum
        $expectedStatuses = [ 'draft', 'pending', 'approved', 'rejected', 'cancelled', 'completed' ];

        $actualStatuses = array_map( fn( $case ) => $case->value, BudgetStatus::cases() );

        $this->assertEquals( $expectedStatuses, $actualStatuses );
        $this->assertCount( 6, BudgetStatus::cases() );
    }

    public function test_budget_status_enum_values_are_lowercase(): void
    {
        // Verificar se todos os valores do enum estão em lowercase
        foreach ( BudgetStatus::cases() as $status ) {
            $this->assertEquals( strtolower( $status->value ), $status->value,
                "Status '{$status->value}' não está em lowercase" );
        }
    }

    public function test_budget_status_enum_has_valid_transitions(): void
    {
        // Testar algumas transições válidas
        $this->assertTrue( BudgetStatus::DRAFT->canTransitionTo( BudgetStatus::PENDING ) );
        $this->assertTrue( BudgetStatus::PENDING->canTransitionTo( BudgetStatus::APPROVED ) );
        $this->assertTrue( BudgetStatus::APPROVED->canTransitionTo( BudgetStatus::COMPLETED ) );

        // Testar transições inválidas
        $this->assertFalse( BudgetStatus::DRAFT->canTransitionTo( BudgetStatus::COMPLETED ) );
        $this->assertFalse( BudgetStatus::COMPLETED->canTransitionTo( BudgetStatus::PENDING ) );
    }

}
