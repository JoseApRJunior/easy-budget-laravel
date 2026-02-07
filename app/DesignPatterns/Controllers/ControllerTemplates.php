<?php

declare(strict_types=1);

namespace App\DesignPatterns\Controllers;

/**
 * Templates Práticos para Controllers
 *
 * Fornece templates prontos para uso imediato no desenvolvimento,
 * seguindo o padrão unificado definido em ControllerPattern.
 */
class ControllerTemplates
{
    /**
     * TEMPLATE COMPLETO - Controller Nível 1 (Simples)
     */
    public function simpleControllerTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\{Module}Service;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para {Module} - Interface Web
 *
 * Gerencia operações básicas de {module} através da interface web.
 */
class {Module}Controller extends Controller
{
    public function __construct(
        private {Module}Service $service
    ) {}

    /**
     * Lista todos os {module}
     */
    public function index(): View
    {
        try {
            $result = $this->service->list();

            if ($result->isSuccess()) {
                $data = $this->getServiceData($result, []);
                $this->logOperation(\'index_accessed\', [\'data_count\' => count($data)]);

                return view(\'pages.{module}.index\', [
                    \'data\' => $data
                ]);
            }

            Log::error(\'Erro no serviço de {module}: \' . $this->getServiceErrorMessage($result));

            return view(\'pages.{module}.index\', [
                \'data\' => []
            ]);

        } catch (Exception $e) {
            Log::error(\'Erro ao carregar {module}: \' . $e->getMessage());

            return view(\'pages.{module}.index\', [
                \'data\' => []
            ]);
        }
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): View
    {
        return view(\'pages.{module}.create\');
    }

    /**
     * Salva novo {module}
     */
    public function store({Module}Request $request): RedirectResponse
    {
        try {
            $result = $this->service->create($request->validated());

            if ($result->isSuccess()) {
                return $this->redirectSuccess(
                    \'pages.{module}.index\',
                    \'{Module} criado com sucesso.\'
                );
            }

            return $this->redirectError(
                \'pages.{module}.create\',
                $this->getServiceErrorMessage($result)
            )->withInput();

        } catch (Exception $e) {
            Log::error(\'Erro ao criar {module}: \' . $e->getMessage());

            return $this->redirectError(
                \'pages.{module}.create\',
                \'Erro ao criar {module}. Tente novamente.\'
            )->withInput();
        }
    }

    /**
     * Exibe detalhes do {module}
     */
    public function show({Module} $model): View
    {
        return view(\'pages.{module}.show\', [
            \'data\' => $model
        ]);
    }

    /**
     * Exibe formulário de edição
     */
    public function edit({Module} $model): View
    {
        return view(\'pages.{module}.edit\', [
            \'data\' => $model
        ]);
    }

