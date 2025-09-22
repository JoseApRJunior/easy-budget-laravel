<?php

namespace app\database\services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use core\library\Twig;
use core\support\report\PdfGenerator;

class PdfService
{
    public function __construct(
        private Twig $twig,
        private PdfGenerator $pdfGenerator,
    ) {}

    // todo testar todos os pdfs

    /**
     * Gera PDF do orçamento.
     *
     * @param object $authenticated Dados do usuário autenticado.
     * @param object $customer Dados do cliente.
     * @param object $budget Dados do orçamento.
     * @param array<int, array<string, mixed>> $services Lista de serviços.
     * @param array<int, array<string, mixed>> $service_items Itens dos serviços.
     * @param array<int, array<string, mixed>> $latest_schedules Últimos agendamentos.
     * @param string $verificationHash Hash de verificação.
     * @return array<string, mixed> Array com conteúdo e nome do arquivo PDF.
     */
    public function generateBudgetPdf( object $authenticated, object $customer, object $budget, array $services, array $service_items, array $latest_schedules, string $verificationHash ): array
    {

        $date = new \DateTime();

        $pdf_name = sprintf(
            'orcamento_%s_%s.pdf',
            $budget->code,
            $date->format( 'Ymd_H_i_s' ),
        );

        $html = $this->twig->env->render( 'pages/budget/pdf_budget_print.twig', [ 
            'authenticated'    => $authenticated,
            'budget'           => $budget,
            'customer'         => $customer,
            'services'         => $services,
            'service_items'    => $service_items,
            'latest_schedules' => $latest_schedules,
            'date'             => $date,
        ] );

        $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name, $verificationHash );

