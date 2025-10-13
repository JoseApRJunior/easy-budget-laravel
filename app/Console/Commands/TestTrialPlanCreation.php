<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Plan;
use App\Services\Application\UserRegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestTrialPlanCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:trial-plan {--cleanup : Remove planos criados durante o teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a criação e configuração do plano trial para novos usuários';

    private UserRegistrationService $userRegistrationService;

    public function __construct( UserRegistrationService $userRegistrationService )
    {
        parent::__construct();
        $this->userRegistrationService = $userRegistrationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info( '🧪 Testando criação do plano trial...' );
        $this->newLine();

        // Teste 1: Verificar se existe plano trial
        $this->info( 'Teste 1: Verificando existência de plano trial' );

        $existingTrialPlan = Plan::where( 'slug', 'trial' )->first();
        if ( $existingTrialPlan ) {
            $this->line( "  ✅ Plano trial encontrado: {$existingTrialPlan->name} (ID: {$existingTrialPlan->id})" );
        } else {
            $this->line( '  ⚠️  Plano trial não encontrado, será criado automaticamente' );
        }

        $this->newLine();

        // Teste 2: Testar criação via UserRegistrationService
        $this->info( 'Teste 2: Testando criação via UserRegistrationService' );

        $reflection = new \ReflectionClass( $this->userRegistrationService );
        $method     = $reflection->getMethod( 'findOrCreateTrialPlan' );
        $method->setAccessible( true );

        try {
            $result = $method->invoke( $this->userRegistrationService );

            if ( $result->isSuccess() ) {
                $plan = $result->getData();
                $this->line( "  ✅ Plano criado/encontrado com sucesso!" );
                $this->line( "     Nome: {$plan->name}" );
                $this->line( "     Slug: {$plan->slug}" );
                $this->line( "     Preço: R$ " . number_format( (float) $plan->price, 2, ',', '.' ) );
                $this->line( "     Máx. Orçamentos: {$plan->max_budgets}" );
                $this->line( "     Máx. Clientes: {$plan->max_clients}" );

                // Verificar features
                $features = $plan->features;
                if ( $features ) {
                    $this->line( '     Features:' );
                    foreach ( $features as $key => $value ) {
                        $this->line( "       - {$key}: {$value}" );
                    }
                }

            } else {
                $this->error( "  ❌ Erro: " . $result->getMessage() );
                return 1;
            }

        } catch ( \Exception $e ) {
            $this->error( "  ❌ Exceção: " . $e->getMessage() );
            return 1;
        }

        $this->newLine();

        // Teste 3: Verificar características específicas do trial
        $this->info( 'Teste 3: Verificando características do plano trial' );

        $plan = Plan::where( 'slug', 'trial' )->first();
        if ( $plan ) {
            // Verificar se é realmente um plano trial
            $features = is_string( $plan->features ) ? json_decode( $plan->features, true ) : $plan->features;

            if ( in_array( 'Plano experimental gratuito', $features ) ) {
                $this->line( "  ✅ Plano marcado como trial" );
            } else {
                $this->line( "  ⚠️  Plano não marcado como trial" );
            }

            if ( in_array( 'Período de teste: 7 dias', $features ) ) {
                $this->line( "  ✅ Período de trial: 7 dias" );
            } else {
                $this->line( "  ⚠️  Período de trial não configurado corretamente" );
            }

            if ( $plan->max_budgets <= 10 && $plan->max_clients <= 50 ) {
                $this->line( "  ✅ Limites adequados para trial (reduzidos)" );
            } else {
                $this->line( "  ⚠️  Limites podem ser muito altos para trial" );
            }
        } else {
            $this->error( "  ❌ Plano trial não encontrado após criação" );
            return 1;
        }

        $this->newLine();

        // Teste 4: Verificar planos existentes
        $this->info( 'Teste 4: Listando todos os planos ativos' );

        $allPlans = Plan::where( 'status', true )->get();
        if ( $allPlans->count() > 0 ) {
            $this->table(
                [ 'ID', 'Nome', 'Slug', 'Preço', 'Máx Orçamentos', 'Máx Clientes' ],
                $allPlans->map( function ( $plan ) {
                    $features = is_string( $plan->features ) ? json_decode( $plan->features, true ) : $plan->features;
                    return [
                        $plan->id,
                        $plan->name,
                        $plan->slug,
                        'R$ ' . number_format( (float) $plan->price, 2, ',', '.' ),
                        $plan->max_budgets,
                        $plan->max_clients
                    ];
                } )->toArray(),
            );
        } else {
            $this->error( '  ❌ Nenhum plano ativo encontrado' );
            return 1;
        }

        $this->newLine();
        $this->info( '✅ Teste do plano trial concluído com sucesso!' );

        return 0;
    }

}
