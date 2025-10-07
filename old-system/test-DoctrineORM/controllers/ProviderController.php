<?php

declare(strict_types=1);

namespace app\controllers;

use app\database\entitiesORM\AddressEntity;
use app\database\entitiesORM\CommonDataEntity;
use app\database\entitiesORM\ContactEntity;
use app\database\entitiesORM\ProviderEntity;
use app\database\entitiesORM\UserEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\AddressService;
use app\database\servicesORM\AreaOfActivityService;
use app\database\servicesORM\BudgetService;
use app\database\servicesORM\CommonDataService;
use app\database\servicesORM\ContactService;
use app\database\servicesORM\FinancialSummary;
use app\database\servicesORM\ProfessionService;
use app\database\servicesORM\ProviderService;
use app\database\servicesORM\UserRegistrationService;
use app\database\servicesORM\UserService;
use app\request\ProviderUpdatePasswordFormRequest;
use app\request\UserWithProviderFormRequest;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use core\support\UploadImage;
use http\Redirect;
use http\Request;

/**
 * Controller para gerenciar operações relacionadas aos providers.
 *
 * Este controller utiliza exclusivamente services ORM seguindo o padrão
 * arquitetural ServiceInterface → Repository → Entity.
 *
 * Funcionalidades:
 * - Dashboard do provider
 * - Atualização de dados do provider
 * - Alteração de senha
 * - Upload de imagens
 */
