<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSlugTest extends TestCase
{
    use RefreshDatabase;

    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RoleRepository();
    }

    /** @test */
    public function it_returns_false_when_slug_does_not_exist(): void
    {
        $result = $this->repository->existsBySlug( 'non-existent-slug' );

        $this->assertFalse( $result );
    }

    /** @test */
    public function it_returns_true_when_slug_exists(): void
    {
        Role::create( [ 'name' => 'Test Role', 'slug' => 'test-role', 'status' => 'active' ] );

        $result = $this->repository->existsBySlug( 'test-role' );

        $this->assertTrue( $result );
    }

    /** @test */
    public function it_excludes_specific_id_when_checking_slug(): void
    {
        $role1 = Role::create( [ 'name' => 'Test Role', 'slug' => 'test-role', 'status' => 'active' ] );
        $role2 = Role::create( [ 'name' => 'Another Role', 'slug' => 'another-role', 'status' => 'active' ] );

        $result1 = $this->repository->existsBySlug( 'test-role', null, $role1->id );
        $result2 = $this->repository->existsBySlug( 'another-role', null, $role2->id );

        $this->assertFalse( $result1 ); // Não deve encontrar porque exclui o próprio role1
        $this->assertFalse( $result2 ); // Não deve encontrar porque exclui o próprio role2
    }

    /** @test */
    public function it_ignores_tenant_id_for_no_tenant_repository(): void
    {
        Role::create( [ 'name' => 'Test Role', 'slug' => 'test-role', 'status' => 'active' ] );

        $result = $this->repository->existsBySlug( 'test-role', 123 ); // tenantId ignorado

        $this->assertTrue( $result );
    }

    /** @test */
    public function it_returns_false_when_slug_exists_but_excluded_by_id(): void
    {
        $role = Role::create( [ 'name' => 'Test Role', 'slug' => 'test-role', 'status' => 'active' ] );

        $result = $this->repository->existsBySlug( 'test-role', null, $role->id );

        $this->assertFalse( $result );
    }

}
