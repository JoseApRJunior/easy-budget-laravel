<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\User;
use App\Services\Domain\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display users management dashboard
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $role = $request->get('role');
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy($sort, $direction)
            ->paginate(15)
            ->appends($request->query());

        return view('admin.users.index', compact('users', 'search', 'status', 'role', 'sort', 'direction'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,provider,customer',
            'cpf_cnpj' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'cpf_cnpj' => $validated['cpf_cnpj'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'email_verified_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'Usuário criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Erro ao criar usuário: '.$e->getMessage());
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user): View
    {
        $user->load(['tenant', 'subscriptions.plan']);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,provider,customer',
            'cpf_cnpj' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->cpf_cnpj = $validated['cpf_cnpj'] ?? null;
            $user->phone = $validated['phone'] ?? null;
            $user->status = $validated['status'];

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            DB::commit();

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'Usuário atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Erro ao atualizar usuário: '.$e->getMessage());
        }
    }

    /**
     * Block the specified user
     */
    public function block(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode bloquear seu próprio usuário.');
        }

        $user->update(['status' => 'inactive']);

        return back()->with('success', 'Usuário bloqueado com sucesso!');
    }

    /**
     * Unblock the specified user
     */
    public function unblock(User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        return back()->with('success', 'Usuário desbloqueado com sucesso!');
    }

    /**
     * Impersonate the specified user
     */
    public function impersonate(User $user): RedirectResponse
    {
        if ($user->status !== 'active') {
            return back()->with('error', 'Não é possível impersonar um usuário inativo.');
        }

        session(['impersonate' => $user->id]);
        session(['original_user' => auth()->id()]);

        return redirect()->route('provider.dashboard')
            ->with('success', 'Você está agora impersonando '.$user->name);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode excluir seu próprio usuário.');
        }

        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', 'Usuário excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao excluir usuário: '.$e->getMessage());
        }
    }

    /**
     * Display user activity
     */
    public function activity(User $user): View
    {
        $activities = $user->activities()
            ->latest()
            ->paginate(20);

        return view('admin.users.activity', compact('user', 'activities'));
    }
}
