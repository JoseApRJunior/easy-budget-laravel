# Relatório de Análise: DocumentVerificationController

## 📋 Informações Gerais

**Controller:** `DocumentVerificationController`  
**Namespace Old System:** `app\controllers`  
**Tipo:** Controller de Verificação de Documentos  
**Propósito:** Verificar autenticidade de documentos via hash

---

## 🎯 Funcionalidades Identificadas

### 1. **verify(string $hash)**
- **Descrição:** Verifica autenticidade de documento através de hash único
- **Método HTTP:** GET
- **Parâmetros:** `$hash` - Hash de verificação do documento
- **Retorno:** View com resultado da verificação
- **Processo:**
  1. Busca hash em tabela `budgets` (campo `pdf_verification_hash`)
  2. Se não encontrar, busca em `services` (campo `pdf_verification_hash`)
  3. Se não encontrar, busca em `reports` (campo `hash`)
  4. Retorna view com documento encontrado ou mensagem de não encontrado
- **Tipos de Documentos:**
  - Orçamento
  - Ordem de Serviço
  - Relatório
- **Dependências:**
  - `Budget` model
  - `Service` model
  - `Report` model
  - `Twig` template engine

---

## 🔗 Dependências do Sistema Antigo

### Models Utilizados
- `Budget` - Orçamentos
- `Service` - Ordens de Serviço
- `Report` - Relatórios

### Campos de Hash
- `budgets.pdf_verification_hash`
- `services.pdf_verification_hash`
- `reports.hash`

### Views
- `pages/document/verify.twig`

---

## 🏗️ Implementação no Novo Sistema Laravel

### Estrutura Proposta

```
app/Http/Controllers/
└── DocumentVerificationController.php

app/Services/Domain/
└── DocumentVerificationService.php

resources/views/
└── pages/
    └── document/
        └── verify.blade.php

routes/
└── web.php (rota pública)
```

### Rotas Sugeridas

```php
// routes/web.php (PÚBLICA - sem autenticação)
Route::get('/verify/{hash}', [DocumentVerificationController::class, 'verify'])
    ->name('document.verify');

// Rota alternativa com prefixo
Route::prefix('document')->group(function () {
    Route::get('/verify/{hash}', [DocumentVerificationController::class, 'verify'])
        ->name('document.verify');
});
```

### Services Necessários

1. **DocumentVerificationService** - Lógica de verificação
   - Buscar documento por hash
   - Identificar tipo de documento
   - Validar integridade
   - Registrar tentativas de verificação

---

## 📝 Padrão de Implementação

### Controller Pattern: Simple Controller (Public)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Domain\DocumentVerificationService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DocumentVerificationController extends Controller
{
    public function __construct(
        private DocumentVerificationService $verificationService
    ) {}

    public function verify(string $hash): View
    {
        $result = $this->verificationService->verifyDocument($hash);

        return view('pages.document.verify', [
            'found' => $result->isSuccess(),
            'document' => $result->data['document'] ?? null,
            'type' => $result->data['type'] ?? 'desconhecido',
            'hash' => $hash,
            'verified_at' => now(),
        ]);
    }
}
```

### Service Implementation

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Budget;
use App\Models\Service;
use App\Models\Report;
use App\Support\ServiceResult;
use App\Enums\OperationStatus;
use Illuminate\Support\Facades\Log;

class DocumentVerificationService
{
    public function __construct(
        private Budget $budgetModel,
        private Service $serviceModel,
        private Report $reportModel,
    ) {}

    public function verifyDocument(string $hash): ServiceResult
    {
        // Log tentativa de verificação
        Log::info('Document verification attempt', ['hash' => $hash]);

        // 1. Buscar em Orçamentos
        $budget = $this->budgetModel
            ->where('pdf_verification_hash', $hash)
            ->first();

        if ($budget) {
            $this->logVerification($hash, 'budget', $budget->id);
            
            return ServiceResult::success([
                'document' => $budget,
                'type' => 'Orçamento',
                'entity_type' => 'budget',
            ]);
        }

        // 2. Buscar em Serviços
        $service = $this->serviceModel
            ->where('pdf_verification_hash', $hash)
            ->first();

        if ($service) {
            $this->logVerification($hash, 'service', $service->id);
            
            return ServiceResult::success([
                'document' => $service,
                'type' => 'Ordem de Serviço',
                'entity_type' => 'service',
            ]);
        }

        // 3. Buscar em Relatórios
        $report = $this->reportModel
            ->where('hash', $hash)
            ->first();

        if ($report) {
            $this->logVerification($hash, 'report', $report->id);
            
            return ServiceResult::success([
                'document' => $report,
                'type' => 'Relatório',
                'entity_type' => 'report',
            ]);
        }

        // Documento não encontrado
        Log::warning('Document not found', ['hash' => $hash]);
        
        return ServiceResult::error(
            OperationStatus::NOT_FOUND,
            'Documento não encontrado',
            ['hash' => $hash]
        );
    }

    private function logVerification(string $hash, string $type, int $entityId): void
    {
        Log::info('Document verified successfully', [
            'hash' => $hash,
            'type' => $type,
            'entity_id' => $entityId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Opcional: Salvar em tabela de auditoria
        // DocumentVerification::create([...]);
    }

    public function generateHash(string $content): string
    {
        return hash('sha256', $content . config('app.key'));
    }

    public function validateHash(string $hash): bool
    {
        return strlen($hash) === 64 && ctype_xdigit($hash);
    }
}
```

