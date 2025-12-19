<?php

/**
 * Teste de Paginação do CustomerRepository
 * Verifica se a correção da paginação foi bem-sucedida
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Configurar Laravel
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTE DE PAGINAÇÃO - CUSTOMER REPOSITORY\n";
echo "==========================================\n\n";

try {
    // 1. Limpar dados de teste anteriores (ordem correta: filhos primeiro)
    echo "1. Limpando dados de teste anteriores...\n";
    Address::where( 'tenant_id', 1 )->delete();
    Contact::where( 'tenant_id', 1 )->delete();
    CommonData::where( 'tenant_id', 1 )->delete();
    Customer::where( 'tenant_id', 1 )->delete();
    echo "✅ Dados limpos\n\n";

    // 2. Criar dados de teste (20 customers)
    echo "2. Criando 20 customers para teste...\n";
    $tenantId = 1;

    for ( $i = 1; $i <= 20; $i++ ) {
        $customer = Customer::create( [
            'tenant_id' => $tenantId,
            'status'    => 'active',
        ] );

        CommonData::create( [
            'tenant_id'   => $tenantId,
            'customer_id' => $customer->id,
            'first_name'  => "Cliente {$i}",
            'last_name'   => "Teste",
            'type'        => 'individual',
        ] );

        Contact::create( [
            'tenant_id'      => $tenantId,
            'customer_id'    => $customer->id,
            'email_personal' => "cliente{$i}@teste.com",
            'phone_personal' => "(11) 99999-{$i}000",
        ] );

        Address::create( [
            'tenant_id'   => $tenantId,
            'customer_id' => $customer->id,
            'address'     => "Rua Teste {$i}",
            'city'        => "São Paulo",
            'state'       => "SP",
            'cep'         => "01000-000",
        ] );

        if ( $i % 5 == 0 ) {
            echo "   Criados {$i} customers...\n";
        }
    }
    echo "✅ 20 customers criados\n\n";

    // 3. Testar paginação
    echo "3. Testando paginação (5 itens por página)...\n";
    $repository = new CustomerRepository();

    // Página 1
    echo "   📄 Página 1: \n";
    $page1 = $repository->getPaginated( [], 5, [ 'commonData' ] );
    echo "      Total: {$page1->total()} itens\n";
    echo "      Por página: {$page1->perPage()} itens\n";
    echo "      Itens nesta página: " . $page1->count() . "\n";
    echo "      Primeiro item: ID " . ( $page1->first()?->id ?? 'N/A' ) . "\n";
    echo "      Último item: ID " . ( $page1->last()?->id ?? 'N/A' ) . "\n";

    if ( $page1->count() == 5 ) {
        echo "      ✅ Página 1 tem 5 itens (CORRETO)\n";
    } else {
        echo "      ❌ Página 1 tem {$page1->count()} itens (ERRO - deve ter 5)\n";
    }
    echo "\n";

    // Página 2 (simular navegação com URL parameter)
    echo "   📄 Página 2: \n";
    // Simular request com página 2
    request()->merge( [ 'page' => 2 ] );
    $page2 = $repository->getPaginated( [], 5, [ 'commonData' ] );
    echo "      Total: {$page2->total()} itens\n";
    echo "      Por página: {$page2->perPage()} itens\n";
    echo "      Itens nesta página: " . $page2->count() . "\n";
    echo "      Primeiro item: ID " . ( $page2->first()?->id ?? 'N/A' ) . "\n";
    echo "      Último item: ID " . ( $page2->last()?->id ?? 'N/A' ) . "\n";

    if ( $page2->count() == 5 ) {
        echo "      ✅ Página 2 tem 5 itens (CORRETO)\n";
    } else {
        echo "      ❌ Página 2 tem {$page2->count()} itens (ERRO - deve ter 5)\n";
    }

    // Verificar se página 2 tem dados diferentes da página 1
    $page1Ids = $page1->pluck( 'id' )->sort()->values();
    $page2Ids = $page2->pluck( 'id' )->sort()->values();

    if ( $page1Ids != $page2Ids ) {
        echo "      ✅ Página 2 tem itens diferentes da página 1 (CORRETO)\n";
    } else {
        echo "      ❌ Página 2 tem os mesmos itens da página 1 (ERRO)\n";
    }
    echo "\n";

    // Página 3 (simular navegação com URL parameter)
    echo "   📄 Página 3: \n";
    request()->merge( [ 'page' => 3 ] );
    $page3 = $repository->getPaginated( [], 5, [ 'commonData' ] );
    echo "      Total: {$page3->total()} itens\n";
    echo "      Por página: {$page3->perPage()} itens\n";
    echo "      Itens nesta página: " . $page3->count() . "\n";
    echo "      Primeiro item: ID " . ( $page3->first()?->id ?? 'N/A' ) . "\n";
    echo "      Último item: ID " . ( $page3->last()?->id ?? 'N/A' ) . "\n";

    if ( $page3->count() == 5 ) {
        echo "      ✅ Página 3 tem 5 itens (CORRETO)\n";
    } else {
        echo "      ❌ Página 3 tem {$page3->count()} itens (ERRO - deve ter 5)\n";
    }
    echo "\n";

    // Página 4 (última) (simular navegação com URL parameter)
    echo "   📄 Página 4: \n";
    request()->merge( [ 'page' => 4 ] );
    $page4 = $repository->getPaginated( [], 5, [ 'commonData' ] );
    echo "      Total: {$page4->total()} itens\n";
    echo "      Por página: {$page4->perPage()} itens\n";
    echo "      Itens nesta página: " . $page4->count() . "\n";
    echo "      Primeiro item: ID " . ( $page4->first()?->id ?? 'N/A' ) . "\n";
    echo "      Último item: ID " . ( $page4->last()?->id ?? 'N/A' ) . "\n";

    // Página 4 deve ter 5 itens (20 total / 5 por página = 4 páginas)
    if ( $page4->count() == 5 ) {
        echo "      ✅ Página 4 tem 5 itens (CORRETO)\n";
    } else {
        echo "      ❌ Página 4 tem {$page4->count()} itens (ERRO - deve ter 5)\n";
    }
    echo "\n";

    // 4. Verificar filtros simples
    echo "4. Testando filtros simples...\n";

    // Filtro por status
    $filtered = $repository->getPaginated( [ 'status' => 'active' ], 10, [ 'commonData' ] );
    echo "   Filtro por status 'active': {$filtered->total()} resultados\n";

    if ( $filtered->total() == 20 ) {
        echo "   ✅ Filtro por status funcionando (CORRETO)\n";
    } else {
        echo "   ❌ Filtro por status retornou {$filtered->total()} resultados (ERRO)\n";
    }
    echo "\n";

    // 5. Verificar eager loading
    echo "5. Verificando eager loading...\n";
    $testItem = $repository->getPaginated( [], 1, [ 'commonData' ] )->first();

    if ( $testItem && $testItem->relationLoaded( 'commonData' ) ) {
        echo "   ✅ Eager loading de 'commonData' funcionando (CORRETO)\n";
    } else {
        echo "   ❌ Eager loading de 'commonData' não funcionando (ERRO)\n";
    }
    echo "\n";

    // 6. Resultado final
    echo "6. RESULTADO FINAL\n";
    echo "==================\n";

    $allPagesWorking     = ( $page1->count() == 5 && $page2->count() == 5 && $page3->count() == 5 && $page4->count() == 5 );
    $dataDifferent       = ( $page1Ids != $page2Ids && $page2Ids != ( $page3->pluck( 'id' )->sort()->values() ) );
    $filtersWorking      = ( $filtered->total() == 20 );
    $eagerLoadingWorking = ( $testItem && $testItem->relationLoaded( 'commonData' ) );

    if ( $allPagesWorking && $dataDifferent && $filtersWorking && $eagerLoadingWorking ) {
        echo "🎉 ✅ PAGINAÇÃO DO CUSTOMER REPOSITORY FUNCIONANDO 100%\n";
        echo "   - Todas as páginas têm dados corretos\n";
        echo "   - Navegação entre páginas funcionando\n";
        echo "   - Filtros simples operacionais\n";
        echo "   - Eager loading ativo\n";
        echo "\n";
        echo "🚀 CORREÇÃO BEM-SUCEDIDA!\n";
    } else {
        echo "❌ PROBLEMAS IDENTIFICADOS:\n";
        if ( !$allPagesWorking ) echo "   - Páginas não têm quantidade correta de itens\n";
        if ( !$dataDifferent ) echo "   - Páginas têm dados duplicados\n";
        if ( !$filtersWorking ) echo "   - Filtros não estão funcionando\n";
        if ( !$eagerLoadingWorking ) echo "   - Eager loading não está ativo\n";
    }

} catch ( Exception $e ) {
    echo "❌ ERRO DURANTE O TESTE:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . " linha " . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
echo "🏁 Teste concluído!\n";
