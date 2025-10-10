<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailTrackingService
{
    private VariableProcessor $variableProcessor;

    public function __construct( VariableProcessor $variableProcessor )
    {
        $this->variableProcessor = $variableProcessor;
    }

    /**
     * Gera pixel de rastreamento para abertura de email.
     */
    public function generateTrackingPixel( string $emailLogId ): string
    {
        $trackingUrl = route( 'api.emails.track', [ 'id' => $emailLogId, 'type' => 'open' ] );
        return '<img src="' . $trackingUrl . '" width="1" height="1" style="display:none;" alt="" />';
    }

    /**
     * Gera link de rastreamento para cliques.
     */
    public function generateTrackingLink( string $emailLogId, string $originalUrl, string $label = '' ): string
    {
        $trackingUrl = route( 'api.emails.track', [
            'id'   => $emailLogId,
            'type' => 'click',
            'url'  => urlencode( $originalUrl )
        ] );

        return '<a href="' . $trackingUrl . '" ' . ( $label ? 'title="' . $label . '"' : '' ) . '>' . $label . '</a>';
    }

    /**
     * Rastreia evento de email.
     */
    public function trackEmailEvent( string $emailLogId, string $type, array $metadata = [] ): void
    {
        try {
            $log = EmailLog::find( $emailLogId );

            if ( !$log ) {
                Log::warning( 'Email log não encontrado para rastreamento', [
                    'email_log_id' => $emailLogId,
                    'type'         => $type
                ] );
                return;
            }

            switch ( $type ) {
                case 'open':
                    if ( !$log->opened_at ) {
                        $log->update( [
                            'opened_at'  => now(),
                            'status'     => 'opened',
                            'metadata'   => array_merge( $log->metadata ?? [], $metadata ),
                            'ip_address' => $metadata[ 'ip_address' ] ?? request()->ip(),
                            'user_agent' => $metadata[ 'user_agent' ] ?? request()->userAgent(),
                        ] );

                        Log::info( 'Email aberto rastreado', [
                            'email_log_id' => $emailLogId,
                            'recipient'    => $log->recipient_email,
                            'ip_address'   => $log->ip_address
                        ] );
                    }
                    break;

                case 'click':
                    $log->update( [
                        'clicked_at' => now(),
                        'status'     => 'clicked',
                        'metadata'   => array_merge( $log->metadata ?? [], $metadata ),
                        'ip_address' => $metadata[ 'ip_address' ] ?? request()->ip(),
                        'user_agent' => $metadata[ 'user_agent' ] ?? request()->userAgent(),
                    ] );

                    Log::info( 'Clique em email rastreado', [
                        'email_log_id' => $emailLogId,
                        'recipient'    => $log->recipient_email,
                        'url'          => $metadata[ 'original_url' ] ?? '',
                        'ip_address'   => $log->ip_address
                    ] );
                    break;

                case 'bounce':
                    $log->update( [
                        'bounced_at'    => now(),
                        'status'        => 'bounced',
                        'error_message' => $metadata[ 'reason' ] ?? 'Unknown bounce',
                        'metadata'      => array_merge( $log->metadata ?? [], $metadata ),
                    ] );

                    Log::warning( 'Email com bounce rastreado', [
                        'email_log_id' => $emailLogId,
                        'recipient'    => $log->recipient_email,
                        'reason'       => $metadata[ 'reason' ] ?? 'Unknown'
                    ] );
                    break;

                case 'delivered':
                    if ( $log->status === 'sent' ) {
                        $log->update( [
                            'status'   => 'delivered',
                            'metadata' => array_merge( $log->metadata ?? [], $metadata ),
                        ] );

                        Log::info( 'Email entregue rastreado', [
                            'email_log_id' => $emailLogId,
                            'recipient'    => $log->recipient_email
                        ] );
                    }
                    break;
            }

            // Disparar evento para analytics se existir
            if ( class_exists( '\App\Events\EmailEventTracked' ) ) {
                event( new \App\Events\EmailEventTracked( $log, $type, $metadata ) );
            }

        } catch ( Exception $e ) {
            Log::error( 'Erro ao rastrear evento de email', [
                'email_log_id' => $emailLogId,
                'type'         => $type,
                'error'        => $e->getMessage()
            ] );
        }
    }

    /**
     * Cria log de email enviado.
     */
    public function createEmailLog(
        EmailTemplate $template,
        string $recipientEmail,
        string $recipientName,
        string $subject,
        array $data = [],
    ): ServiceResult {
        try {
            $trackingId = Str::uuid()->toString();

            $log = EmailLog::create( [
                'tenant_id'         => $template->tenant_id,
                'email_template_id' => $template->id,
                'recipient_email'   => $recipientEmail,
                'recipient_name'    => $recipientName,
                'subject'           => $subject,
                'sender_email'      => $data[ 'sender_email' ] ?? config( 'mail.from.address' ),
                'sender_name'       => $data[ 'sender_name' ] ?? config( 'mail.from.name' ),
                'status'            => 'pending',
                'tracking_id'       => $trackingId,
                'metadata'          => $data[ 'metadata' ] ?? [],
            ] );

            return ServiceResult::success( $log, 'Log de email criado com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar log de email', [
                'template_id'     => $template->id,
                'recipient_email' => $recipientEmail,
                'error'           => $e->getMessage()
            ] );

            return ServiceResult::error( 'Erro ao criar log de email: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza status do log de email.
     */
    public function updateEmailLogStatus( string $emailLogId, string $status, array $metadata = [] ): ServiceResult
    {
        try {
            $log = EmailLog::find( $emailLogId );

            if ( !$log ) {
                return ServiceResult::notFound( 'Log de email não encontrado.' );
            }

            $updateData = [ 'status' => $status ];

            switch ( $status ) {
                case 'sent':
                    $updateData[ 'sent_at' ] = now();
                    break;
                case 'delivered':
                    $updateData[ 'sent_at' ] = $log->sent_at ?? now();
                    break;
                case 'opened':
                    $updateData[ 'opened_at' ] = now();
                    break;
                case 'clicked':
                    $updateData[ 'clicked_at' ] = now();
                    break;
                case 'bounced':
                case 'failed':
                    $updateData[ 'bounced_at' ] = now();
                    $updateData[ 'error_message' ] = $metadata[ 'error_message' ] ?? 'Unknown error';
                    break;
            }

            if ( !empty( $metadata ) ) {
                $updateData[ 'metadata' ] = array_merge( $log->metadata ?? [], $metadata );
            }

            $log->update( $updateData );

            return ServiceResult::success( $log, 'Status do log atualizado com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao atualizar status do log de email', [
                'email_log_id' => $emailLogId,
                'status'       => $status,
                'error'        => $e->getMessage()
            ] );

            return ServiceResult::error( 'Erro ao atualizar status do log: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém estatísticas de rastreamento para um template.
     */
    public function getTemplateStats( int $templateId, int $tenantId ): array
    {
        try {
            $logs = EmailLog::where( 'email_template_id', $templateId )
                ->where( 'tenant_id', $tenantId )
                ->get();

            $totalSent      = $logs->count();
            $totalDelivered = $logs->where( 'status', 'delivered' )->count();
            $totalOpened    = $logs->whereNotNull( 'opened_at' )->count();
            $totalClicked   = $logs->whereNotNull( 'clicked_at' )->count();
            $totalBounced   = $logs->where( 'status', 'bounced' )->count();
            $totalFailed    = $logs->where( 'status', 'failed' )->count();

            return [
                'total_sent'      => $totalSent,
                'total_delivered' => $totalDelivered,
                'total_opened'    => $totalOpened,
                'total_clicked'   => $totalClicked,
                'total_bounced'   => $totalBounced,
                'total_failed'    => $totalFailed,
                'delivery_rate'   => $totalSent > 0 ? round( ( $totalDelivered / $totalSent ) * 100, 2 ) : 0,
                'open_rate'       => $totalDelivered > 0 ? round( ( $totalOpened / $totalDelivered ) * 100, 2 ) : 0,
                'click_rate'      => $totalOpened > 0 ? round( ( $totalClicked / $totalOpened ) * 100, 2 ) : 0,
                'bounce_rate'     => $totalSent > 0 ? round( ( $totalBounced / $totalSent ) * 100, 2 ) : 0,
            ];

        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter estatísticas de rastreamento', [
                'template_id' => $templateId,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage()
            ] );

            return [];
        }
    }

    /**
     * Obtém estatísticas gerais de email para um tenant.
     */
    public function getTenantEmailStats( int $tenantId, ?string $period = null ): array
    {
        try {
            $query = EmailLog::where( 'tenant_id', $tenantId );

            if ( $period ) {
                $query = $this->applyPeriodFilter( $query, $period );
            }

            $logs = $query->get();

            $totalSent      = $logs->count();
            $totalDelivered = $logs->where( 'status', 'delivered' )->count();
            $totalOpened    = $logs->whereNotNull( 'opened_at' )->count();
            $totalClicked   = $logs->whereNotNull( 'clicked_at' )->count();
            $totalBounced   = $logs->where( 'status', 'bounced' )->count();
            $totalFailed    = $logs->where( 'status', 'failed' )->count();

            // Estatísticas por template
            $templateStats  = [];
            $logsByTemplate = $logs->groupBy( 'email_template_id' );

            foreach ( $logsByTemplate as $templateId => $templateLogs ) {
                $template = EmailTemplate::find( $templateId );
                if ( $template ) {
                    $templateStats[] = [
                        'template_id'   => $templateId,
                        'template_name' => $template->name,
                        'sent'          => $templateLogs->count(),
                        'opened'        => $templateLogs->whereNotNull( 'opened_at' )->count(),
                        'clicked'       => $templateLogs->whereNotNull( 'clicked_at' )->count(),
                    ];
                }
            }

            return [
                'overview'    => [
                    'total_sent'      => $totalSent,
                    'total_delivered' => $totalDelivered,
                    'total_opened'    => $totalOpened,
                    'total_clicked'   => $totalClicked,
                    'total_bounced'   => $totalBounced,
                    'total_failed'    => $totalFailed,
                    'delivery_rate'   => $totalSent > 0 ? round( ( $totalDelivered / $totalSent ) * 100, 2 ) : 0,
                    'open_rate'       => $totalDelivered > 0 ? round( ( $totalOpened / $totalDelivered ) * 100, 2 ) : 0,
                    'click_rate'      => $totalOpened > 0 ? round( ( $totalClicked / $totalOpened ) * 100, 2 ) : 0,
                    'bounce_rate'     => $totalSent > 0 ? round( ( $totalBounced / $totalSent ) * 100, 2 ) : 0,
                ],
                'by_template' => $templateStats,
                'period'      => $period ?? 'all',
            ];

        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter estatísticas gerais de email', [
                'tenant_id' => $tenantId,
                'period'    => $period,
                'error'     => $e->getMessage()
            ] );

            return [];
        }
    }

    /**
     * Aplica filtro de período na query.
     */
    private function applyPeriodFilter( $query, string $period )
    {
        switch ( $period ) {
            case 'today':
                return $query->whereDate( 'created_at', today() );
            case 'week':
                return $query->whereBetween( 'created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ] );
            case 'month':
                return $query->whereMonth( 'created_at', now()->month );
            case 'year':
                return $query->whereYear( 'created_at', now()->year );
            case 'last_7_days':
                return $query->where( 'created_at', '>=', now()->subDays( 7 ) );
            case 'last_30_days':
                return $query->where( 'created_at', '>=', now()->subDays( 30 ) );
            case 'last_90_days':
                return $query->where( 'created_at', '>=', now()->subDays( 90 ) );
            default:
                return $query;
        }
    }

    /**
     * Processa conteúdo HTML adicionando rastreamento.
     */
    public function processHtmlContent( string $htmlContent, string $emailLogId ): string
    {
        // Adicionar pixel de rastreamento no final do HTML
        $trackingPixel    = $this->generateTrackingPixel( $emailLogId );
        $processedContent = $htmlContent . $trackingPixel;

        // Substituir links por links de rastreamento
        $processedContent = preg_replace_callback(
            '/<a([^>]+)href=["\']([^"\']+)["\']([^>]*)>([^<]*)<\/a>/i',
            function ( $matches ) use ( $emailLogId ) {
                $originalUrl = $matches[ 2 ];
                $linkContent = $matches[ 4 ];

                // Não rastrear links para unsubscribe ou imagens
                if (
                    stripos( $originalUrl, 'unsubscribe' ) !== false ||
                    stripos( $originalUrl, '.png' ) !== false ||
                    stripos( $originalUrl, '.jpg' ) !== false ||
                    stripos( $originalUrl, '.gif' ) !== false
                ) {
                    return $matches[ 0 ];
                }

                $trackingLink = $this->generateTrackingLink( $emailLogId, $originalUrl, $linkContent );
                return $trackingLink;
            },
            $processedContent,
        );

        return $processedContent;
    }

    /**
     * Obtém dados de rastreamento para um log específico.
     */
    public function getTrackingData( string $emailLogId ): ?array
    {
        try {
            $log = EmailLog::with( 'emailTemplate' )->find( $emailLogId );

            if ( !$log ) {
                return null;
            }

            return [
                'id'            => $log->id,
                'tracking_id'   => $log->tracking_id,
                'template_name' => $log->emailTemplate->name,
                'recipient'     => [
                    'email' => $log->recipient_email,
                    'name'  => $log->recipient_name,
                ],
                'sender'        => [
                    'email' => $log->sender_email,
                    'name'  => $log->sender_name,
                ],
                'subject'       => $log->subject,
                'status'        => $log->status,
                'sent_at'       => $log->sent_at?->toISOString(),
                'opened_at'     => $log->opened_at?->toISOString(),
                'clicked_at'    => $log->clicked_at?->toISOString(),
                'bounced_at'    => $log->bounced_at?->toISOString(),
                'error_message' => $log->error_message,
                'ip_address'    => $log->ip_address,
                'user_agent'    => $log->user_agent,
                'metadata'      => $log->metadata,
                'is_opened'     => !is_null( $log->opened_at ),
                'is_clicked'    => !is_null( $log->clicked_at ),
                'is_failed'     => in_array( $log->status, [ 'failed', 'bounced' ] ),
            ];

        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter dados de rastreamento', [
                'email_log_id' => $emailLogId,
                'error'        => $e->getMessage()
            ] );

            return null;
        }
    }

}
