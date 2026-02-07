<?php

declare(strict_types=1);

namespace App\DesignPatterns\Controllers;

/**
 * Padrão Unificado para Controllers no Easy Budget Laravel
 *
 * Define convenções consistentes para desenvolvimento de controllers,
 * garantindo uniformidade, manutenibilidade e reutilização de código.
 */
class ControllerPattern
{
    /**
     * PADRÃO UNIFICADO PARA MÉTODOS INDEX()
     *
     * Baseado na análise dos controllers existentes, definimos 3 níveis:
     */

    /**
     * NÍVEL 1 - Controller Simples (Apenas View)
     * Para páginas básicas sem filtros ou funcionalidades avançadas
     *
     * @example HomeController, AboutController
     */
    public function simpleIndex(): string
    {
        return '
    public function index(): View
    {
        try {
            $result = $this->service->list();

            if ($result->isSuccess()) {
                $data = $this->getServiceData($result, []);
                $this->logOperation(\'index_accessed\', [\'data_count\' => count($data)]);

                return view(\'pages.module.index\', [
                    \'data\' => $data
                ]);
            }

            Log::error(\'Erro no serviço: \' . $this->getServiceErrorMessage($result));

            return view(\'pages.module.index\', [
                \'data\' => []
            ]);

        } catch (Exception $e) {
            Log::error(\'Erro ao carregar página: \' . $e->getMessage());

            return view(\'pages.module.index\', [
                \'data\' => []
            ]);
        }
    }';
    }

