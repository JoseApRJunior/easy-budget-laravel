<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Customer\CustomerDTO;
use App\DTOs\Customer\CustomerFilterDTO;
use App\DTOs\Customer\CustomerInteractionDTO;
use App\Enums\OperationStatus;
use App\Models\Customer;
use App\Repositories\AreaOfActivityRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ProfessionRepository;
use App\Services\Application\AuditLogService;
use App\Services\Application\CustomerInteractionService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;

/**
 * Serviço de Clientes - Lógica de negócio para gestão de clientes
 *
 * Centraliza operações complexas relacionadas a clientes,
 * incluindo criação, atualização, busca e relacionamentos.
 */
class CustomerService extends AbstractBaseService
{
    private CustomerRepository $customerRepository;

    private CustomerInteractionService $interactionService;

    private AuditLogService $auditLogService;

    private AreaOfActivityRepository $areaOfActivityRepository;

    private ProfessionRepository $professionRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CustomerInteractionService $interactionService,
        AuditLogService $auditLogService,
        AreaOfActivityRepository $areaOfActivityRepository,
        ProfessionRepository $professionRepository,
    ) {
        parent::__construct($customerRepository);
        $this->customerRepository = $customerRepository;
        $this->interactionService = $interactionService;
        $this->auditLogService = $auditLogService;
        $this->areaOfActivityRepository = $areaOfActivityRepository;
        $this->professionRepository = $professionRepository;
    }

    /**
     * Lista clientes com filtros.
     */
    public function listCustomers(array $filters = []): ServiceResult
    {
        return $this->list($filters);
    }

    /**
     * Cria um novo cliente com suas relações.
     */
    public function createCustomer(CustomerDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $validation = $this->validateForCreate($dto);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            $customer = $this->customerRepository->createFromDTO($dto);

            $this->auditLogService->logCreated($customer, [
                'entity' => 'customer',
                'type' => $dto->type,
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
            $customer = $this->customerRepository->findWithCompleteData($id);

            if (! $customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $validation = $this->validateForUpdate($customer, $dto);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            $oldData = $customer->toArray();
            $this->customerRepository->updateFromDTO($customer, $dto);
            $customer->refresh();

            $this->auditLogService->logUpdated($customer, $oldData, $customer->toArray(), [
                'entity' => 'customer',
                'type' => $dto->type,
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
            $customer = $this->customerRepository->find($id);

            if (! $customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $oldStatus = $customer->status;
            $newStatus = $oldStatus === 'active' ? 'inactive' : 'active';

            $customer->status = $newStatus;
            $customer->save();

            $this->auditLogService->logUpdated($customer, ['status' => $oldStatus], ['status' => $newStatus], [
                'entity' => 'customer',
                'action' => 'toggle_status',
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
            $customer = $this->customerRepository->find($id);

            if (! $customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $validation = $this->validateCanDelete($id);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            $this->customerRepository->delete($customer->id);

            $this->auditLogService->logDeleted($customer, [
                'entity' => 'customer',
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
            $customer = $this->customerRepository->restore($id);

            if (! $customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado ou não está excluído');
            }

            $this->auditLogService->logRestored($customer, [
                'entity' => 'customer',
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
            $stats = $this->customerRepository->getDashboardStats();

            // Adicionar clientes ativos com estatísticas de orçamentos e faturas
            $stats['active_with_stats'] = $this->customerRepository->getActiveWithStats(10);

            return $this->success($stats, 'Estatísticas obtidas com sucesso');
        }, 'Erro ao obter estatísticas de clientes.');
    }

    /**
     * Obtém clientes filtrados com paginação opcional.
     */
    public function getFilteredCustomers(CustomerFilterDTO $filterDTO, bool $paginate = true): ServiceResult
    {
        return $this->safeExecute(function () use ($filterDTO, $paginate) {
            $filters = $filterDTO->toFilterArray();
            $perPage = $filterDTO->per_page;

            if ($paginate) {
                $customers = $this->customerRepository->getPaginated($filters, $perPage);
            } else {
                $customers = $this->customerRepository->getFiltered($filters);
            }

            return $this->success($customers, 'Clientes obtidos com sucesso');
        }, 'Erro ao obter clientes filtrados.');
    }

    /**
     * Obtém áreas de atuação ativas.
     */
    public function getAreasOfActivity(): ServiceResult
    {
        return $this->safeExecute(function () {
            $areas = $this->areaOfActivityRepository->getActive();

            return $this->success($areas);
        }, 'Erro ao carregar áreas de atuação.');
    }

    /**
     * Obtém profissões ativas.
     */
    public function getProfessions(): ServiceResult
    {
        return $this->safeExecute(function () {
            $professions = $this->professionRepository->getActive();

            return $this->success($professions);
        }, 'Erro ao carregar profissões.');
    }

    /**
     * Busca cliente por ID com dados completos.
     */
    public function findCustomer(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $customer = $this->customerRepository->findWithCompleteData($id);

            if (! $customer) {
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
            $customers = $this->customerRepository->findNearbyByCep($cep);

            return $this->success($customers, 'Clientes próximos encontrados');
        }, 'Erro ao buscar clientes próximos.');
    }

    /**
     * Cria interação com cliente.
     */
    public function createInteraction(int $customerId, CustomerInteractionDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($customerId, $dto) {
            $customer = $this->customerRepository->find($customerId);

            if (! $customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            return $this->interactionService->createInteraction($customer, $dto, $this->authUser());
        }, 'Erro ao criar interação.');
    }

    /**
     * Busca clientes para autocompletar.
     */
    public function searchForAutocomplete(string $query): ServiceResult
    {
        return $this->safeExecute(function () use ($query) {
            $customers = $this->customerRepository->findBySearch($query, 10);

            $formatted = $customers->map(function ($customer) {
                $commonData = $customer->commonData;
                $name = $commonData ? ($commonData->company_name ?: trim(($commonData->first_name ?? '').' '.($commonData->last_name ?? ''))) : 'Sem nome';

                return [
                    'id' => $customer->id,
                    'text' => $name.($commonData->cpf ? " ({$commonData->cpf})" : ($commonData->cnpj ? " ({$commonData->cnpj})" : '')),
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
    public function exportCustomers(CustomerFilterDTO $filterDTO): ServiceResult
    {
        return $this->safeExecute(function () use ($filterDTO) {
            $filters = $filterDTO->toFilterArray();
            $customers = $this->customerRepository->getFiltered($filters);

            // Aqui você poderia integrar com um Excel export service
            // Por enquanto, apenas retornamos os dados formatados
            $data = $customers->map(function ($customer) {
                $commonData = $customer->commonData;
                $contact = $customer->contact;
                $address = $customer->address;

                return [
                    'ID' => $customer->id,
                    'Tipo' => $commonData->type === 'individual' ? 'PF' : 'PJ',
                    'Nome/Razão Social' => $commonData->company_name ?: ($commonData->first_name.' '.$commonData->last_name),
                    'CPF/CNPJ' => $commonData->cpf ?: $commonData->cnpj,
                    'Email' => $contact->email_personal ?: $contact->email_business,
                    'Telefone' => $contact->phone_personal ?: $contact->phone_business,
                    'Cidade/UF' => ($address->city ?? '').'/'.($address->state ?? ''),
                    'Status' => $customer->status === 'active' ? 'Ativo' : 'Inativo',
                    'Data Cadastro' => $customer->created_at->format('d/m/Y'),
                ];
            });

            return $this->success($data);
        }, 'Erro ao exportar clientes.');
    }

    // --- Métodos Auxiliares Privados ---

    private function validateForCreate(CustomerDTO $dto): ServiceResult
    {
        // Validação de CPF/CNPJ único por tenant
        if (! empty($dto->cpf)) {
            if (! $this->customerRepository->isCpfUnique($dto->cpf)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CPF já cadastrado para este tenant.');
            }
        }

        if (! empty($dto->cnpj)) {
            if (! $this->customerRepository->isCnpjUnique($dto->cnpj)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CNPJ já cadastrado para este tenant.');
            }
        }

        // Validação de Email único por tenant
        if (! empty($dto->email)) {
            if (! $this->customerRepository->isEmailUnique($dto->email)) {
                return $this->error(OperationStatus::INVALID_DATA, 'Email já cadastrado para este tenant.');
            }
        }

        return $this->success();
    }

    private function validateForUpdate(Customer $customer, CustomerDTO $dto): ServiceResult
    {
        // Validação de CPF/CNPJ único por tenant (exceto o próprio cliente)
        if (! empty($dto->cpf)) {
            if (! $this->customerRepository->isCpfUnique($dto->cpf, $customer->id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CPF já cadastrado para outro cliente neste tenant.');
            }
        }

        if (! empty($dto->cnpj)) {
            if (! $this->customerRepository->isCnpjUnique($dto->cnpj, $customer->id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CNPJ já cadastrado para outro cliente neste tenant.');
            }
        }

        // Validação de Email único por tenant (exceto o próprio cliente)
        if (! empty($dto->email)) {
            if (! $this->customerRepository->isEmailUnique($dto->email, $customer->id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'Email já cadastrado para outro cliente neste tenant.');
            }
        }

        return $this->success();
    }

    private function validateCanDelete(int $customerId): ServiceResult
    {
        $validation = $this->customerRepository->canDelete($customerId);

        if (! ($validation['canDelete'] ?? false)) {
            return $this->error(
                OperationStatus::CONFLICT,
                $validation['reason'] ?? 'Cliente possui vínculos e não pode ser removido.'
            );
        }

        return $this->success();
    }
}
