<?php

namespace app\controllers;

use app\database\models\AreaOfActivity;
use app\database\models\Contact;
use app\database\models\Customer;
use app\database\models\Profession;
use app\database\services\ActivityService;
use app\database\services\CustomerService;
use app\request\CustomerFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

class CustomerController extends AbstractController
{
    /**
     * Constructor for the CustomerController class.
     * Initializes a new instance of the CustomerController.
     *
     * @author Easy Budget System
     * @since 1.0.0
     */
    public function __construct(
        private Twig $twig,
        private AreaOfActivity $areaOfActivity,
        private Profession $profession,
        private Customer $customer,
        private CustomerService $customerService,
        private Contact $contact,
        private Sanitize $sanitize,
        private ActivityService $activityService,
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
        return new Response($this->twig->env->render('pages/customer/index.twig'));
    }

    public function create(): Response
    {
        // Áreas de atuação
        $areas_of_activity = $this->areaOfActivity->findAll();
        // Profissões
        $professions = $this->profession->findAll();

        // Retornar a view de criação do cliente com os dados completos do cliente, áreas de atuação e profissões
        return new Response($this->twig->env->render('pages/customer/create.twig', [
            'areas_of_activity' => $areas_of_activity,
            'professions' => $professions,
        ]));
    }

    public function store(): Response
    {
        try {
            // Validar os dados do formulário de criação de cliente
            $validated = CustomerFormRequest::validate($this->request);

            // Se os dados não forem válidos, redirecionar para a página de criação de cliente e mostrar a mensagem de erro
            if (!$validated) {
                return Redirect::redirect('/provider/customers/create')->withMessage('error', 'Erro ao cadastar o cliente.');
            }

            // Obter os dados do formulário de criação de usuário
            $data = $this->request->all();

            // Verificar se email já existe
            $checkObj = $this->contact->getContactByEmail($data[ 'email' ], $this->authenticated->tenant_id);

            // Se já existe um cliente com este email, redirecionar para a página de criação de cliente e mostrar a mensagem de erro
            if (!$checkObj instanceof EntityNotFound) {
                return Redirect::redirect('/provider/customers/create')->withMessage('error', 'Cliente com este e-mail já cadastrado.');
            }

            // Criar novo cliente
            $response = $this->customerService->create($data);

            // Se não foi possível criar o novo usuário, redirecionar para a página inicial e mostrar a mensagem de erro
            if ($response[ 'status' ] === 'error') {
                return Redirect::redirect('/provider/customers/create')->withMessage('error', "Falha ao registrar o cliente, tente novamente mais tarde ou entre em contato com suporte!");
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'customer_created',
                'customer',
                (int) $response[ 'data' ][ 'id' ],
                $response[ 'message' ],
                $response[ 'data' ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de detalhes do cliente e mostrar a mensagem de sucesso
            return Redirect::redirect('/provider/customers/show/' . $response[ 'data' ][ 'id' ])->withMessage('success', $response[ 'message' ]);
        } catch (Throwable $e) {
            getDetailedErrorInfo($e);
            Session::flash('error', "Falha ao registrar o cliente, tente novamente mais tarde ou entre em contato com suporte!");

            return Redirect::redirect('/provider/customers/create');
        }

    }

    public function show($id)
    {
        // Dados completos do usuario logado
        $customer = $this->customer->getCustomerFullById(
            $this->sanitize->sanitizeParamValue($id, 'int'),
            $this->authenticated->tenant_id,
        );

        // Retornar a view de criação do cliente com os dados completos do cliente, áreas de atuação e profissões
        return new Response($this->twig->env->render('pages/customer/show.twig', [
            'customer' => $customer,
        ]));
    }

    public function update($id): Response
    {

        // Dados completos do usuario logado
        $customer = $this->customer->getCustomerFullById(
            $this->sanitize->sanitizeParamValue($id, 'int'),
            $this->authenticated->tenant_id,
        );
        // Áreas de atuação
        $areas_of_activity = $this->areaOfActivity->findAll();
        // Profissões
        $professions = $this->profession->findAll();

        // Retornar a view de criação do cliente com os dados completos do cliente, áreas de atuação e profissões
        // Retornar a view de atualização do prestador com os dados completos do prestador, áreas de atuação e profissões
        return new Response($this->twig->env->render('pages/customer/update.twig', [
            'customer' => $customer,
            'areas_of_activity' => $areas_of_activity,
            'professions' => $professions,
        ]));

    }

    public function update_store(): Response
    {
        // Validar dados do formulário
        $validated = CustomerFormRequest::validate($this->request);
        //  Obter os dados do formulário
        $data = $this->request->all();
        // Se os dados não forem válidos, redirecionar para a página de atualização do prestador e mostrar a mensagem de erro
        if (!$validated) {
            return Redirect::redirect('/provider/customers/update/' . $data[ 'id' ])->withMessage('error', 'Erro ao atualizar prestador');
        }

        $response = $this->customerService->update($data);

        // Se houve erro, redirecionar com a mensagem adequada
        if ($response[ 'status' ] === 'error') {
            return Redirect::redirect('/provider/customers/update/' . $data[ 'id' ])
                ->withMessage('error', $response[ 'message' ] . ", tente novamente mais tarde ou entre em contato com suporte!");
        }

        $this->activityLogger(
            $this->authenticated->tenant_id,
            $this->authenticated->user_id,
            'customer_updated',
            'customer',
            $data[ 'id' ],
            "Cliente atualizado com sucesso!",
            $data,
        );

        // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
        return Redirect::redirect('/provider/customers/show/' . $data[ 'id' ])
            ->withMessage('success', 'Cliente atualizado com sucesso!');
    }

    public function delete_store($id): Response
    {
        $response = [];
        $id = $this->sanitize->sanitizeParamValue($id, 'int');
        $relationships = $this->customerService->checkRelationships($id, $this->authenticated->tenant_id);

        if ($relationships[ 'error' ]) {
            // Trata erro
            return Redirect::redirect('/provider/customers')
                ->withMessage('error', $relationships[ 'message' ]);
        }

        if ($relationships[ 'hasRelationships' ]) {
            $message = "Cliente não pode ser deletado pois possui {$relationships[ 'count' ]} ";
            $message .= "{$relationships[ 'table' ]} vinculado(s).";

            return Redirect::redirect('/provider/customers')
                ->withMessage('error', $message);
        }

        $response = $this->customerService->delete($id);
        if ($response[ 'status' ] === 'success') {

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'customer_updated',
                'customer',
                $id,
                "Cliente deletado com sucesso!",
                [
                    'id' => $id,
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
            return Redirect::redirect('/provider/customers')
                ->withMessage('success', 'Cliente deletado com sucesso!');
        }

        // Se tudo ocorreu bem, redirecionar para a página de atualização e mostrar a mensagem de sucesso
        return Redirect::redirect('/provider/customers')
            ->withMessage('error', 'Cliente não pode ser deletado, pode haver relações com outros registros, contate o suporte!');

    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
        $this->activityService->logActivity($tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata);
    }

}
