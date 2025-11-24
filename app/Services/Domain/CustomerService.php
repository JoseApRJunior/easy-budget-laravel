<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;
use App\Models\Address;
use App\Models\AuditLog;
use App\Models\BusinessData;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\Application\CustomerInteractionService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function __construct(
        CustomerRepository $customerRepository,
        CustomerInteractionService $interactionService,
    ) {
        parent::__construct($customerRepository);
        $this->customerRepository = $customerRepository;
        $this->interactionService = $interactionService;
    }

    /**
     * Cria um cliente com validação PF/PJ e associação automática ao tenant.
     */
    public function create(array $data): ServiceResult
    {
        try {
            $tenantId   = Auth::user()->tenant_id;
            $normalized = $this->normalizeCustomerInput($data, $tenantId);

            $validation = $this->validateForCreate($normalized);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            $customer = $this->customerRepository->createWithRelations($normalized);

            AuditLog::log('created', $customer, null, $customer->toArray(), [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
                'type'      => $normalized['type'] ?? CommonData::TYPE_INDIVIDUAL,
            ]);

            Log::info('Cliente criado', [
                'customer_id' => $customer->id,
                'tenant_id'   => $tenantId,
                'type'        => $normalized['type'] ?? CommonData::TYPE_INDIVIDUAL,
            ]);

            return $this->success($customer, 'Cliente criado com sucesso');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Erro de dados ao criar cliente', ['error' => $e->getMessage()]);
            return $this->error(OperationStatus::CONFLICT, 'Erro de dados: verifique a unicidade ou constraints.', null, $e);
        } catch (\Exception $e) {
            Log::error('Erro ao criar cliente', ['error' => $e->getMessage()]);
            return $this->error(OperationStatus::ERROR, 'Erro ao criar cliente: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Atualiza um cliente com validação PF/PJ.
     */
    public function update(int $id, array $data): ServiceResult
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $normalized = $this->normalizeCustomerInput($data, $tenantId);
            $validation = $this->validateForUpdate($customer, $normalized);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            $old = $customer->toArray();
            $this->customerRepository->updateWithRelations($customer, $normalized);
            $customer->refresh();

            AuditLog::log('updated', $customer, $old, $customer->toArray(), [
                'entity'    => 'customer',
                'tenant_id' => $tenantId,
                'type'      => $normalized['type'] ?? $customer->commonData?->type,
            ]);

            Log::info('Cliente atualizado', [
                'customer_id' => $customer->id,
                'tenant_id'   => $tenantId,
            ]);

            return $this->success($customer, 'Cliente atualizado com sucesso');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Erro de dados ao atualizar cliente', ['error' => $e->getMessage()]);
            return $this->error(OperationStatus::CONFLICT, 'Erro de dados: verifique a unicidade ou constraints.', null, $e);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar cliente', ['error' => $e->getMessage()]);
            return $this->error(OperationStatus::ERROR, 'Erro ao atualizar cliente: ' . $e->getMessage(), null, $e);
        }
    }

    /** Compatibilidade: cria cliente vinculando explicitamente a um tenant. */
    public function createByTenantId(array $data, int $tenantId): ServiceResult
    {
        $normalized                = $this->normalizeCustomerInput($data, $tenantId);
        $normalized['tenant_id'] = $tenantId;
        return $this->create($normalized);
    }

    /**
     * Lista clientes com filtros
     */
    public function listCustomers(array $filters = []): ServiceResult
    {
        try {
            $customers = $this->customerRepository->listByFilters($filters);
            return $this->success($customers, 'Clientes listados com sucesso');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar clientes: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Cria novo cliente
     */
    public function createCustomer(array $data): ServiceResult
    {
        return $this->create($data);
    }

    /**
     * Valida dados do cliente
     */
    private function validateForCreate(array $data): ServiceResult
    {
        $tenantId = $data['tenant_id'] ?? Auth::user()->tenant_id;
        $type     = $this->mapType($data['type'] ?? CommonData::TYPE_INDIVIDUAL);

        $email = $data['email'] ?? $data['email_personal'] ?? null;
        if (!ValidationHelper::isValidEmail($email)) {
            return $this->error(OperationStatus::INVALID_DATA, 'Email inválido ou ausente');
        }
        if (!$this->customerRepository->isEmailUnique($email, $tenantId)) {
            return $this->error(OperationStatus::CONFLICT, 'Email já está em uso neste tenant');
        }

        $phone = $data['phone'] ?? $data['phone_personal'] ?? null;
        if (!ValidationHelper::isValidPhone($phone)) {
            return $this->error(OperationStatus::INVALID_DATA, 'Telefone inválido ou ausente');
        }

        if (!ValidationHelper::isValidCep($data['cep'] ?? null)) {
            return $this->error(OperationStatus::INVALID_DATA, 'CEP inválido ou ausente');
        }
        if (empty($data['city']) || empty($data['state'])) {
            return $this->error(OperationStatus::INVALID_DATA, 'Cidade e Estado são obrigatórios');
        }

        if ($type === CommonData::TYPE_INDIVIDUAL) {
            if (empty($data['first_name']) || empty($data['last_name'])) {
                return $this->error(OperationStatus::INVALID_DATA, 'Nome e sobrenome são obrigatórios');
            }
            $cpf = preg_replace('/[^0-9]/', '', $data['cpf'] ?? '');
            if (!ValidationHelper::isValidCpf($cpf)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CPF inválido ou ausente');
            }
            if (!$this->customerRepository->isCpfUnique($cpf, $tenantId)) {
                return $this->error(OperationStatus::CONFLICT, 'CPF já está em uso neste tenant');
            }
            if (!empty($data['birth_date']) && !ValidationHelper::isValidBirthDate($data['birth_date'])) {
                return $this->error(OperationStatus::INVALID_DATA, 'Data de nascimento inválida');
            }
        } else {
            if (empty($data['company_name'])) {
                return $this->error(OperationStatus::INVALID_DATA, 'Razão social é obrigatória para PJ');
            }
            $cnpj = preg_replace('/[^0-9]/', '', $data['cnpj'] ?? '');
            if (!ValidationHelper::isValidCnpj($cnpj)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CNPJ inválido ou ausente');
            }
            if (!$this->customerRepository->isCnpjUnique($cnpj, $tenantId)) {
                return $this->error(OperationStatus::CONFLICT, 'CNPJ já está em uso neste tenant');
            }
        }

        return $this->success();
    }

    /** Validação para atualização (PF/PJ) */
    private function validateForUpdate(Customer $customer, array $data): ServiceResult
    {
        $tenantId = $customer->tenant_id;
        $type     = $this->mapType($data['type'] ?? ($customer->commonData?->isCompany() ? 'company' : 'individual'));

        $email = $data['email'] ?? $data['email_personal'] ?? $customer->contact?->email_personal;
        if (!ValidationHelper::isValidEmail($email)) {
            return $this->error(OperationStatus::INVALID_DATA, 'Email inválido ou ausente');
        }
        if (!$this->customerRepository->isEmailUnique($email, $tenantId, $customer->id)) {
            return $this->error(OperationStatus::CONFLICT, 'Email já está em uso neste tenant');
        }

        $phone = $data['phone'] ?? $data['phone_personal'] ?? $customer->contact?->phone_personal;
        if (!ValidationHelper::isValidPhone($phone)) {
            return $this->error(OperationStatus::INVALID_DATA, 'Telefone inválido ou ausente');
        }

        $cep = $data['cep'] ?? $customer->address?->cep;
        if (!ValidationHelper::isValidCep($cep)) {
            return $this->error(OperationStatus::INVALID_DATA, 'CEP inválido ou ausente');
        }
        if (empty($data['city'] ?? $customer->address?->city) || empty($data['state'] ?? $customer->address?->state)) {
            return $this->error(OperationStatus::INVALID_DATA, 'Cidade e Estado são obrigatórios');
        }

        if ($type === CommonData::TYPE_INDIVIDUAL) {
            $cpf = preg_replace('/[^0-9]/', '', $data['cpf'] ?? $customer->commonData?->cpf ?? '');
            if (!ValidationHelper::isValidCpf($cpf)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CPF inválido ou ausente');
            }
            if (!$this->customerRepository->isCpfUnique($cpf, $tenantId, $customer->id)) {
                return $this->error(OperationStatus::CONFLICT, 'CPF já está em uso neste tenant');
            }
        } else {
            $cnpj = preg_replace('/[^0-9]/', '', $data['cnpj'] ?? $customer->commonData?->cnpj ?? '');
            if (!ValidationHelper::isValidCnpj($cnpj)) {
                return $this->error(OperationStatus::INVALID_DATA, 'CNPJ inválido ou ausente');
            }
            if (!$this->customerRepository->isCnpjUnique($cnpj, $tenantId, $customer->id)) {
                return $this->error(OperationStatus::CONFLICT, 'CNPJ já está em uso neste tenant');
            }
        }

        return $this->success();
    }

    /** Normaliza dados de entrada e aplica limpeza de máscaras */
    private function normalizeCustomerInput(array $data, int $tenantId): array
    {
        $type = $this->mapType($data['type'] ?? ( $data[ 'person_type' ] ??CommonData::TYPE_INDIVIDUAL ) );

        $normalized = [
            'tenant_id'           => $tenantId,
            'type'                => $type,
            'first_name'          => $data['first_name'] ?? null,
            'last_name'           => $data['last_name'] ?? null,
            'birth_date'          => DateHelper::parseBirthDate($data['birth_date'] ?? null),
            'cpf'                 => isset($data['cpf']) ? preg_replace('/[^0-9]/', '', (string) $data['cpf']) : null,
            'company_name'        => $data['company_name'] ?? null,
            'cnpj'                => isset($data['cnpj']) ? preg_replace('/[^0-9]/', '', (string) $data['cnpj']) : null,
            'description'         => $data['description'] ?? null,
            'area_of_activity_id' => $data['area_of_activity_id'] ?? null,
            'profession_id'       => $data['profession_id'] ?? null,
            'email'               => $data['email'] ?? $data['email_personal'] ?? null,
            'phone'               => $data['phone'] ?? $data['phone_personal'] ?? null,
            'email_personal'      => $data['email_personal'] ?? ($data['email'] ?? null),
            'phone_personal'      => $data['phone_personal'] ?? ($data['phone'] ?? null),
            'email_business'      => $data['email_business'] ?? null,
            'phone_business'      => $data['phone_business'] ?? null,
            'website'             => $data['website'] ?? null,
            'address'             => $data['address'] ?? null,
            'address_number'      => $data['address_number'] ?? null,
            'neighborhood'        => $data['neighborhood'] ?? null,
            'city'                => $data['city'] ?? null,
            'state'               => $data['state'] ?? null,
            'cep'                 => $data['cep'] ?? null,
            'status'              => $data['status'] ?? 'active',
            'fantasy_name'           => $data['fantasy_name'] ?? null,
            'state_registration'     => $data['state_registration'] ?? null,
            'municipal_registration' => $data['municipal_registration'] ?? null,
            'founding_date'          => DateHelper::parseBirthDate($data['founding_date'] ?? null),
            'industry'               => $data['industry'] ?? null,
            'company_size'           => $data['company_size'] ?? null,
            'business_notes'         => $data['business_notes'] ?? ($data['notes'] ?? null),
        ];

        return $normalized;
    }

    /** Mapeia valores externos para tipos internos do CommonData */
    private function mapType(?string $external): string
    {
        $value = strtolower((string) $external);
        return match ($value) {
            'persona_fisica', 'pf', 'individual' => CommonData::TYPE_INDIVIDUAL,
            'persona_juridica', 'pj', 'company'  => CommonData::TYPE_COMPANY,
            default                              => CommonData::TYPE_INDIVIDUAL,
        };
    }

    /**
     * Busca cliente por ID
     */
    public function findCustomer(int $id): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId($id, auth()->user()->tenant_id);
            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }
            return $this->success($customer);
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao buscar cliente: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Atualiza cliente
     */
    public function updateCustomer(int $id, array $data): ServiceResult
    {
        return $this->update($id, $data);
    }

    /**
     * Remove cliente
     */
    public function deleteCustomer(int $id): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId($id, auth()->user()->tenant_id);
            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $this->customerRepository->delete($customer->id);

            AuditLog::log('deleted', $customer, $customer->toArray(), null, [
                'entity'    => 'customer',
                'tenant_id' => $customer->tenant_id,
            ]);

            return $this->success(null, 'Cliente removido com sucesso');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao remover cliente: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Cria interação com cliente
     */
    public function createInteraction(int $customerId, array $data): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId($customerId, auth()->user()->tenant_id);
            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $interaction = $this->interactionService->createInteraction($customer, $data, auth()->user());
            return $this->success($interaction, 'Interação criada com sucesso');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar interação: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Lista interações de um cliente
     */
    public function listInteractions(int $customerId, array $filters = []): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId($customerId, auth()->user()->tenant_id);
            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }

            $interactions = $this->interactionService->getCustomerInteractions($customer, $filters);
            return $this->success($interactions, 'Interações listadas com sucesso');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar interações: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Obtém estatísticas de clientes para o dashboard
     */
    public function getCustomerStats(int $tenantId): ServiceResult
    {
        try {
            $total    = Customer::where('tenant_id', $tenantId)->count();
            $active   = Customer::where('tenant_id', $tenantId)->where('status', 'active')->count();
            $inactive = Customer::where('tenant_id', $tenantId)->where('status', 'inactive')->count();

            $recentCustomers = Customer::where('tenant_id', $tenantId)
                ->latest()
                ->limit(10)
                ->with(['commonData', 'contact'])
                ->get();

            $stats = [
                'total_customers'    => $total,
                'active_customers'   => $active,
                'inactive_customers' => $inactive,
                'recent_customers'   => $recentCustomers,
            ];

            return $this->success($stats, 'Estatísticas obtidas com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de clientes', ['error' => $e->getMessage()]);
            return $this->error(OperationStatus::ERROR, 'Erro ao obter estatísticas: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Obtém clientes filtrados com paginação
     */
    public function getFilteredCustomers(array $filters, int $tenantId): ServiceResult
    {
        try {
            // Adicionar tenant_id aos filtros para garantir isolamento
            $filters['tenant_id'] = $tenantId;

            $customers = $this->customerRepository->getPaginated($filters);

            return $this->success($customers, 'Clientes obtidos com sucesso');
        } catch (\Exception $e) {
            Log::error('Erro ao obter clientes filtrados', [
                'error'     => $e->getMessage(),
                'tenant_id' => $tenantId,
                'filters'   => $filters
            ]);
            return $this->error(OperationStatus::ERROR, 'Erro ao obter clientes: ' . $e->getMessage(), null, $e);
        }
    }
}
