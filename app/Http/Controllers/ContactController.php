<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para gerenciamento de contatos.
 * Implementa operações CRUD tenant-aware para contatos.
 * Migração do sistema legacy app/controllers/ContactController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class ContactController extends BaseController
{
    /**
     * @var ContactService
     */
    protected ContactService $contactService;

    /**
     * Construtor da classe ContactController.
     *
     * @param ContactService $contactService
     */
    public function __construct( ContactService $contactService )
    {
        parent::__construct();
        $this->contactService = $contactService;
    }

    /**
     * Exibe uma listagem dos contatos do tenant atual.
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
            action: 'view_contacts',
            entity: 'contacts',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $search   = $request->get( 'search' );
        $contacts = $this->contactService->getContactsByTenant(
            tenantId: $tenantId,
            search: $search,
            perPage: 15,
        );

        return $this->renderView( 'contacts.index', [ 
            'contacts' => $contacts,
            'search'   => $search,
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Mostra o formulário para criação de um novo contato.
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
            action: 'view_create_contact',
            entity: 'contacts',
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'contacts.create', [ 
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Armazena um novo contato no banco de dados.
     *
     * @param ContactFormRequest $request
     * @return RedirectResponse
     */
    public function store( ContactFormRequest $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $request->merge( [ 'tenant_id' => $tenantId ] );

        $result = $this->contactService->createContact( $request->validated() );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Contato criado com sucesso.',
            errorMessage: 'Erro ao criar contato.',
        );
    }

    /**
     * Exibe o contato específico.
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

        $contact = $this->contactService->getContactById( $id, $tenantId );

        if ( !$contact ) {
            return $this->errorRedirect( 'Contato não encontrado.' );
        }

        $this->logActivity(
            action: 'view_contact',
            entity: 'contacts',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'contacts.show', [ 
            'contact'  => $contact,
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Mostra o formulário para edição do contato.
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

        $contact = $this->contactService->getContactById( $id, $tenantId );

        if ( !$contact ) {
            return $this->errorRedirect( 'Contato não encontrado.' );
        }

        $this->logActivity(
            action: 'view_edit_contact',
            entity: 'contacts',
            entityId: $id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        return $this->renderView( 'contacts.edit', [ 
            'contact'  => $contact,
            'tenantId' => $tenantId
        ] );
    }

    /**
     * Atualiza o contato no banco de dados.
     *
     * @param ContactFormRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update( ContactFormRequest $request, int $id ): RedirectResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $existingContact = $this->contactService->getContactById( $id, $tenantId );

        if ( !$existingContact ) {
            return $this->errorRedirect( 'Contato não encontrado.' );
        }

        $request->merge( [ 'tenant_id' => $tenantId ] );

        $result = $this->contactService->updateContact( $id, $request->validated() );

        return $this->handleServiceResult(
            result: $result,
            request: $request,
            successMessage: 'Contato atualizado com sucesso.',
            errorMessage: 'Erro ao atualizar contato.',
        );
    }

    /**
     * Remove o contato do banco de dados.
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

        $existingContact = $this->contactService->getContactById( $id, $tenantId );

        if ( !$existingContact ) {
            return $this->errorRedirect( 'Contato não encontrado.' );
        }

        // Verifica se o contato está sendo usado em relacionamentos
        if ( $this->contactService->isContactInUse( $id ) ) {
            return $this->errorRedirect( 'Este contato está sendo usado em outros registros e não pode ser excluído.' );
        }

        $result = $this->contactService->deleteContact( $id );

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Contato excluído com sucesso.',
            errorMessage: 'Erro ao excluir contato.',
        );
    }

    /**
     * Define um contato como principal para o tenant.
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

        $existingContact = $this->contactService->getContactById( $id, $tenantId );

        if ( !$existingContact ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Contato não encontrado.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Contato não encontrado.' );
        }

        $result = $this->contactService->setPrimaryContact( $id, $tenantId );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'set_primary_contact',
                    entity: 'contacts',
                    entityId: $id,
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->jsonSuccess(
                    data: $result->getData(),
                    message: 'Contato definido como principal com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao definir contato principal.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Contato definido como principal com sucesso.',
            errorMessage: 'Erro ao definir contato principal.',
        );
    }

    /**
     * Valida email em tempo real via AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateEmail( Request $request ): JsonResponse
    {
        $request->validate( [ 
            'email' => 'required|email|max:255'
        ] );

        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
        }

        $isUnique = $this->contactService->isEmailUnique(
            email: $request->email,
            tenantId: $tenantId,
            excludeId: $request->exclude_id ?? null
        );

        if ( $isUnique ) {
            return $this->jsonSuccess(
                data: true,
                message: 'Email disponível.',
            );
        }

        return $this->jsonError(
            message: 'Este email já está cadastrado.',
            statusCode: 422,
        );
    }

    /**
     * Envia email de teste para o contato.
     *
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function testEmail( int $id ): RedirectResponse|JsonResponse
    {
        $tenantId = $this->tenantId();

        if ( !$tenantId ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Tenant não encontrado.', statusCode: 403 );
            }
            return $this->errorRedirect( 'Tenant não encontrado.' );
        }

        $contact = $this->contactService->getContactById( $id, $tenantId );

        if ( !$contact ) {
            if ( request()->expectsJson() ) {
                return $this->jsonError( 'Contato não encontrado.', statusCode: 404 );
            }
            return $this->errorRedirect( 'Contato não encontrado.' );
        }

        $result = $this->contactService->sendTestEmail( $contact );

        if ( request()->expectsJson() ) {
            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'test_email_contact',
                    entity: 'contacts',
                    entityId: $id,
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->jsonSuccess(
                    message: 'Email de teste enviado com sucesso.',
                );
            }

            return $this->jsonError(
                message: $result->getError() ?? 'Erro ao enviar email de teste.',
                statusCode: 422,
            );
        }

        return $this->handleServiceResult(
            result: $result,
            request: request(),
            successMessage: 'Email de teste enviado com sucesso.',
            errorMessage: 'Erro ao enviar email de teste.',
        );
    }

}