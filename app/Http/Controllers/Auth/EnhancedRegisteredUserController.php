<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\CommonData;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EnhancedRegisteredUserController extends Controller
{
    /**
     * Exibir a tela de registro aprimorada.
     */
    public function create(): View
    {
        return view( 'auth.enhanced-register' );
    }

    /**
     * Processar o registro de um novo usuário com estrutura completa.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store( Request $request ): RedirectResponse
    {
        $request->validate( [
            'first_name'     => [ 'required', 'string', 'max:100' ],
            'last_name'      => [ 'required', 'string', 'max:100' ],
            'email'          => [ 'required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone'          => [ 'required', 'string', 'max:20' ],
            'password'       => [ 'required', 'confirmed', 'min:8' ],
            'terms_accepted' => [ 'required', 'accepted' ],
        ], [
            'first_name.required'     => 'O nome é obrigatório.',
            'last_name.required'      => 'O sobrenome é obrigatório.',
            'email.required'          => 'O email é obrigatório.',
            'phone.required'          => 'O telefone é obrigatório.',
            'terms_accepted.required' => 'Você deve aceitar os termos de serviço.',
            'terms_accepted.accepted' => 'Você deve aceitar os termos de serviço.',
        ] );

        try {
            DB::beginTransaction();

            // 1. Criar ou buscar tenant (por simplicidade, vamos criar um novo tenant)
            Log::info( 'Criando tenant...', [ 'name' => $request->first_name . ' ' . $request->last_name ] );
            $tenant = Tenant::create( [
                'name'      => $request->first_name . ' ' . $request->last_name . ' ' . time(),
                'is_active' => true,
            ] );
            Log::info( 'Tenant criado com sucesso', [ 'tenant_id' => $tenant->id ] );

            // 2. Buscar o plano gratuito ou primeiro disponível
            Log::info( 'Buscando plano disponível...' );
            $plan = Plan::where( 'status', true )->orderBy( 'price' )->first();

            if ( !$plan ) {
                // Criar plano básico se nenhum existir
                $plan = Plan::create( [
                    'name'        => 'Plano Básico',
                    'slug'        => 'basico',
                    'description' => 'Plano básico para novos usuários',
                    'price'       => 0.00,
                    'status'      => true,
                    'max_budgets' => 10,
                    'max_clients' => 50,
                    'features'    => json_encode( [
                        'budgets' => 10,
                        'clients' => 50,
                        'reports' => true,
                        'support' => 'basic'
                    ] )
                ] );
                Log::info( 'Plano básico criado automaticamente', [ 'plan_id' => $plan->id ] );
            } else {
                Log::info( 'Plano encontrado', [ 'plan_id' => $plan->id, 'plan_name' => $plan->name ] );
            }

            // 3. Criar o usuário
            Log::info( 'Criando usuário...' );
            $user = User::create( [
                'tenant_id' => $tenant->id,
                'email'     => $request->email,
                'password'  => Hash::make( $request->password ),
                'is_active' => true,
            ] );
            Log::info( 'Usuário criado', [ 'user_id' => $user->id ] );

            // 4. Criar os dados comuns
            Log::info( 'Criando dados comuns...' );
            $commonData = CommonData::create( [
                'tenant_id'    => $tenant->id,
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'cpf'          => null, // Pode ser adicionado posteriormente
                'cnpj'         => null, // Pode ser adicionado posteriormente
                'company_name' => null, // Pode ser adicionado posteriormente
                'description'  => null, // Pode ser adicionado posteriormente
            ] );
            Log::info( 'Dados comuns criados', [ 'common_data_id' => $commonData->id ] );

            // 5. Criar o provider
            Log::info( 'Criando provider...' );
            $provider = Provider::create( [
                'tenant_id'      => $tenant->id,
                'user_id'        => $user->id,
                'common_data_id' => $commonData->id,
                'contact_id'     => null, // Pode ser adicionado posteriormente
                'address_id'     => null, // Pode ser adicionado posteriormente
                'terms_accepted' => $request->terms_accepted,
            ] );
            Log::info( 'Provider criado', [ 'provider_id' => $provider->id ] );

            // 6. Atualizar o common_data_id no provider (relacionamento bidirecional)
            $provider->common_data_id = $commonData->id;
            $provider->save();

            // 7. Buscar o role 'provider' e criar a relação user_roles
            Log::info( 'Buscando role provider...' );
            $providerRole = Role::where( 'name', 'provider' )->first();

            if ( $providerRole ) {
                Log::info( 'Role provider encontrado', [ 'role_id' => $providerRole->id ] );

                // Criar a relação user_roles
                $user->roles()->attach( $providerRole->id, [
                    'tenant_id'  => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ] );

                Log::info( 'Relação user_roles criada com sucesso', [
                    'user_id'   => $user->id,
                    'role_id'   => $providerRole->id,
                    'tenant_id' => $tenant->id
                ] );
            } else {
                Log::warning( 'Role provider não encontrado no banco de dados' );
            }

            // 8. Criar assinatura do plano (se necessário)
            Log::info( 'Criando assinatura do plano...' );
            $plan_subscription = PlanSubscription::create( [
                'tenant_id'          => $tenant->id,
                'plan_id'            => $plan->id,
                'user_id'            => $user->id,
                'provider_id'        => $provider->id,
                'status'             => 'active',
                'transaction_amount' => $plan->price ?? 0.00,
                'transaction_date'   => now(),
                'start_date'         => now(),
                'end_date'           => date( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
            ] );
            Log::info( 'Assinatura do plano criada', [ 'subscription_id' => $plan_subscription->id ] );

            DB::commit();

            // 8. Disparar evento de registro
            event( new Registered( $user ) );

            // 9. Enviar e-mail de verificação
            try {
                $user->sendEmailVerificationNotification();
                Log::info( 'E-mail de verificação enviado', [ 'user_id' => $user->id, 'email' => $user->email ] );

                // Para desenvolvimento, mostrar informações do e-mail enviado
                if ( app()->environment( 'local' ) ) {
                    Log::info( 'E-mail de verificação (desenvolvimento):', [
                        'user_id'          => $user->id,
                        'email'            => $user->email,
                        'verification_url' => route( 'verification.verify', [
                            'id'   => $user->id,
                            'hash' => sha1( $user->email )
                        ] )
                    ] );
                }
            } catch ( \Exception $e ) {
                Log::error( 'Erro ao enviar e-mail de verificação: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'trace'   => $e->getTraceAsString()
                ] );
                // Para desenvolvimento, mostrar erro detalhado
                if ( app()->environment( 'local' ) ) {
                    return back()->withErrors( [
                        'email' => 'Erro no envio de e-mail: ' . $e->getMessage()
                    ] )->withInput();
                }
            }

            // 10. Fazer login automático
            Auth::login( $user );

            // 11. Redirecionar para dashboard com mensagem de sucesso
            Log::info( 'Registro concluído com sucesso', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id
            ] );

            return redirect()->route( 'dashboard' )
                ->with( 'success', 'Registro realizado com sucesso! Bem-vindo ao Easy Budget.' );

        } catch ( \Exception $e ) {
            DB::rollBack();

            // Log do erro para debug
            Log::error( 'Erro no registro de usuário: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ] );

            return back()
                ->withInput()
                ->withErrors( [ 'registration' => 'Erro interno do servidor. Tente novamente em alguns minutos.' ] );
        }
    }

}