    /**
     * Atualiza {module}
     */
    public function update({Module}Request $request, {Module} $model): RedirectResponse
    {
        try {
            $result = $this->service->update($model->id, $request->validated());

            if ($result->isSuccess()) {
                return $this->redirectSuccess(
                    \'pages.{module}.index\',
                    \'{Module} atualizado com sucesso.\'
                );
            }

            return $this->redirectError(
                \'pages.{module}.show\',
                $this->getServiceErrorMessage($result)
            )->withInput();

        } catch (Exception $e) {
            Log::error(\'Erro ao atualizar {module}: \' . $e->getMessage());

            return $this->redirectError(
                \'pages.{module}.edit\',
                \'Erro ao atualizar {module}. Tente novamente.\'
            )->withInput();
        }
    }

    /**
     * Remove {module}
     */
    public function destroy({Module} $model): RedirectResponse
    {
        try {
            $result = $this->service->delete($model->id);

            if ($result->isSuccess()) {
                return $this->redirectSuccess(
                    \'pages.{module}.index\',
                    \'{Module} removido com sucesso.\'
                );
            }

            return $this->redirectError(
                \'pages.{module}.index\',
                $this->getServiceErrorMessage($result)
            );

        } catch (Exception $e) {
            Log::error(\'Erro ao remover {module}: \' . $e->getMessage());

            return $this->redirectError(
                \'pages.{module}.index\',
                \'Erro ao remover {module}. Tente novamente.\'
            );
        }
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Controller Nível 2 (Com Filtros)
     */
    public function filteredControllerTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\{Module}Request;
use App\Models\{Module};
use App\Services\{Module}Service;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller para {Module} - Interface Web com Filtros
 *
 * Gerencia operações avançadas de {module} com filtros, busca e paginação.
 */
class {Module}Controller extends Controller
{
    public function __construct(
        private {Module}Service $service
    ) {}

    /**
     * Lista {module} com filtros e paginação
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            \'search\', \'status\', \'category\', \'date_from\', \'date_to\',
            \'sort_by\', \'sort_direction\', \'per_page\'
        ]);

        try {
            $result = $this->service->list($filters);

            if ($result->isSuccess()) {
                $data = $this->getServiceData($result, []);

                // Dados adicionais para a view
                $additionalData = $this->getAdditionalIndexData($request);

                $this->logOperation(\'index_accessed\', [
                    \'filters\' => $filters,
                    \'data_count\' => count($data)
                ]);

                return view(\'pages.{module}.index\', array_merge([
                    \'data\' => $data,
                    \'filters\' => $filters
                ], $additionalData));
            }

            Log::error(\'Erro no serviço de {module}: \' . $this->getServiceErrorMessage($result));

            return view(\'pages.{module}.index\', [
                \'data\' => [],
                \'filters\' => $filters
            ]);

        } catch (Exception $e) {
            Log::error(\'Erro ao carregar {module}: \' . $e->getMessage());

            return view(\'pages.{module}.index\', [
                \'data\' => [],
                \'filters\' => $filters
            ]);
        }
    }

    /**
     * Busca avançada via AJAX
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only([\'search\', \'status\', \'category\']);

        try {
            $result = $this->service->search($filters);

            if ($result->isSuccess()) {
                return $this->jsonSuccess($result->getData());
            }

            return $this->jsonError($this->getServiceErrorMessage($result));

        } catch (Exception $e) {
            Log::error(\'Erro na busca de {module}: \' . $e->getMessage());

            return $this->jsonError(\'Erro na busca. Tente novamente.\');
        }
    }

    /**
     * Exporta dados
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([\'search\', \'status\', \'category\', \'format\']);

        try {
            $result = $this->service->export($filters);

            if ($result->isSuccess()) {
                $data = $result->getData();

                $filename = \'{module}_export_\' . now()->format(\'Y-m-d_H-i-s\');

                return match($request->get(\'format\', \'xlsx\')) {
                    \'csv\' => $this->exportToCsv($data, $filename),
                    \'pdf\' => $this->exportToPdf($data, $filename),
                    default => $this->exportToExcel($data, $filename),
                };
            }

            return back()->with(\'error\', $this->getServiceErrorMessage($result));

        } catch (Exception $e) {
            Log::error(\'Erro na exportação de {module}: \' . $e->getMessage());

            return back()->with(\'error\', \'Erro na exportação. Tente novamente.\');
        }
    }

    /**
     * Obtém dados adicionais para a view index
     */
    protected function getAdditionalIndexData(Request $request): array
    {
        return [
            \'stats\' => $this->service->getStats(),
            \'categories\' => $this->service->getCategories(),
            \'recentItems\' => $this->service->getRecentItems(),
        ];
    }

    // Implementações básicas de exportação
    private function exportToCsv(array $data, string $filename): BinaryFileResponse
    {
        // Implementação específica para CSV
        return response()->download(storage_path("app/exports/{$filename}.csv"));
    }

    private function exportToPdf(array $data, string $filename): BinaryFileResponse
    {
        // Implementação específica para PDF
        return response()->download(storage_path("app/exports/{$filename}.pdf"));
    }

    private function exportToExcel(array $data, string $filename): BinaryFileResponse
    {
        // Implementação específica para Excel
        return response()->download(storage_path("app/exports/{$filename}.xlsx"));
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Controller Nível 3 (Híbrido Web + API)
     */
    public function hybridControllerTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\{Module}Request;
use App\Models\{Module};
use App\Services\{Module}Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller Híbrido para {Module} - Web + API
 *
 * Gerencia operações de {module} para interface web e API simultaneamente.
 */
class {Module}Controller extends Controller
{
    public function __construct(
        private {Module}Service $service
    ) {}

    /**
     * Lista {module} (Web + API)
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            $filters = $request->only([\'status\', \'name\', \'order_by\', \'limit\']);
            $orderBy = $request->get(\'order_by\', [\'name\' => \'asc\']);
            $limit = $request->get(\'limit\', 15);

            $result = $this->service->list($filters);

            // Resposta para API
            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'data\' => $result->isSuccess() ? $result->getData() : [],
                    \'message\' => \'{Module} listados com sucesso.\'
                ]);
            }

            // Resposta para Web
            if ($result->isSuccess()) {
                $data = $this->getServiceData($result, []);

                $this->logOperation(\'index_accessed\', [
                    \'filters\' => $filters,
                    \'data_count\' => count($data)
                ]);

                return view(\'pages.{module}.index\', [
                    \'data\' => $data,
                    \'filters\' => $filters
                ]);
            }

            Log::error(\'Erro no serviço de {module}: \' . $this->getServiceErrorMessage($result));

            return view(\'pages.{module}.index\', [
                \'data\' => [],
                \'filters\' => $filters
            ]);

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao listar {module}.\');
        }
    }

    /**
     * Exibe formulário de criação (Web + API)
     */
    public function create(Request $request): View|JsonResponse
    {
        try {
            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'message\' => \'Formulário de criação disponível.\'
                ]);
            }

            return view(\'pages.{module}.create\');

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao exibir formulário de criação.\');
        }
    }

    /**
     * Cria novo {module} (Web + API)
     */
    public function store({Module}Request $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->service->create($request->validated());

            if (!$result->isSuccess()) {
                return $this->handleValidationError($result, $request);
            }

            $message = \'{Module} criado com sucesso.\';

            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'data\' => $result->getData(),
                    \'message\' => $message
                ], 201);
            }

            return redirect()->route(\'pages.{module}.index\')
                ->with(\'success\', $message);

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao criar {module}.\');
        }
    }

    /**
     * Exibe {module} específico (Web + API)
     */
    public function show(int $id, Request $request): View|JsonResponse
    {
        try {
            $result = $this->service->findById($id);

            if (!$result->isSuccess()) {
                return $this->handleNotFound($request, $result->getMessage() ?? \'{Module} não encontrado.\');
            }

            $data = $result->getData();

            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'data\' => $data,
                    \'message\' => \'{Module} encontrado com sucesso.\'
                ]);
            }

            return view(\'pages.{module}.show\', [
                \'data\' => $data
            ]);

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao exibir {module}.\');
        }
    }

