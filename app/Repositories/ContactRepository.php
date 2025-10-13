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
     * Remove contatos por ID do cliente dentro do tenant atual.
     *
     * @param int $customerId ID do cliente
     * @return int Número de contatos removidos
     */
    public function deleteByCustomerId( int $customerId ): int
    {
        return $this->model->where( 'customer_id', $customerId )->delete();
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
     * Atualiza contato para provider dentro do tenant atual.
     *
     * @param array<string, mixed> $data Dados para atualização
     * @param int $providerId ID do provider
     * @return bool True se atualizado com sucesso
     */
    public function updateForProvider( array $data, int $providerId ): bool
    {
        $contact = $this->model->where( 'provider_id', $providerId )->first();
        if ( !$contact ) {
            return false;
        }

        $contact->fill( $data );
        $contact->save();
        return true;
    }

    /**
     * Remove contatos por ID do provider dentro do tenant atual.
     *
     * @param int $providerId ID do provider
     * @return int Número de contatos removidos
     */
    public function deleteByProviderId( int $providerId ): int
    {
        return $this->model->where( 'provider_id', $providerId )->delete();
    }

    /**
     * Busca contato por email dentro do tenant atual.
     *
     * @param string $email Email do contato
     * @return Contact|null Contato encontrado
     */
    public function findByEmail( string $email ): ?Contact
    {
        return $this->model->where( 'email', $email )->first();
    }

    /**
     * Lista contatos por cliente dentro do tenant atual.
     *
     * @param int $customerId ID do cliente
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact> Contatos do cliente
     */
    public function listByCustomerId( int $customerId ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where( 'customer_id', $customerId )->get();
    }

    /**
     * Lista contatos por provider dentro do tenant atual.
     *
     * @param int $providerId ID do provider
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact> Contatos do provider
     */
    public function listByProviderId( int $providerId ): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where( 'provider_id', $providerId )->get();
    }

}
