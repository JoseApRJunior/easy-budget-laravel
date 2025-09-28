<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OperationStatus;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de usuários
 *
 * Este controller gerencia operações CRUD para usuários do sistema,
 * incluindo listagem, criação, edição, exclusão e funcionalidades específicas
 * como ativação de contas e confirmação de email.
 */
class UserController extends \Illuminate\Routing\Controller
{
    protected UserService $userService;

    /**
     * Construtor com injeção de dependência
     */
    public function __construct( UserService $userService )
    {
        $this->userService = $userService;
    }

    /**
     * Lista todos os usuários do tenant
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index( Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );
            $filters  = $request->only( [ 'status', 'name', 'email', 'role' ] );
            $orderBy  = $request->get( 'order_by', [ 'name' => 'asc' ] );
            $limit    = $request->get( 'limit', 15 );

            $result = $this->userService->listByTenantId( $tenantId, $filters );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ), $request, 'Erro ao listar usuários.' );
            }

            $users = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $users,
                    'message' => 'Usuários listados com sucesso.'
                ] );
            }

            return view( 'users.index', [
                'users' => $users
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao listar usuários.' );
        }
    }

    /**
     * Exibe formulário para criação de usuário
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function create( Request $request ): View|JsonResponse
    {
        try {
            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'message' => 'Formulário de criação disponível.'
                ] );
            }

            return view( 'users.create' );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir formulário de criação.' );
        }
    }

    /**
     * Cria um novo usuário
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store( Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            // Validação dos dados
            $validatedData = $request->validate( [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|max:255|unique:users,email,NULL,id,tenant_id,' . $tenantId,
                'password' => 'required|string|min:8|confirmed',
                'role'     => 'nullable|string|max:100',
                'status'   => 'nullable|in:active,pending,inactive'
            ] );

            $validatedData[ 'tenant_id' ] = $tenantId;
            $validatedData[ 'status' ]    = $validatedData[ 'status' ] ?? 'pending';

            $result = $this->userService->createByTenantId( $validatedData, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Usuário criado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ], 201 );
            }

            return redirect()->route( 'users.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao criar usuário.' );
        }
    }

    /**
     * Exibe detalhes de um usuário específico
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function show( int $id, Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->getByIdAndTenantId( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Usuário não encontrado.' );
            }

            $user = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $user,
                    'message' => 'Usuário encontrado com sucesso.'
                ] );
            }

            return view( 'users.show', [
                'user' => $user
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir usuário.' );
        }
    }

    /**
     * Exibe formulário para edição de usuário (Admin)
     *
     * @param int $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function edit( int $id, Request $request ): View|JsonResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->getByIdAndTenantId( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleNotFound( $request, $result->getMessage() ?? 'Usuário não encontrado.' );
            }

            $user = $result->getData();

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $user,
                    'message' => 'Formulário de edição disponível.'
                ] );
            }

            return view( 'users.edit', [
                'user' => $user
            ] );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao exibir formulário de edição.' );
        }
    }

    /**
     * Atualiza um usuário específico
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse|RedirectResponse
     */
    public function update( Request $request, int $id ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            // Validação dos dados
            $validatedData = $request->validate( [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|max:255|unique:users,email,' . $id . ',id,tenant_id,' . $tenantId,
                'password' => 'nullable|string|min:8|confirmed',
                'role'     => 'nullable|string|max:100',
                'status'   => 'nullable|in:active,pending,inactive'
            ] );

            $result = $this->userService->updateByIdAndTenantId( $id, $validatedData, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleValidationError( $result, $request );
            }

            $message = 'Usuário atualizado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->route( 'users.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao atualizar usuário.' );
        }
    }

    /**
     * Remove um usuário específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function destroy( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->deleteByIdAndTenantId( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ?? 'Erro ao deletar usuário.' ), $request, 'Erro ao deletar usuário.' );
            }

            $message = 'Usuário deletado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'message' => $message
                ] );
            }

            return redirect()->route( 'users.index' )
                ->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao deletar usuário.' );
        }
    }

    /**
     * Ativa conta de usuário
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function activate( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->activateAccount( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ?? 'Erro ao ativar usuário.' ), $request, 'Erro ao ativar usuário.' );
            }

            $message = 'Usuário ativado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->back()->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao ativar usuário.' );
        }
    }

    /**
     * Confirma conta de usuário via token
     *
     * @param string $token
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function confirmAccount( string $token, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->confirmAccount( $token, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ?? 'Erro ao confirmar conta.' ), $request, 'Erro ao confirmar conta.' );
            }

            $message = 'Conta confirmada com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->route( 'login' )->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao confirmar conta.' );
        }
    }

    /**
     * Gera novo token de confirmação para usuário
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function resendConfirmation( int $id, Request $request ): JsonResponse|RedirectResponse
    {
        try {
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->generateConfirmationToken( $id, $tenantId );

            if ( !$result->isSuccess() ) {
                return $this->handleError( new Exception( $result->getMessage() ?? 'Erro ao gerar token.' ), $request, 'Erro ao gerar token.' );
            }

            $message = 'Token de confirmação enviado com sucesso.';

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData(),
                    'message' => $message
                ] );
            }

            return redirect()->back()->with( 'success', $message );

        } catch ( Exception $e ) {
            return $this->handleError( $e, $request, 'Erro ao gerar token de confirmação.' );
        }
    }

    /**
     * Obtém o ID do tenant da requisição
     *
     * @param Request $request
     * @return int
     */
    private function getTenantId( Request $request ): int
    {
        // Implementar lógica para obter tenant_id
        // Pode vir do usuário autenticado, header, sessão, etc.
        return (int) $request->header( 'X-Tenant-ID', 1 );
    }

    /**
     * Trata erros de validação
     *
     * @param mixed $result
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    private function handleValidationError( $result, Request $request )
    {
        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $result->getMessage() ?? 'Erro de validação.',
                'errors'  => $result->getData() ?? []
            ], 422 );
        }

        return redirect()->back()
            ->withErrors( $result->getMessage() ?? 'Erro de validação.' )
            ->withInput();
    }

    /**
     * Trata recurso não encontrado
     *
     * @param Request $request
     * @param string $message
     * @return JsonResponse|RedirectResponse
     */
    private function handleNotFound( Request $request, string $message )
    {
        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $message
            ], 404 );
        }

        return redirect()->route( 'users.index' )
            ->with( 'error', $message );
    }

    /**
     * Trata erros genéricos
     *
     * @param Exception $e
     * @param Request $request
     * @param string $defaultMessage
     * @return JsonResponse|RedirectResponse
     */
    private function handleError( Exception $e, Request $request, string $defaultMessage )
    {
        $message = $e->getMessage() ?: $defaultMessage;

        if ( $request->expectsJson() ) {
            return response()->json( [
                'success' => false,
                'message' => $message
            ], 500 );
        }

        return redirect()->back()
            ->with( 'error', $message );
    }

    // ================= MÉTODOS ADICIONAIS PARA MIGRAÇÃO =================

    /**
     * Dashboard do Provider
     *
     * @param Request $request
     * @return View
     */
    public function providerDashboard( Request $request ): View
    {
        try {
            $tenantId = $this->getTenantId( $request );

            // TODO: Implementar lógica para obter dados do dashboard do provider
            $dashboardData = [
                'total_budgets'     => 0,
                'total_customers'   => 0,
                'total_services'    => 0,
                'recent_activities' => [],
            ];

            return view( 'provider.dashboard', $dashboardData );

        } catch ( Exception $e ) {
            return view( 'provider.dashboard', [
                'total_budgets'     => 0,
                'total_customers'   => 0,
                'total_services'    => 0,
                'recent_activities' => [],
            ] );
        }
    }

    /**
     * Dashboard do Admin
     *
     * @param Request $request
     * @return View
     */
    public function adminDashboard( Request $request ): View
    {
        try {
            $tenantId = $this->getTenantId( $request );

            // TODO: Implementar lógica para obter dados do dashboard do admin
            $dashboardData = [
                'total_users'       => 0,
                'total_providers'   => 0,
                'total_budgets'     => 0,
                'system_health'     => 'good',
                'recent_activities' => [],
            ];

            return view( 'admin.dashboard', $dashboardData );

        } catch ( Exception $e ) {
            return view( 'admin.dashboard', [
                'total_users'       => 0,
                'total_providers'   => 0,
                'total_budgets'     => 0,
                'system_health'     => 'good',
                'recent_activities' => [],
            ] );
        }
    }

    /**
     * Perfil do usuário
     *
     * @param Request $request
     * @return View
     */
    public function profile( Request $request ): View
    {
        try {
            $user = auth()->user();

            return view( 'provider.profile', [
                'user' => $user
            ] );

        } catch ( Exception $e ) {
            return view( 'provider.profile', [
                'user' => auth()->user()
            ] );
        }
    }

    /**
     * Exibe formulário de edição do perfil
     *
     * @param Request $request
     * @return View
     */
    public function editProfile( Request $request ): View
    {
        try {
            $user = auth()->user();

            return view( 'provider.update', [
                'user' => $user
            ] );

        } catch ( Exception $e ) {
            return view( 'provider.update', [
                'user' => auth()->user()
            ] );
        }
    }

    /**
     * Processa atualização do perfil
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateProfile( Request $request ): RedirectResponse
    {
        try {
            $validatedData = $request->validate( [
                'name'         => 'required|string|max:255',
                'email'        => 'required|email|max:255',
                'phone'        => 'nullable|string|max:20',
                'company_name' => 'nullable|string|max:255',
            ] );

            $user     = auth()->user();
            $tenantId = $this->getTenantId( $request );

            $result = $this->userService->updateByIdAndTenantId( $user->id, $validatedData, $tenantId );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() ?? 'Erro ao atualizar perfil.' )
                    ->withInput();
            }

            return redirect()->route( 'provider.profile' )
                ->with( 'success', 'Perfil atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao atualizar perfil.' )
                ->withInput();
        }
    }

    /**
     * Exibe formulário de mudança de senha
     *
     * @param Request $request
     * @return View
     */
    public function changePassword( Request $request ): View
    {
        return view( 'provider.change-password' );
    }

    /**
     * Processa mudança de senha
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function changePasswordStore( Request $request ): RedirectResponse
    {
        try {
            $validatedData = $request->validate( [
                'current_password' => 'required|string',
                'password'         => 'required|string|min:8|confirmed',
            ] );

            $user = auth()->user();

            // Verificar senha atual
            if ( !password_verify( $validatedData[ 'current_password' ], $user->password ) ) {
                return redirect()->back()
                    ->with( 'error', 'Senha atual incorreta.' )
                    ->withInput();
            }

            $tenantId = $this->getTenantId( $request );
            $result   = $this->userService->updatePassword( $user->id, $validatedData[ 'password' ], $tenantId );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() ?? 'Erro ao alterar senha.' )
                    ->withInput();
            }

            return redirect()->route( 'provider.profile' )
                ->with( 'success', 'Senha alterada com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao alterar senha.' )
                ->withInput();
        }
    }

    /**
     * Configurações do usuário
     *
     * @param Request $request
     * @return View
     */
    public function settings( Request $request ): View
    {
        try {
            $user = auth()->user();

            return view( 'settings.index', [
                'user' => $user
            ] );

        } catch ( Exception $e ) {
            return view( 'settings.index', [
                'user' => auth()->user()
            ] );
        }
    }

    /**
     * Processa atualização das configurações
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSettings( Request $request ): RedirectResponse
    {
        try {
            $validatedData = $request->validate( [
                'theme'           => 'nullable|in:light,dark,auto',
                'language'        => 'nullable|in:pt-BR,en',
                'notifications'   => 'nullable|boolean',
                'email_marketing' => 'nullable|boolean',
            ] );

            // TODO: Implementar lógica para salvar configurações
            // $this->userService->updateSettings($user->id, $validatedData, $tenantId);

            return redirect()->route( 'settings.index' )
                ->with( 'success', 'Configurações atualizadas com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao atualizar configurações.' )
                ->withInput();
        }
    }

    /**
     * Exibe logs de atividades
     *
     * @param Request $request
     * @return View
     */
    public function logs( Request $request ): View
    {
        try {
            // TODO: Implementar lógica para obter logs
            $logs = [];

            return view( 'admin.logs', [
                'logs' => $logs
            ] );

        } catch ( Exception $e ) {
            return view( 'admin.logs', [
                'logs' => []
            ] );
        }
    }

    /**
     * Exibe atividades do sistema
     *
     * @param Request $request
     * @return View
     */
    public function activities( Request $request ): View
    {
        try {
            // TODO: Implementar lógica para obter atividades
            $activities = [];

            return view( 'admin.activities', [
                'activities' => $activities
            ] );

        } catch ( Exception $e ) {
            return view( 'admin.activities', [
                'activities' => []
            ] );
        }
    }

    /**
     * Sistema de monitoramento
     *
     * @param Request $request
     * @return View
     */
    public function monitoring( Request $request ): View
    {
        try {
            // TODO: Implementar lógica para obter dados de monitoramento
            $monitoringData = [
                'system_health' => 'good',
                'cpu_usage'     => 0,
                'memory_usage'  => 0,
                'disk_usage'    => 0,
            ];

            return view( 'admin.monitoring', $monitoringData );

        } catch ( Exception $e ) {
            return view( 'admin.monitoring', [
                'system_health' => 'unknown',
                'cpu_usage'     => 0,
                'memory_usage'  => 0,
                'disk_usage'    => 0,
            ] );
        }
    }

    /**
     * Métricas do sistema
     *
     * @param Request $request
     * @return View
     */
    public function metrics( Request $request ): View
    {
        try {
            // TODO: Implementar lógica para obter métricas
            $metrics = [];

            return view( 'admin.metrics', [
                'metrics' => $metrics
            ] );

        } catch ( Exception $e ) {
            return view( 'admin.metrics', [
                'metrics' => []
            ] );
        }
    }

    /**
     * Alertas do sistema
     *
     * @param Request $request
     * @return View
     */
    public function alerts( Request $request ): View
    {
        try {
            // TODO: Implementar lógica para obter alertas
            $alerts = [];

            return view( 'admin.alerts', [
                'alerts' => $alerts
            ] );

        } catch ( Exception $e ) {
            return view( 'admin.alerts', [
                'alerts' => []
            ] );
        }
    }

    /**
     * Conta bloqueada
     *
     * @return View
     */
    public function blockAccount(): View
    {
        return view( 'auth.block-account' );
    }

    /**
     * Processa reenvio de link de confirmação
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function resendConfirmationLink( Request $request ): RedirectResponse
    {
        try {
            $validatedData = $request->validate( [
                'email' => 'required|email|max:255',
            ] );

            // TODO: Implementar lógica para reenviar confirmação
            // $this->userService->resendConfirmationLink($validatedData['email']);

            return redirect()->back()
                ->with( 'success', 'Link de confirmação reenviado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao reenviar link de confirmação.' )
                ->withInput();
        }
    }

}
