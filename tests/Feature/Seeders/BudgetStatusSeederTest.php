<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use Database\Seeders\BudgetStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetStatusSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_status_slugs_are_lowercase(): void
    {
        $this->seed( BudgetStatusSeeder::class);

        $statuses = \App\Models\BudgetStatus::all();

        foreach ( $statuses as $status ) {
            $this->assertMatchesRegularExpression( '/^[a-z_]+$/', $status->slug, 'Slug must be lowercase' );
        }

        $this->assertCount( 3, $statuses, 'Expected 3 budget statuses' );
    }

}
