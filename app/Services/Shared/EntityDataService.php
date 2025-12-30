<?php

declare(strict_types=1);

namespace App\Services\Shared;

use App\Helpers\DateHelper;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

/**
 * Service compartilhado para operações comuns em entidades.
 *
 * Centraliza a criação e atualização de CommonData, Contact e Address
 * que são compartilhados entre Provider e Customer.
 */
class EntityDataService
{
    /**
     * Cria CommonData a partir de dados validados.
     */
    public function createCommonData(array $data, int $tenantId): CommonData
    {
        // Limpar CPF/CNPJ se fornecidos
        $cpf = null;
        $cnpj = null;

        if (! empty($data['cpf'])) {
            $cpf = preg_replace('/\D/', '', $data['cpf']);
        }

        if (! empty($data['cnpj'])) {
            $cnpj = preg_replace('/\D/', '', $data['cnpj']);
        }

        return CommonData::create([
            'tenant_id' => $tenantId,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'birth_date' => isset($data['birth_date'])
                ? DateHelper::parseDate($data['birth_date'])
                : null,
            'cpf' => $cpf,
            'cnpj' => $cnpj,
            'company_name' => $data['company_name'] ?? null,
            'description' => $data['description'] ?? null,
            'area_of_activity_id' => $data['area_of_activity_id'] ?? null,
            'profession_id' => $data['profession_id'] ?? null,
        ]);
    }

    /**
     * Atualiza CommonData existente.
     */
    public function updateCommonData(CommonData $commonData, array $data): CommonData
    {
        $updateData = [];

        if (isset($data['first_name'])) {
            $updateData['first_name'] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $updateData['last_name'] = $data['last_name'];
        }
        if (isset($data['birth_date'])) {
            $updateData['birth_date'] = DateHelper::parseDate($data['birth_date']);
        }
        if (isset($data['cpf'])) {
            $updateData['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        }
        if (isset($data['cnpj'])) {
            $updateData['cnpj'] = preg_replace('/\D/', '', $data['cnpj']);
        }
        if (isset($data['company_name'])) {
            $updateData['company_name'] = $data['company_name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['area_of_activity_id'])) {
            $updateData['area_of_activity_id'] = $data['area_of_activity_id'];
        }
        if (isset($data['profession_id'])) {
            $updateData['profession_id'] = $data['profession_id'];
        }

        if (! empty($updateData)) {
            $commonData->update($updateData);
        }

        return $commonData->fresh();
    }

    /**
     * Cria Contact a partir de dados validados.
     */
    public function createContact(array $data, int $tenantId): Contact
    {
        return Contact::create([
            'tenant_id' => $tenantId,
            'email_personal' => $data['email_personal'] ?? null,
            'phone_personal' => $data['phone_personal'] ?? null,
            'email_business' => $data['email_business'] ?? null,
            'phone_business' => $data['phone_business'] ?? null,
            'website' => $data['website'] ?? null,
        ]);
    }

    /**
     * Atualiza Contact existente.
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        $updateData = array_filter([
            'email_personal' => $data['email_personal'] ?? null,
            'phone_personal' => $data['phone_personal'] ?? null,
            'email_business' => $data['email_business'] ?? null,
            'phone_business' => $data['phone_business'] ?? null,
            'website' => $data['website'] ?? null,
        ], fn ($value) => $value !== null);

        if (! empty($updateData)) {
            $contact->update($updateData);
        }

        return $contact->fresh();
    }

    /**
     * Cria Address a partir de dados validados.
     */
    public function createAddress(array $data, int $tenantId): Address
    {
        return Address::create([
            'tenant_id' => $tenantId,
            'address' => $data['address'] ?? null,
            'address_number' => $data['address_number'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'cep' => $data['cep'] ?? null,
        ]);
    }

    /**
     * Atualiza Address existente.
     */
    public function updateAddress(Address $address, array $data): Address
    {
        $updateData = array_filter([
            'address' => $data['address'] ?? null,
            'address_number' => $data['address_number'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'cep' => $data['cep'] ?? null,
        ], fn ($value) => $value !== null);

        if (! empty($updateData)) {
            $address->update($updateData);
        }

        return $address->fresh();
    }

    /**
     * Cria conjunto completo de dados (CommonData + Contact + Address).
     */
    public function createCompleteEntityData(array $data, int $tenantId): array
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $commonData = $this->createCommonData($data, $tenantId);
            $contact = $this->createContact($data, $tenantId);
            $address = $this->createAddress($data, $tenantId);

            return [
                'common_data' => $commonData,
                'contact' => $contact,
                'address' => $address,
            ];
        });
    }

    /**
     * Atualiza conjunto completo de dados existentes.
     */
    public function updateCompleteEntityData(
        CommonData $commonData,
        Contact $contact,
        Address $address,
        array $data
    ): array {
        return DB::transaction(function () use ($commonData, $contact, $address, $data) {
            $updatedCommonData = $this->updateCommonData($commonData, $data);
            $updatedContact = $this->updateContact($contact, $data);
            $updatedAddress = $this->updateAddress($address, $data);

            return [
                'common_data' => $updatedCommonData,
                'contact' => $updatedContact,
                'address' => $updatedAddress,
            ];
        });
    }
}
