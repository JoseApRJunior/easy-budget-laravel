<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserFormRequest;
use App\Services\ActivityService;
use App\Services\RoleService;
use App\Services\UserRegistrationService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RoleService $roleService,
        private readonly UserRegistrationService $userRegistrationService,
        private readonly ActivityService $activityService,
    ) {}

    public function index( Request $request ): View
    {
        $page   = $request->get( 'page', 1 );
        $limit  = 20;
        $offset = ( $page - 1 ) * $limit;

        $filters     = $request->only( [ 'role', 'status', 'search' ] );
        $usersResult = $this->userService->findAllByTenantId(
            Auth::user()->tenant_id,
            $filters,
            [ 'createdAt' => 'DESC' ],
            $limit,
            $offset,
        );

        $users      = $usersResult->isSuccess() ? $usersResult->data : [];
        $totalUsers = $this->userService->countByTenantId( Auth::user()->tenant_id, $filters );

        $totalPages = ceil( $totalUsers / $limit );

        return view( 'admin.user.index', compact( 'users', 'page', 'totalPages', 'filters' ) );
    }

    public function create(): View
    {
        $rolesResult = $this->roleService->findAll();
        $roles       = $rolesResult->isSuccess() ? $rolesResult->data : [];

        return view( 'admin.user.create', compact( 'roles' ) );
    }

    public function store( UserFormRequest $request ): RedirectResponse
    {
        try {
            $data                = $request->validated();
            $data[ 'tenant_id' ] = Auth::user()->tenant_id;

            $userResult = $this->userRegistrationService->registerUser(
                $data[ 'email' ],
                $data[ 'password' ],
                $data[ 'first_name' ],
                $data[ 'last_name' ],
                $data[ 'tenant_id' ],
                $data[ 'role_id' ] ?? 2
            );

            if ( !$userResult->isSuccess() ) {
                return redirect()->route( 'admin.users.create' )->with( 'error', $userResult->getMessage() );
            }

            $user = $userResult->getData();

            $this->activityService->logActivity(
                Auth::user()->tenant_id,
                Auth::id(),
                'user_created',
                'user',
                $user->getId(),
                "Usuário {$data[ 'first_name' ]} {$data[ 'last_name' ]} criado",
                $data,
            );

            return redirect()->route( 'admin.users.index' )->with( 'success', 'Usuário criado com sucesso!' );
        } catch ( ValidationException $e ) {
            return back()->withErrors( $e->errors() )->withInput();
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Falha ao criar o usuário: ' . $e->getMessage() )->withInput();
        }
    }

    public function show( int $id ): View
    {
        $userResult = $this->userService->findByIdAndTenantId( $id, Auth::user()->tenant_id );

        if ( !$userResult->isSuccess() ) {
            abort( 404, 'Usuário não encontrado' );
        }

        $user = $userResult->getData();

        return view( 'admin.user.show', compact( 'user' ) );
    }

    public function edit( int $id ): View
    {
        $userResult = $this->userService->findByIdAndTenantId( $id, Auth::user()->tenant_id );

        if ( !$userResult->isSuccess() ) {
            abort( 404, 'Usuário não encontrado' );
        }

        $user = $userResult->getData();

        $rolesResult = $this->roleService->findAll();
        $roles       = $rolesResult->isSuccess() ? $rolesResult->data : [];

        return view( 'admin.user.edit', compact( 'user', 'roles' ) );
    }

    public function update( UserFormRequest $request, int $id ): RedirectResponse
    {
        try {
            $userResult = $this->userService->findByIdAndTenantId( $id, Auth::user()->tenant_id );

            if ( !$userResult->isSuccess() ) {
                abort( 404, 'Usuário não encontrado' );
            }

            $user = $userResult->getData();

            $data = $request->validated();

            $user->setFirstName( $data[ 'first_name' ] );
            $user->setLastName( $data[ 'last_name' ] );
            $user->setEmail( $data[ 'email' ] );
            $user->setRoleId( $data[ 'role_id' ] ?? $user->getRoleId() );

            $updateResult = $this->userService->update( $user, Auth::user()->tenant_id );

            if ( !$updateResult->isSuccess() ) {
                return back()->with( 'error', $updateResult->getMessage() );
            }

            $this->activityService->logActivity(
                Auth::user()->tenant_id,
                Auth::id(),
                'user_updated',
                'user',
                $id,
                "Usuário {$data[ 'first_name' ]} {$data[ 'last_name' ]} atualizado",
                $data,
            );

            return redirect()->route( 'admin.users.show', $id )->with( 'success', 'Usuário atualizado com sucesso!' );
        } catch ( ValidationException $e ) {
            return back()->withErrors( $e->errors() )->withInput();
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Falha ao atualizar o usuário: ' . $e->getMessage() );
        }
    }

    public function destroy( int $id ): RedirectResponse
    {
        try {
            $userResult = $this->userService->findByIdAndTenantId( $id, Auth::user()->tenant_id );

            if ( !$userResult->isSuccess() ) {
                abort( 404, 'Usuário não encontrado' );
            }

            $user     = $userResult->getData();
            $userName = $user->getFirstName() . ' ' . $user->getLastName();

            $deleteResult = $this->userService->delete( $id, Auth::user()->tenant_id );

            if ( !$deleteResult->isSuccess() ) {
                return back()->with( 'error', $deleteResult->getMessage() );
            }

            $this->activityService->logActivity(
                Auth::user()->tenant_id,
                Auth::id(),
                'user_deleted',
                'user',
                $id,
                "Usuário {$userName} removido",
                [ 'user_id' => $id, 'user_name' => $userName ],
            );

            return redirect()->route( 'admin.users.index' )->with( 'success', 'Usuário removido com sucesso!' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Falha ao excluir o usuário: ' . $e->getMessage() );
        }
    }

    public function alpha( Request $request ): View
    {
        $letter = $request->get( 'letter', 'A' );

        $usersResult = $this->userService->findAllByTenantId(
            Auth::user()->tenant_id,
            [],
            [ 'firstName' => 'ASC' ],
        );

        $users = [];
        if ( $usersResult->isSuccess() ) {
            $users = array_filter( $usersResult->data, function ($user) use ($letter) {
                return strtoupper( substr( $user->getFirstName(), 0, 1 ) ) === strtoupper( $letter );
            } );
        }

        $letters = range( 'A', 'Z' );

        return view( 'admin.user.alpha', compact( 'users', 'letter', 'letters' ) );
    }

}
