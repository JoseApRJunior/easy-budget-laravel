<?php

use App\Http\Controllers\Api\BudgetController as ApiBudgetController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\ChartDataController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\BudgetController;
use Illuminate\Http\Request;
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
        Route::get( '/', [ App\Http\Controllers\Api\BudgetApiController::class, 'index' ] )->name( 'index' );
        Route::post( '/', [ App\Http\Controllers\Api\BudgetApiController::class, 'store' ] )->name( 'store' );
        Route::get( '/{budget}', [ App\Http\Controllers\Api\BudgetApiController::class, 'show' ] )->name( 'show' );
        Route::put( '/{budget}', [ App\Http\Controllers\Api\BudgetApiController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{budget}', [ App\Http\Controllers\Api\BudgetApiController::class, 'destroy' ] )->name( 'destroy' );

        // Itens do orçamento
        Route::post( '/{budget}/items', [ App\Http\Controllers\Api\BudgetApiController::class, 'addItem' ] )->name( 'add-item' );
        Route::put( '/{budget}/items/{item}', [ App\Http\Controllers\Api\BudgetApiController::class, 'updateItem' ] )->name( 'update-item' );
        Route::delete( '/{budget}/items/{item}', [ App\Http\Controllers\Api\BudgetApiController::class, 'removeItem' ] )->name( 'remove-item' );

        // Workflow de aprovação
        Route::post( '/{budget}/send', [ App\Http\Controllers\Api\BudgetApiController::class, 'sendToCustomer' ] )->name( 'send' );
        Route::post( '/{budget}/approve', [ App\Http\Controllers\Api\BudgetApiController::class, 'approve' ] )->name( 'approve' );
        Route::post( '/{budget}/reject', [ App\Http\Controllers\Api\BudgetApiController::class, 'reject' ] )->name( 'reject' );

        // Versionamento
        Route::get( '/{budget}/versions', [ App\Http\Controllers\Api\BudgetApiController::class, 'getVersions' ] )->name( 'versions' );
        Route::post( '/{budget}/create-version', [ App\Http\Controllers\Api\BudgetApiController::class, 'createVersion' ] )->name( 'create-version' );
        Route::post( '/{budget}/restore-version/{version}', [ App\Http\Controllers\Api\BudgetApiController::class, 'restoreVersion' ] )->name( 'restore-version' );

        // PDF e documentos
        Route::get( '/{budget}/pdf', [ App\Http\Controllers\Api\BudgetApiController::class, 'generatePdf' ] )->name( 'generate-pdf' );
        Route::post( '/{budget}/email', [ App\Http\Controllers\Api\BudgetApiController::class, 'emailBudget' ] )->name( 'email' );

        // Templates
        Route::get( '/templates', [ App\Http\Controllers\Api\BudgetApiController::class, 'getTemplates' ] )->name( 'templates' );
        Route::post( '/templates', [ App\Http\Controllers\Api\BudgetApiController::class, 'createTemplate' ] )->name( 'create-template' );
        Route::put( '/templates/{template}', [ App\Http\Controllers\Api\BudgetApiController::class, 'updateTemplate' ] )->name( 'update-template' );

        // Cálculos
        Route::post( '/calculate', [ App\Http\Controllers\Api\BudgetApiController::class, 'calculateTotals' ] )->name( 'calculate' );
    } );

    // Email Templates Module - APIs RESTful
    Route::prefix( 'email-templates' )->name( 'api.email-templates.' )->group( function () {
        // CRUD básico
        Route::get( '/', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'index' ] )->name( 'index' );
        Route::post( '/', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'store' ] )->name( 'store' );
        Route::get( '/{template}', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'show' ] )->name( 'show' );
        Route::put( '/{template}', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'update' ] )->name( 'update' );
        Route::delete( '/{template}', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'destroy' ] )->name( 'destroy' );

        // Funcionalidades específicas
        Route::get( '/{template}/preview', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'preview' ] )->name( 'preview' );
        Route::post( '/{template}/test', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'sendTest' ] )->name( 'send-test' );
        Route::post( '/{template}/duplicate', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'duplicate' ] )->name( 'duplicate' );
        Route::get( '/{template}/stats', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'getStats' ] )->name( 'stats' );

        // Variáveis disponíveis
        Route::get( '/variables/available', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'getVariables' ] )->name( 'variables' );

        // Estatísticas gerais
        Route::get( '/analytics', [ App\Http\Controllers\Api\EmailTemplateApiController::class, 'getAnalytics' ] )->name( 'analytics' );

        // Templates predefinidos
        Route::get( '/presets/transactional', function () {
            $controller = new App\Http\Controllers\Api\EmailTemplateApiController(
                app( App\Services\EmailTemplateService::class),
                app( App\Services\VariableProcessor::class),
            );
            return $controller->getPresets( request() );
        } )->name( 'presets.transactional' );

        Route::get( '/presets/promotional', function () {
            $controller = new App\Http\Controllers\Api\EmailTemplateApiController(
                app( App\Services\EmailTemplateService::class),
                app( App\Services\VariableProcessor::class),
            );
            return $controller->getPresets( request() );
        } )->name( 'presets.promotional' );

        Route::get( '/presets/notifications', function () {
            $controller = new App\Http\Controllers\Api\EmailTemplateApiController(
                app( App\Services\EmailTemplateService::class),
                app( App\Services\VariableProcessor::class),
            );
            return $controller->getPresets( request() );
        } )->name( 'presets.notifications' );
    } );

} );
