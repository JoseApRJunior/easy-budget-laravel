<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AlertsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('manage-alerts');

        $alerts = collect([
            [
                'id' => 1,
                'type' => 'system',
                'title' => 'Uso de CPU Acima do Normal',
                'message' => 'CPU usage está em 85% no servidor principal',
                'severity' => 'warning',
                'status' => 'active',
                'created_at' => now()->subMinutes(15),
            ],
            [
                'id' => 2,
                'type' => 'security',
                'title' => 'Tentativa de Login Suspeita',
                'message' => 'Múltiplas tentativas de login falhadas detectadas',
                'severity' => 'danger',
                'status' => 'active',
                'created_at' => now()->subMinutes(30),
            ],
            [
                'id' => 3,
                'type' => 'financial',
                'title' => 'Fatura Atrasada',
                'message' => 'Fatura #12345 está 5 dias atrasada',
                'severity' => 'info',
                'status' => 'resolved',
                'created_at' => now()->subHours(2),
            ],
        ]);

        $stats = [
            'total' => $alerts->count(),
            'active' => $alerts->where('status', 'active')->count(),
            'resolved' => $alerts->where('status', 'resolved')->count(),
            'critical' => $alerts->where('severity', 'danger')->count(),
        ];

        return view('admin.alerts.index', compact('alerts', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('manage-alerts');

        $alertTypes = [
            'system' => 'Sistema',
            'security' => 'Segurança',
            'financial' => 'Financeiro',
            'performance' => 'Performance',
            'backup' => 'Backup',
            'maintenance' => 'Manutenção',
        ];

        $severities = [
            'info' => 'Informativo',
            'warning' => 'Aviso',
            'danger' => 'Crítico',
        ];

        return view('admin.alerts.create', compact('alertTypes', 'severities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-alerts');

        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'severity' => 'required|string|max:20',
            'status' => 'required|string|max:20',
        ]);

        // Simular criação do alerta
        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerta criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $this->authorize('manage-alerts');

        $alert = [
            'id' => $id,
            'type' => 'system',
            'title' => 'Exemplo de Alerta',
            'message' => 'Este é um exemplo de alerta para demonstração',
            'severity' => 'warning',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return view('admin.alerts.show', compact('alert'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $this->authorize('manage-alerts');

        $alert = [
            'id' => $id,
            'type' => 'system',
            'title' => 'Exemplo de Alerta',
            'message' => 'Este é um exemplo de alerta para demonstração',
            'severity' => 'warning',
            'status' => 'active',
        ];

        $alertTypes = [
            'system' => 'Sistema',
            'security' => 'Segurança',
            'financial' => 'Financeiro',
            'performance' => 'Performance',
            'backup' => 'Backup',
            'maintenance' => 'Manutenção',
        ];

        $severities = [
            'info' => 'Informativo',
            'warning' => 'Aviso',
            'danger' => 'Crítico',
        ];

        return view('admin.alerts.edit', compact('alert', 'alertTypes', 'severities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $this->authorize('manage-alerts');

        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'severity' => 'required|string|max:20',
            'status' => 'required|string|max:20',
        ]);

        // Simular atualização do alerta
        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerta atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $this->authorize('manage-alerts');

        // Simular exclusão do alerta
        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerta excluído com sucesso!');
    }

    /**
     * Toggle the status of the specified alert.
     */
    public function toggleStatus($id): RedirectResponse
    {
        $this->authorize('manage-alerts');

        // Simular alteração de status
        return redirect()->route('admin.alerts.index')
            ->with('success', 'Status do alerta alterado com sucesso!');
    }

    /**
     * Export alerts to specified format.
     */
    public function export($format): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('manage-alerts');

        // Simular exportação
        $filename = 'alerts_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        // Retornar uma resposta vazia para exemplo
        return response()->download(
            storage_path('app/public/' . $filename),
            $filename
        );
    }
}