---
name: customer-management
description: Garante o controle correto de clientes PF/PJ, seus relacionamentos e dados associados no Easy Budget.
---

# Gestão de Clientes do Easy Budget

Esta skill define o padrão para gestão de clientes (PF e PJ), seus dados comuns, contatos, endereços e relacionamentos no sistema Easy Budget.

## Estrutura de Dados do Cliente

```
👥 Customer (Cliente)
├── 📄 CommonData (Dados comuns PF/PJ)
│   ├── first_name, last_name (PF)
│   ├── cpf (PF)
│   ├── company_name, cnpj (PJ)
│   ├── birth_date, description
│   └── area_of_activity, profession
├── 📞 Contact (Contatos)
│   ├── email, phone (pessoal)
│   ├── email_business, phone_business (empresarial)
│   └── website
├── 📍 Address (Endereço)
│   ├── address, address_number, neighborhood
│   ├── city, state, cep
└── 📊 Status (ativo, inativo)
```

## Tipos de Cliente

| Tipo | Descrição | Campos Específicos |
|------|-----------|-------------------|
| **PF** | Pessoa Física | cpf, birth_date, first_name, last_name |
| **PJ** | Pessoa Jurídica | cnpj, company_name, area_of_activity |

## Padrão de Service de Customer

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Customer\CustomerDTO;
use App\Repositories\CustomerRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\AddressRepository;
use App\Enums\CustomerStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CommonDataRepository $commonDataRepository,
        private ContactRepository $contactRepository,
        private AddressRepository $addressRepository
    ) {}

    /**
     * Cria um novo cliente com todos os dados associados.
     */
    public function create(CustomerDTO $dto): ServiceResult
    {
        try {
            return DB::transaction(function () use ($dto) {
                // 1. Criar CommonData
                $commonData = $this->commonDataRepository->create(
                    $dto->toCommonDataArray() + ['tenant_id' => tenant('id')]
                );

                // 2. Criar Contact (se dados fornecidos)
                $contact = null;
                if ($dto->hasContactData()) {
                    $contact = $this->contactRepository->create(
                        $dto->toContactArray() + ['tenant_id' => tenant('id')]
                    );
                }

                // 3. Criar Address (se dados fornecidos)
                $address = null;
                if ($dto->hasAddressData()) {
                    $address = $this->addressRepository->create(
                        $dto->toAddressArray() + ['tenant_id' => tenant('id')]
                    );
                }

                // 4. Criar Customer
                $customer = $this->customerRepository->create([
                    'tenant_id' => tenant('id'),
                    'common_data_id' => $commonData->id,
                    'contact_id' => $contact?->id,
                    'address_id' => $address?->id,
                    'status' => CustomerStatus::ACTIVE->value,
                ]);

                return ServiceResult::success(
                    $customer->load(['commonData', 'contact', 'address']),
                    'Cliente criado com sucesso.'
                );
            });
        } catch (Exception $e) {
            return ServiceResult::error($e->getMessage());
        }
    }

    /**
     * Atualiza dados do cliente.
     */
    public function update(int $id, CustomerDTO $dto): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId($id, tenant('id'));
            if (!$customer) {
                return ServiceResult::error('Cliente não encontrado.');
            }

            return DB::transaction(function () use ($customer, $dto) {
                // Atualizar CommonData
                if ($dto->hasCommonDataChanges()) {
                    $this->commonDataRepository->update(
                        $customer->commonData,
                        $dto->toCommonDataArray()
                    );
                }

                // Atualizar Contact
                if ($dto->hasContactData() && $customer->contact) {
                    $this->contactRepository->update(
                        $customer->contact,
                        $dto->toContactArray()
                    );
                }

                // Atualizar Address
                if ($dto->hasAddressData() && $customer->address) {
                    $this->addressRepository->update(
                        $customer->address,
                        $dto->toAddressArray()
                    );
                }

                return ServiceResult::success(
                    $customer->fresh()->load(['commonData', 'contact', 'address']),
                    'Cliente atualizado com sucesso.'
                );
            });
        } catch (Exception $e) {
            return ServiceResult::error($e->getMessage());
        }
    }

    /**
     * Lista clientes com filtros.
     */
    public function list(array $filters = []): ServiceResult
    {
        try {
            $customers = $this->customerRepository->getAllByTenantId(
                tenant('id'),
                $this->buildFilters($filters)
            );

            return ServiceResult::success(
                $customers->load(['commonData', 'contact']),
                'Listagem obtida com sucesso.'
            );
        } catch (Exception $e) {
            return ServiceResult::error($e->getMessage());
        }
    }

    /**
     * Verifica se e-mail já existe no tenant.
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        return $this->contactRepository->existsByEmailAndTenant(
            $email,
            tenant('id'),
            $excludeId
        );
    }

    /**
     * Verifica se CPF/CNPJ já existe no tenant.
     */
    public function documentExists(string $document, ?int $excludeId = null): bool
    {
        return $this->commonDataRepository->existsByDocumentAndTenant(
            $document,
            tenant('id'),
            $excludeId
        );
    }

    protected function buildFilters(array $filters): array
    {
        $normalized = [];

        if (!empty($filters['search'])) {
            $normalized['search'] = $filters['search'];
        }

        if (isset($filters['status'])) {
            $normalized['status'] = $filters['status'];
        }

        if (isset($filters['type'])) {
            $normalized['type'] = $filters['type']; // PF ou PJ
        }

        return $normalized;
    }
}
```

## Validação de Documentos

### CPF (Pessoa Física)

```php
// Regra: Usar skill brazilian-data-utils para geração e validação
// Validação de dígitos verificadores via Módulo 11
```

### CNPJ (Pessoa Jurídica)

```php
// Regra: Usar skill brazilian-data-utils para geração e validação
// Validação de dígitos verificadores específicos para CNPJ
```

## DTO de Customer

```php
<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\AbstractDTO;

