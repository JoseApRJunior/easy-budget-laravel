# Script de Correção Automática - PHPStan Level 8
# Easy Budget Laravel System (PowerShell Version)

Write-Host "🚀 Iniciando correções automáticas do código..." -ForegroundColor Blue
Write-Host "📊 Análise baseada em PHPStan Level 8" -ForegroundColor Blue
Write-Host ""

# Contadores
$FIXED_IMPORTS = 0
$FIXED_NAMESPACES = 0
$CREATED_FILES = 0
$ERRORS_FOUND = 0

function Write-Info {
    param($Message)
    Write-Host "[INFO] $Message" -ForegroundColor Cyan
}

function Write-Success {
    param($Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor Green
}

function Write-Warning {
    param($Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param($Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
    $script:ERRORS_FOUND++
}

# 1. CORRIGIR IMPORTAÇÕES EM ROTAS
Write-Host "📋 1. Corrigindo importações em arquivos de rotas..." -ForegroundColor Blue

$routeFiles = Get-ChildItem -Path "routes" -Filter "*.php" -File

foreach ($file in $routeFiles) {
    $content = Get-Content $file.FullName -Raw
    
    # Verificar se já tem use statement
    if ($content -notmatch "use Illuminate\\Support\\Facades\\Route") {
        # Adicionar use statement após <?php
        $newContent = $content -replace "<\?php", "<?php`r`nuse Illuminate\Support\Facades\Route;"
        Set-Content -Path $file.FullName -Value $newContent -NoNewline
        $FIXED_IMPORTS++
        Write-Success "Adicionado use statement em $($file.Name)"
    }
}

# 2. CRIAR CLASSES DE SERVIÇO AUSENTES
Write-Host ""
Write-Host "🔧 2. Criando classes de serviço ausentes..." -ForegroundColor Blue

# Criar MailerService
$MAILER_SERVICE_PATH = "app/Services/Infrastructure/MailerService.php"
if (!(Test-Path $MAILER_SERVICE_PATH)) {
    $mailerServiceContent = @'
<?php

namespace App\Services\Infrastructure;

use App\Support\ServiceResult;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
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
     * @param string `$to` Email do destinatário
     * @param Mailable `$mailable` Instância do Mailable
     * @return ServiceResult
     */
    public function send(string `$to`, Mailable `$mailable`): ServiceResult
    {
        try {
            Mail::to(`$to)->send(`$mailable);
            
            return ServiceResult::success(['to' => `$to], 'Email enviado com sucesso');
        } catch (Exception `$e) {
            return ServiceResult::error('Erro ao enviar email: ' . `$e->getMessage());
        }
    }

    /**
     * Envia email para múltiplos destinatários
     *
     * @param array `$recipients` Array de emails
     * @param Mailable `$mailable` Instância do Mailable
     * @return ServiceResult
     */
    public function sendToMany(array `$recipients`, Mailable `$mailable`): ServiceResult
    {
        try {
            Mail::to(`$recipients)->send(`$mailable);
            
            return ServiceResult::success(['recipients' => `$recipients], 'Emails enviados com sucesso');
        } catch (Exception `$e) {
            return ServiceResult::error('Erro ao enviar emails: ' . `$e->getMessage());
        }
    }

    /**
     * Valida se um email está em formato válido
     *
     * @param string `$email` Email a validar
     * @return bool
     */
    public function isValidEmail(string `$email`): bool
    {
        return filter_var(`$email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
'@
    
    # Criar diretório se não existir
    if (!(Test-Path "app/Services/Infrastructure")) {
        New-Item -ItemType Directory -Path "app/Services/Infrastructure" -Force | Out-Null
    }
    
    Set-Content -Path $MAILER_SERVICE_PATH -Value $mailerServiceContent -NoNewline
    $CREATED_FILES++
    Write-Success "Criado MailerService em $MAILER_SERVICE_PATH"
}

# 3. CORRIGIR CONTROLLERS COM IMPORTAÇÕES AUSENTES
Write-Host ""
Write-Host "🎮 3. Corrigindo controllers com importações ausentes..." -ForegroundColor Blue

function Fix-Controller-Imports {
    param($ControllerFile)
    
    if (!(Test-Path $ControllerFile)) {
        return
    }
    
    $content = Get-Content $ControllerFile -Raw
    
    # Imports comuns que podem estar faltando
    $commonImports = @(
        "use App\Support\ServiceResult;",
        "use Illuminate\Http\JsonResponse;",
        "use Illuminate\Http\RedirectResponse;",
        "use Illuminate\Http\Request;",
        "use Illuminate\View\View;",
        "use Illuminate\Support\Facades\Auth;",
        "use Illuminate\Support\Facades\Log;",
        "use Illuminate\Support\Facades\DB;"
    )
    
    foreach ($import in $commonImports) {
        $importClass = $import -replace "use ", "" -replace ";", ""
        $className = $importClass.Split("\")[-1]
        
        # Verificar se a classe é usada no arquivo e não está importada
        if ($content -match $className -and $content -notmatch [regex]::Escape($import)) {
            # Adicionar após a linha do namespace
            $content = $content -replace "^(namespace .+)$", "`$1`r`n$import"
            $script:FIXED_IMPORTS++
            Write-Success "Adicionado import: $import em $(Split-Path $ControllerFile -Leaf)"
        }
    }
    
    Set-Content -Path $ControllerFile -Value $content -NoNewline
}

# Processar todos os controllers
$controllers = Get-ChildItem -Path "app/Http/Controllers" -Filter "*.php" -Recurse -File
foreach ($controller in $controllers) {
    Fix-Controller-Imports -ControllerFile $controller.FullName
}

# 4. CORRIGIR MODELS
Write-Host ""
Write-Host "📊 4. Corrigindo models..." -ForegroundColor Blue

function Fix-Model-Traits {
    param($ModelFile)
    
    if (!(Test-Path $ModelFile)) {
        return
    }
    
    $content = Get-Content $ModelFile -Raw
    
    # Verificar se é um model Eloquent
    if ($content -match "extends Model") {
        # Adicionar imports de traits comuns se necessário
        $traitsImports = @(
            "use Illuminate\Database\Eloquent\Factories\HasFactory;",
            "use Illuminate\Database\Eloquent\SoftDeletes;"
        )
        
        foreach ($import in $traitsImports) {
            if ($content -notmatch [regex]::Escape($import)) {
                $content = $content -replace "^(namespace .+)$", "`$1`r`n$import"
                Write-Info "Adicionado import: $import em $(Split-Path $ModelFile -Leaf)"
            }
        }
        
        Set-Content -Path $ModelFile -Value $content -NoNewline
    }
}

# Processar todos os models
$models = Get-ChildItem -Path "app/Models" -Filter "*.php" -File
foreach ($model in $models) {
    Fix-Model-Traits -ModelFile $model.FullName
}

# 5. CORRIGIR SERVICES
Write-Host ""
Write-Host "⚙️ 5. Corrigindo services..." -ForegroundColor Blue

function Fix-Services {
    param($ServiceFile)
    
    if (!(Test-Path $ServiceFile)) {
        return
    }
    
    $content = Get-Content $ServiceFile -Raw
    
    # Adicionar use statement para ServiceResult se necessário
    if ($content -match "ServiceResult" -and $content -notmatch [regex]::Escape("use App\Support\ServiceResult")) {
        $content = $content -replace "^(namespace .+)$", "`$1`r`nuse App\Support\ServiceResult;"
        $script:FIXED_IMPORTS++
        Write-Success "Adicionado ServiceResult import em $(Split-Path $ServiceFile -Leaf)"
    }
    
    Set-Content -Path $ServiceFile -Value $content -NoNewline
}

# Processar todos os services
$services = Get-ChildItem -Path "app/Services" -Filter "*.php" -Recurse -File
foreach ($service in $services) {
    Fix-Services -ServiceFile $service.FullName
}

# 6. ATUALIZAR AUTOLOAD
Write-Host ""
Write-Host "🔄 6. Atualizando autoload..." -ForegroundColor Blue

composer dump-autoload
Write-Success "Autoload atualizado"

# 7. EXECUTAR LARAVEL PINT (FORMATAÇÃO)
Write-Host ""
Write-Host "🎨 7. Executando Laravel Pint para formatação..." -ForegroundColor Blue

if (Test-Path "./vendor/bin/pint") {
    ./vendor/bin/pint
    Write-Success "Código formatado com Laravel Pint"
} else {
    Write-Warning "Laravel Pint não encontrado, instalando..."
    composer require --dev laravel/pint
    ./vendor/bin/pint
}

# RELATÓRIO FINAL
Write-Host ""
Write-Host "========================================" -ForegroundColor Blue
Write-Host "📈 RELATÓRIO DE CORREÇÕES AUTOMÁTICAS" -ForegroundColor Blue
Write-Host "========================================" -ForegroundColor Blue
Write-Host ""
Write-Host -NoNewline -ForegroundColor Green "✅ Importações corrigidas: "
Write-Host $FIXED_IMPORTS
Write-Host -NoNewline -ForegroundColor Green "✅ Namespaces corrigidos: "
Write-Host $FIXED_NAMESPACES
Write-Host -NoNewline -ForegroundColor Green "✅ Arquivos criados: "
Write-Host $CREATED_FILES
Write-Host -NoNewline -ForegroundColor Red "❌ Erros encontrados: "
Write-Host $ERRORS_FOUND
Write-Host ""

if ($ERRORS_FOUND -eq 0) {
    Write-Host "🎉 Todas as correções automáticas foram aplicadas com sucesso!" -ForegroundColor Green
} else {
    Write-Host "⚠️ Algumas correções automáticas falharam." -ForegroundColor Yellow
    Write-Host "   Verifique os logs acima para detalhes." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🔍 Próximos passos:" -ForegroundColor Cyan
Write-Host "   1. Execute PHPStan novamente para verificar melhorias" -ForegroundColor Cyan
Write-Host "   2. Revise manualmente os erros críticos restantes" -ForegroundColor Cyan
Write-Host "   3. Execute os testes do sistema" -ForegroundColor Cyan
Write-Host "   4. Commit e push das alterações" -ForegroundColor Cyan
Write-Host ""
Write-Host "📊 Comando para re-análise:" -ForegroundColor Cyan
Write-Host "   .\vendor\bin\phpstan analyse --configuration=phpstan-max.neon" -ForegroundColor Cyan

# Re-executar análise simplificada
Write-Host ""
Write-Host "🔄 Executando re-análise simplificada..." -ForegroundColor Blue

if (Test-Path ".\vendor\bin\phpstan") {
    .\vendor\bin\phpstan analyse --configuration=phpstan-max.neon app\Http\Controllers\Admin\ --error-format=table --no-progress | Select-Object -First 20
}

Write-Host ""
Write-Host "✅ Processo de correção automática concluído!" -ForegroundColor Green