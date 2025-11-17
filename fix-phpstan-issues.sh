#!/bin/bash

# Script de Correção Automática - PHPStan Level 8
# Easy Budget Laravel System

echo "🚀 Iniciando correções automáticas do código..."
echo "📊 Análise baseada em PHPStan Level 8"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Contadores
FIXED_IMPORTS=0
FIXED_NAMESPACES=0
CREATED_FILES=0
ERRORS_FOUND=0

# Função para log
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    ((ERRORS_FOUND++))
}

# 1. CORRIGIR IMPORTAÇÕES EM ROTAS
echo "📋 1. Corrigindo importações em arquivos de rotas..."

# Adicionar use statements em arquivos de rotas
for route_file in routes/*.php; do
    if [ -f "$route_file" ]; then
        # Verificar se já tem use statement
        if ! grep -q "use Illuminate\\Support\\Facades\\Route" "$route_file"; then
            # Adicionar use statement após <?php
            sed -i '1a use Illuminate\\Support\\Facades\\Route;' "$route_file"
            ((FIXED_IMPORTS++))
            log_success "Adicionado use statement em $route_file"
        fi
    fi
done

# 2. CRIAR CLASSES DE SERVIÇO AUSENTES
echo ""
echo "🔧 2. Criando classes de serviço ausentes..."

# Criar MailerService
MAILER_SERVICE_PATH="app/Services/Infrastructure/MailerService.php"
if [ ! -f "$MAILER_SERVICE_PATH" ]; then
    mkdir -p app/Services/Infrastructure
    cat > "$MAILER_SERVICE_PATH" << 'EOF'
<?php

namespace App\Services\Infrastructure;

use App\Support\ServiceResult;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Exception;

/**
 * Serviço de envio de emails para o sistema Easy Budget
 * 
 * @package App\Services\Infrastructure
 */
class MailerService
{
    /**
     * Envia um email utilizando uma classe Mailable
     *
     * @param string $to Email do destinatário
     * @param Mailable $mailable Instância do Mailable
     * @return ServiceResult
     */
    public function send(string $to, Mailable $mailable): ServiceResult
    {
        try {
            Mail::to($to)->send($mailable);
            
            return ServiceResult::success(['to' => $to], 'Email enviado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error('Erro ao enviar email: ' . $e->getMessage());
        }
    }

    /**
     * Envia email para múltiplos destinatários
     *
     * @param array $recipients Array de emails
     * @param Mailable $mailable Instância do Mailable
     * @return ServiceResult
     */
    public function sendToMany(array $recipients, Mailable $mailable): ServiceResult
    {
        try {
            Mail::to($recipients)->send($mailable);
            
            return ServiceResult::success(['recipients' => $recipients], 'Emails enviados com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error('Erro ao enviar emails: ' . $e->getMessage());
        }
    }

    /**
     * Envia email com anexos
     *
     * @param string $to Email do destinatário
     * @param Mailable $mailable Instância do Mailable
     * @param array $attachments Array de paths dos anexos
     * @return ServiceResult
     */
    public function sendWithAttachments(string $to, Mailable $mailable, array $attachments): ServiceResult
    {
        try {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mailable->attach($attachment);
                }
            }
            
            return $this->send($to, $mailable);
        } catch (Exception $e) {
            return ServiceResult::error('Erro ao enviar email com anexos: ' . $e->getMessage());
        }
    }

    /**
     * Valida se um email está em formato válido
     *
     * @param string $email Email a validar
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Formata um array de emails para envio
     *
     * @param array $emails Array de emails (strings ou array com nome/email)
     * @return array Array formatado
     */
    public function formatRecipients(array $emails): array
    {
        $formatted = [];
        
        foreach ($emails as $key => $value) {
            if (is_string($value)) {
                if ($this->isValidEmail($value)) {
                    $formatted[] = $value;
                }
            } elseif (is_array($value) && isset($value['email'])) {
                $email = $value['email'];
                $name = $value['name'] ?? null;
                
                if ($this->isValidEmail($email)) {
                    $formatted[$email] = $name;
                }
            }
        }
        
        return $formatted;
    }
}
EOF
    ((CREATED_FILES++))
    log_success "Criado MailerService em $MAILER_SERVICE_PATH"
fi

# 3. CORRIGIR CONTROLLERS COM IMPORTAÇÕES AUSENTES
echo ""
echo "🎮 3. Corrigindo controllers com importações ausentes..."

