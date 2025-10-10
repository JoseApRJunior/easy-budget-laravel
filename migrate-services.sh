#!/bin/bash

# Script auxiliar para migração de estrutura de serviços
# Uso: ./migrate-services.sh [opções]

echo "🏗️  Migração de Estrutura de Serviços - Easy Budget Laravel"
echo "=========================================================="

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script a partir do diretório raiz do projeto Laravel"
    exit 1
fi

echo "📋 Opções disponíveis:"
echo "  --dry-run    : Executar em modo simulação (recomendado primeiro)"
echo "  --backup     : Criar backup antes da migração"
echo "  --force      : Forçar execução mesmo com arquivos existentes"
echo "  --help       : Mostrar esta ajuda"
echo ""

# Processar argumentos
DRY_RUN=""
BACKUP=""
FORCE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN="--dry-run"
            echo "✅ Modo simulação ativado"
            shift
            ;;
        --backup)
            BACKUP="--backup"
            echo "✅ Backup será criado"
            shift
            ;;
        --force)
            FORCE="--force"
            echo "✅ Modo força ativado"
            shift
            ;;
        --help)
            echo "Uso: $0 [--dry-run] [--backup] [--force]"
            echo ""
            echo "Exemplos:"
            echo "  $0 --dry-run              # Apenas simular a migração"
            echo "  $0 --dry-run --backup     # Simular com backup"
            echo "  $0 --backup               # Executar com backup"
            echo "  $0 --backup --force       # Executar forçando sobrescrita"
            exit 0
            ;;
        *)
            echo "❌ Opção desconhecida: $1"
            echo "Use --help para ver opções disponíveis"
            exit 1
            ;;
    esac
done

echo ""
echo "🚀 Executando migração..."
echo "Comando: php artisan services:migrate-structure $DRY_RUN $BACKUP $FORCE"
echo ""

# Executar o comando Artisan
php artisan services:migrate-structure $DRY_RUN $BACKUP $FORCE

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Migração concluída com sucesso!"

    if [ -n "$DRY_RUN" ]; then
        echo ""
        echo "💡 Próximos passos recomendados:"
        echo "  1. Revise o plano de migração exibido acima"
        echo "  2. Execute sem --dry-run quando estiver pronto"
        echo "  3. Considere usar --backup para segurança"
    else
        echo ""
        echo "🎉 Estrutura de serviços reorganizada!"
        echo "📂 Nova estrutura criada em app/Services/"
        echo "📋 Relatório detalhado salvo em storage/app/"
    fi
else
    echo ""
    echo "❌ Migração falhou. Verifique os erros acima."
    exit 1
fi