    /**
     * NÍVEL 2 - Controller com Filtros (View com Request)
     * Para páginas com filtros, paginação e ordenação
     *
     * @example DashboardController, CustomerController
     */
    public function filteredIndex(): string
    {
        return '
    public function index(Request $request): View
    {
        $filters = $request->only([
            \'search\', \'status\', \'date_from\', \'date_to\',
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

                return view(\'pages.module.index\', array_merge([
                    \'data\' => $data,
                    \'filters\' => $filters
                ], $additionalData));
            }

            Log::error(\'Erro no serviço: \' . $this->getServiceErrorMessage($result));

            return view(\'pages.module.index\', [
                \'data\' => [],
                \'filters\' => $filters
            ]);

        } catch (Exception $e) {
            Log::error(\'Erro ao carregar página: \' . $e->getMessage());

            return view(\'pages.module.index\', [
                \'data\' => [],
                \'filters\' => $filters
            ]);
        }
    }';
    }

    /**
     * NÍVEL 3 - Controller Híbrido (View + API)
     * Para páginas que precisam servir tanto interface web quanto API
     *
     * @example PlanController, ProductController
     */
    public function hybridIndex(): string
    {
        return '
    public function index(Request $request): View|JsonResponse
    {
        try {
            $filters = $request->only([\'status\', \'name\', \'order_by\', \'limit\']);
            $orderBy = $request->get(\'order_by\', [\'name\' => \'asc\']);
            $limit = $request->get(\'limit\', 15);

            $result = $this->service->list($filters);

            // Resposta para requisições AJAX/API
            if ($request->expectsJson()) {
                return response()->json([
                    \'success\' => true,
                    \'data\' => $result->isSuccess() ? $result->getData() : [],
                    \'message\' => \'Dados listados com sucesso.\'
                ]);
            }

            // Resposta para interface web
            if ($result->isSuccess()) {
                $data = $this->getServiceData($result, []);

                $this->logOperation(\'index_accessed\', [
                    \'filters\' => $filters,
                    \'data_count\' => count($data)
                ]);

                return view(\'pages.module.index\', [
                    \'data\' => $data,
                    \'filters\' => $filters
                ]);
            }

            Log::error(\'Erro no serviço: \' . $this->getServiceErrorMessage($result));

            return view(\'pages.module.index\', [
                \'data\' => [],
                \'filters\' => $filters
            ]);

        } catch (Exception $e) {
            return $this->handleError($e, $request, \'Erro ao listar dados.\');
        }
    }';
    }

    /**
     * CONVENÇÕES PARA TRATAMENTO DE REQUEST
     */

    /**
     * Filtros Comuns por Tipo de Controller
     */
    public function getCommonFilters(): array
    {
        return [
            // Filtros básicos (todos os controllers)
            'basic' => ['search', 'status', 'created_at'],

            // Filtros de paginação (controllers com muitos dados)
            'pagination' => ['sort_by', 'sort_direction', 'per_page', 'page'],

            // Filtros de data (controllers com dados temporais)
            'date' => ['date_from', 'date_to', 'period'],

            // Filtros específicos por módulo
            'customer' => ['customer_type', 'priority_level', 'tags'],
            'product' => ['category_id', 'active', 'price_min', 'price_max'],
            'budget' => ['budget_status', 'due_date_from', 'due_date_to'],
            'invoice' => ['invoice_status', 'payment_method'],
        ];
    }

    /**
     * Validações Comuns por Tipo de Operação
     */
    public function getCommonValidations(): array
    {
        return [
            'create' => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'active' => 'sometimes|boolean',
            ],

            'update' => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'active' => 'sometimes|boolean',
            ],

            'search' => [
                'search' => 'nullable|string|min:2|max:100',
                'status' => 'nullable|string|in:active,inactive,pending',
                'per_page' => 'nullable|integer|min:10|max:100',
            ],
        ];
    }

    /**
     * Mensagens de Sucesso Padronizadas
     */
    public function getSuccessMessages(): array
    {
        return [
            'created' => 'Registro criado com sucesso.',
            'updated' => 'Registro atualizado com sucesso.',
            'deleted' => 'Registro excluído com sucesso.',
            'restored' => 'Registro restaurado com sucesso.',
            'duplicated' => 'Registro duplicado com sucesso.',
            'activated' => 'Registro ativado com sucesso.',
            'deactivated' => 'Registro desativado com sucesso.',
        ];
    }

    /**
     * Estrutura de Logging Padronizada
     */
    public function getLoggingStructure(): array
    {
        return [
            'index_accessed' => [
                'action' => 'Lista acessada',
                'context' => ['filters', 'data_count', 'execution_time'],
            ],

            'show_accessed' => [
                'action' => 'Detalhes visualizados',
                'context' => ['resource_id', 'user_id'],
            ],

            'created' => [
                'action' => 'Novo registro criado',
                'context' => ['resource_id', 'user_id', 'data_summary'],
            ],

            'updated' => [
                'action' => 'Registro atualizado',
                'context' => ['resource_id', 'user_id', 'changes_summary'],
            ],

            'deleted' => [
                'action' => 'Registro excluído',
                'context' => ['resource_id', 'user_id'],
            ],
        ];
    }

    /**
     * EXEMPLOS PRÁTICOS DE IMPLEMENTAÇÃO
     */

    /**
     * Exemplo de Controller Nivel 1 - Simples
     */
    public function simpleControllerExample(): string
    {
        return '
<?php

namespace App\Http\Controllers;

use App\Services\ModuleService;
use Illuminate\View\View;

/**
 * Controller simples para módulo básico
 */
class SimpleModuleController extends Controller
{
    public function __construct(
        private ModuleService $service
    ) {}

    public function index(): View
    {
        $result = $this->service->list();

        if ($result->isSuccess()) {
            $data = $this->getServiceData($result, []);
            $this->logOperation(\'index_accessed\', [\'count\' => count($data)]);

            return view(\'pages.simple-module.index\', [
                \'data\' => $data
            ]);
        }

        return view(\'pages.simple-module.index\', [\'data\' => []]);
    }
}';
    }

    /**
     * Exemplo de Controller Nivel 2 - Com Filtros
     */
    public function filteredControllerExample(): string
    {
        return '
<?php

namespace App\Http\Controllers;

use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller com filtros para módulo avançado
 */
class FilteredModuleController extends Controller
{
    public function __construct(
        private ModuleService $service
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            \'search\', \'status\', \'category\',
            \'sort_by\', \'sort_direction\', \'per_page\'
        ]);

        $result = $this->service->list($filters);

        if ($result->isSuccess()) {
            $data = $this->getServiceData($result, []);

            // Dados adicionais
            $stats = $this->service->getStats();
            $categories = $this->service->getCategories();

            return view(\'pages.filtered-module.index\', [
                \'data\' => $data,
                \'stats\' => $stats,
                \'categories\' => $categories,
                \'filters\' => $filters
            ]);
        }

        return view(\'pages.filtered-module.index\', [
            \'data\' => [],
            \'filters\' => $filters
        ]);
    }

    protected function getAdditionalIndexData(Request $request): array
    {
        return [
            \'stats\' => $this->service->getStats(),
            \'categories\' => $this->service->getCategories(),
        ];
    }
}';
    }

    /**
     * Exemplo de Controller Nivel 3 - Híbrido (Web + API)
     */
    public function hybridControllerExample(): string
    {
        return '
<?php

namespace App\Http\Controllers;

use App\Services\ModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller combinado para módulo com API
 */
class HybridModuleController extends Controller
{
    public function __construct(
        private ModuleService $service
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only([\'status\', \'name\', \'order_by\', \'limit\']);

        $result = $this->service->list($filters);

        // API Response
        if ($request->expectsJson()) {
            return response()->json([
                \'success\' => true,
                \'data\' => $result->isSuccess() ? $result->getData() : [],
                \'message\' => \'Dados listados com sucesso.\'
            ]);
        }

        // Web Response
        if ($result->isSuccess()) {
            $data = $this->getServiceData($result, []);

            return view(\'pages.hybrid-module.index\', [
                \'data\' => $data,
                \'filters\' => $filters
            ]);
        }

        return view(\'pages.hybrid-module.index\', [
            \'data\' => [],
            \'filters\' => $filters
        ]);
    }
}';
    }

    /**
     * GUIA DE IMPLEMENTAÇÃO
     */

    /**
     * Como escolher o nível correto para seu controller:
     */
    public function getImplementationGuide(): string
    {
        return '
## Guia de Implementação - Escolhendo o Nível Correto

### NÍVEL 1 - Controller Simples
✅ Quando usar:
- Página básica sem filtros
- Dados estáticos ou raramente alterados
- Interface simples (ex: About, Terms)
- Prototipagem rápida

❌ Não usar quando:
- Precisa de filtros de busca
- Dados mudam frequentemente
- Interface complexa

### NÍVEL 2 - Controller com Filtros
✅ Quando usar:
- Listagem com filtros de busca
- Paginação necessária
- Ordenação por diferentes campos
- Múltiplas opções de visualização

❌ Não usar quando:
- Interface muito simples
- Não precisa de filtros
- API não é necessária

### NÍVEL 3 - Controller Híbrido
✅ Quando usar:
- Mesmo módulo serve Web e API
- Aplicação SPA que consome JSON
- Microserviços internos
- Aplicação mobile integrada

❌ Não usar quando:
- Apenas interface web
- Não há consumo de API
- Projeto muito simples

## Benefícios do Padrão

✅ Consistência entre todos os controllers
✅ Manutenibilidade facilitada
✅ Reutilização de código
✅ Tratamento de erro padronizado
✅ Logging automático
✅ Suporte a API quando necessário
✅ Performance otimizada';
    }
}
