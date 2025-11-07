<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Contact;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

class ContactRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Contact();
    }

    /**
     * Cria contato para cliente dentro do tenant atual.
     *
     * @param array<string, mixed> $data Dados do contato
     * @param int $customerId ID do cliente
     * @return Contact Contato criado
     */
    public function createForCustomer( array $data, int $customerId ): Contact
    {
        return $this->create( array_merge( $data, [
            'customer_id' => $customerId,
        ] ) );
    }

    /**
     * Remove contato por ID do cliente (1:1).
     *
     * @param int $customerId ID do cliente
     * @return bool
     */
    public function deleteByCustomerId( int $customerId ): bool
    {
        return $this->model->where( 'customer_id', $customerId )->delete() > 0;
    }

    /**
     * Busca contato por cliente (1:1).
     *
     * @param int $customerId ID do cliente
     * @return Contact|null
     */
    public function findByCustomerId( int $customerId ): ?Contact
    {
        return $this->model->where( 'customer_id', $customerId )->first();
    }

    /**
     * Cria contato para provider dentro do tenant atual.
     *
     * @param array<string, mixed> $data Dados do contato
     * @param int $providerId ID do provider
     * @return Contact Contato criado
     */
    public function createForProvider( array $data, int $providerId ): Contact
    {
        return $this->create( array_merge( $data, [
            'provider_id' => $providerId,
        ] ) );
    }

    /**
     * Atualiza contato para provider.
     *
     * @param array<string, mixed> $data Dados para atualização
     * @param int $providerId ID do provider
     * @return bool
     */
    public function updateForProvider( array $data, int $providerId ): bool
    {
        $contact = $this->model->where( 'provider_id', $providerId )->first();
        
        if ( !$contact ) {
            return false;
        }
        
        return $contact->update( $data );
    }

    /**
     * Remove contato por ID do provider (1:1).
     *
     * @param int $providerId ID do provider
     * @return bool
     */
    public function deleteByProviderId( int $providerId ): bool
    {
        return $this->model->where( 'provider_id', $providerId )->delete() > 0;
    }

    /**
     * Busca contato por provider (1:1).
     *
     * @param int $providerId ID do provider
     * @return Contact|null
     */
    public function findByProviderId( int $providerId ): ?Contact
    {
        return $this->model->where( 'provider_id', $providerId )->first();
    }



}
