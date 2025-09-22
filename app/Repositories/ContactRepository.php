<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class ContactRepository extends AbstractRepository implements RepositoryInterface
{
    protected string $modelClass = Contact::class;

    /**
     * Cria contato para cliente.
     */
    public function createForCustomer( array $data, int $tenantId, int $customerId ): Model
    {
        $contact = new Contact();
        $contact->fill( [ 
            'tenant_id'   => $tenantId,
            'customer_id' => $customerId,
            'phone'       => $data[ 'phone' ] ?? null,
            'email'       => $data[ 'email' ] ?? null,
            // Outros campos de contato como mobile, etc.
        ] );
        $contact->save();
        return $contact;
    }

    /**
     * Deleta por ID do cliente.
     */
    public function deleteByCustomerId( int $customerId, int $tenantId ): bool
    {
        return $this->model->where( 'customer_id', $customerId )
            ->where( 'tenant_id', $tenantId )
            ->delete() > 0;
    }

    /**
     * Cria contato para provider.
     */
    public function createForProvider( array $data, int $tenantId, int $providerId ): Model
    {
        $contact = new Contact();
        $contact->fill( [ 
            'tenant_id'   => $tenantId,
            'provider_id' => $providerId,
            'phone'       => $data[ 'phone' ] ?? null,
            'email'       => $data[ 'email' ] ?? null,
            // Outros campos de contato
        ] );
        $contact->save();
        return $contact;
    }

    /**
     * Atualiza contato para provider.
     */
    public function updateForProvider( array $data, int $tenantId, int $providerId ): bool
    {
        $contact = $this->model->where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->first();
        if ( !$contact ) {
            return false;
        }
        $contact->fill( $data );
        $contact->save();
        return true;
    }

    /**
     * Deleta por ID do provider.
     */
    public function deleteByProviderId( int $providerId, int $tenantId ): bool
    {
        return $this->model->where( 'provider_id', $providerId )
            ->where( 'tenant_id', $tenantId )
            ->delete() > 0;
    }

}
