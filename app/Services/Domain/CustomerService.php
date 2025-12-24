<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Customer\CustomerDTO;
use App\Enums\OperationStatus;
use App\Models\AreaOfActivity;
use App\Models\CommonData;
use App\Models\Customer;
use App\Models\Profession;
use App\Repositories\CustomerRepository;
use App\Services\Application\AuditLogService;
use App\Services\Application\CustomerInteractionService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Clientes - Lógica de negócio para gestão de clientes
 *
 * Centraliza operações complexas relacionadas a clientes,
 * incluindo criação, atualização, busca e relacionamentos.
 */
class CustomerService extends AbstractBaseService
{
    private CustomerRepository         $customerRepository;
    private CustomerInteractionService $interactionService;
    private AuditLogService            $auditLogService;

    public function __construct(
        CustomerRepository $customerRepository,
        CustomerInteractionService $interactionService,
        AuditLogService $auditLogService,
    ) {
        parent::__construct($customerRepository);
        $this->customerRepository = $customerRepository;
        $this->interactionService = $interactionService;
        $this->auditLogService = $auditLogService;
    }

    /**
     * Cria um novo cliente com suas relações.
     */
    public function createCustomer(CustomerDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = $this->ensureTenantId();
            $data = $dto->toArray();
            $data['tenant_id'] = $tenantId;

            $validation = $this->validateForCreate($data);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            $customer = $this->customerRepository->createWithRelations($data);

            $this->auditLogService->logCreated($customer, [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
                'type'      => $dto->type,
            ]);

            Log::info('Cliente criado', [
                'customer_id' => $customer->id,
                'tenant_id'   => $tenantId,
                'type'        => $dto->type,
            ]);

            return $this->success($customer, 'Cliente criado com sucesso');
        }, 'Erro ao criar cliente.');
    }

    /**
     * Atualiza um cliente existente com suas relações.
     */
    public function updateCustomer(int $id, CustomerDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $tenantId = $this->ensureTenantId();
            $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);

            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $data = $dto->toArray();
            $data['tenant_id'] = $tenantId;

            $validation = $this->validateForUpdate($customer, $data);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            $oldData = $customer->toArray();
            $this->customerRepository->updateWithRelations($customer, $data);
            $customer->refresh();

            $this->auditLogService->logUpdated($customer, $oldData, $customer->toArray(), [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
                'type'      => $dto->type,
            ]);

            return $this->success($customer, 'Cliente atualizado com sucesso');
        }, 'Erro ao atualizar cliente.');
    }

    /**
     * Alterna o status do cliente entre ativo e inativo.
     */
    public function toggleStatus(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $tenantId = $this->ensureTenantId();
            $customer = $this->customerRepository->findByIdAndTenantId($id, $tenantId);

            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $oldStatus = $customer->status;
            $newStatus = $oldStatus === 'active' ? 'inactive' : 'active';

            $customer->status = $newStatus;
            $customer->save();

            AuditLog::log('updated', $customer, ['status' => $oldStatus], ['status' => $newStatus], [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
                'action'    => 'toggle_status',
            ]);

            $message = $newStatus === 'active' ? 'Cliente ativado com sucesso' : 'Cliente desativado com sucesso';
            return $this->success($customer, $message);
        }, 'Erro ao alterar status do cliente.');
    }

    /**
     * Remove um cliente se não houver dependências.
     */
    public function deleteCustomer(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $tenantId = $this->ensureTenantId();
            $customer = $this->customerRepository->findByIdAndTenantId($id, $tenantId);

            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $validation = $this->validateCanDelete($id, $tenantId);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            $this->customerRepository->delete($customer->id);

            AuditLog::log('deleted', $customer, $customer->toArray(), null, [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
            ]);

            return $this->success(null, 'Cliente removido com sucesso');
        }, 'Erro ao remover cliente.');
    }

    /**
     * Restaura um cliente deletado.
     */
    public function restoreCustomer(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $tenantId = $this->ensureTenantId();
            $customer = Customer::onlyTrashed()
                ->where('id', $id)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado ou não está excluído');
            }

            $customer->restore();

            AuditLog::log('restored', $customer, null, $customer->toArray(), [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
            ]);

            return $this->success($customer, 'Cliente restaurado com sucesso');
        }, 'Erro ao restaurar cliente.');
    }

    /**
     * Obtém estatísticas do dashboard de clientes.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            $total    = Customer::where('tenant_id', $tenantId)->count();
            $active   = Customer::where('tenant_id', $tenantId)->where('status', 'active')->count();
            $inactive = Customer::where('tenant_id', $tenantId)->where('status', 'inactive')->count();

            $recentCustomers = Customer::where('tenant_id', $tenantId)
                ->latest()
                ->limit(5)
                ->with(['commonData', 'contact'])
                ->get();

            $stats = [
                'total_customers'    => $total,
                'active_customers'   => $active,
                'inactive_customers' => $inactive,
                'recent_customers'   => $recentCustomers,
            ];

            return $this->success($stats, 'Estatísticas obtidas com sucesso');
        }, 'Erro ao obter estatísticas de clientes.');
    }

    /**
     * Obtém clientes filtrados com paginação.
     */
    public function getFilteredCustomers(array $filters): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $tenantId = $this->ensureTenantId();
            $perPage = (int) ($filters['per_page'] ?? 15);

            // Preparar filtros para o repository
            $filters['tenant_id'] = $tenantId;
            $showOnlyTrashed = ($filters['deleted'] ?? '') === 'only';

            if ($showOnlyTrashed) {
                $filters['deleted'] = 'only';
            } else {
                unset($filters['deleted']);
            }

            $customers = $this->customerRepository->getPaginated($filters, $perPage);

            return $this->success($customers, 'Clientes obtidos com sucesso');
        }, 'Erro ao obter clientes filtrados.');
    }

    /**
     * Obtém áreas de atuação ativas.
     */
    public function getAreasOfActivity(): ServiceResult
    {
        return $this->safeExecute(function () {
            $areas = AreaOfActivity::where('is_active', true)->orderBy('name')->get();
            return $this->success($areas);
        }, 'Erro ao carregar áreas de atuação.');
    }

    /**
     * Obtém profissões ativas.
     */
    public function getProfessions(): ServiceResult
    {
        return $this->safeExecute(function () {
            $professions = Profession::where('is_active', true)->orderBy('name')->get();
            return $this->success($professions);
        }, 'Erro ao carregar profissões.');
    }

    /**
     * Busca cliente por ID com dados completos.
     */
    public function findCustomer(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $tenantId = $this->ensureTenantId();
            $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);

            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            return $this->success($customer);
        }, 'Erro ao buscar cliente.');
    }

    /**
     * Busca clientes próximos por CEP.
     */
    public function findNearbyCustomers(string $cep): ServiceResult
    {
        return $this->safeExecute(function () use ($cep) {
            $tenantId = $this->ensureTenantId();
            // Implementação simplificada de busca por proximidade via CEP (primeiros 5 dígitos)
            $cepPrefix = substr(preg_replace('/[^0-9]/', '', $cep), 0, 5);

            $customers = Customer::where('tenant_id', $tenantId)
                ->whereHas('address', function ($query) use ($cepPrefix) {
                    $query->where('cep', 'like', $cepPrefix . '%');
                })
                ->with(['commonData', 'contact', 'address'])
                ->get();

            return $this->success($customers, 'Clientes próximos encontrados');
        }, 'Erro ao buscar clientes próximos.');
    }

    /**
     * Cria interação com cliente.
     */
    public function createInteraction(int $customerId, array $data): ServiceResult
    {
        return $this->safeExecute(function () use ($customerId, $data) {
            $tenantId = $this->ensureTenantId();
            $customer = $this->customerRepository->findByIdAndTenantId($customerId, $tenantId);

            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $interaction = $this->interactionService->createInteraction($customer, $data, $this->authUser());
            return $this->success($interaction, 'Interação criada com sucesso');
        }, 'Erro ao criar interação.');
    }

    /**
     * Busca clientes para autocompletar.
     */
    public function searchForAutocomplete(string $query): ServiceResult
    {
        return $this->safeExecute(function () use ($query) {
            $tenantId = $this->ensureTenantId();
            $customers = $this->customerRepository->findBySearch($query, $tenantId, 10);

            $formatted = $customers->map(function ($customer) {
                $commonData = $customer->commonData;
                $name = $commonData ? ($commonData->company_name ?: trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''))) : 'Sem nome';

                return [
                    'id'    => $customer->id,
                    'text'  => $name . ($commonData->cpf ? " ({$commonData->cpf})" : ($commonData->cnpj ? " ({$commonData->cnpj})" : "")),
                    'value' => $customer->id,
                    'label' => $name,
                ];
            });

            return $this->success($formatted);
        }, 'Erro ao buscar clientes para autocompletar.');
    }

    /**
     * Exporta clientes com base nos filtros.
     */
    public function exportCustomers(array $filters): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $tenantId = $this->ensureTenantId();
            $filters['tenant_id'] = $tenantId;

            $customers = $this->customerRepository->listByFilters($filters, ['created_at' => 'desc']);

            // Aqui você poderia integrar com um Excel export service
            // Por enquanto, apenas retornamos os dados formatados
            $data = $customers->map(function ($customer) {
                $commonData = $customer->commonData;
                $contact = $customer->contact;
                $address = $customer->address;

                return [
                    'ID' => $customer->id,
                    'Tipo' => $commonData->type === 'individual' ? 'PF' : 'PJ',
                    'Nome/Razão Social' => $commonData->company_name ?: ($commonData->first_name . ' ' . $commonData->last_name),
                    'CPF/CNPJ' => $commonData->cpf ?: $commonData->cnpj,
                    'Email' => $contact->email_personal ?: $contact->email_business,
                    'Telefone' => $contact->phone_personal ?: $contact->phone_business,
                    'Cidade/UF' => ($address->city ?? '') . '/' . ($address->state ?? ''),
                    'Status' => $customer->status === 'active' ? 'Ativo' : 'Inativo',
                    'Data Cadastro' => $customer->created_at->format('d/m/Y'),
                ];
            });

            return $this->success($data);
        }, 'Erro ao exportar clientes.');
    }

    // --- Métodos Auxiliares Privados ---

    private function ensureTenantId(): int
    {
        $tenantId = $this->tenantId();
        if (!$tenantId) {
            throw new Exception('Tenant não identificado.');
        }
        return (int) $tenantId;
    }

    private function validateForCreate(array $data): ServiceResult
    {
        $tenantId = (int) $data['tenant_id'];

        // Validação de CPF/CNPJ único por tenant
        if (!empty($data['cpf'])) {
            if (!$this->customerRepository->isCpfUnique($data['cpf'], $tenantId)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CPF já cadastrado para este tenant.');
            }
        }

        if (!empty($data['cnpj'])) {
            if (!$this->customerRepository->isCnpjUnique($data['cnpj'], $tenantId)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CNPJ já cadastrado para este tenant.');
            }
        }

        // Validação de Email único por tenant
        if (!empty($data['email'])) {
            if (!$this->customerRepository->isEmailUnique($data['email'], $tenantId)) {
                return $this->error(OperationStatus::INVALID_DATA, 'Email já cadastrado para este tenant.');
            }
        }

        return $this->success();
    }

    private function validateForUpdate(Customer $customer, array $data): ServiceResult
    {
        $tenantId = (int) $data['tenant_id'];

        // Validação de CPF/CNPJ único por tenant (exceto o próprio cliente)
        if (!empty($data['cpf'])) {
            if (!$this->customerRepository->isCpfUnique($data['cpf'], $tenantId, $customer->id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CPF já cadastrado para outro cliente neste tenant.');
            }
        }

        if (!empty($data['cnpj'])) {
            if (!$this->customerRepository->isCnpjUnique($data['cnpj'], $tenantId, $customer->id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CNPJ já cadastrado para outro cliente neste tenant.');
            }
        }

        // Validação de Email único por tenant (exceto o próprio cliente)
        if (!empty($data['email'])) {
            if (!$this->customerRepository->isEmailUnique($data['email'], $tenantId, $customer->id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'Email já cadastrado para outro cliente neste tenant.');
            }
        }

        return $this->success();
    }

    private function validateCanDelete(int $customerId, int $tenantId): ServiceResult
    {
        $hasBudgets = Customer::where('id', $customerId)
            ->where('tenant_id', $tenantId)
            ->whereHas('budgets')
            ->exists();

        if ($hasBudgets) {
            return $this->error(OperationStatus::CONFLICT, 'Cliente possui orçamentos cadastrados e não pode ser removido.');
        }

        return $this->success();
    }
}
