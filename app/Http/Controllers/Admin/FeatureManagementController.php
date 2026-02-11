<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Resource;
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureManagementController extends Controller
{
    public function __construct(
        private FeatureService $featureService
    ) {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        $features = $this->featureService->getAllFeatures();
        return view('admin.features.index', compact('features'));
    }

    public function toggle(Resource $resource)
    {
        $this->featureService->toggleFeature($resource);
        return back()->with('success', "Status do módulo '{$resource->name}' atualizado!");
    }

    public function toggleDev(Resource $resource)
    {
        $resource->update([
            'in_dev' => !$resource->in_dev
        ]);
        
        $status = $resource->in_dev ? 'Em Desenvolvimento (Restrito)' : 'Produção (Liberado)';
        return back()->with('success', "Status de desenvolvimento de '{$resource->name}' alterado para: {$status}");
    }

    public function update(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $resource->update($validated);

        return back()->with('success', "Módulo '{$resource->name}' atualizado com sucesso!");
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:resources,slug',
        ]);

        $this->featureService->createFeature($request->all());

        return back()->with('success', "Novo módulo cadastrado com sucesso!");
    }

    public function destroy(Resource $resource)
    {
        $name = $resource->name;
        $this->featureService->deleteFeature($resource);

        return back()->with('success', "Módulo '{$name}' removido com sucesso!");
    }
}
