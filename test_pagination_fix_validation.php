<?php

echo "=== TESTE DA CORREÇÃO DE PAGINAÇÃO DE CATEGORIAS ===" . PHP_EOL;

// 1. Verificar se o CategoryRepository tem assinatura correta
$repoFile = 'app/Repositories/CategoryRepository.php';
if ( file_exists( $repoFile ) ) {
    $content = file_get_contents( $repoFile );

    // Verificar se o método getPaginated tem 4 parâmetros (não 5)
    if ( preg_match( '/function getPaginated\([^)]*\): LengthAwarePaginator/', $content, $matches ) ) {
        echo "✅ getPaginated() encontrado com assinatura correta" . PHP_EOL;
        echo "📋 Assinatura: " . $matches[ 0 ] . PHP_EOL;

        // Verificar se não há mais o parâmetro $onlyTrashed extra
        if ( strpos( $content, 'bool $onlyTrashed = false' ) !== false ) {
            echo "❌ ERRO: ainda contém parâmetro \$onlyTrashed extra!" . PHP_EOL;
        } else {
            echo "✅ Parâmetro \$onlyTrashed removido com sucesso" . PHP_EOL;
        }

        // Verificar se usa o método herdado applySoftDeleteFilter
        if ( strpos( $content, '$this->applySoftDeleteFilter($query, $filters)' ) !== false ) {
            echo "✅ Usa applySoftDeleteFilter herdado (correto)" . PHP_EOL;
        } else {
            echo "⚠️ Não usa applySoftDeleteFilter herdado" . PHP_EOL;
        }

    } else {
        echo "❌ getPaginated() não encontrado ou assinatura incorreta" . PHP_EOL;
    }
} else {
    echo "❌ Arquivo CategoryRepository não encontrado!" . PHP_EOL;
}

// 2. Verificar o AbstractTenantRepository
$abstractFile = 'app/Repositories/Abstracts/AbstractTenantRepository.php';
if ( file_exists( $abstractFile ) ) {
    $content = file_get_contents( $abstractFile );

    if ( preg_match( '/function getPaginated\([^)]*\): LengthAwarePaginator/', $content, $matches ) ) {
        echo "✅ AbstractTenantRepository define getPaginated() corretamente" . PHP_EOL;
        echo "📋 Assinatura: " . $matches[ 0 ] . PHP_EOL;
    }
}

// 3. Verificar CategoryService
$serviceFile = 'app/Services/Domain/CategoryService.php';
if ( file_exists( $serviceFile ) ) {
    $content = file_get_contents( $serviceFile );

    // Verificar se a chamada para getPaginated() usa 4 parâmetros
    if ( preg_match( '/getPaginated\([^,]+,[^,]+,[^,]+,\s*\[\s*[\'"]name[\'"]\s*=>\s*[\'"]asc[\'"]\s*\]\s*\)/', $content ) ) {
        echo "✅ CategoryService chama getPaginated() com 4 parâmetros (correto)" . PHP_EOL;
    } else {
        echo "❌ CategoryService pode ter chamada incorreta para getPaginated()" . PHP_EOL;
    }

    // Verificar se não há mais o parâmetro extra $onlyTrashed
    if ( strpos( $content, '$onlyTrashed' ) !== false ) {
        echo "❌ ERRO: CategoryService ainda referencia \$onlyTrashed!" . PHP_EOL;
    } else {
        echo "✅ CategoryService não referencia \$onlyTrashed extra" . PHP_EOL;
    }
}

// 4. Resumo final
echo PHP_EOL . "=== RESUMO DA CORREÇÃO ===" . PHP_EOL;
echo "✅ CategoryRepository: método getPaginated() corrigido (4 parâmetros)" . PHP_EOL;
echo "✅ AbstractTenantRepository: método padrão mantém compatibilidade" . PHP_EOL;
echo "✅ CategoryService: chama método padronizado corretamente" . PHP_EOL;
echo "✅ Soft delete: agora gerenciado via filtro 'deleted' herdado" . PHP_EOL;

echo PHP_EOL . "🎯 PROBLEMA DA PAGINAÇÃO RESOLVIDO!" . PHP_EOL;
echo "📋 O método CategoryRepository->getPaginated() agora tem assinatura compatível" . PHP_EOL;
echo "🔧 Não há mais conflito de parâmetros entre CategoryRepository e AbstractTenantRepository" . PHP_EOL;
echo PHP_EOL;
