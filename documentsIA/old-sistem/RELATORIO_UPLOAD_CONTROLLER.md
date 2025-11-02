# Relatório de Análise: UploadController

## 📋 Informações Gerais

**Controller:** `UploadController`  
**Namespace Old System:** `app\controllers`  
**Tipo:** Controller de Upload de Arquivos  
**Propósito:** Gerenciar upload e processamento de imagens

---

## 🎯 Funcionalidades Identificadas

### 1. **index()**
- **Descrição:** Exemplo de upload com redimensionamento e marca d'água
- **Método HTTP:** POST
- **Funcionalidades:**
  - Upload de imagem
  - Redimensionamento (200px largura, altura proporcional)
  - Aplicação de marca d'água (watermark.png)
  - Posicionamento: top-right
  - Opacidade: 70%
  - Remoção de arquivo antigo
- **Dependências:**
  - `core\support\UploadImage`
  - `Session`, `User` (não utilizados no código)

---

## 🔗 Dependências do Sistema Antigo

### Classes Utilizadas
- `UploadImage` - Classe customizada para processamento de imagens

### Funcionalidades da Classe UploadImage
- `make()` - Inicializa o upload
- `resize($width, $height, $proportional)` - Redimensiona imagem
- `watermark($file, $position, $x, $y, $width, $height, $opacity)` - Aplica marca d'água
- `execute()` - Executa o processamento
- `get_image_info()` - Retorna informações da imagem

---

## 🏗️ Implementação no Novo Sistema Laravel

### Estrutura Proposta

```
app/Http/Controllers/
└── UploadController.php

app/Services/Infrastructure/
└── ImageProcessingService.php

config/
└── upload.php (configurações)

storage/app/
├── public/
│   ├── uploads/
│   ├── logos/
│   └── watermarks/
```

### Rotas Sugeridas

```php
// routes/web.php
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/upload/image', [UploadController::class, 'uploadImage']);
    Route::post('/upload/logo', [UploadController::class, 'uploadLogo']);
    Route::delete('/upload/{id}', [UploadController::class, 'delete']);
});
```

### Services Necessários

1. **ImageProcessingService** - Processamento de imagens com Intervention Image
   - Upload
   - Redimensionamento
   - Marca d'água
   - Otimização
   - Conversão de formatos

---

## 📝 Padrão de Implementação

### Controller Pattern: Simple Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Services\Infrastructure\ImageProcessingService;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageService
    ) {}

    public function uploadImage(ImageUploadRequest $request): JsonResponse
    {
        $result = $this->imageService->upload(
            file: $request->file('image'),
            options: [
                'resize' => ['width' => 200, 'proportional' => true],
                'watermark' => config('upload.watermark'),
                'path' => 'uploads',
            ]
        );

        return $result->isSuccess()
            ? response()->json(['url' => $result->data['url']])
            : response()->json(['error' => $result->message], 400);
    }

    public function uploadLogo(ImageUploadRequest $request): JsonResponse
    {
        $result = $this->imageService->upload(
            file: $request->file('logo'),
            options: [
                'resize' => ['width' => 300, 'height' => 300],
                'path' => 'logos',
            ]
        );

        return response()->json($result->data);
    }

    public function delete(int $id): JsonResponse
    {
        $result = $this->imageService->delete($id);
        
        return response()->json(['success' => $result->isSuccess()]);
    }
}
```

### Service Implementation

```php
<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Support\ServiceResult;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    public function upload(UploadedFile $file, array $options = []): ServiceResult
    {
        try {
            $image = Image::make($file);

            // Resize
            if (isset($options['resize'])) {
                $image->resize(
                    $options['resize']['width'] ?? null,
                    $options['resize']['height'] ?? null,
                    fn($constraint) => $constraint->aspectRatio()
                );
            }

            // Watermark
            if (isset($options['watermark'])) {
                $watermark = Image::make(storage_path('app/watermarks/watermark.png'));
                $watermark->opacity($options['watermark']['opacity'] ?? 70);
                
                $image->insert(
                    $watermark,
                    $options['watermark']['position'] ?? 'top-right',
                    $options['watermark']['x'] ?? 10,
                    $options['watermark']['y'] ?? 10
                );
            }

            // Save
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = ($options['path'] ?? 'uploads') . '/' . $filename;
            
            Storage::disk('public')->put($path, (string) $image->encode());

            return ServiceResult::success([
                'url' => Storage::url($path),
                'path' => $path,
                'size' => $file->getSize(),
            ]);

        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao processar imagem: ' . $e->getMessage()
            );
        }
    }

    public function delete(string $path): ServiceResult
    {
        try {
            Storage::disk('public')->delete($path);
            return ServiceResult::success();
        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao deletar imagem'
            );
        }
    }
}
```

### Request Validation

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'A imagem é obrigatória.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ser maior que 5MB.',
        ];
    }
}
```