class ProviderController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private ProviderService $providerService,
        private UserService $userService,
        private CommonDataService $commonDataService,
        private ContactService $contactService,
        private AddressService $addressService,
        private UserRegistrationService $userRegistrationService,
        private BudgetService $budgetService,
        private FinancialSummary $financialSummary,
        private AreaOfActivityService $areaOfActivityService,
        private ProfessionService $professionService,
        protected ActivityService $activityService,
        private UploadImage $uploadImage,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Dashboard do provider com resumo de orçamentos, atividades e financeiro.
     *
     * @return Response
     */
    public function index(): Response
    {
        // Buscar orçamentos recentes usando BudgetService
        $budgetsResponse = $this->budgetService->getRecentBudgets( $this->authenticated['tenant_id'], 1 );
        $budgets         = $budgetsResponse->isSuccess() ? $budgetsResponse->data : [];

        // Buscar atividades recentes usando ActivityService
        $activitiesResponse = $this->activityService->getRecentActivities( $this->authenticated['tenant_id'] );
        $activities         = $activitiesResponse->isSuccess() ? $activitiesResponse->data : [];

        // Buscar resumo financeiro
        $financial_summary = $this->financialSummary->getMonthlySummary( $this->authenticated['tenant_id'] );

        return new Response( $this->twig->env->render( 'pages/provider/index.twig', [ 
            'budgets'           => $budgets,
            'activities'        => $activities,
            'financial_summary' => $financial_summary,
        ] ) );
    }

    /**
     * Exibe formulário de atualização do provider.
     *
     * @return Response
     */
    public function update(): Response
    {
        // Buscar dados completos do provider usando ProviderService
        $providerResponse = $this->providerService->findProviderFullByUserId(
            $this->authenticated->user_id,
            $this->authenticated->tenant_id,
        );

        if ( !$providerResponse->isSuccess() ) {
            return Redirect::redirect( '/provider' )
                ->withMessage( 'error', 'Provider não encontrado' );
        }

        // Buscar áreas de atuação usando AreaOfActivityService
        $areasResponse     = $this->areaOfActivityService->findAll();
        $areas_of_activity = $areasResponse->isSuccess() ? $areasResponse->data : [];

        // Buscar profissões usando ProfessionService
        $professionsResponse = $this->professionService->findAll();
        $professions         = $professionsResponse->isSuccess() ? $professionsResponse->data : [];

        return new Response( $this->twig->env->render( 'pages/provider/update.twig', [ 
            'provider'          => $providerResponse->data,
            'areas_of_activity' => $areas_of_activity,
            'professions'       => $professions,
        ] ) );
    }

    /**
     * Processa atualização dos dados do provider.
     *
     * @return Response
     */
    public function update_store(): Response
    {
        // Validar dados do formulário
        $validated = UserWithProviderFormRequest::validate( $this->request );

        if ( !$validated ) {
            return Redirect::redirect( '/provider/update' )
                ->withMessage( 'error', 'Erro ao atualizar prestador' );
        }

        // Dados do formulário sanitizados
        $data = $this->request->all();

        // Verificar se email já existe usando UserService
        $checkResponse = $this->userService->findByEmail( $data[ 'email' ] );

        if ( $checkResponse->isSuccess() ) {
            /** @var UserEntity $existingUser */
            $existingUser = $checkResponse->data;
            if ( $existingUser->getId() != $this->authenticated->user_id ) {
                return Redirect::redirect( '/provider/update' )
                    ->withMessage( 'error', 'Este e-mail já está registrado!' );
            }
        }

        // Processar upload de imagem
        $info = null;
        if ( $this->request->hasFile( 'logo' ) ) {
            $this->uploadImage->make( 'logo' )
                ->resize( 200, null, true )
                ->execute();
            $info         = $this->uploadImage->get_image_info();
            $data[ 'logo' ] = $info[ 'path' ];
        }

        // Buscar dados atuais do usuário usando UserService
        $userResponse = $this->userService->findByIdAndTenantId(
            $this->authenticated->user_id,
            $this->authenticated->tenant_id,
        );

        if ( !$userResponse->isSuccess() ) {
            return Redirect::redirect( '/provider/update' )
                ->withMessage( 'error', 'Usuário não encontrado' );
        }

        /** @var UserEntity $userData */
        $userData     = $userResponse->data;
        $originalData = $userData->toArray();

        // Gerenciar arquivo de logo
        if ( isset( $info[ 'path' ] ) && $originalData[ 'logo' ] !== null && $info[ 'path' ] !== $originalData[ 'logo' ] ) {
            removeFile( $originalData[ 'logo' ] );
        }
        $data[ 'logo' ] = isset( $info[ 'path' ] ) ? $info[ 'path' ] : $originalData[ 'logo' ];

        // Criar UserEntity atualizada
        $userEntity = UserEntity::create( removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ) );

        // Atualizar usuário usando UserService
        if ( !compareObjects( $userData, $userEntity, [ 'created_at', 'updated_at' ] ) ) {
            $updateResponse = $this->userService->update( $userEntity, $this->authenticated->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return Redirect::redirect( '/provider/update' )
                    ->withMessage( 'error', 'Falha ao atualizar os dados do usuário: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais de CommonData usando CommonDataService
        $commonDataResponse = $this->commonDataService->findByIdAndTenantId(
            $this->authenticated->common_data_id,
            $this->authenticated->tenant_id,
        );

        if ( !$commonDataResponse->isSuccess() ) {
            return Redirect::redirect( '/provider/update' )
                ->withMessage( 'error', 'Dados comuns não encontrados' );
        }

        /** @var CommonDataEntity $commonDataData */
        $commonDataData = $commonDataResponse->data;
        $originalData   = $commonDataData->toArray();

        // Converter IDs para inteiros
        $data[ 'area_of_activity_id' ] = (int) $data[ 'area_of_activity_id' ];
        $data[ 'profession_id' ]       = (int) $data[ 'profession_id' ];

        // Criar CommonDataEntity atualizada
        $commonDataEntity = CommonDataEntity::create( removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ) );

        // Atualizar CommonData usando CommonDataService
        if ( !compareObjects( $commonDataData, $commonDataEntity, [ 'created_at', 'updated_at' ] ) ) {
            $updateResponse = $this->commonDataService->update( $commonDataEntity, $this->authenticated->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return Redirect::redirect( '/provider/update' )
                    ->withMessage( 'error', 'Falha ao atualizar dados comuns: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais de Contact usando ContactService
        $contactResponse = $this->contactService->findByIdAndTenantId(
            $this->authenticated->contact_id,
            $this->authenticated->tenant_id,
        );

        if ( !$contactResponse->isSuccess() ) {
            return Redirect::redirect( '/provider/update' )
                ->withMessage( 'error', 'Contato não encontrado' );
        }

        /** @var ContactEntity $contactData */
        $contactData  = $contactResponse->data;
        $originalData = $contactData->toArray();

        // Criar ContactEntity atualizada
        $contactEntity = ContactEntity::create( removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ) );

        // Atualizar Contact usando ContactService
        if ( !compareObjects( $contactData, $contactEntity, [ 'created_at', 'updated_at' ] ) ) {
            $updateResponse = $this->contactService->update( $contactEntity, $this->authenticated->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return Redirect::redirect( '/provider/update' )
                    ->withMessage( 'error', 'Falha ao atualizar contato: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais de Address usando AddressService
        $addressResponse = $this->addressService->findByIdAndTenantId(
            $this->authenticated->address_id,
            $this->authenticated->tenant_id,
        );

        if ( !$addressResponse->isSuccess() ) {
            return Redirect::redirect( '/provider/update' )
                ->withMessage( 'error', 'Endereço não encontrado' );
        }

        /** @var AddressEntity $addressData */
        $addressData  = $addressResponse->data;
        $originalData = $addressData->toArray();

        // Criar AddressEntity atualizada
        $addressEntity = AddressEntity::create( removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ) );

        // Atualizar Address usando AddressService
        if ( !compareObjects( $addressData, $addressEntity, [ 'created_at', 'updated_at' ] ) ) {
            $updateResponse = $this->addressService->update( $addressEntity, $this->authenticated->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return Redirect::redirect( '/provider/update' )
                    ->withMessage( 'error', 'Falha ao atualizar endereço: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais do provider usando ProviderService
        $providerResponse = $this->providerService->findByIdAndTenantId(
            $this->authenticated->id,
            $this->authenticated->tenant_id,
        );

        if ( !$providerResponse->isSuccess() ) {
            return Redirect::redirect( '/provider/update' )
                ->withMessage( 'error', 'Prestador não encontrado' );
        }

        /** @var ProviderEntity $providerData */
        $providerData = $providerResponse->data;

        // Criar array com dados do provider para compatibilidade
        $originalData = [ 
            'id'             => $providerData->getId(),
            'tenant_id'      => $providerData->getTenant()->getId(),
            'user_id'        => $providerData->getUser()->getId(),
            'terms_accepted' => $providerData->isTermsAccepted(),
        ];

        // Criar ProviderEntity atualizada
        $providerEntity = ProviderEntity::create( removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ) );

        // Atualizar Provider usando ProviderService
        if ( !compareObjects( $providerData, $providerEntity, [ 'created_at', 'updated_at' ] ) ) {
            $updateResponse = $this->providerService->update( $providerEntity, $this->authenticated->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return Redirect::redirect( '/provider/update' )
                    ->withMessage( 'error', 'Falha ao atualizar prestador: ' . $updateResponse->message );
            }
        }

        // Log da atividade usando ActivityService
        $this->activityService->logActivity(
            $this->authenticated->tenant_id,
            $this->authenticated->user_id,
            'provider_updated',
            'provider',
            $this->authenticated->id,
            "Prestador {$data[ 'first_name' ]} {$data[ 'last_name' ]} atualizado com sucesso!",
            $data,
        );

        // Limpar sessões relacionadas
        Session::remove( 'checkPlan' );
        Session::remove( 'last_updated_session_provider' );

        return Redirect::redirect( '/settings' )
            ->withMessage( 'success', 'Prestador atualizado com sucesso!' );
    }

    /**
     * Summary of change_password
     * @return Response
     */
    public function change_password()
    {
        return new Response( $this->twig->env->render( 'pages/provider/change_password.twig' ) );
    }

    /**
     * Summary of change_password_store
     * @return Redirect
     */
    public function change_password_store()
    {
        // Validar dados do formulário
        $validated = ProviderUpdatePasswordFormRequest::validate( $this->request );

        // Se os dados não forem válidos, redirecionar para a página de atualização do prestador e mostrar a mensagem de erro
        if ( !$validated ) {
            return Redirect::redirect( '/provider/change-password' )->withMessage( 'error', 'Erro ao atualizar senha' );
        }

        // Dados do formulário sanitizados
        $data = $this->request->all();
        // Verificar se password e confirm_password são iguais se não redirecionar para a página de mudanca de senha do prestador e mostrar a mensagem de erro
        if ( $data[ 'password' ] !== $data[ 'confirm_password' ] ) {
            return Redirect::redirect( '/provider/change-password' )->withMessage( 'error', 'As senhas nao conferem' );
        }

        // Verificar se a senha atual é igual a nova senha se sim redirecionar para a página de mudanca de senha do prestador e mostrar a mensagem de erro
        if ( $data[ 'password' ] === $data[ 'current_password' ] ) {
            return Redirect::redirect( '/provider/change-password' )->withMessage( 'error', 'A nova senha deve ser diferente da senha atual' );
        }

        // Atualizar a senha do prestador usando UserRegistrationService
        $response = $this->userRegistrationService->updatePassword( $data[ 'password' ] );

        // Verificar se a atualização foi bem-sucedida
        if ( $response[ 'status' ] === 'error' ) {
            return Redirect::redirect( '/provider/change-password' )
                ->withMessage( 'error', 'Erro ao atualizar senha' );
        }

        // Log da atividade usando ActivityService
        $this->activityService->logActivity(
            $response[ 'data' ][ 'user' ]->tenant_id,
            $response[ 'data' ][ 'user' ]->id,
            'user_updated',
            'user',
            $response[ 'data' ][ 'user' ]->id,
            'Senha atualizada com sucesso!',
            $response[ 'data' ],
        );

        return Redirect::redirect( '/settings' )
            ->withMessage( 'success', 'Senha alterada com sucesso, sua nova senha foi enviada para o seu e-mail' );
    }

}
