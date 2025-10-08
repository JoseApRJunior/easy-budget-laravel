<?php

namespace core\support\report;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Mpdf\Mpdf;

class PdfGenerator
{
    private Mpdf $mpdf;

    public function __construct()
    {
        // Suprimir warnings durante inicialização
        $originalErrorReporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE);
        
        try {
            // Configurações simplificadas para evitar erro de fonte
            $this->mpdf = new Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 25,
                'margin_footer' => 10,
                'default_font_size' => 10,
                // Otimizações
                'tempDir' => sys_get_temp_dir(),
                'enableImports' => false,
                'autoScriptToLang' => false,
                'autoLangToFont' => false,
                // Otimizações de tabela
                'simpleTables' => true,
                'packTableData' => true,
                'shrink_tables_to_fit' => 1.4,
            ]);

            // Configurar fonte explicitamente
            $this->mpdf->SetDefaultFont('Arial');
            $this->mpdf->SetDefaultFontSize(10);
            
            $this->mpdf->setAutoPageBreak(true, 25);
            $this->mpdf->showImageErrors = false;
            $this->mpdf->useSubstitutions = false;
            $this->mpdf->simpleTables = true;
            $this->mpdf->SetCompression(true);
            
        } finally {
            // Restaurar error reporting
            error_reporting($originalErrorReporting);
        }
    }

    /**
     * Gera o PDF
     */
    public function generate(string $html, ?string $filename, ?string $verificationHash = null): array
    {
        try {
            // Suprimir warnings do mPDF
            $originalErrorReporting = error_reporting();
            error_reporting(E_ERROR | E_PARSE);
            
            // Define o rodapé
            $this->setFooter($filename, $verificationHash);

            // Otimiza o HTML
            $html = $this->optimizeHtml($html);
            
            // Validação UTF-8
            if (!mb_check_encoding($html, 'UTF-8')) {
                $html = mb_convert_encoding($html, 'UTF-8', 'auto');
            }
            
            // Configurar fonte antes de gerar
            $this->mpdf->SetFont('Arial', '', 10);
            
            // Gera o PDF
            $this->mpdf->WriteHTML($html);
            $pdf = $this->mpdf->Output('', 'S');

            $sizeInBytes = strlen($pdf);

            // Calcula o tamanho
            $size = [
                'bytes' => $sizeInBytes,
                'kb' => round($sizeInBytes / 1024, 2),
                'mb' => round($sizeInBytes / (1024 * 1024), 2),
                'pages' => $this->mpdf->page ?? 1,
            ];

            // Restaurar error reporting
            error_reporting($originalErrorReporting);
            
            // Limpa o buffer
            $this->mpdf->cleanup();

            return [
                'content' => $pdf,
                'size' => $size,
                'success' => true,
            ];

        } catch (\Exception $e) {
            // Restaurar error reporting em caso de erro
            if (isset($originalErrorReporting)) {
                error_reporting($originalErrorReporting);
            }
            throw new \Exception('Erro ao gerar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Define o rodapé do PDF
     */
    private function setFooter(?string $filename, ?string $verificationHash = null): void
    {
        // Nome do arquivo padrão se não for fornecido
        $filename = $filename ?? 'documento_' . date('Ymd_H-i-s') . '.pdf';

        $verificationHtml = '';
        if ($verificationHash) {
            $verificationUrl = env('APP_URL') . '/documents/verify/' . $verificationHash;

            // Gera o QR Code como uma Data URI
            // A sintaxe foi ajustada para a versão 4 da biblioteca endroid/qr-code
            $builder = new Builder();
            $qrCodeResult = $builder->build(
                writer: new PngWriter(),
                data: $verificationUrl,
                size: 80,
                margin: 5,
            );

            $qrCodeDataUri = $qrCodeResult->getDataUri();

            $verificationHtml = '
                <td width="33%" style="text-align: center; vertical-align: middle;">
                    <div style="font-size: 8pt; color: #555;">
                        <img src="' . $qrCodeDataUri . '" alt="QR Code" style="width: 60px; height: 60px;"><br>
                        Verifique a autenticidade
                    </div>
                </td>';
        }

        // HTML do rodapé
        $footerHTML = '
        <table width="100%" style="border-top: 1px solid #000000; font-size: 9pt; vertical-align: bottom;">
            <tr>
                <td width="33%" style="text-align: left;">' . htmlspecialchars($filename) . '</td>
                ' . $verificationHtml . '
                <td width="33%" style="text-align: right;">Página {PAGENO} de {nbpg}</td>
            </tr>
        </table>';

        // Define o rodapé
        $this->mpdf->SetHTMLFooter($footerHTML);
    }

    /**
     * Otimiza o HTML antes de gerar o PDF
     */
    private function optimizeHtml(string $html): string
    {
        // Compilar regex uma única vez para melhor performance
        static $patterns = [
        // Remove comentários HTML
        '/<!--.*?-->/su',
        // Remove estilos não utilizados e scripts
        '/<(style|script)[^>]*>.*?<\/\1>/isu',
        // Remove atributos desnecessários
        '/\s+(class|id|data-[^=]*|aria-[^=]*|role)="[^"]*"/iu',
        // Remove espaços múltiplos
        '/\s{2,}/u',
        // Remove quebras de linha desnecessárias
        '/\n\s*\n/u',
        // Remove espaços entre tags
        '/>\s+</u',
        ];

        static $replacements = [ '', '', '', ' ', "\n", '><' ];

        // Aplicar todas as otimizações de uma vez
        $html = preg_replace($patterns, $replacements, $html);

        // Remove espaços no início e fim de linhas
        $html = preg_replace('/^\s+|\s+$/mu', '', $html);

        return trim($html);
    }

}
