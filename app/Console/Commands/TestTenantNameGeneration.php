<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\Application\UserRegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestTenantNameGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tenant-names {--cleanup : Remove tenants criados durante o teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa diferentes cenários de geração de nomes únicos para tenants';

    private UserRegistrationService $userRegistrationService;
    private TenantRepository        $tenantRepository;

    public function __construct(
        UserRegistrationService $userRegistrationService,
        TenantRepository $tenantRepository,
    ) {
        parent::__construct();
        $this->userRegistrationService = $userRegistrationService;
        $this->tenantRepository        = $tenantRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info( '🧪 Testando geração de nomes únicos para tenants...' );
        $this->newLine();

        // Cenários de teste
        $testCases = [
            [
                'name'              => 'João Silva',
                'email'             => 'joao.silva@empresa.com',
                'expected_strategy' => 'Nome completo disponível'
            ],
            [
                'name'              => 'Maria Santos',
                'email'             => 'maria.santos@empresa.com',
                'expected_strategy' => 'Nome completo disponível'
            ],
            [
                'name'              => 'João Silva', // Duplicado
                'email'             => 'joao.silva.duplicado@empresa.com',
                'expected_strategy' => 'Email como alternativa'
            ],
            [
                'name'              => 'Ana Costa',
                'email'             => 'ana.costa@empresa.com',
                'expected_strategy' => 'Nome completo disponível'
            ],
            [
                'name'              => 'João Silva', // Duplicado novamente
                'email'             => 'joao.silva.outro@empresa.com',
                'expected_strategy' => 'Email como alternativa'
            ],
            [
                'name'              => 'João Silva', // Terceira duplicata
                'email'             => 'jsilva@empresa.com',
                'expected_strategy' => 'Contador sequencial'
            ],
        ];

        $createdTenants = [];
        $results        = [];

        foreach ( $testCases as $index => $testCase ) {
            $this->info( "Teste " . ( $index + 1 ) . ": {$testCase[ 'name' ]} - {$testCase[ 'email' ]}" );

            // Separar nome em first_name e last_name
            $nameParts = explode( ' ', $testCase[ 'name' ] );
            $firstName = $nameParts[ 0 ];
            $lastName  = $nameParts[ 1 ] ?? '';

            $userData = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $testCase[ 'email' ]
            ];

            // Testar geração de nome
            $reflection = new \ReflectionClass( $this->userRegistrationService );
            $method     = $reflection->getMethod( 'generateUniqueTenantName' );
            $method->setAccessible( true );

            $generatedName = $method->invoke( $this->userRegistrationService, $userData );

            // Criar tenant para testar
            $tenant = new Tenant( [
                'name'      => $generatedName,
                'is_active' => true,
            ] );

            try {
                $savedTenant      = $this->tenantRepository->createTenant( [
                    'name'      => $generatedName,
                    'is_active' => true,
                ] );
                $createdTenants[] = $savedTenant;

                $results[] = [
                    'input'     => $testCase[ 'name' ] . ' <' . $testCase[ 'email' ] . '>',
                    'generated' => $generatedName,
                    'strategy'  => $this->determineStrategy( $generatedName, $userData ),
                    'success'   => true
                ];

                $this->line( "  ✅ Gerado: {$generatedName}" );

            } catch ( \Exception $e ) {
                $results[] = [
                    'input'     => $testCase[ 'name' ] . ' <' . $testCase[ 'email' ] . '>',
                    'generated' => $generatedName,
                    'strategy'  => 'Erro na criação',
                    'success'   => false,
                    'error'     => $e->getMessage()
                ];

                $this->error( "  ❌ Erro: " . $e->getMessage() );
            }

            $this->newLine();
        }

        // Mostrar resumo
        $this->info( '📊 Resumo dos testes:' );
        $this->table(
            [ 'Entrada', 'Nome Gerado', 'Estratégia', 'Status' ],
            collect( $results )->map( fn( $result ) => [
                $result[ 'input' ],
                $result[ 'generated' ],
                $result[ 'strategy' ],
                $result[ 'success' ] ? '✅ Sucesso' : '❌ Falha'
            ] )->toArray(),
        );

        // Limpeza se solicitado
        if ( $this->option( 'cleanup' ) ) {
            $this->info( '🧹 Limpando tenants criados...' );

            foreach ( $createdTenants as $tenant ) {
                try {
                    $tenant->delete();
                    $this->line( "  🗑️  Removido: {$tenant->name}" );
                } catch ( \Exception $e ) {
                    $this->error( "  ❌ Erro ao remover {$tenant->name}: " . $e->getMessage() );
                }
            }
        }

        $this->info( '✅ Teste concluído!' );
        return 0;
    }

    /**
     * Determina qual estratégia foi usada para gerar o nome.
     */
    private function determineStrategy( string $generatedName, array $userData ): string
    {
        $baseName    = \Illuminate\Support\Str::slug( trim( $userData[ 'first_name' ] . ' ' . $userData[ 'last_name' ] ) );
        $emailPrefix = \Illuminate\Support\Str::slug( explode( '@', $userData[ 'email' ] )[ 0 ] );

        if ( $generatedName === $baseName ) {
            return 'Nome completo';
        }

        if ( $generatedName === $emailPrefix ) {
            return 'Email';
        }

        if ( str_starts_with( $generatedName, $baseName . '-' ) ) {
            return 'Nome + contador';
        }

        return 'Desconhecida';
    }

}