### View Implementation (Blade)

```blade
@extends('layouts.public')

@section('title', 'Verificação de Documento')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-primary" style="font-size: 4rem;"></i>
                        <h1 class="h3 mt-3">Verificação de Documento</h1>
                    </div>

                    @if($found)
                        {{-- Documento Encontrado --}}
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Documento Autêntico</strong>
                        </div>

                        <div class="document-info">
                            <h5 class="mb-3">Informações do Documento</h5>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Tipo:</th>
                                    <td><span class="badge bg-primary">{{ $type }}</span></td>
                                </tr>
                                <tr>
                                    <th>Código:</th>
                                    <td>{{ $document->code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Data de Emissão:</th>
                                    <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Hash de Verificação:</th>
                                    <td><code class="small">{{ $hash }}</code></td>
                                </tr>
                                <tr>
                                    <th>Verificado em:</th>
                                    <td>{{ $verified_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>

                            @if($type === 'Orçamento')
                                <div class="mt-4">
                                    <h6>Detalhes do Orçamento</h6>
                                    <p><strong>Cliente:</strong> {{ $document->customer->common_data->first_name ?? 'N/A' }}</p>
                                    <p><strong>Valor Total:</strong> R$ {{ number_format($document->total, 2, ',', '.') }}</p>
                                    <p><strong>Status:</strong> {{ $document->budget_status->label() }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Este documento foi gerado pelo sistema Easy Budget e sua autenticidade foi verificada.
                        </div>

                    @else
                        {{-- Documento Não Encontrado --}}
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            <strong>Documento Não Encontrado</strong>
                        </div>

                        <div class="text-center py-4">
                            <p class="text-muted">
                                O hash de verificação fornecido não corresponde a nenhum documento em nosso sistema.
                            </p>
                            <p class="small text-muted">
                                Hash: <code>{{ $hash }}</code>
                            </p>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Atenção:</strong> Este documento pode não ser autêntico ou o hash pode estar incorreto.
                        </div>
                    @endif

                    <div class="text-center mt-4">
                        <a href="{{ route('home') }}" class="btn btn-outline-primary">
                            <i class="bi bi-house me-2"></i>Voltar para Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .document-info {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
    }
    code {
        word-break: break-all;
    }
</style>
@endpush
```

---

## ✅ Checklist de Implementação

### Fase 1: Database
- [ ] Verificar campo `pdf_verification_hash` em `budgets`
- [ ] Verificar campo `pdf_verification_hash` em `services`
- [ ] Verificar campo `hash` em `reports`
- [ ] Adicionar índices para performance

### Fase 2: Service
- [ ] Criar `DocumentVerificationService`
- [ ] Implementar `verifyDocument()`
- [ ] Implementar `generateHash()`
- [ ] Implementar `validateHash()`
- [ ] Implementar logging de verificações

### Fase 3: Controller
- [ ] Criar `DocumentVerificationController`
- [ ] Implementar método `verify()`
- [ ] Configurar rota pública

### Fase 4: View
- [ ] Criar `verify.blade.php`
- [ ] Implementar layout para documento encontrado
- [ ] Implementar layout para documento não encontrado
- [ ] Adicionar informações de segurança

### Fase 5: Integração
- [ ] Adicionar hash em PDFs de orçamentos
- [ ] Adicionar hash em PDFs de serviços
- [ ] Adicionar hash em relatórios
- [ ] Adicionar QR Code com link de verificação

### Fase 6: Testes
- [ ] Testes unitários para `DocumentVerificationService`
- [ ] Testes de feature para rota pública
- [ ] Testes de segurança

---

## 🔒 Considerações de Segurança

1. **Rota Pública:** Não requer autenticação
2. **Rate Limiting:** Limitar tentativas por IP
3. **Logging:** Registrar todas as tentativas
4. **Hash Validation:** Validar formato do hash
5. **Information Disclosure:** Não expor dados sensíveis
6. **CAPTCHA:** Considerar CAPTCHA após múltiplas tentativas

---

## 📊 Prioridade de Implementação

**Prioridade:** MÉDIA  
**Complexidade:** BAIXA  
**Dependências:** Models (Budget, Service, Report)

**Ordem Sugerida:**
1. Criar DocumentVerificationService
2. Criar DocumentVerificationController
3. Criar view de verificação
4. Integrar com geração de PDFs
5. Testes

---

## 💡 Melhorias Sugeridas

1. **Auditoria:** Tabela para registrar verificações
2. **Analytics:** Dashboard de verificações
3. **QR Code:** Gerar QR Code com link de verificação
4. **Notificação:** Notificar tenant quando documento é verificado
5. **Expiração:** Hash com validade
6. **API:** Endpoint API para verificação programática
7. **Blockchain:** Considerar blockchain para maior segurança
8. **Certificado:** Gerar certificado de autenticidade
9. **Histórico:** Mostrar histórico de verificações
10. **Multi-idioma:** Suporte a múltiplos idiomas

---

## 📦 Tabela de Auditoria (Opcional)

```php
Schema::create('document_verifications', function (Blueprint $table) {
    $table->id();
    $table->string('hash', 64)->index();
    $table->string('entity_type', 50)->nullable();
    $table->unsignedBigInteger('entity_id')->nullable();
    $table->boolean('found')->default(false);
    $table->ipAddress('ip_address');
    $table->text('user_agent')->nullable();
    $table->timestamps();
    
    $table->index(['entity_type', 'entity_id']);
    $table->index('created_at');
});
```
