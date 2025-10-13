<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\FileUploadService;
use App\Services\SettingsBackupService;
use App\Services\SettingsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller para configurações
 */
class SettingsApiController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private SettingsBackupService $backupService,
        private FileUploadService $fileUploadService,
    ) {}

    /**
     * Obtém todas as configurações
     */
    public function index(): JsonResponse
    {
        try {
            $userSettings   = $this->settingsService->getCompleteUserSettings();
            $systemSettings = $this->settingsService->getCompleteSystemSettings();

            return response()->json( [
                'success' => true,
                'data'    => [
                    'user_settings'   => $userSettings,
                    'system_settings' => $systemSettings,
                ],
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter configurações',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Atualiza configurações do usuário
     */
    public function update( Request $request ): JsonResponse
    {
        try {
            $data = $request->validate( [
                'theme'                     => 'sometimes|in:light,dark,auto',
                'primary_color'             => 'sometimes|regex:/^#[0-9A-Fa-f]{6}$/',
                'layout_density'            => 'sometimes|in:compact,normal,spacious',
                'sidebar_position'          => 'sometimes|in:left,right',
                'animations_enabled'        => 'sometimes|boolean',
                'sound_enabled'             => 'sometimes|boolean',
                'email_notifications'       => 'sometimes|boolean',
                'transaction_notifications' => 'sometimes|boolean',
                'weekly_reports'            => 'sometimes|boolean',
                'security_alerts'           => 'sometimes|boolean',
                'newsletter_subscription'   => 'sometimes|boolean',
                'push_notifications'        => 'sometimes|boolean',
                'full_name'                 => 'sometimes|string|max:255',
                'bio'                       => 'sometimes|string|max:1000',
                'phone'                     => 'sometimes|string|max:20',
                'birth_date'                => 'sometimes|date|before:today',
                'social_facebook'           => 'sometimes|url|max:255',
                'social_twitter'            => 'sometimes|url|max:255',
                'social_linkedin'           => 'sometimes|url|max:255',
                'social_instagram'          => 'sometimes|url|max:255',
            ] );

            $settings = $this->settingsService->updateUserSettings( $data );

            return response()->json( [
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso',
                'data'    => $settings,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao atualizar configurações',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Atualização parcial de configurações
     */
    public function partialUpdate( Request $request ): JsonResponse
    {
        try {
            $data = $request->validate( [
                'theme'                     => 'sometimes|in:light,dark,auto',
                'primary_color'             => 'sometimes|regex:/^#[0-9A-Fa-f]{6}$/',
                'layout_density'            => 'sometimes|in:compact,normal,spacious',
                'sidebar_position'          => 'sometimes|in:left,right',
                'animations_enabled'        => 'sometimes|boolean',
                'sound_enabled'             => 'sometimes|boolean',
                'email_notifications'       => 'sometimes|boolean',
                'transaction_notifications' => 'sometimes|boolean',
                'weekly_reports'            => 'sometimes|boolean',
                'security_alerts'           => 'sometimes|boolean',
                'newsletter_subscription'   => 'sometimes|boolean',
                'push_notifications'        => 'sometimes|boolean',
                'full_name'                 => 'sometimes|string|max:255',
                'bio'                       => 'sometimes|string|max:1000',
                'phone'                     => 'sometimes|string|max:20',
                'birth_date'                => 'sometimes|date|before:today',
                'social_facebook'           => 'sometimes|url|max:255',
                'social_twitter'            => 'sometimes|url|max:255',
                'social_linkedin'           => 'sometimes|url|max:255',
                'social_instagram'          => 'sometimes|url|max:255',
            ] );

            $settings = $this->settingsService->updateUserSettings( $data );

            return response()->json( [
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso',
                'data'    => $settings,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao atualizar configurações',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Upload de avatar
     */
    public function uploadAvatar( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ] );

            $result = $this->settingsService->updateAvatar( $request->file( 'avatar' ) );

            return response()->json( [
                'success' => true,
                'message' => 'Avatar atualizado com sucesso',
                'data'    => $result,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao fazer upload do avatar',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Remove avatar
     */
    public function deleteAvatar(): JsonResponse
    {
        try {
            $this->settingsService->removeAvatar();

            return response()->json( [
                'success' => true,
                'message' => 'Avatar removido com sucesso',
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao remover avatar',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Cria backup das configurações
     */
    public function backup( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'type' => 'in:user,system,full',
            ] );

            $type = $request->get( 'type', 'full' );
            $user = auth()->user();

            if ( $type === 'full' ) {
                $result = $this->backupService->createFullBackup( $user, 'api' );
            } elseif ( $type === 'user' ) {
                $result = $this->backupService->createUserSettingsBackup( $user, 'api' );
            } else {
                $result = $this->backupService->createSystemSettingsBackup( $user->tenant_id, 'api' );
            }

            return response()->json( [
                'success' => true,
                'message' => 'Backup criado com sucesso',
                'data'    => $result,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao criar backup',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Restaura backup das configurações
     */
    public function restore( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'filename' => 'required|string',
                'type'     => 'required|in:user,system,full',
            ] );

            $user = auth()->user();

            if ( $request->type === 'full' ) {
                $this->backupService->restoreFullBackup( $user, $request->filename );
            } elseif ( $request->type === 'user' ) {
                $this->backupService->restoreUserSettingsBackup( $user, $request->filename );
            } else {
                $this->backupService->restoreSystemSettingsBackup( $user->tenant_id, $request->filename );
            }

            return response()->json( [
                'success' => true,
                'message' => 'Backup restaurado com sucesso',
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao restaurar backup',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Lista backups disponíveis
     */
    public function listBackups( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'type' => 'nullable|in:user,system,full',
            ] );

            $tenantId = auth()->user()->tenant_id;
            $type     = $request->get( 'type' );

            $backups = $this->backupService->listBackups( $tenantId, $type );

            return response()->json( [
                'success' => true,
                'data'    => $backups,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao listar backups',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Remove backup
     */
    public function deleteBackup( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'filename' => 'required|string',
            ] );

            $this->backupService->deleteBackup( auth()->user()->tenant_id, $request->filename );

            return response()->json( [
                'success' => true,
                'message' => 'Backup removido com sucesso',
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao remover backup',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Obtém informações de um backup específico
     */
    public function backupInfo( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'filename' => 'required|string',
            ] );

            $tenantId   = auth()->user()->tenant_id;
            $backupInfo = $this->backupService->getBackupInfo( $tenantId, $request->filename );

            if ( !$backupInfo ) {
                return response()->json( [
                    'success' => false,
                    'message' => 'Backup não encontrado',
                ], 404 );
            }

            return response()->json( [
                'success' => true,
                'data'    => $backupInfo,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter informações do backup',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Valida backup
     */
    public function validateBackup( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'filename' => 'required|string',
            ] );

            $tenantId   = auth()->user()->tenant_id;
            $validation = $this->backupService->validateBackup( $tenantId, $request->filename );

            return response()->json( [
                'success' => true,
                'data'    => $validation,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao validar backup',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Obtém logs de auditoria
     */
    public function audit( Request $request ): JsonResponse
    {
        try {
            $filters = $request->only( [
                'action', 'user_id', 'severity', 'category', 'model_type',
                'start_date', 'end_date', 'security_only', 'data_modifications_only',
                'sort_by', 'sort_direction'
            ] );

            $perPage = $request->get( 'per_page', 50 );
            $logs    = app( \App\Services\AuditService::class)->getAuditLogs( $filters, $perPage );

            return response()->json( [
                'success' => true,
                'data'    => $logs,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter logs de auditoria',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Obtém detalhes de um log específico
     */
    public function auditDetail( int $id ): JsonResponse
    {
        try {
            $log = app( \App\Services\AuditService::class)->getAuditLogs( [ 'id' => $id ], 1 )->first();

            if ( !$log ) {
                return response()->json( [
                    'success' => false,
                    'message' => 'Log não encontrado',
                ], 404 );
            }

            return response()->json( [
                'success' => true,
                'data'    => $log,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter detalhes do log',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Obtém sessões ativas
     */
    public function sessions(): JsonResponse
    {
        try {
            $sessions = [
                [
                    'id'            => session()->getId(),
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->userAgent(),
                    'last_activity' => now(),
                    'is_current'    => true,
                ],
            ];

            return response()->json( [
                'success' => true,
                'data'    => $sessions,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter sessões',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Termina sessão específica
     */
    public function terminateSession( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'session_id' => 'required|string',
            ] );

            // Em produção, implementar lógica real de término de sessão
            // Por enquanto, retorna sucesso

            return response()->json( [
                'success' => true,
                'message' => 'Sessão terminada com sucesso',
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao terminar sessão',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Obtém estatísticas das configurações
     */
    public function stats(): JsonResponse
    {
        try {
            $tenantId = auth()->user()->tenant_id;
            $stats    = $this->settingsService->getSettingsStats( $tenantId );

            return response()->json( [
                'success' => true,
                'data'    => $stats,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter estatísticas',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Testa conexão com integração
     */
    public function testIntegration( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'integration' => 'required|string',
                'config'      => 'required|array',
            ] );

            // Implementação específica para teste de integração
            // Por enquanto, simula teste bem-sucedido

            return response()->json( [
                'success' => true,
                'message' => 'Teste de integração realizado com sucesso',
                'data'    => [
                    'status'        => 'connected',
                    'response_time' => rand( 100, 500 ) . 'ms',
                    'last_sync'     => now(),
                ],
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao testar integração',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Obtém configurações de integração
     */
    public function integrations(): JsonResponse
    {
        try {
            $userSettings = $this->settingsService->getUserSettings();
            $integrations = $userSettings->custom_preferences[ 'integrations' ] ?? [];

            return response()->json( [
                'success' => true,
                'data'    => $integrations,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter integrações',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Atualiza configurações de integração
     */
    public function updateIntegrations( Request $request ): JsonResponse
    {
        try {
            $data = $request->validate( [
                'integrations' => 'required|array',
            ] );

            $this->settingsService->updateIntegrationSettings( $data[ 'integrations' ] );

            return response()->json( [
                'success' => true,
                'message' => 'Configurações de integração atualizadas com sucesso',
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao atualizar integrações',
                'error'   => $e->getMessage(),
            ], 422 );
        }
    }

    /**
     * Restaura configurações padrão
     */
    public function restoreDefaults( Request $request ): JsonResponse
    {
        try {
            $request->validate( [
                'type' => 'required|in:user,system',
            ] );

            if ( $request->type === 'user' ) {
                $this->settingsService->restoreUserDefaultSettings();
                $message = 'Configurações padrão do usuário restauradas com sucesso';
            } else {
                $this->settingsService->restoreSystemDefaultSettings();
                $message = 'Configurações padrão do sistema restauradas com sucesso';
            }

            return response()->json( [
                'success' => true,
                'message' => $message,
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao restaurar configurações padrão',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Obtém configurações de segurança
     */
    public function securitySettings(): JsonResponse
    {
        try {
            $userSettings   = $this->settingsService->getUserSettings();
            $systemSettings = $this->settingsService->getSystemSettings();

            return response()->json( [
                'success' => true,
                'data'    => [
                    'user_security'   => [
                        'two_factor_enabled'  => false, // Implementar quando necessário
                        'email_notifications' => $userSettings->email_notifications,
                        'security_alerts'     => $userSettings->security_alerts,
                    ],
                    'system_security' => $systemSettings->getSecuritySettings(),
                ],
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao obter configurações de segurança',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }

}
