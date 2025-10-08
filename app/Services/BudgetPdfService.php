<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;
use App\Models\SystemSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BudgetPdfService
{
    /**
     * Gera PDF do orçamento.
     */
    public function generatePdf( Budget $budget ): string
    {
        $data = $this->preparePdfData( $budget );

        $html = $this->renderPdfHtml( $data );

        $options = $this->getPdfOptions();

        $filename = $this->generatePdfFilename( $budget );

        $path = $this->savePdf( $html, $options, $filename, $budget );

        return $path;
    }

    /**
     * Prepara dados para geração do PDF.
     */
    private function preparePdfData( Budget $budget ): array
    {
        $budget->load( [ 'customer', 'user', 'items.category', 'budgetStatus' ] );

        $totals = app( BudgetCalculationService::class)->calculateTotals( $budget );

        return [
            'budget'       => $budget,
            'company'      => $this->getCompanyInfo(),
            'logo_path'    => $this->getCompanyLogo(),
            'qr_code'      => $this->generateQrCode( $budget ),
            'watermark'    => $this->getWatermark( $budget ),
            'totals'       => $totals,
            'settings'     => $this->getPdfSettings(),
            'generated_at' => now(),
        ];
    }

    /**
     * Renderiza HTML para o PDF.
     */
    private function renderPdfHtml( array $data ): string
    {
        return View::make( 'budgets.pdf.layout', $data )->render();
    }

    /**
     * Obtém opções de configuração do PDF.
     */
    private function getPdfOptions(): array
    {
        return [
            'orientation'              => 'portrait',
            'page-size'                => 'a4',
            'margin-top'               => 20,
            'margin-right'             => 15,
            'margin-bottom'            => 20,
            'margin-left'              => 15,
            'encoding'                 => 'UTF-8',
            'footer-right'             => 'Página [page] de [topage]',
            'footer-font-size'         => 8,
            'enable-local-file-access' => true,
        ];
    }

    /**
     * Gera nome do arquivo PDF.
     */
    private function generatePdfFilename( Budget $budget ): string
    {
        return sprintf(
            'orcamento_%s_v%s_%s.pdf',
            $budget->budget_number,
            $budget->version,
            now()->format( 'Y_m_d_H_i_s' ),
        );
    }

    /**
     * Salva PDF no storage.
     */
    private function savePdf( string $html, array $options, string $filename, Budget $budget ): string
    {
        $path = "budgets/{$budget->id}/{$filename}";

        $pdf = Pdf::loadHTML( $html )
            ->setOptions( $options )
            ->save( storage_path( "app/public/{$path}" ) );

        // Atualizar hash de verificação no orçamento
        $budget->update( [
            'pdf_verification_hash' => hash( 'sha256', $html ),
        ] );

        return $path;
    }

    /**
     * Obtém informações da empresa.
     */
    private function getCompanyInfo(): array
    {
        $systemSettings = SystemSettings::first();

        return [
            'name'    => $systemSettings->company_name ?? 'Empresa',
            'cnpj'    => $systemSettings->company_cnpj ?? '',
            'address' => $systemSettings->company_address ?? '',
            'phone'   => $systemSettings->company_phone ?? '',
            'email'   => $systemSettings->company_email ?? '',
            'website' => $systemSettings->company_website ?? '',
        ];
    }

    /**
     * Obtém caminho do logo da empresa.
     */
    private function getCompanyLogo(): ?string
    {
        $systemSettings = SystemSettings::first();

        if ( $systemSettings && $systemSettings->company_logo ) {
            return storage_path( 'app/public/' . $systemSettings->company_logo );
        }

        return null;
    }

    /**
     * Gera QR Code para o orçamento.
     */
    private function generateQrCode( Budget $budget ): string
    {
        $url = route( 'budgets.public.show', [
            'budget' => $budget->id,
            'token'  => $budget->public_token ?? 'preview'
        ] );

        return base64_encode( QrCode::format( 'png' )
            ->size( 100 )
            ->generate( $url ) );
    }

    /**
     * Obtém marca d'água para o PDF.
     */
    private function getWatermark( Budget $budget ): ?string
    {
        $statusSlug = $budget->budgetStatus->slug ?? '';

        if ( $statusSlug === 'rascunho' ) {
            return 'RASCUNHO';
        }

        if ( $statusSlug === 'expirado' ) {
            return 'EXPIRADO';
        }

        return null;
    }

    /**
     * Obtém configurações de PDF.
     */
    private function getPdfSettings(): array
    {
        return [
            'show_qr_code'              => true,
            'show_watermark'            => true,
            'show_company_logo'         => true,
            'show_item_images'          => false,
            'group_items_by_category'   => true,
            'show_terms_and_conditions' => true,
            'show_payment_methods'      => true,
        ];
    }

    /**
     * Gera preview do PDF (sem salvar).
     */
    public function generatePreview( Budget $budget ): string
    {
        $data    = $this->preparePdfData( $budget );
        $html    = $this->renderPdfHtml( $data );
        $options = $this->getPdfOptions();

        return Pdf::loadHTML( $html )->setOptions( $options )->output();
    }

    /**
     * Envia PDF por email.
     */
    public function emailPdf( Budget $budget, array $recipients, string $message = '' ): bool
    {
        $pdfPath = $this->generatePdf( $budget );

        // Usar serviço de notificação existente
        $notificationService = app( NotificationService::class);

        foreach ( $recipients as $recipient ) {
            $notificationService->sendBudgetPdfNotification(
                $budget,
                $pdfPath,
                $recipient[ 'email' ],
                $recipient[ 'name' ] ?? '',
                $message,
            );
        }

        return true;
    }

    /**
     * Gera PDF para compartilhamento público.
     */
    public function generatePublicPdf( Budget $budget ): string
    {
        // Criar token público se não existir
        if ( !$budget->public_token ) {
            $budget->update( [
                'public_token'      => str_random( 64 ),
                'public_expires_at' => now()->addDays( 30 ),
            ] );
        }

        return $this->generatePdf( $budget );
    }

    /**
     * Verifica se o PDF precisa ser regenerado.
     */
    public function needsRegeneration( Budget $budget ): bool
    {
        // Se não há hash, precisa gerar
        if ( !$budget->pdf_verification_hash ) {
            return true;
        }

        // Se houve mudanças nos dados principais, regenerar
        $currentHash = hash( 'sha256', json_encode( [
            'budget'       => $budget->only( [ 'customer_id', 'total', 'description' ] ),
            'items_count'  => $budget->items->count(),
            'last_updated' => $budget->updated_at,
        ] ) );

        return $budget->pdf_verification_hash !== $currentHash;
    }

    /**
     * Obtém estatísticas de geração de PDFs.
     */
    public function getGenerationStats( int $tenantId ): array
    {
        $totalBudgets   = Budget::where( 'tenant_id', $tenantId )->count();
        $budgetsWithPdf = Budget::where( 'tenant_id', $tenantId )
            ->whereNotNull( 'pdf_verification_hash' )
            ->count();

        return [
            'total_budgets'       => $totalBudgets,
            'budgets_with_pdf'    => $budgetsWithPdf,
            'pdf_generation_rate' => $totalBudgets > 0
                ? round( ( $budgetsWithPdf / $totalBudgets ) * 100, 2 )
                : 0,
        ];
    }

    /**
     * Limpa PDFs antigos.
     */
    public function cleanupOldPdfs( int $daysOld = 90 ): int
    {
        $cutoffDate = now()->subDays( $daysOld );

        $oldBudgets = Budget::where( 'updated_at', '<', $cutoffDate )
            ->whereNotNull( 'pdf_verification_hash' )
            ->get();

        $cleanedCount = 0;

        foreach ( $oldBudgets as $budget ) {
            $pdfPath = storage_path( "app/public/budgets/{$budget->id}/" );

            if ( is_dir( $pdfPath ) ) {
                $files = glob( $pdfPath . '*.pdf' );

                foreach ( $files as $file ) {
                    if ( is_file( $file ) ) {
                        unlink( $file );
                    }
                }

                rmdir( $pdfPath );
                $cleanedCount++;
            }
        }

        return $cleanedCount;
    }

}