# Função para adicionar imports ausentes em controllers
fix_controller_imports() {
    local controller_file="$1"
    
    if [ ! -f "$controller_file" ]; then
        return
    fi
    
    # Imports comuns que podem estar faltando
    local common_imports=(
        "use App\Support\ServiceResult;"
        "use Illuminate\Http\JsonResponse;"
        "use Illuminate\Http\RedirectResponse;"
        "use Illuminate\Http\Request;"
        "use Illuminate\View\View;"
        "use Illuminate\Support\Facades\Auth;"
        "use Illuminate\Support\Facades\Log;"
        "use Illuminate\Support\Facades\DB;"
    )
    
    for import in "${common_imports[@]}"; do
        local import_class=$(echo "$import" | sed 's/use //' | sed 's/;//')
        
        # Verificar se o import é necessário (classe é usada no arquivo)
        local class_name=$(echo "$import_class" | rev | cut -d'\\' -f1 | rev)
        
        if grep -q "$class_name" "$controller_file" && ! grep -q "$import" "$controller_file"; then
            # Adicionar após a linha do namespace
            sed -i "/^namespace /a $import" "$controller_file"
            ((FIXED_IMPORTS++))
            log_success "Adicionado import: $import em $(basename "$controller_file")"
        fi
    done
}

# Processar todos os controllers
for controller in app/Http/Controllers/**/*.php; do
    if [ -f "$controller" ]; then
        fix_controller_imports "$controller"
    fi
done

# 4. CORRIGIR MODELS
echo ""
echo "📊 4. Corrigindo models..."

# Função para adicionar traits ausentes em models
fix_model_traits() {
    local model_file="$1"
    
    if [ ! -f "$model_file" ]; then
        return
    fi
    
    # Verificar se é um model Eloquent
    if grep -q "extends Model" "$model_file"; then
        # Adicionar imports de traits comuns se necessário
        local traits_imports=(
            "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;"
            "use Illuminate\\Database\\Eloquent\\SoftDeletes;"
        )
        
        for import in "${traits_imports[@]}"; do
            if ! grep -q "$import" "$model_file"; then
                sed -i "/^namespace /a $import" "$model_file"
                log_info "Adicionado import: $import em $(basename "$model_file")"
            fi
        done
    fi
}

# Processar todos os models
for model in app/Models/*.php; do
    if [ -f "$model" ]; then
        fix_model_traits "$model"
    fi
done

# 5. CORRIGIR SERVICES
echo ""
echo "⚙️  5. Corrigindo services..."

# Função para corrigir services
fix_services() {
    local service_file="$1"
    
    if [ ! -f "$service_file" ]; then
        return
    fi
    
    # Adicionar use statement para ServiceResult se necessário
    if grep -q "ServiceResult" "$service_file" && ! grep -q "use App\\Support\\ServiceResult" "$service_file"; then
        sed -i '/^namespace /a use App\\Support\\ServiceResult;' "$service_file"
        ((FIXED_IMPORTS++))
        log_success "Adicionado ServiceResult import em $(basename "$service_file")"
    fi
}

# Processar todos os services
for service in app/Services/**/*.php; do
    if [ -f "$service" ]; then
        fix_services "$service"
    fi
done

# 6. ATUALIZAR AUTOLOAD
echo ""
echo "🔄 6. Atualizando autoload..."
composer dump-autoload
log_success "Autoload atualizado"

# 7. EXECUTAR LARAVEL PINT (FORMATAÇÃO)
echo ""
echo "🎨 7. Executando Laravel Pint para formatação..."
if [ -f "./vendor/bin/pint" ]; then
    ./vendor/bin/pint
    log_success "Código formatado com Laravel Pint"
else
    log_warning "Laravel Pint não encontrado, instalando..."
    composer require --dev laravel/pint
    ./vendor/bin/pint
fi

# 8. RELATÓRIO FINAL
echo ""
echo "========================================"
echo "📈 RELATÓRIO DE CORREÇÕES AUTOMÁTICAS"
echo "========================================"
echo ""
echo -e "${GREEN}✅ Importações corrigidas:${NC} $FIXED_IMPORTS"
echo -e "${GREEN}✅ Namespaces corrigidos:${NC} $FIXED_NAMESPACES"
echo -e "${GREEN}✅ Arquivos criados:${NC} $CREATED_FILES"
echo -e "${RED}❌ Erros encontrados:${NC} $ERRORS_FOUND"
echo ""

if [ $ERRORS_FOUND -eq 0 ]; then
    echo -e "${GREEN}🎉 Todas as correções automáticas foram aplicadas com sucesso!${NC}"
else
    echo -e "${YELLOW}⚠️  Algumas correções automáticas falharam.${NC}"
    echo "   Verifique os logs acima para detalhes."
fi

echo ""
echo "🔍 Próximos passos:"
echo "   1. Execute PHPStan novamente para verificar melhorias"
echo "   2. Revise manualmente os erros críticos restantes"
echo "   3. Execute os testes do sistema"
echo "   4. Commit e push das alterações"
echo ""
echo "📊 Comando para re-análise:"
echo "   ./vendor/bin/phpstan analyse --configuration=phpstan-max.neon"

# Re-executar análise simplificada
echo ""
echo "🔄 Executando re-análise simplificada..."
if [ -f "./vendor/bin/phpstan" ]; then
    ./vendor/bin/phpstan analyse --configuration=phpstan-max.neon app/Http/Controllers/Admin/ --error-format=table --no-progress | head -20
fi

echo ""
echo "✅ Processo de correção automática concluído!"