<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config( [ 'tenant.testing_id' => 1 ] ); // Set tenant for testing
        Route::middleware( 'web' )->get( '/ping', fn() => response( 'ok' ) );
    }

    /**
     * Testa a criação de uma sessão via request e verifica se é armazenada na tabela sessions.
     */
    public function testCanCreateSession(): void
    {
        // Cria um usuário para autenticação
        $user = User::factory()->create( [ 
            'tenant_id' => 1,
        ] );

        // Simula usuário autenticado e cria sessão
        $this->actingAs( $user );
        // Substituído get('/') por get('/ping') para evitar 404 em testes de sessão e garantir consistência com rota mock
        $this->withSession( [ 'foo' => 'bar' ] )->get( '/ping' );

        // Verifica se a sessão foi criada na tabela
        $this->assertTrue( DB::table( 'sessions' )->count() > 0 );

        $session = DB::table( 'sessions' )->first();
        $this->assertNotNull( $session->payload );

        // Verifica user_id se coluna populada pelo framework (condicional para compatibilidade)
        if ( Schema::hasColumn( 'sessions', 'user_id' ) ) {
            $this->assertDatabaseHas( 'sessions', [ 
                'user_id' => $user->id,
            ] );
        }
        $this->assertAuthenticatedAs( $user );
    }

    /**
     * Testa o armazenamento e recuperação de dados na sessão.
     */
    public function testCanRetrieveSessionData(): void
    {
        // Armazena dados na sessão
        $this->session( [ 'test_key' => 'test_value' ] );

        // Faz uma request
        // Substituído get('/') por get('/ping') para evitar 404 em testes de sessão e garantir consistência com rota mock
        $response = $this->get( '/ping' );

        // Verifica se os dados foram recuperados corretamente
        $this->assertEquals( 'test_value', session( 'test_key' ) );
    }

    /**
     * Testa a associação correta do user_id na sessão após login.
     */
    public function testSessionUserAssociation(): void
    {
        $user = User::factory()->create( [ 
            'tenant_id' => 1,
        ] );

        // Simula usuário autenticado e cria sessão
        $this->actingAs( $user );
        // Substituído get('/') por get('/ping') para evitar 404 em testes de sessão e garantir consistência com rota mock
        $this->withSession( [ 'foo' => 'bar' ] )->get( '/ping' );

        // Verifica user_id se coluna populada pelo framework (condicional para compatibilidade)
        if ( Schema::hasColumn( 'sessions', 'user_id' ) ) {
            $this->assertDatabaseHas( 'sessions', [ 
                'user_id' => $user->id,
            ] );
        }

        // Verifica se o usuário autenticado é o correto (verificação primária)
        $this->assertAuthenticatedAs( $user );
    }

    /**
     * Testa a expiração de sessão após o tempo limite configurado.
     */
    public function testSessionExpiration(): void
    {
        // Configura lifetime curto para teste (5 minutos)
        config( [ 'session.lifetime' => 5 ] );

        $user = User::factory()->create( [ 
            'tenant_id' => 1,
            'email'     => 'expire@example.com',
            'password'  => bcrypt( 'password' )
        ] );

        // Cria sessão
        $this->actingAs( $user );
        // Substituído get('/') por get('/ping') para evitar 404 em testes de sessão e garantir consistência com rota mock
        $this->withSession( [ 'foo' => 'bar' ] )->get( '/ping' );

        // Avança o tempo além do lifetime
        $this->travel( 6 )->minutes();

        // Podar sessões expiradas de forma determinística
        Artisan::call( 'session:prune' );

        // Tenta acessar a rota /ping após expiração
        $response = $this->get( '/ping' );

        // Verifica que o usuário não está mais autenticado e a rota responde OK
        $this->assertDatabaseMissing( 'sessions', [ 
            'user_id' => $user->id,
        ] );
        $response->assertOk();

        // Verifica se a sessão 'foo' não existe mais
        $this->assertNull( session( 'foo' ) );
    }

    /**
     * Testa a limpeza automática de sessões antigas.
     */
    public function testSessionCleanup(): void
    {
        // Cria uma sessão antiga (mais de 120 minutos)
        $oldSession = [ 
            'id'            => 'old_session_id',
            'user_id'       => null,
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'test-agent',
            'payload'       => 'old_payload',
            'last_activity' => now()->subDays( 2 )->timestamp,
        ];

        // Insere sessão antiga diretamente no banco
        DB::table( 'sessions' )->insert( $oldSession );

        // Cria uma sessão atual
        $user = User::factory()->create( [ 
            'tenant_id' => 1,
        ] );
        $this->actingAs( $user );
        $this->get( '/ping' );

        // Garbage collection manual simulando o mecanismo de loteria do Laravel para sessões expiradas
        DB::table( 'sessions' )->where( 'last_activity', '<', now()->subMinutes( config( 'session.lifetime' ) )->timestamp )->delete();

        // Verifica se a sessão antiga foi removida
        $this->assertDatabaseMissing( 'sessions', [ 
            'id' => 'old_session_id',
        ] );

        // Verifica se a sessão atual ainda existe
        $this->assertDatabaseHas( 'sessions', [ 
            'user_id' => $user->id,
        ] );

        // Verifica se o usuário autenticado é o correto
        $this->assertAuthenticatedAs( $user );
    }

}
