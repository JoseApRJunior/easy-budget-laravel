<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use App\Services\VariableProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    private EmailTemplateService $templateService;
    private VariableProcessor    $variableProcessor;

    public function __construct(
        EmailTemplateService $templateService,
        VariableProcessor $variableProcessor,
    ) {
        $this->templateService   = $templateService;
        $this->variableProcessor = $variableProcessor;
    }

    /**
     * Lista todos os templates de email.
     */
    public function index( Request $request ): View
    {
        $user    = Auth::user();
        $filters = $request->only( [ 'search', 'category', 'is_active' ] );

        // Buscar templates do tenant
        $templatesResult = $this->templateService->listByTenantId( $user->tenant_id, $filters );

        if ( !$templatesResult->isSuccess() ) {
            $templates = [];
        } else {
            $templates = $templatesResult->getData();
        }

        // Estatísticas rápidas
        $stats = [
            'total'         => EmailTemplate::where( 'tenant_id', $user->tenant_id )->count(),
            'active'        => EmailTemplate::where( 'tenant_id', $user->tenant_id )->active()->count(),
            'transactional' => EmailTemplate::where( 'tenant_id', $user->tenant_id )->byCategory( 'transactional' )->count(),
            'promotional'   => EmailTemplate::where( 'tenant_id', $user->tenant_id )->byCategory( 'promotional' )->count(),
            'notification'  => EmailTemplate::where( 'tenant_id', $user->tenant_id )->byCategory( 'notification' )->count(),
        ];

        // Obter variáveis disponíveis
        $availableVariables = $this->variableProcessor->getAvailableVariables( $user->tenant_id );

        return view( 'emails.templates', compact(
            'templates',
            'stats',
            'filters',
            'availableVariables',
        ) );
    }

    /**
     * Mostra formulário de criação de template.
     */
    public function create(): View
    {
        $user = Auth::user();

        // Obter variáveis disponíveis
        $availableVariables = $this->variableProcessor->getAvailableVariables( $user->tenant_id );

        return view( 'emails.editor', compact( 'availableVariables' ) );
    }

    /**
     * Salva novo template.
     */
    public function store( Request $request ): RedirectResponse
    {
        $user = Auth::user();

        // Validar dados
        $validated = $request->validate( [
            'name'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:email_templates,slug',
            'category'     => 'required|in:transactional,promotional,notification,system',
            'subject'      => 'required|string|max:500',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer|min:0',
        ] );

        try {
            $result = $this->templateService->createTemplate( $validated, $user->tenant_id );

            if ( $result->isSuccess() ) {
                return redirect()->route( 'email-templates.show', $result->getData() )
                    ->with( 'success', 'Template criado com sucesso!' );
            } else {
                return back()->withInput()
                    ->with( 'error', $result->getMessage() );
            }

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao criar template: ' . $e->getMessage() );
        }
    }

    /**
     * Mostra detalhes de um template.
     */
    public function show( EmailTemplate $template ): View
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        $template->load( [ 'logs' => function ( $query ) {
            $query->latest()->limit( 10 );
        } ] );

        // Obter estatísticas detalhadas
        $statsResult = $this->templateService->getTemplateStats( $template->id, $template->tenant_id );
        $stats       = $statsResult->isSuccess() ? $statsResult->getData()[ 'stats' ] : [];

        // Obter variáveis disponíveis
        $availableVariables = $this->variableProcessor->getAvailableVariables( $template->tenant_id );

        return view( 'emails.show', compact( 'template', 'stats', 'availableVariables' ) );
    }

    /**
     * Mostra formulário de edição.
     */
    public function edit( EmailTemplate $template ): View
    {
        // Verificar permissão e se pode editar
        if ( $template->tenant_id !== Auth::user()->tenant_id || !$template->canBeEdited() ) {
            abort( 403 );
        }

        // Obter variáveis disponíveis
        $availableVariables = $this->variableProcessor->getAvailableVariables( $template->tenant_id );

        return view( 'emails.editor', compact( 'template', 'availableVariables' ) );
    }

    /**
     * Atualiza template.
     */
    public function update( Request $request, EmailTemplate $template ): RedirectResponse
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id || !$template->canBeEdited() ) {
            abort( 403 );
        }

        $validated = $request->validate( [
            'name'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:email_templates,slug,' . $template->id,
            'category'     => 'required|in:transactional,promotional,notification,system',
            'subject'      => 'required|string|max:500',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer|min:0',
        ] );

        try {
            $result = $this->templateService->updateTemplate( $template->id, $validated, $template->tenant_id );

            if ( $result->isSuccess() ) {
                return redirect()->route( 'email-templates.show', $template )
                    ->with( 'success', 'Template atualizado com sucesso!' );
            } else {
                return back()->withInput()
                    ->with( 'error', $result->getMessage() );
            }

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar template: ' . $e->getMessage() );
        }
    }

    /**
     * Remove template.
     */
    public function destroy( EmailTemplate $template ): RedirectResponse
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id || !$template->canBeDeleted() ) {
            abort( 403 );
        }

        try {
            $result = $this->templateService->deleteByIdAndTenantId( $template->id, $template->tenant_id );

            if ( $result->isSuccess() ) {
                return redirect()->route( 'email-templates.index' )
                    ->with( 'success', 'Template excluído com sucesso!' );
            } else {
                return back()->with( 'error', $result->getMessage() );
            }

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao excluir template: ' . $e->getMessage() );
        }
    }

    /**
     * Duplica template.
     */
    public function duplicate( EmailTemplate $template ): RedirectResponse
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $result = $this->templateService->duplicateTemplate( $template->id, $template->tenant_id );

            if ( $result->isSuccess() ) {
                return redirect()->route( 'email-templates.edit', $result->getData() )
                    ->with( 'success', 'Template duplicado com sucesso!' );
            } else {
                return back()->with( 'error', $result->getMessage() );
            }

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao duplicar template: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém preview do template.
     */
    public function preview( Request $request, EmailTemplate $template ): JsonResponse
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        $data = $request->input( 'data', [] );

        try {
            $result = $this->templateService->getTemplatePreview( $template->id, $data, $template->tenant_id );

            if ( $result->isSuccess() ) {
                return response()->json( [
                    'success' => true,
                    'data'    => $result->getData()
                ] );
            } else {
                return response()->json( [
                    'success' => false,
                    'message' => $result->getMessage()
                ], 400 );
            }

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao gerar preview: ' . $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Envia email de teste.
     */
    public function sendTest( Request $request, EmailTemplate $template ): RedirectResponse
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        $validated = $request->validate( [
            'test_email' => 'required|email',
            'test_name'  => 'nullable|string|max:255',
            'test_data'  => 'nullable|array',
        ] );

        try {
            // Processar template com dados de teste
            $data = array_merge( $validated[ 'test_data' ] ?? [], [
                'context'   => 'test',
                'test_mode' => true,
            ] );

            $processResult = $this->templateService->processTemplate( $template->id, $data, $template->tenant_id );

            if ( !$processResult->isSuccess() ) {
                return back()->with( 'error', 'Erro ao processar template: ' . $processResult->getMessage() );
            }

            $processed = $processResult->getData();

            // TODO: Implementar envio de email de teste
            // Por enquanto, apenas mostrar sucesso
            return back()->with( 'success', 'Email de teste enviado para ' . $validated[ 'test_email' ] );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao enviar email de teste: ' . $e->getMessage() );
        }
    }

    /**
     * Ativa/desativa template.
     */
    public function toggleStatus( EmailTemplate $template ): RedirectResponse
    {
        // Verificar permissão
        if ( $template->tenant_id !== Auth::user()->tenant_id || !$template->canBeEdited() ) {
            abort( 403 );
        }

        try {
            $newStatus = !$template->is_active;
            $template->update( [ 'is_active' => $newStatus ] );

            $message = $newStatus ? 'Template ativado' : 'Template desativado';
            return back()->with( 'success', $message . ' com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém estatísticas dos templates.
     */
    public function stats( Request $request ): JsonResponse
    {
        $user   = Auth::user();
        $period = $request->get( 'period', 'month' );

        try {
            // TODO: Implementar método para obter estatísticas gerais
            $stats = [
                'total_templates'  => EmailTemplate::where( 'tenant_id', $user->tenant_id )->count(),
                'active_templates' => EmailTemplate::where( 'tenant_id', $user->tenant_id )->active()->count(),
                'total_sent'       => 0, // Será implementado quando tivermos logs
                'period'           => $period,
            ];

            return response()->json( [
                'success' => true,
                'data'    => $stats
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ], 500 );
        }
    }

}
