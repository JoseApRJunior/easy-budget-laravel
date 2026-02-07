# Relatório de Análise: QrCodeController

## 📋 Informações Gerais

**Controller:** `QrCodeController`  
**Namespace Old System:** `app\controllers`  
**Tipo:** Controller de Geração e Leitura de QR Code  
**Propósito:** Gerar e decodificar QR Codes

---

## 🎯 Funcionalidades Identificadas

### 1. **handle(string $text)**
- **Descrição:** Gera QR Code e imediatamente o decodifica
- **Método HTTP:** POST
- **Parâmetros:** `$text` - Texto ou URL para codificar
- **Retorno:** JSON com texto original e decodificado
- **Processo:**
  1. Cria QR Code com o texto
  2. Salva em arquivo temporário
  3. Lê o QR Code gerado
  4. Retorna ambos os textos
  5. Remove arquivo temporário
- **Dependências:**
  - `Endroid\QrCode\QrCode`
  - `Zxing\QrReader`

### 2. **generate(string $text)**
- **Descrição:** Apenas gera QR Code
- **Método HTTP:** POST
- **Parâmetros:** `$text` - Texto ou URL para codificar
- **Retorno:** JSON com QR Code em base64
- **Dependências:**
  - `Endroid\QrCode\QrCode`

---

## 🔗 Dependências do Sistema Antigo

### Bibliotecas Externas
- `endroid/qr-code` - Geração de QR Codes
- `zxing-php/qrcode-reader` - Leitura de QR Codes

---

## 🏗️ Implementação no Novo Sistema Laravel

### Estrutura Proposta

```
app/Http/Controllers/
└── QrCodeController.php

app/Services/Infrastructure/
└── QrCodeService.php

storage/app/
└── qrcodes/ (temporário)
```

### Rotas Sugeridas

```php
// routes/web.php
Route::middleware(['auth', 'tenant'])->prefix('qrcode')->group(function () {
    Route::post('/generate', [QrCodeController::class, 'generate']);
    Route::post('/read', [QrCodeController::class, 'read']);
    Route::post('/handle', [QrCodeController::class, 'handle']);
});

// routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/qrcode')->group(function () {
    Route::post('/generate', [QrCodeController::class, 'generate']);
});
```

### Services Necessários

1. **QrCodeService** - Geração e leitura de QR Codes
   - Gerar QR Code
   - Ler QR Code
   - Customizar aparência
   - Validar dados

---

## 📝 Padrão de Implementação

### Controller Pattern: API Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\QrCodeRequest;
use App\Services\Infrastructure\QrCodeService;
use Illuminate\Http\JsonResponse;

class QrCodeController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService
    ) {}

    public function generate(QrCodeRequest $request): JsonResponse
    {
        $result = $this->qrCodeService->generate(
            text: $request->input('text'),
            options: $request->only(['size', 'margin', 'format'])
        );

        return $result->isSuccess()
            ? response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $result->data['base64'],
                    'url' => $result->data['url'] ?? null,
                ]
            ])
            : response()->json([
                'success' => false,
                'message' => $result->message
            ], 400);
    }

    public function read(QrCodeRequest $request): JsonResponse
    {
        $result = $this->qrCodeService->read(
            $request->file('qrcode')
        );

        return response()->json([
            'success' => $result->isSuccess(),
            'data' => ['text' => $result->data['text'] ?? null],
            'message' => $result->message
        ]);
    }

    public function handle(QrCodeRequest $request): JsonResponse
    {
        $generateResult = $this->qrCodeService->generate($request->input('text'));
        
        if (!$generateResult->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => $generateResult->message
            ], 400);
        }

        $readResult = $this->qrCodeService->readFromBase64(
            $generateResult->data['base64']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'original_text' => $request->input('text'),
                'decoded_text' => $readResult->data['text'] ?? null,
                'qr_code' => $generateResult->data['base64'],
            ]
        ]);
    }
}
```

### Service Implementation

```php
<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Support\ServiceResult;
use App\Enums\OperationStatus;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Zxing\QrReader;

