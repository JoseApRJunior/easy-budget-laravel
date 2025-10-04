<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CommonData;
use App\Models\Plan;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnhancedRegisteredUserController extends Controller
{
    /**
     * Exibir a tela de registro aprimorada.
     */
    public function create(): View
    {
        // Buscar planos ativos disponíveis
        $plans = Plan::active()
            ->orderBy( 'price' )
            ->get()
            ->toArray();

        return view( 'auth.enhanced-register', compact( 'plans' ) );
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
            'plan'           => [ 'required', 'string', Rule::exists( 'plans', 'slug' )->where( 'status', true ) ],
            'terms_accepted' => [ 'required', 'accepted' ],
        ], [
            'first_name.required'     => 'O nome é obrigatório.',
            'last_name.required'      => 'O sobrenome é obrigatório.',
            'phone.required'          => 'O telefone é obrigatório.',
            'plan.required'           => 'A seleção de um plano é obrigatória.',
            'plan.exists'             => 'O plano selecionado não está disponível.',
            'terms_accepted.required' => 'Você deve aceitar os termos de serviço.',
            'terms_accepted.accepted' => 'Você deve aceitar os termos de serviço.',
        ] );

        try {
            DB::beginTransaction();

            // 1. Criar ou buscar tenant (por simplicidade, vamos criar um novo tenant)
            $tenant = Tenant::create( [
                'name'      => $request->first_name . ' ' . $request->last_name,
                'domain'    => strtolower( str_replace( ' ', '', $request->first_name . $request->last_name ) ),
                'is_active' => true,
            ] );

            // 2. Buscar o plano selecionado
            $plan = Plan::where( 'slug', $request->plan )->where( 'status', true )->first();

            if ( !$plan ) {
                throw new \Exception( 'Plano selecionado não encontrado ou não está ativo.' );
            }

            // 3. Criar o usuário
            $user = User::create( [
                'tenant_id' => $tenant->id,
                'email'     => $request->email,
                'password'  => Hash::make( $request->password ),
                'is_active' => true,
            ] );

            // 4. Criar os dados comuns
            $commonData = CommonData::create( [
                'tenant_id'    => $tenant->id,
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'cpf'          => null, // Pode ser adicionado posteriormente
                'cnpj'         => null, // Pode ser adicionado posteriormente
                'company_name' => null, // Pode ser adicionado posteriormente
                'description'  => null, // Pode ser adicionado posteriormente
            ] );

            // 5. Criar o provider
            $provider = Provider::create( [
                'tenant_id'      => $tenant->id,
                'user_id'        => $user->id,
                'common_data_id' => $commonData->id,
                'contact_id'     => null, // Pode ser adicionado posteriormente
                'address_id'     => null, // Pode ser adicionado posteriormente
                'terms_accepted' => $request->terms_accepted,
            ] );

            // 6. Atualizar o common_data_id no provider (relacionamento bidirecional)
            $provider->common_data_id = $commonData->id;
            $provider->save();

            // 7. Criar assinatura do plano (se necessário)
            // Por enquanto, apenas associamos o plano ao provider
            // Você pode implementar PlanSubscription posteriormente se necessário

            DB::commit();

            // 8. Disparar evento de registro
            event( new Registered( $user ) );

            // 9. Fazer login automático
            Auth::login( $user );

            // 10. Redirecionar para dashboard com mensagem de sucesso
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

    /**
     * Exibir informações sobre os planos disponíveis.
     */
    public function showPlans(): View
    {
        $plans = Plan::active()
            ->orderBy( 'price' )
            ->get()
            ->toArray();

        return view( 'auth.plans', compact( 'plans' ) );
    }

}
