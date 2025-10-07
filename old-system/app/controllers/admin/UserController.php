<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entities\UserEntity;
use app\database\models\User;
use app\request\UserCreateFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;

class UserController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private User $user,
        Request $request,
    ) {
        parent::__construct($request);
    }

    public function index(): Response
    {
        return new Response(
            $this->twig->env->render(
                'admin/user/user.twig',
            ),
        );
    }

    public function create(): Response
    {
        return new Response(
            $this->twig->env->render(
                'admin/user/create.twig',
                [ 'title' => 'Create User' ],
            ),
        );
    }

    public function store(): Response
    {
        $validated = UserCreateFormRequest::validate($this->request);

        if (!$validated) {
            return new Redirect('/admin/user/create');
        }

        $entity = UserEntity::create([
            'firstName' => $this->request->get('firstName'),
            'lastName' => $this->request->get('lastName'),
            'email' => $this->request->get('email'),
            'password' => password_hash($this->request->get('password'), PASSWORD_DEFAULT),
        ]);

        $checkObj = $this->user->getUserByEmail($this->request->get('email'));

        if (!$checkObj instanceof EntityNotFound) {
            Session::flash('email', "This email is already registered");

            return Redirect::redirect('/admin/user/create');
        }

        $created = $this->user->create($entity);

        if (!$created) {
            return Redirect::redirect('/admin/user/create')->withMessage('error', 'Error creating user');
        }

        return Redirect::redirect('/admin/user/create')->withMessage('message', 'User created successfully');
    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
    }

}