    /**
     * Atualiza {module} (Web + API)
     */
    public function update({Module}Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->service->update($id, $request->validated());

            if (!$result->isSuccess()) {
                return $this->handleValidationError($result, $request);
            }

            $message = \'{Module} atualizado com sucesso.\';

            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'data\' => $result->getData(),
                    \'message\' => $message
                ]);
            }

            return redirect()->route(\'pages.{module}.index\')
                ->with(\'success\', $message);

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao atualizar {module}.\');
        }
    }

    /**
     * Remove {module} (Web + API)
     */
    public function destroy(int $id, Request $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->service->findById($id);

            if (!$result->isSuccess()) {
                return $this->handleNotFound($request, $result->getMessage() ?? \'{Module} não encontrado.\');
            }

            $deleteResult = $this->service->delete($id);

            if (!$deleteResult->isSuccess()) {
                return $this->handleError(
                    new Exception($deleteResult->getMessage() ?? \'Falha ao deletar {module}.\'),
                    $request,
                    \'Erro ao deletar {module}.\'
                );
            }

            $message = \'{Module} deletado com sucesso.\';

            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'message\' => $message
                ]);
            }

            return redirect()->route(\'pages.{module}.index\')
                ->with(\'success\', $message);

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao deletar {module}.\');
        }
    }

    /**
     * Trata erros de validação
     */
    private function handleValidationError($result, Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                \'success\' => false,
                \'message\' => $result->getMessage() ?? \'Erro de validação.\',
                \'errors\' => $result->getData() ?? []
            ], 422);
        }

        return redirect()->back()
            ->withErrors($result->getMessage() ?? \'Erro de validação.\')
            ->withInput();
    }

    /**
     * Trata recurso não encontrado
     */
    private function handleNotFound(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                \'success\' => false,
                \'message\' => $message
            ], 404);
        }

        return redirect()->route(\'pages.{module}.index\')
            ->with(\'error\', $message);
    }

    /**
     * Trata erros genéricos
     */
    private function handleError(Exception $e, Request $request, string $defaultMessage)
    {
        $message = $e->getMessage() ?: $defaultMessage;

        if ($request->expectsJson()) {
            return response()->json([
                \'success\' => false,
                \'message\' => $message
            ], 500);
        }

        return redirect()->route(\'pages.{module}.index\')
            ->with(\'error\', $message);
    }
}';
    }

    /**
     * GUIA DE UTILIZAÇÃO DOS TEMPLATES
     */
    public function getUsageGuide(): string
    {
        return '
## Como Usar os Templates

### 1. Escolha o Nível Correto

**Nível 1 (Simples):**
- Para módulos básicos sem filtros
- Páginas estáticas ou com poucos dados
- Exemplos: About, Terms, Settings básicos

**Nível 2 (Com Filtros):**
- Para módulos com necessidade de busca/filtros
- Listagens com paginação
- Dashboards com métricas
- Exemplos: Customers, Products, Reports

**Nível 3 (Híbrido):**
- Para módulos que precisam de API
- Aplicações SPA integradas
- Microserviços internos
- Exemplos: Plans, Invoices, APIs públicas

### 2. Substitua os Placeholders

No template, substitua:
- `{Module}` → Nome do módulo (ex: Customer, Product, Budget)
- `{module}` → Nome em minúsculo (ex: customer, product, budget)
- `{Module}Service` → Nome do service (ex: CustomerService)
- `{Module}Request` → Nome do request (ex: CustomerRequest)

### 3. Personalize conforme Necessário

Cada template serve como base. Ajuste:
- Filtros específicos do módulo
- Validações particulares
- Métodos adicionais necessários
- Tratamento de erro específico

### 4. Siga as Convenções

**Estrutura de Arquivos:**
```
app/Http/Controllers/{Module}Controller.php
app/Http/Requests/{Module}Request.php
app/Services/{Module}Service.php
resources/views/pages/{module}/
```

**Nomenclatura:**
- Controllers: `{Module}Controller`
- Services: `{Module}Service`
- Requests: `{Module}Request`
- Views: `pages.{module}.*`

**Rotas:**
```php
// Em routes/web.php
Route::resource(\'{module}\', {Module}Controller::class)->names([
    \'index\' => \'pages.{module}.index\',
    \'create\' => \'pages.{module}.create\',
    \'store\' => \'pages.{module}.store\',
    \'show\' => \'pages.{module}.show\',
    \'edit\' => \'pages.{module}.edit\',
    \'update\' => \'pages.{module}.update\',
    \'destroy\' => \'pages.{module}.destroy\',
]);
```

### 5. Benefícios dos Templates

✅ **Rapidez**: Criação rápida de controllers padronizados
✅ **Consistência**: Todos seguem o mesmo padrão
✅ **Manutenibilidade**: Código familiar e fácil de manter
✅ **Qualidade**: Tratamento de erro e logging inclusos
✅ **Flexibilidade**: Fáceis de personalizar quando necessário';
    }
}
