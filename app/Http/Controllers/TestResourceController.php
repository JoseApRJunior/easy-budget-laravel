<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

// Aplicar padrões arquiteturais do projeto
use App\Support\ServiceResult;

class TestResourceController
{
    /**
     * Serviço responsável pela lógica de negócio
     * Seguindo padrão de injeção de dependência
     */
    protected $service;

    /**
     * Construtor com injeção de dependência
     * Aplicando padrão de 3 níveis para controllers
     */
    public function __construct()
    {
        // Serviço será injetado automaticamente pelo Laravel
        // Exemplo: TestResourceControllerService $service
    }

    /**
     * Display a listing of the resource.
     *
     * PADRÃO NÍVEL 2: Controller com filtros e paginação
     * Suporte híbrido Web + API (View ou JsonResponse)
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            // Aplicar filtros seguindo padrão arquitetural
            $filters = $request->validate([
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|string',
                'per_page' => 'nullable|integer|min:10|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            // Usar service layer para lógica de negócio
            $result = $this->service->list($filters);

            // Log automático da operação
            Log::info("Listagem de {{ pluralClass }} realizada", [
                'controller' => static::class,
                'filters' => $filters,
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            // Resposta híbrida: Web ou API
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result->isSuccess(),
                    'data' => $result->getData(),
                    'message' => $result->getMessage(),
                    'pagination' => $result->getPagination()
                ]);
            }

            return view('{{ pluralClass }}.index', [
                'data' => $result->getData(),
                'message' => $result->getMessage(),
                'filters' => $filters
            ]);

        } catch (\Exception $e) {
            Log::error("Erro na listagem de {{ pluralClass }}", [
                'controller' => static::class,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno do servidor',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return redirect()->route('{{ pluralClass }}.index')
                           ->with('error', 'Erro ao carregar listagem');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * PADRÃO NÍVEL 1: Controller básico para criação
     */
    public function create(): View
    {
        try {
            // Buscar dados necessários para o formulário
            $result = $this->service->getCreateData();

            return view('{{ pluralClass }}.create', [
                'data' => $result->getData(),
                'message' => $result->getMessage()
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao carregar formulário de criação", [
                'controller' => static::class,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->route('{{ pluralClass }}.index')
                           ->with('error', 'Erro ao carregar formulário');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * PADRÃO NÍVEL 2: Controller com validação e tratamento de erro
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            // Validação usando Form Request (será criado automaticamente)
            $validated = $request->validate([
                // Campos específicos serão definidos no Form Request
                'name' => 'required|string|max:255',
                // Adicionar outros campos conforme necessidade
            ]);

            // Usar service para lógica de criação
            $result = $this->service->create($validated);

            // Log da operação
            Log::info("TestResourceController criado com sucesso", [
                'controller' => static::class,
                'data' => $result->getData(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            // Resposta híbrida
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result->isSuccess(),
                    'data' => $result->getData(),
                    'message' => $result->getMessage()
                ], $result->isSuccess() ? 201 : 400);
            }

            if ($result->isSuccess()) {
                return redirect()->route('{{ pluralClass }}.index')
                               ->with('success', $result->getMessage());
            }

            return redirect()->back()
                           ->with('error', $result->getMessage())
                           ->withInput();

        } catch (\Exception $e) {
            Log::error("Erro na criação de TestResourceController", [
                'controller' => static::class,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno do servidor',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Erro ao criar TestResourceController')
                           ->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * PADRÃO NÍVEL 1: Controller básico para visualização
     */
    public function show(Request $request, string $id): View|JsonResponse
    {
        try {
            $result = $this->service->find($id);

            if (!$result->isSuccess()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result->getMessage()
                    ], 404);
                }

                return redirect()->route('{{ pluralClass }}.index')
                               ->with('error', $result->getMessage());
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $result->getData(),
                    'message' => $result->getMessage()
                ]);
            }

            return view('{{ pluralClass }}.show', [
                'data' => $result->getData(),
                'message' => $result->getMessage()
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao visualizar TestResourceController", [
                'controller' => static::class,
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno do servidor'
                ], 500);
            }

            return redirect()->route('{{ pluralClass }}.index')
                           ->with('error', 'Erro ao carregar TestResourceController');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * PADRÃO NÍVEL 1: Controller básico para edição
     */
    public function edit(Request $request, string $id): View|RedirectResponse
    {
        try {
            $result = $this->service->find($id);

            if (!$result->isSuccess()) {
                return redirect()->route('{{ pluralClass }}.index')
                               ->with('error', $result->getMessage());
            }

            return view('{{ pluralClass }}.edit', [
                'data' => $result->getData(),
                'message' => $result->getMessage()
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao carregar formulário de edição", [
                'controller' => static::class,
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->route('{{ pluralClass }}.index')
                           ->with('error', 'Erro ao carregar formulário de edição');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * PADRÃO NÍVEL 2: Controller com validação e tratamento completo
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        try {
            // Validação usando Form Request
            $validated = $request->validate([
                // Campos específicos serão definidos no Form Request
                'name' => 'required|string|max:255',
                // Adicionar outros campos conforme necessidade
            ]);

            // Usar service para lógica de atualização
            $result = $this->service->update($id, $validated);

            // Log da operação
            Log::info("TestResourceController atualizado com sucesso", [
                'controller' => static::class,
                'id' => $id,
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            // Resposta híbrida
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result->isSuccess(),
                    'data' => $result->getData(),
                    'message' => $result->getMessage()
                ]);
            }

            if ($result->isSuccess()) {
                return redirect()->route('{{ pluralClass }}.index')
                               ->with('success', $result->getMessage());
            }

            return redirect()->back()
                           ->with('error', $result->getMessage())
                           ->withInput();

        } catch (\Exception $e) {
            Log::error("Erro na atualização de TestResourceController", [
                'controller' => static::class,
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno do servidor',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Erro ao atualizar TestResourceController')
                           ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * PADRÃO NÍVEL 2: Controller com confirmação e tratamento seguro
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        try {
            // Buscar dados antes da exclusão para log
            $entityResult = $this->service->find($id);
            $entityData = $entityResult->getData();

            // Usar service para lógica de exclusão
            $result = $this->service->delete($id);

            // Log da operação
            Log::warning("TestResourceController excluído", [
                'controller' => static::class,
                'id' => $id,
                'data' => $entityData,
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            // Resposta híbrida
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result->isSuccess(),
                    'message' => $result->getMessage()
                ]);
            }

            if ($result->isSuccess()) {
                return redirect()->route('{{ pluralClass }}.index')
                               ->with('success', $result->getMessage());
            }

            return redirect()->back()
                           ->with('error', $result->getMessage());

        } catch (\Exception $e) {
            Log::error("Erro na exclusão de TestResourceController", [
                'controller' => static::class,
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno do servidor',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Erro ao excluir TestResourceController');
        }
    }
}
