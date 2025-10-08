<?php

namespace core\support;

use Mpdf\Config\ConfigVariables;
use Mpdf\Mpdf;

class PdfGenerator
{
    private Mpdf $mpdf;

    public function __construct()
    {
        // Configurações otimizadas
        $this->mpdf = new Mpdf( [ 
            'mode'                   => 'utf-8',
            'format'                 => 'A4',
            'margin_left'            => 15,
            'margin_right'           => 15,
            'margin_top'             => 15,
            'margin_bottom'          => 15,
            'default_font'           => 'dejavusans',
            // Otimizações
            'dpi'                    => 96,
            'img_dpi'                => 96,
            'cache_dir'              => sys_get_temp_dir(),
            'allow_output_buffering' => true,
            'enableImports'          => false,
            'autoScriptToLang'       => false,
            'autoLangToFont'         => false,
            // Otimizações de tabela
            'simpleTables'           => true,
            'packTableData'          => true,
            'shrink_tables_to_fit'   => 1.4
        ] );

        $this->mpdf->setAutoPageBreak( true, 15 );
        $this->mpdf->showImageErrors  = false; // Desativa para melhor performance
        $this->mpdf->useSubstitutions = false;
        $this->mpdf->simpleTables     = true;
        $this->mpdf->SetCompression( true );
    }

    /**
     * Gera o PDF
     */
    public function generate( string $html ): array
    {
        try {
            // Otimiza o HTML
            $html = $this->optimizeHtml( $html );

            // Gera o PDF
            $this->mpdf->WriteHTML( $html );
            $pdf = $this->mpdf->Output( '', 'S' );

            $sizeInBytes = strlen( $pdf );

            // Calcula o tamanho
            $size = [ 
                'bytes' => $sizeInBytes,
                'kb'    => round( $sizeInBytes / 1024, 2 ),
                'mb'    => round( $sizeInBytes / ( 1024 * 1024 ), 2 ),
                'pages' => $this->mpdf->page
            ];

            // Limpa o buffer
            $this->mpdf->cleanup();

            return [ 
                'content' => $pdf,
                'size'    => $size,
                'success' => true
            ];

        } catch ( \Exception $e ) {
            throw new \Exception( 'Erro ao gerar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Otimiza o HTML antes de gerar o PDF
     */
    private function optimizeHtml( string $html ): string
    {
        // Remove estilos não utilizados
        $html = preg_replace( '/<style>[^<]*(?:<(?!\/style>)[^<]*)*<\/style>/i', '', $html );

        // Remove comentários HTML
        $html = preg_replace( '/<!--(.|\s)*?-->/', '', $html );

        // Minimiza CSS inline
        $html = preg_replace( '/\s+/', ' ', $html );

        // Remove espaços extras entre tags
        $html = preg_replace( '/>(\s+)</', '><', $html );

        return trim( $html );
    }

}
