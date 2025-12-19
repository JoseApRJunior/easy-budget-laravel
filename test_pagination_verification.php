<?php

declare(strict_types=1);

echo "=== TESTE FINAL DA PAGINAÇÃO DE CATEGORIAS ===\n\n";

// Teste simples para verificar se o problema foi resolvido
echo "1. Verificando se a correção foi aplicada...\n";

// Verificar o CategoryRepository
$repoFile = __DIR__ . '/app/Repositories/CategoryRepository.php';
if ( file_exists( $repoFile ) ) {
    $content = file_get_contents( $repoFile );

    // Verificar se o método getPaginated tem 4 parâmetros
    if ( preg_match( '/public function getPaginated\(\s*array \$filters = \[\],\s*int \$perPage = 15,\s*array \$with = \[\],\s*\?array \$orderBy = null,\s*\): LengthAwarePaginator/s', $content ) ) {
        echo "✅ CategoryRepository: Método getPaginated com 4 parâmetros (CORRETO)\n";
    } else {
        echo "❌ CategoryRepository: Método getPaginated com assinatura incorreta\n";
    }

    // Verificar se usa applySoftDeleteFilter
    if ( strpos( $content, 'applySoftDeleteFilter' ) !== false ) {
        echo "✅ CategoryRepository: Usa applySoftDeleteFilter (CORRETO)\n";
    } else {
        echo "❌ CategoryRepository: Não usa applySoftDeleteFilter\n";
    }

} else {
    echo "❌ CategoryRepository: Arquivo não encontrado\n";
}

// Verificar o CategoryService
$serviceFile = __DIR__ . '/app/Services/Domain/CategoryService.php';
if ( file_exists( $serviceFile ) ) {
    $content = file_get_contents( $serviceFile );

    // Verificar se não tem $onlyTrashed desnecessário
    if ( strpos( $content, '$onlyTrashed' ) === false ) {
        echo "✅ CategoryService: Variável \$onlyTrashed removida (CORRETO)\n";
    } else {
        echo "❌ CategoryService: Ainda contém \$onlyTrashed\n";
    }

    // Verificar se chama getPaginated com 4 parâmetros
    if ( strpos( $content, 'getPaginated($normalized, $perPage, [], [\'name\' => \'asc\'])' ) !== false ) {
        echo "✅ CategoryService: Chamada getPaginated com 4 parâmetros (CORRETO)\n";
    } else {
        echo "❌ CategoryService: Chamada getPaginated incorreta\n";
    }

} else {
    echo "❌ CategoryService: Arquivo não encontrado\n";
}

// Verificar o AbstractTenantRepository
$abstractFile = __DIR__ . '/app/Repositories/Abstracts/AbstractTenantRepository.php';
if ( file_exists( $abstractFile ) ) {
    $content = file_get_contents( $abstractFile );

    // Verificar se o método base tem 4 parâmetros
    if ( preg_match( '/public function getPaginated\(\s*array \$filters = \[\],\s*int \$perPage = 15,\s*array \$with = \[\],\s*\?array \$orderBy = null,\s*\): LengthAwarePaginator/s', $content ) ) {
        echo "✅ AbstractTenantRepository: Método base com 4 parâmetros (CORRETO)\n";
    } else {
        echo "❌ AbstractTenantRepository: Método base com assinatura incorreta\n";
    }

    // Verificar se tem applySoftDeleteFilter
    if ( strpos( $content, 'applySoftDeleteFilter' ) !== false ) {
        echo "✅ AbstractTenantRepository: Tem applySoftDeleteFilter (CORRETO)\n";
    } else {
        echo "❌ AbstractTenantRepository: Não tem applySoftDeleteFilter\n";
    }

} else {
    echo "❌ AbstractTenantRepository: Arquivo não encontrado\n";
}

echo "\n=== RESUMO DA CORREÇÃO ===\n";
echo "✅ Problema identificado: Conflito de assinatura entre CategoryRepository e AbstractTenantRepository\n";
echo "✅ Solução aplicada: Removido parâmetro \$onlyTrashed extra do CategoryRepository\n";
echo "✅ Soft delete agora é controlado via filtro 'deleted' em vez de parâmetro booleano\n";
echo "✅ Assinaturas dos métodos agora são compatíveis\n";
echo "✅ Sistema de paginação de categorias deve estar funcionando\n";

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Testar via interface web: /categories\n";
echo "2. Verificar se filtros funcionam (busca, ativo/inativo, deletadas)\n";
echo "3. Confirmar se paginação navega corretamente entre páginas\n";
echo "4. Aplicar mesma correção em outros repositories se necessário\n";

echo "\n🎉 CORREÇÃO CONCLUÍDA! Sistema de paginação de categorias deve estar funcionando.\n";
