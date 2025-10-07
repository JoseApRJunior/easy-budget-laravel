<?php

/**
 * Summary of namespace app\controllers
 */

namespace app\controllers;

use app\database\entities\AddressEntity;
use app\database\entities\CommonDataEntity;
use app\database\entities\ContactEntity;
use app\database\entities\ProviderEntity;
use app\database\entities\UserEntity;
use app\database\models\Activity;
use app\database\models\Address;
use app\database\models\AreaOfActivity;
use app\database\models\Budget;
use app\database\models\CommonData;
use app\database\models\Contact;
use app\database\models\Profession;
use app\database\models\Provider;
use app\database\models\User;
use app\database\services\ActivityService;
use app\database\services\FinancialSummary;
use app\database\services\UserRegistrationService;
use app\request\ProviderUpdatePasswordFormRequest;
use app\request\UserWithProviderFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use core\support\UploadImage;
use http\Redirect;
use http\Request;

/**
 * Summary of ProviderController
 */
class ProviderController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private User $user,
        private CommonData $commonData,
        private Contact $contact,
        private Address $address,
        private Provider $provider,
        private UserRegistrationService $userRegistrationService,
        private UploadImage $uploadImage,
        private Budget $budget,
        private ActivityService $activityService,
        private Activity $activity,
        private FinancialSummary $financialSummary,
        private AreaOfActivity $areaOfActivity,
        private Profession $profession,
        Request $request,
    ) {
        parent::__construct($request);
    }

    /**
     * Summary of index
     * @return Response
     */
    public function index(): Response
    {
        $budgets = $this->budget->getRecentBudgets($this->authenticated->tenant_id, 1);
        $activities = $this->activity->getRecentActivities($this->authenticated->tenant_id);
        $financial_summary = $this->financialSummary->getMonthlySummary($this->authenticated->tenant_id);

        return new Response($this->twig->env->render('pages/provider/index.twig', [
            'budgets' => $budgets,
            'activities' => $activities,
            'financial_summary' => $financial_summary,
        ]));
    }

    /**
     * Summary of update
     * @return Response
     */
    public function update(): Response
    {
        // Dados completos do usuario logado
        $provider = $this->provider->getProviderFullByUserId($this->authenticated->user_id, $this->authenticated->tenant_id);

        // Áreas de atuação
        $areas_of_activity = $this->areaOfActivity->findAll();
        // Profissões
        $professions = $this->profession->findAll();

        // Retornar a view de atualização do prestador com os dados completos do prestador, áreas de atuação e profissões
        return new Response($this->twig->env->render('pages/provider/update.twig', [
            'provider' => $provider,
            'areas_of_activity' => $areas_of_activity,
            'professions' => $professions,
        ]));
    }

    /**
     * Summary of update_store
     * @return Redirect
     */
    public function update_store(): Response
    {

        $this->request->all();

        // Validar dados do formulário
        $validated = UserWithProviderFormRequest::validate($this->request);

        // Se os dados não forem válidos, redirecionar para a página de atualização do prestador e mostrar a mensagem de erro
        if (!$validated) {
            return Redirect::redirect('/provider/update')->withMessage('error', 'Erro ao atualizar prestador');
        }
        // Pegas os dados do formulário sanitizados
        $data = $this->request->all();

        // Verificar se email já existe
        $checkObj = $this->user->getUserByEmail($data[ 'email' ]);

        // Se já existe um prestador com este email, redirecionar para a página inicial e mostrar a mensagem de erro
        if (!$checkObj instanceof EntityNotFound) {
            /** @var UserEntity $checkObj */
            if ($checkObj->id != $this->authenticated->user_id) {
                return Redirect::redirect('/provider/update')
                    ->withMessage('error', 'Este e-mail já está registrado!');
            }
        }

        // Verificar se o campo de imagem de perfil está vazio
        if ($this->request->hasFile('logo')) {
            $this->uploadImage->make('logo')
                ->resize(200, null, true)
                ->execute();
            $info = $this->uploadImage->get_image_info();

            $data[ 'logo' ] = $info[ 'path' ];
        } else {
            $data[ 'logo' ] = null;
        }

        // Busca os dados atuais do prestador
        $userData = $this->user->getUserById($this->authenticated->user_id, $this->authenticated->tenant_id);

        // Subistitui os dados do usuário com os dados do formulário
        // Converter o objeto para array
        $originalData = $userData->toArray();

        if (isset($info[ 'path' ]) && $originalData[ 'logo' ] !== null && $info[ 'path' ] !== $originalData[ 'logo' ]) {
            removeFile($originalData[ 'logo' ]);
        }
        $data[ 'logo' ] = isset($info[ 'path' ]) ? $info[ 'path' ] : $originalData[ 'logo' ];

        // Popula UserEntity com os dados do formulário
        $userEntity = UserEntity::create(removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ));

        // Atualizar UserEntity com os dados do formuláriorio
        if (!compareObjects($userData, $userEntity, [ 'created_at', 'updated_at' ])) {
            $response = $this->user->update($userEntity);

            // Se não foi possível atualizar o usuário, redirecionar para a página de atualização e mostrar a mensagem de erro
            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/update')->withMessage('error', "Falha ao atualizar os dados, tente novamente mais tarde ou entre em contato com suporte!");
            }
        }

        // Busca os dados atuais de CommonData do prestador
        $commonDataData = $this->commonData->getCommonDataById($this->authenticated->common_data_id, $this->authenticated->tenant_id);

        // Subistitui os dados do CommonData com os dados do formulário
        // Converter o objeto para array
        $originalData = $commonDataData->toArray();

        $data[ "area_of_activity_id" ] = (int) $data[ "area_of_activity_id" ];
        $data[ "profession_id" ] = (int) $data[ "profession_id" ];
        // Popula CommonDataEntity com os dados do formulário
        $commonDataEntity = CommonDataEntity::create(removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ));

        // Atualizar CommonDataEntity com os dados do formuláriorio
        if (!compareObjects($commonDataData, $commonDataEntity, [ 'created_at', 'updated_at' ])) {
            $response = $this->commonData->update($commonDataEntity);

            // Se não foi possível atualizar o usuário, redirecionar para a página de atualização e mostrar a mensagem de erro
            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/update')->withMessage('error', "Falha ao atualizar os dados, tente novamente mais tarde ou entre em contato com suporte!");
            }
        }

        // Busca os dados atuais de Contact do prestador
        $contactData = $this->contact->getContactById($this->authenticated->contact_id, $this->authenticated->tenant_id);

        // Subistitui os dados do Contact com os dados do formulário
        // Converter o objeto para array
        $originalData = $contactData->toArray();

        // Popula ContactEntity com os dados do formulário
        $contactEntity = ContactEntity::create(removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ));

        // Atualizar ContactEntity com os dados do formuláriorio
        if (!compareObjects($contactData, $contactEntity, [ 'created_at', 'updated_at' ])) {
            $response = $this->contact->update($contactEntity);

            // Se não foi possível atualizar o usuário, redirecionar para a página de atualização e mostrar a mensagem de erro
            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/update')->withMessage('error', "Falha ao atualizar os dados, tente novamente mais tarde ou entre em contato com suporte!");
            }
        }

        // Busca os dados atuais de Address do prestador
        $addressData = $this->address->getAddressById($this->authenticated->address_id, $this->authenticated->tenant_id);

        // Subistitui os dados do Address com os dados do formulário
        // Converter o objeto para array
        $originalData = $addressData->toArray();

        // Popula AddressEntity com os dados do formulário
        $addressEntity = AddressEntity::create(removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ));

        if (!compareObjects($addressData, $addressEntity, [ 'created_at', 'updated_at' ])) {
            $response = $this->address->update($addressEntity);

            // Se não foi possível atualizar o usuário, redirecionar para a página de atualização e mostrar a mensagem de erro
            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/update')->withMessage('error', "Falha ao atualizar os dados, tente novamente mais tarde ou entre em contato com suporte!");
            }
        }

        // Busca os dados atuais do prestador
        $providerData = $this->provider->getProviderById($this->authenticated->id, $this->authenticated->tenant_id);

        // Subistitui os dados do prestador com os dados do formulário
        $originalData = $providerData->toArray();

        // Popula ProviderEntity com os dados do formulário
        $providerEntity = ProviderEntity::create(removeUnnecessaryIndexes(
            $originalData,
            [ 'created_at', 'updated_at' ],
            $data,
        ));

        // Atualizar ProviderEntity com os dados do formuláriorio
        if (!compareObjects($providerData, $providerEntity, [ 'created_at', 'updated_at' ])) {
            $response = $this->provider->update($providerEntity);

            // Se não foi possível atualizar o prestador, redirecionar para a página de atualização e mostrar a mensagem de erro
            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/update')->withMessage('error', "Falha ao atualizar os dados, tente novamente mais tarde ou entre em contato com suporte!");
            }
        }

        $this->activityLogger(
            $this->authenticated->tenant_id,
            $this->authenticated->user_id,
            'provider_updated',
            'provider',
            $this->authenticated->id,
            "Prestador {$data[ 'first_name' ]} {$data[ 'last_name' ]} atualizado com sucesso!",
            $data,
        );

        // Limpa o last updated session provider
        Session::remove('checkPlan');
        Session::remove('last_updated_session_provider');

        // Se tudo ocorreu bem, redirecionar para a página de perfil e mostrar a mensagem de sucesso
        return Redirect::redirect('/settings')
            ->withMessage('success', 'Prestador atualizado com sucesso!');
    }

    /**
     * Summary of change_password
     * @return Response
     */
    public function change_password()
    {
        return new Response($this->twig->env->render('pages/provider/change_password.twig'));
    }

    /**
     * Summary of change_password_store
     * @return Redirect
     */
    public function change_password_store()
    {
        // Validar dados do formulário
        $validated = ProviderUpdatePasswordFormRequest::validate($this->request);

        // Se os dados não forem válidos, redirecionar para a página de atualização do prestador e mostrar a mensagem de erro
        if (!$validated) {
            return Redirect::redirect('/provider/change-password')->withMessage('error', 'Erro ao atualizar senha');
        }

        // Dados do formulário sanitizados
        $data = $this->request->all();
        // Verificar se password e confirm_password são iguais se não redirecionar para a página de mudanca de senha do prestador e mostrar a mensagem de erro
        if ($data[ 'password' ] !== $data[ 'confirm_password' ]) {
            return Redirect::redirect('/provider/change-password')->withMessage('error', 'As senhas nao conferem');
        }

        // Verificar se a senha atual é igual a nova senha se sim redirecionar para a página de mudanca de senha do prestador e mostrar a mensagem de erro
        if ($data[ 'password' ] === $data[ 'current_password' ]) {
            return Redirect::redirect('/provider/change-password')->withMessage('error', 'A nova senha deve ser diferente da senha atual');
        }

        // Atualizar a senha do prestador
        $response = $this->userRegistrationService->updatePassword($data[ 'password' ]);

        // Se não foi possível atualizar a senha, redirecionar para a página de mudanca de senha do prestador e mostrar a mensagem de erro
        if ($response[ 'status' ] === 'error') {
            return Redirect::redirect('/provider/change-password')->withMessage('error', 'Erro ao atualizar senha');
        }

        $this->activityService->logActivity(
            $response[ 'data' ][ 'user' ]->tenant_id,
            $response[ 'data' ][ 'user' ]->id,
            'user_updated',
            'user',
            $response[ 'data' ][ 'user' ]->id,
            "Senha atualizada com sucesso!",
            $response[ 'data' ],
        );

        // Redirecionar para a página de perfil do prestador e mostrar a mensagem de sucesso
        return Redirect::redirect('/provider/profile')->withMessage('success', "Senha alterada com sucesso, sua nova senha foi enviada para o seu e-mail");
    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
        $this->activityService->logActivity($tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata);
    }

}