class CustomerDTO extends AbstractDTO
{
    // Tipo de cliente: PF ou PJ
    public ?string $type = null;

    // Dados Pessoais/Empresariais
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $cpf = null;
    public ?string $companyName = null;
    public ?string $cnpj = null;
    public ?string $birthDate = null;
    public ?string $description = null;

    // Contatos
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $emailBusiness = null;
    public ?string $phoneBusiness = null;
    public ?string $website = null;

    // Endereço
    public ?string $address = null;
    public ?string $addressNumber = null;
    public ?string $neighborhood = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $cep = null;

    public function isPF(): bool
    {
        return $this->type === 'PF';
    }

    public function isPJ(): bool
    {
        return $this->type === 'PJ';
    }

    public function hasContactData(): bool
    {
        return !empty($this->email) || !empty($this->phone);
    }

    public function hasAddressData(): bool
    {
        return !empty($this->address) && !empty($this->cep);
    }

    public function toCommonDataArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'cpf' => $this->cpf,
            'company_name' => $this->companyName,
            'cnpj' => $this->cnpj,
            'birth_date' => $this->birthDate,
            'description' => $this->description,
        ];
    }

    public function toContactArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'email_business' => $this->emailBusiness,
            'phone_business' => $this->phoneBusiness,
            'website' => $this->website,
        ];
    }

    public function toAddressArray(): array
    {
        return [
            'address' => $this->address,
            'address_number' => $this->addressNumber,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'cep' => $this->cep,
        ];
    }
}
```

## Regras de Negócio

### 1. Unicidade de Documentos

```php
// CPF e CNPJ devem ser únicos por tenant
// Usar documento apenas números para comparação
$cpfClean = preg_replace('/[^0-9]/', '', $cpf);
```

### 2. Unicidade de E-mail

```php
// E-mail deve ser único por tenant
// Pode haver e-mail pessoal e empresarial diferentes
```

### 3. Dados Obrigatórios

| Tipo | Obrigatórios |
|------|--------------|
| **PF** | first_name, last_name, cpf, email |
| **PJ** | company_name, cnpj, email (business) |

### 4. Dados Opcionais

- Telefone (pessoal e empresarial)
- Endereço completo
- Data de nascimento (PF)
- Área de atividade (PJ)