class QrCodeService
{
    public function generate(string $text, array $options = []): ServiceResult
    {
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($text)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size($options['size'] ?? 300)
                ->margin($options['margin'] ?? 10)
                ->build();

            $base64 = base64_encode($result->getString());

            // Opcionalmente salvar em storage
            if ($options['save'] ?? false) {
                $filename = 'qrcodes/' . uniqid() . '.png';
                Storage::put($filename, $result->getString());
                $url = Storage::url($filename);
            }

            return ServiceResult::success([
                'base64' => $base64,
                'url' => $url ?? null,
                'data_uri' => $result->getDataUri(),
            ]);

        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao gerar QR Code: ' . $e->getMessage()
            );
        }
    }

    public function read(UploadedFile $file): ServiceResult
    {
        try {
            $tempPath = $file->store('temp');
            $fullPath = Storage::path($tempPath);

            $qrReader = new QrReader($fullPath);
            $text = $qrReader->text();

            Storage::delete($tempPath);

            if (empty($text)) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Não foi possível ler o QR Code'
                );
            }

            return ServiceResult::success(['text' => $text]);

        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao ler QR Code: ' . $e->getMessage()
            );
        }
    }

    public function readFromBase64(string $base64): ServiceResult
    {
        try {
            $imageData = base64_decode($base64);
            $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
            file_put_contents($tempFile, $imageData);

            $qrReader = new QrReader($tempFile);
            $text = $qrReader->text();

            unlink($tempFile);

            return ServiceResult::success(['text' => $text]);

        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao decodificar QR Code'
            );
        }
    }

    public function generateForBudget(int $budgetId, string $url): ServiceResult
    {
        return $this->generate($url, [
            'size' => 200,
            'save' => true,
        ]);
    }

    public function generateForInvoice(int $invoiceId, string $url): ServiceResult
    {
        return $this->generate($url, [
            'size' => 200,
            'save' => true,
        ]);
    }
}
```

### Request Validation

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QrCodeRequest extends FormRequest
{
    public function rules(): array
    {
        return match($this->route()->getName()) {
            'qrcode.generate' => [
                'text' => 'required|string|max:1000',
                'size' => 'nullable|integer|min:100|max:1000',
                'margin' => 'nullable|integer|min:0|max:50',
            ],
            'qrcode.read' => [
                'qrcode' => 'required|file|mimes:png,jpg,jpeg|max:2048',
            ],
            'qrcode.handle' => [
                'text' => 'required|string|max:1000',
            ],
            default => [],
        };
    }
}
```

---

## ✅ Checklist de Implementação

### Fase 1: Instalação
- [ ] Instalar: `composer require endroid/qr-code`
- [ ] Instalar: `composer require khanamiryan/qrcode-detector-decoder`
- [ ] Configurar storage para QR Codes

### Fase 2: Service
- [ ] Criar `QrCodeService`
- [ ] Implementar `generate()`
- [ ] Implementar `read()`
- [ ] Implementar `readFromBase64()`
- [ ] Implementar métodos específicos (Budget, Invoice)

### Fase 3: Controller
- [ ] Criar `QrCodeController`
- [ ] Implementar `generate()`
- [ ] Implementar `read()`
- [ ] Implementar `handle()`
- [ ] Criar `QrCodeRequest`

### Fase 4: Integração
- [ ] Adicionar QR Code em PDFs de orçamentos
- [ ] Adicionar QR Code em PDFs de faturas
- [ ] Adicionar QR Code em páginas públicas

### Fase 5: Testes
- [ ] Testes unitários para `QrCodeService`
- [ ] Testes de feature para endpoints
- [ ] Testes de integração com PDFs

---

## 🔒 Considerações de Segurança

1. **Validação de Input:** Limitar tamanho do texto (max 1000 chars)
2. **Sanitização:** Sanitizar URLs antes de gerar QR Code
3. **Rate Limiting:** Limitar geração de QR Codes por minuto
4. **File Upload:** Validar tipo e tamanho de arquivo
5. **Temporary Files:** Limpar arquivos temporários
6. **Storage:** Limpar QR Codes antigos periodicamente

---

## 📊 Prioridade de Implementação

**Prioridade:** BAIXA  
**Complexidade:** BAIXA  
**Dependências:** endroid/qr-code, qrcode-detector-decoder

**Ordem Sugerida:**
1. Instalar pacotes
2. Criar QrCodeService
3. Criar QrCodeController
4. Integrar com PDFs
5. Testes

---

## 💡 Melhorias Sugeridas

1. **Customização:** Cores, logo, formato
2. **Cache:** Cachear QR Codes gerados
3. **Analytics:** Rastrear scans de QR Codes
4. **Expiração:** QR Codes com validade
5. **Encurtador:** Integrar com encurtador de URL
6. **Estatísticas:** Dashboard de uso de QR Codes
7. **Batch:** Gerar múltiplos QR Codes de uma vez
8. **Templates:** Templates pré-configurados
9. **Download:** Opção de download em diferentes formatos (PNG, SVG, PDF)
10. **Preview:** Preview antes de gerar

---

## 📦 Casos de Uso

1. **Orçamentos:** QR Code para visualização pública
2. **Faturas:** QR Code para pagamento
3. **Produtos:** QR Code para informações do produto
4. **Verificação:** QR Code para verificar autenticidade de documentos
5. **Check-in:** QR Code para eventos/serviços
6. **Compartilhamento:** QR Code para compartilhar perfil/contato
