<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\CustomerPessoaFisicaRequest;
use App\Models\AreaOfActivity;
use App\Models\Customer;
use App\Models\Profession;
use App\Models\User;
use App\Services\Domain\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller para gestão de clientes - Interface Web
 * Versão simplificada para resolver problemas de sintaxe
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
    ) {}

    /**
     * Lista todos os clientes
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        
        $customers = Customer::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->paginate(20);

        return view('pages.customer.index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Formulário de criação de cliente
     */
    public function create(): View
    {
        $areasOfActivity = AreaOfActivity::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $professions = Profession::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.customer.create', [
            'areas_of_activity' => $areasOfActivity,
            'professions' => $professions,
            'customer' => new Customer(),
        ]);
    }

    /**
     * Criar cliente - Pessoa Física
     */
    public function storePessoaFisica(CustomerPessoaFisicaRequest $request): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $this->customerService->createPessoaFisica($request->validated(), $user->tenant_id);
            
            return redirect()->route('customers.index')
                ->with('success', 'Cliente criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar cliente: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalhes do cliente
     */
    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);
        
        return view('pages.customer.show', [
            'customer' => $customer->load(['commonData', 'contact', 'address']),
        ]);
    }
}
