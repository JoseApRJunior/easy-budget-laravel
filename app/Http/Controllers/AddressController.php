<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddressFormRequest;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para gerenciamento de endereços.
 * Implementa operações CRUD tenant-aware para endereços.
 * Migração do sistema legacy app/controllers/AddressController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class AddressController extends BaseController
{
    /**
     * @var AddressService
     */
    protected AddressService $addressService;

    /**
     * Construtor da classe AddressController.
     *
     * @param AddressService $addressService
     */
    public function __construct( AddressService $addressService )
    {
        parent::__construct();
        $this->addressService = $addressService;
    }

    /**
     * Exibe uma listagem dos endereços do tenant atual.
     *
     * @param Request $request
     * @return View
     */
    public function index( Request $request ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_addresses',
            entity: 'addresses',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $search    = $request->get( 'search' );
        $addresses = $this->addressService->getAddressesByTenant(
            tenantId: $tenantId,
            search: $search,
            perPage: 15,
        );

        return $this->renderView( 'addresses.index', [ 
            'addresses' => $addresses,
            'search'    => $search,
            'tenantId'  => $tenantId
        ] );
    }

    /**
     * Mostra o formulário para criação de um novo endereço.
     *
     * @return View
     */
    public function create(): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $this->logActivity(
            action: 'view_create_address',
            entity: 'addresses',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'addresses.create', [ 
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Armazena um novo endereço no banco de dados.
     *
     * @param AddressFormRequest $request
     * @return RedirectResponse
     */
    public function store( AddressFormRequest $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->merge( [ 'tenant_id' => $tenantId ] );

        $result = $this->addressService->createAddress( $request->validated() );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Endereço criado com sucesso.',
            errorMessage: 'Erro ao criar endereço.',
        );
    }

    /**
     * Exibe o endereço específico.
     *
     * @param int $id
     * @return View
     */
    public function show( int $id ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $address = $this->addressService->getAddressById( $id, $tenantId );

        if ( !$address ) {
            return $this->errorRedirect( 'Endereço não encontrado.' );
        }

        $this->logActivity(
            action: 'view_address',
            entity: 'addresses',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'addresses.show', [ 
            'address'  => $address,
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Mostra o formulário para edição do endereço.
     *
     * @param int $id
     * @return View
     */
    public function edit( int $id ): View
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $address = $this->addressService->getAddressById( $id, $tenantId );

        if ( !$address ) {
            return $this->errorRedirect( 'Endereço não encontrado.' );
        }

        $this->logActivity(
            action: 'view_edit_address',
            entity: 'addresses',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'addresses.edit', [ 
            'address'  => $address,
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Atualiza o endereço no banco de dados.
     *
     * @param AddressFormRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update( AddressFormRequest $request, int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingAddress = $this->addressService->getAddressById( $id, $tenantId );

        if ( !$existingAddress ) {
            return $this->errorRedirect( 'Endereço não encontrado.' );
        }

        $request->merge( [ 'tenant_id' => $tenantId ] );

        $result = $this->addressService->updateAddress( $id, $request->validated() );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Endereço atualizado com sucesso.',
            errorMessage: 'Erro ao atualizar endereço.',
        );
    }

    /**
     * Remove o endereço do banco de dados.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy( int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingAddress = $this->addressService->getAddressById( $id, $tenantId );

        if ( !$existingAddress ) {
            return $this->errorRedirect( 'Endereço não encontrado.' );
        }

        // Verifica se o endereço está sendo usado em relacionamentos
        if ( $this->addressService->isAddressInUse( $id ) ) {
            return $this->errorRedirect( 'Este endereço está sendo usado em outros registros e não pode ser excluído.' );
        }

        $result = $this->addressService->deleteAddress( $id );

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Endereço excluído com sucesso.',
            errorMessage: 'Erro ao excluir endereço.',
        );
    }

    /**
     * Define um endereço como principal para o tenant.
     *
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function setPrimary( int $id ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingAddress = $this->addressService->getAddressById( $id, $tenantId );

        if ( !$existingAddress ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Endereço não encontrado.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Endereço não encontrado.' );
        }

        $result = $this->addressService->setPrimaryAddress( $id, $tenantId );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'set_primary_address',
                    entity: 'addresses',
                    entityId: $id,
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->jsonSuccess(
                    data: $result->getData(),
                    message: 'Endereço definido como principal com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao definir endereço principal.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Endereço definido como principal com sucesso.',
            errorMessage: 'Erro ao definir endereço principal.',
        );
    }

    /**
     * Busca endereços por CEP via API externa (Correios).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByCep( Request $request ): JsonResponse
    {
        $request->validate( [ 
            'cep' => 'required|string|size:9|regex:/^[0-9]{5}-[0-9]{3}$/'
        ] );

        $cep = preg_replace( '/[^0-9]/', '', $request->cep );

        try {
            $response = $this->addressService->searchAddressByCep( $cep );

            $this->logActivity(
                action: 'search_address_by_cep',
                entity: 'addresses',
                metadata: [ 'cep' => $cep, 'tenant_id' => $this->tenantId() ],
            );

            return $this->jsonSuccess(
                data: $response,
                message: 'Endereços encontrados com sucesso.',
            );
        } catch ( \Exception $e ) {
            return $this->jsonError(
                message: 'Erro ao buscar endereço por CEP: ' . $e->getMessage(),
                statusCode: 422,
            );
        }
    }

}