---

## ✅ Checklist de Implementação

### Fase 1: Configuração
- [ ] Instalar Intervention Image: `composer require intervention/image`
- [ ] Configurar storage links: `php artisan storage:link`
- [ ] Criar diretórios de upload
- [ ] Adicionar marca d'água padrão

### Fase 2: Service
- [ ] Criar `ImageProcessingService`
- [ ] Implementar método `upload()`
- [ ] Implementar método `resize()`
- [ ] Implementar método `watermark()`
- [ ] Implementar método `delete()`
- [ ] Implementar método `optimize()`

### Fase 3: Controller
- [ ] Criar `UploadController`
- [ ] Implementar `uploadImage()`
- [ ] Implementar `uploadLogo()`
- [ ] Implementar `delete()`
- [ ] Criar `ImageUploadRequest`

### Fase 4: Configuração
- [ ] Criar `config/upload.php`
- [ ] Configurar limites de tamanho
- [ ] Configurar formatos permitidos
- [ ] Configurar marca d'água padrão

### Fase 5: Testes
- [ ] Testes unitários para `ImageProcessingService`
- [ ] Testes de feature para upload
- [ ] Testes de validação
- [ ] Testes de segurança

---

## 🔒 Considerações de Segurança

1. **Validação de Tipo:** Verificar MIME type real do arquivo
2. **Tamanho Máximo:** Limitar tamanho de upload (5MB)
3. **Formatos Permitidos:** Apenas imagens (jpeg, png, jpg, gif)
4. **Nome de Arquivo:** Gerar nomes únicos (UUID)
5. **Sanitização:** Remover metadados EXIF sensíveis
6. **Storage:** Armazenar fora do public root
7. **Vírus Scan:** Considerar scan de vírus em produção

---

## 📊 Prioridade de Implementação

**Prioridade:** ALTA  
**Complexidade:** MÉDIA  
**Dependências:** Intervention Image

**Ordem Sugerida:**
1. Instalar e configurar Intervention Image
2. Criar ImageProcessingService
3. Criar UploadController
4. Implementar validações
5. Testes

---

## 💡 Melhorias Sugeridas

1. **Otimização Automática:** Comprimir imagens automaticamente
2. **Múltiplos Tamanhos:** Gerar thumbnails (small, medium, large)
3. **CDN:** Integração com CDN para servir imagens
4. **Lazy Loading:** Implementar lazy loading no frontend
5. **WebP:** Converter para formato WebP para melhor performance
6. **Crop:** Adicionar funcionalidade de crop
7. **Filtros:** Adicionar filtros de imagem (blur, grayscale, etc)
8. **Progress Bar:** Mostrar progresso de upload
9. **Drag & Drop:** Interface drag and drop
10. **Preview:** Preview antes de upload

---

## 📦 Pacotes Recomendados

- **intervention/image** (v3) - Processamento de imagens
- **spatie/laravel-medialibrary** - Gerenciamento completo de mídia (opcional)
- **league/flysystem-aws-s3-v3** - Storage em S3 (produção)
