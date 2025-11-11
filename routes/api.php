<?php

use App\Http\Controllers\Api\BudgetApiController;
use App\Http\Controllers\Api\ChartDataController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\EmailTemplateApiController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\BudgetController;
use App\Services\Application\EmailTemplateService;
use App\Services\Infrastructure\VariableProcessor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware( 'auth' )->group( function () {
    // Budget filter API (legacy)
    Route::get( '/budgets/filter', [ BudgetController::class, 'filter' ] )->name( 'api.budgets.filter' );

    // Dashboard APIs - Fase 2
    Route::prefix( 'dashboard' )->group( function () {
        // Métricas principais
        Route::get( '/metrics', [ MetricsController::class, 'index' ] )->name( 'api.dashboard.metrics' );
        Route::get( '/metrics/charts', [ MetricsController::class, 'charts' ] )->name( 'api.dashboard.metrics.charts' );
        Route::get( '/metrics/realtime', [ MetricsController::class, 'realtime' ] )->name( 'api.dashboard.metrics.realtime' );

        // Dados para gráficos específicos
        Route::get( '/charts/receita-despesa', [ ChartDataController::class, 'receitaDespesa' ] )->name( 'api.dashboard.charts.receita-despesa' );
        Route::get( '/charts/categorias', [ ChartDataController::class, 'categorias' ] )->name( 'api.dashboard.charts.categorias' );
        Route::get( '/charts/mensal', [ ChartDataController::class, 'mensal' ] )->name( 'api.dashboard.charts.mensal' );
        Route::get( '/charts/tendencias', [ ChartDataController::class, 'tendencias' ] )->name( 'api.dashboard.charts.tendencias' );
    } );

    // API Routes para configurações
    Route::prefix( 'settings' )->group( function () {
        Route::get( '/', [ SettingsApiController::class, 'index' ] );
        Route::put( '/', [ SettingsApiController::class, 'update' ] );
        Route::patch( '/', [ SettingsApiController::class, 'partialUpdate' ] );

        // Upload de avatar
        Route::post( '/avatar', [ SettingsApiController::class, 'uploadAvatar' ] );
        Route::delete( '/avatar', [ SettingsApiController::class, 'deleteAvatar' ] );

        // Backup de configurações
        Route::post( '/backup', [ SettingsApiController::class, 'backup' ] );
        Route::post( '/restore', [ SettingsApiController::class, 'restore' ] );
        Route::get( '/backups', [ SettingsApiController::class, 'listBackups' ] );
        Route::delete( '/backup', [ SettingsApiController::class, 'deleteBackup' ] );
        Route::get( '/backup/{filename}', [ SettingsApiController::class, 'backupInfo' ] );
        Route::post( '/backup/validate', [ SettingsApiController::class, 'validateBackup' ] );

        // Auditoria
        Route::get( '/audit', [ SettingsApiController::class, 'audit' ] );
        Route::get( '/audit/{id}', [ SettingsApiController::class, 'auditDetail' ] );

        // Sessões ativas
        Route::get( '/sessions', [ SettingsApiController::class, 'sessions' ] );
        Route::delete( '/sessions', [ SettingsApiController::class, 'terminateSession' ] );

        // Estatísticas
        Route::get( '/stats', [ SettingsApiController::class, 'stats' ] );

        // Integrações
        Route::get( '/integrations', [ SettingsApiController::class, 'integrations' ] );
        Route::put( '/integrations', [ SettingsApiController::class, 'updateIntegrations' ] );
        Route::post( '/integrations/test', [ SettingsApiController::class, 'testIntegration' ] );

        // Configurações de segurança
        Route::get( '/security', [ SettingsApiController::class, 'securitySettings' ] );

        // Restaurar padrões
        Route::post( '/restore-defaults', [ SettingsApiController::class, 'restoreDefaults' ] );
    } );

    // Customer Module - APIs RESTful
    Route::prefix( 'customers' )->name( 'api.customers.' )->group( function () {
        // CRUD básico
        Route::get( '/', [ CustomerApiController::class, 'index' ] )->name( 'index' );
        Route::post( '/pessoa-fisica', [ CustomerApiController::class, 'storePessoaFisica' ] )->name( 'store.pessoa-fisica' );
        Route::post( '/pessoa-juridica', [ CustomerApiController::class, 'storePessoaJuridica' ] )->name( 'store.pessoa-juridica' );
        Route::get( '/{customer}', [ CustomerApiController::class, 'show' ] )->name( 'show' );
        Route::put( '/{customer}', [ CustomerApiController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{customer}', [ CustomerApiController::class, 'destroy' ] )->name( 'destroy' );

        // Endereços
        Route::post( '/{customer}/addresses', [ CustomerApiController::class, 'addAddress' ] )->name( 'add-address' );
        Route::put( '/{customer}/addresses/{address}', [ CustomerApiController::class, 'updateAddress' ] )->name( 'update-address' );
        Route::delete( '/{customer}/addresses/{address}', [ CustomerApiController::class, 'removeAddress' ] )->name( 'remove-address' );

        // Contatos
        Route::post( '/{customer}/contacts', [ CustomerApiController::class, 'addContact' ] )->name( 'add-contact' );
        Route::put( '/{customer}/contacts/{contact}', [ CustomerApiController::class, 'updateContact' ] )->name( 'update-contact' );
        Route::delete( '/{customer}/contacts/{contact}', [ CustomerApiController::class, 'removeContact' ] )->name( 'remove-contact' );

        // Interações
        Route::get( '/{customer}/interactions', [ CustomerApiController::class, 'getInteractions' ] )->name( 'get-interactions' );
        Route::post( '/{customer}/interactions', [ CustomerApiController::class, 'addInteraction' ] )->name( 'add-interaction' );
        Route::put( '/{customer}/interactions/{interaction}', [ CustomerApiController::class, 'updateInteraction' ] )->name( 'update-interaction' );

        // Busca e filtros
        Route::get( '/search/autocomplete', [ CustomerApiController::class, 'autocomplete' ] )->name( 'autocomplete' );
        Route::get( '/search/table', [ CustomerApiController::class, 'searchForTable' ] )->name( 'search-table' );
        Route::get( '/filter/by-tags', [ CustomerApiController::class, 'filterByTags' ] )->name( 'filter-by-tags' );
        Route::get( '/nearby/{latitude}/{longitude}', [ CustomerApiController::class, 'findNearby' ] )->name( 'find-nearby' );

        // Estatísticas
        Route::get( '/stats', [ CustomerApiController::class, 'getStats' ] )->name( 'stats' );

        // Importação/Exportação
        Route::post( '/import', [ CustomerApiController::class, 'import' ] )->name( 'import' );
        Route::get( '/export', [ CustomerApiController::class, 'export' ] )->name( 'export' );
    } );

    // Budget Module - APIs RESTful
    Route::prefix( 'budgets' )->name( 'api.budgets.' )->group( function () {
        // CRUD básico
        Route::get( '/', [ BudgetApiController::class, 'index' ] )->name( 'index' );
        Route::post( '/', [ BudgetApiController::class, 'store' ] )->name( 'store' );
        Route::get( '/{budget}', [ BudgetApiController::class, 'show' ] )->name( 'show' );
        Route::put( '/{budget}', [ BudgetApiController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{budget}', [ BudgetApiController::class, 'destroy' ] )->name( 'destroy' );

        // Itens do orçamento
        Route::post( '/{budget}/items', [ BudgetApiController::class, 'addItem' ] )->name( 'add-item' );
        Route::put( '/{budget}/items/{item}', [ BudgetApiController::class, 'updateItem' ] )->name( 'update-item' );
        Route::delete( '/{budget}/items/{item}', [ BudgetApiController::class, 'removeItem' ] )->name( 'remove-item' );

        // Workflow de aprovação
        Route::post( '/{budget}/send', [ BudgetApiController::class, 'sendToCustomer' ] )->name( 'send' );
        Route::post( '/{budget}/approve', [ BudgetApiController::class, 'approve' ] )->name( 'approve' );
        Route::post( '/{budget}/reject', [ BudgetApiController::class, 'reject' ] )->name( 'reject' );

        // Versionamento
        Route::get( '/{budget}/versions', [ BudgetApiController::class, 'getVersions' ] )->name( 'versions' );
        Route::post( '/{budget}/create-version', [ BudgetApiController::class, 'createVersion' ] )->name( 'create-version' );
        Route::post( '/{budget}/restore-version/{version}', [ BudgetApiController::class, 'restoreVersion' ] )->name( 'restore-version' );

        // PDF e documentos
        Route::get( '/{budget}/pdf', [ BudgetApiController::class, 'generatePdf' ] )->name( 'generate-pdf' );
        Route::post( '/{budget}/email', [ BudgetApiController::class, 'emailBudget' ] )->name( 'email' );

        // Templates
        Route::get( '/templates', [ BudgetApiController::class, 'getTemplates' ] )->name( 'templates' );
        Route::post( '/templates', [ BudgetApiController::class, 'createTemplate' ] )->name( 'create-template' );
        Route::put( '/templates/{template}', [ BudgetApiController::class, 'updateTemplate' ] )->name( 'update-template' );

        // Cálculos
        Route::post( '/calculate', [ BudgetApiController::class, 'calculateTotals' ] )->name( 'calculate' );
    } );

    // Email Templates Module - APIs RESTful
    Route::prefix( 'email-templates' )->name( 'api.email-templates.' )->group( function () {
        // CRUD básico
        Route::get( '/', [ EmailTemplateApiController::class, 'index' ] )->name( 'index' );
        Route::post( '/', [ EmailTemplateApiController::class, 'store' ] )->name( 'store' );
        Route::get( '/{template}', [ EmailTemplateApiController::class, 'show' ] )->name( 'show' );
        Route::put( '/{template}', [ EmailTemplateApiController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{template}', [ EmailTemplateApiController::class, 'destroy' ] )->name( 'destroy' );

        // Funcionalidades específicas
        Route::get( '/{template}/preview', [ EmailTemplateApiController::class, 'preview' ] )->name( 'preview' );
        Route::post( '/{template}/test', [ EmailTemplateApiController::class, 'sendTest' ] )->name( 'send-test' );
        Route::post( '/{template}/duplicate', [ EmailTemplateApiController::class, 'duplicate' ] )->name( 'duplicate' );
        Route::get( '/{template}/stats', [ EmailTemplateApiController::class, 'getStats' ] )->name( 'stats' );

        // Variáveis disponíveis
        Route::get( '/variables/available', [ EmailTemplateApiController::class, 'getVariables' ] )->name( 'variables' );

        // Estatísticas gerais
        Route::get( '/analytics', [ EmailTemplateApiController::class, 'getAnalytics' ] )->name( 'analytics' );

        // Templates predefinidos
        Route::get( '/presets/transactional', function () {
            $controller = new EmailTemplateApiController(
                app( EmailTemplateService::class),
                app( VariableProcessor::class),
            );
            return $controller->getPresets( request() );
        } )->name( 'presets.transactional' );

        Route::get( '/presets/promotional', function () {
            $controller = new EmailTemplateApiController(
                app( EmailTemplateService::class),
                app( VariableProcessor::class),
            );
            return $controller->getPresets( request() );
        } )->name( 'presets.promotional' );

        Route::get( '/presets/notifications', function () {
            $controller = new EmailTemplateApiController(
                app( EmailTemplateService::class),
                app( VariableProcessor::class),
            );
            return $controller->getPresets( request() );
        } )->name( 'presets.notifications' );
    } );

} );