        return [ 
            'content'  => $pdfGenerated[ 'content' ],
            'fileName' => $pdf_name,
        ];

    }

    /**
     * Gera PDF do serviço.
     *
     * @param object $authenticated Dados do usuário autenticado.
     * @param object $customer Dados do cliente.
     * @param object $budget Dados do orçamento.
     * @param object $service Dados do serviço.
     * @param array<int, array<string, mixed>> $serviceItems Itens do serviço.
     * @param object $latest_schedule Último agendamento.
     * @param string $verificationHash Hash de verificação.
     * @return array<string, mixed> Array com conteúdo e nome do arquivo PDF.
     */
    public function generateServicePdf( object $authenticated, object $customer, object $budget, object $service, array $serviceItems, object $latest_schedule, string $verificationHash ): array
    {

        $date = new \DateTime();

        $pdf_name = sprintf(
            'servico_%s_%s.pdf',
            $service->code,
            $date->format( 'Ymd_H_i_s' ),
        );

        $html = $this->twig->env->render( 'pages/service/pdf_service_print.twig', [ 
            'authenticated'   => $authenticated,
            'budget'          => $budget,
            'customer'        => $customer,
            'service'         => $service,
            'serviceItems'    => $serviceItems,
            'latest_schedule' => $latest_schedule,
            'date'            => $date,
        ] );

        $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name, $verificationHash );

        return [ 
            'content'  => $pdfGenerated[ 'content' ],
            'fileName' => $pdf_name,
        ];

    }

    /**
     * Gera PDF da fatura.
     *
     * @param object $authenticated Dados do usuário autenticado.
     * @param object $invoice Dados da fatura.
     * @return array<string, mixed> Array com conteúdo e nome do arquivo PDF.
     */
    public function generateInvoicePdf( object $authenticated, object $invoice ): array
    {
        $date = new \DateTime();

        $pdf_name = sprintf(
            'fatura_%s_%s.pdf',
            $invoice->code,
            $date->format( 'Ymd_His' ),
        );

        // Gerar PIX real baseado nos dados do provider
        $pixPayload = $this->generatePixPayload( $authenticated, $invoice );
        $qrCodePix  = $this->generateQrCodeDataUri( $pixPayload );

        $html = $this->twig->env->render( 'pages/invoice/pdf_invoice_print.twig', [ 
            'authenticated' => $authenticated,
            'invoice'       => $invoice,
            'date'          => $date,
            'qrCodePix'     => $qrCodePix,
            'pixPayload'    => $pixPayload,
        ] );

        $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name, null );

        return [ 
            'content'  => $pdfGenerated[ 'content' ],
            'fileName' => $pdf_name,
        ];
    }

    /**
     * Gera payload PIX real baseado nos dados do provider e fatura
     */
    private function generatePixPayload( object $authenticated, object $invoice ): string
    {
        // Dados do recebedor (provider)
        $receiverName = $this->sanitizePixField( $authenticated->company_name ?? $authenticated->first_name . ' ' . $authenticated->last_name, 25 );
        $receiverCity = $this->sanitizePixField( $authenticated->city ?? 'NAO INFORMADO', 15 );

        // Chave PIX (usar CNPJ se tiver, senão CPF, senão email)
        $pixKey = $authenticated->cnpj ?? $authenticated->cpf ?? $authenticated->email_business ?? $authenticated->email;
        $pixKey = preg_replace( '/[^0-9]/', '', $pixKey ); // Remove caracteres especiais para CNPJ/CPF

        if ( empty( $pixKey ) || ( !$this->isValidCnpj( $pixKey ) && !$this->isValidCpf( $pixKey ) ) ) {
            $pixKey = $authenticated->email_business ?? $authenticated->email; // Fallback para email
        }

        // Valor da fatura
        $amount = number_format( (float) $invoice->total, 2, '.', '' );

        // Identificador da transação
        $txId = $invoice->code;

        // Montar payload PIX (EMV)
        $payload = $this->buildPixEMV( $pixKey, $receiverName, $receiverCity, $amount, $txId );

        return $payload;
    }

    /**
     * Constrói o payload PIX no formato EMV
     */
    private function buildPixEMV( string $pixKey, string $name, string $city, string $amount, string $txId ): string
    {
        // Payload Indicator
        $payload = '000201';

        // Point of Initiation Method
        $payload .= '010212';

        // Merchant Account Information
        $pixKeyLength       = str_pad( (string) strlen( (string) $pixKey ), 2, '0', STR_PAD_LEFT );
        $merchantInfo       = '0014br.gov.bcb.pix01' . $pixKeyLength . $pixKey;
        $merchantInfoLength = str_pad( (string) strlen( (string) $merchantInfo ), 2, '0', STR_PAD_LEFT );
        $payload .= '26' . $merchantInfoLength . $merchantInfo;

        // Merchant Category Code
        $payload .= '52040000';

        // Transaction Currency (BRL)
        $payload .= '5303986';

        // Transaction Amount
        if ( $amount > 0 ) {
            $amountLength = str_pad( (string) strlen( (string) $amount ), 2, '0', STR_PAD_LEFT );
            $payload .= '54' . $amountLength . $amount;
        }

        // Country Code
        $payload .= '5802BR';

        // Merchant Name
        $nameLength = str_pad( (string) strlen( $name ), 2, '0', STR_PAD_LEFT );
        $payload .= '59' . $nameLength . $name;

        // Merchant City
        $cityLength = str_pad( (string) strlen( $city ), 2, '0', STR_PAD_LEFT );
        $payload .= '60' . $cityLength . $city;

        // Additional Data Field
        if ( !empty( $txId ) ) {
            $txIdLength           = str_pad( (string) strlen( $txId ), 2, '0', STR_PAD_LEFT );
            $additionalData       = '05' . $txIdLength . $txId;
            $additionalDataLength = str_pad( (string) strlen( $additionalData ), 2, '0', STR_PAD_LEFT );
            $payload .= '62' . $additionalDataLength . $additionalData;
        }

        // CRC16
        $payload .= '6304';
        $crc     = $this->calculateCRC16( $payload );
        $payload .= strtoupper( $crc );

        return $payload;
    }

    /**
     * Calcula CRC16 para o payload PIX
     */
    private function calculateCRC16( string $data ): string
    {
        $polynomial = 0x1021;
        $crc        = 0xFFFF;

        for ( $i = 0; $i < strlen( $data ); $i++ ) {
            $crc ^= ( ord( $data[ $i ] ) << 8 );
            for ( $j = 0; $j < 8; $j++ ) {
                if ( $crc & 0x8000 ) {
                    $crc = ( $crc << 1 ) ^ $polynomial;
                } else {
                    $crc = $crc << 1;
                }
                $crc &= 0xFFFF;
            }
        }

        return sprintf( '%04X', $crc );
    }

    /**
     * Sanitiza campo para PIX (remove acentos e caracteres especiais)
     */
    private function sanitizePixField( string $field, int $maxLength ): string
    {
        // Remove acentos
        $field = iconv( 'UTF-8', 'ASCII//TRANSLIT', $field );
        // Remove caracteres especiais
        $field = preg_replace( '/[^A-Za-z0-9 ]/', '', $field );
        // Converte para maiúsculo
        $field = strtoupper( $field );
        // Limita tamanho
        return substr( $field, 0, $maxLength );
    }

    /**
     * Valida CNPJ
     */
    private function isValidCnpj( string $cnpj ): bool
    {
        return strlen( $cnpj ) === 14 && ctype_digit( $cnpj );
    }

    /**
     * Valida CPF
     */
    private function isValidCpf( string $cpf ): bool
    {
        return strlen( $cpf ) === 11 && ctype_digit( $cpf );
    }

    /**
     * Gera um QR Code como uma Data URI a partir de uma string.
     */
    private function generateQrCodeDataUri( string $data ): string
    {
        $renderer     = new GDLibRenderer( 256, 0 );
        $writer       = new Writer( $renderer );
        $qrCodeString = $writer->writeString( $data );

        return 'data:image/png;base64,' . base64_encode( $qrCodeString );
    }

}
