<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\library\Twig;
use core\support\report\PdfGenerator;
use Exception;

/**
 * Serviço para geração de PDFs
 * Implementa ServiceNoTenantInterface pois não utiliza tenant_id diretamente
 */
class PdfService implements ServiceNoTenantInterface
{
    private Twig         $twig;
    private PdfGenerator $pdfGenerator;

    public function __construct(
        Twig $twig,
        PdfGenerator $pdfGenerator,
    ) {
        $this->twig         = $twig;
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Busca um PDF pelo ID (não aplicável para PdfService)
     *
     * @param int $id ID do PDF
     * @return ServiceResult Sempre retorna erro indicando que não é aplicável
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            // PdfService não armazena PDFs para busca por ID
            return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'PdfService não armazena PDFs para busca por ID.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Lista PDFs (não aplicável para PdfService)
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            // PdfService não armazena PDFs para listagem
            return ServiceResult::success( [ 
                'entities' => [],
                'count'    => 0,
            ], 'PdfService não armazena PDFs para listagem.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar PDFs: ' . $e->getMessage() );
        }
    }

    /**
     * Cria/gera um novo PDF
     *
     * @param array<string, mixed> $data Dados do PDF
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Verificar se o índice 'type' existe
            if ( !isset( $data[ 'type' ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Tipo de PDF é obrigatório.' );
            }

            $pdfContent = null;

            // Determinar tipo de PDF e gerar
            switch ( $data[ 'type' ] ) {
                case 'budget':
                    // Verificar se os índices obrigatórios existem
                    if ( !isset( $data[ 'authenticated' ] ) || !isset( $data[ 'customer' ] ) || !isset( $data[ 'budget' ] ) ) {
                        return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados obrigatórios ausentes para PDF de orçamento.' );
                    }

                    $pdfContent = $this->generateBudgetPdf(
                        $data[ 'authenticated' ],
                        $data[ 'customer' ],
                        $data[ 'budget' ],
                        $data[ 'services' ] ?? [],
                        $data[ 'service_items' ] ?? [],
                        $data[ 'latest_schedules' ] ?? [],
                        $data[ 'verificationHash' ] ?? ''
                    );
                    break;
                case 'service':
                    // Verificar se os índices obrigatórios existem
                    if ( !isset( $data[ 'authenticated' ] ) || !isset( $data[ 'customer' ] ) || !isset( $data[ 'budget' ] ) || !isset( $data[ 'service' ] ) ) {
                        return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados obrigatórios ausentes para PDF de serviço.' );
                    }

                    $pdfContent = $this->generateServicePdf(
                        $data[ 'authenticated' ],
                        $data[ 'customer' ],
                        $data[ 'budget' ],
                        $data[ 'service' ],
                        $data[ 'serviceItems' ] ?? [],
                        $data[ 'latest_schedule' ] ?? null,
                        $data[ 'verificationHash' ] ?? ''
                    );
                    break;
                case 'invoice':
                    // Verificar se os índices obrigatórios existem
                    if ( !isset( $data[ 'authenticated' ] ) || !isset( $data[ 'invoice' ] ) ) {
                        return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados obrigatórios ausentes para PDF de fatura.' );
                    }

                    $pdfContent = $this->generateInvoicePdf(
                        $data[ 'authenticated' ],
                        $data[ 'invoice' ],
                    );
                    break;
                default:
                    return ServiceResult::error( OperationStatus::INVALID_DATA, 'Tipo de PDF não suportado: ' . $data[ 'type' ] );
            }

            // Retorna sucesso com o PDF gerado
            return ServiceResult::success( [ 
                'entity' => [ 
                    'type'         => $data[ 'type' ],
                    'content'      => $pdfContent,
                    'generated_at' => date( 'Y-m-d H:i:s' ),
                ],
            ], 'PDF gerado com sucesso.' );
        } catch ( Exception $e ) {
            // Retorna erro com a mensagem da exceção
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao gerar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um PDF (não aplicável para PdfService)
     *
     * @param int $id ID do PDF
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // PdfService não suporta atualização de PDFs
            return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'PdfService não suporta atualização de PDFs.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um PDF (não aplicável para PdfService)
     *
     * @param int $id ID do PDF
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // PdfService não suporta remoção de PDFs
            return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'PdfService não suporta remoção de PDFs.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao remover PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados do PDF
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        try {
            $errors = [];

            // Tipo obrigatório
            if ( empty( $data[ 'type' ] ) ) {
                $errors[] = 'Tipo de PDF é obrigatório.';
            } elseif ( !in_array( $data[ 'type' ], [ 'budget', 'service', 'invoice' ] ) ) {
                $errors[] = 'Tipo de PDF inválido.';
            }

            // Authenticated obrigatório
            if ( empty( $data[ 'authenticated' ] ) ) {
                $errors[] = 'Dados de autenticação são obrigatórios.';
            }

            // Validações específicas por tipo
            if ( !empty( $data[ 'type' ] ) ) {
                switch ( $data[ 'type' ] ) {
                    case 'budget':
                        if ( empty( $data[ 'budget' ] ) ) {
                            $errors[] = 'Dados do orçamento são obrigatórios para PDF de orçamento.';
                        }
                        if ( empty( $data[ 'customer' ] ) ) {
                            $errors[] = 'Dados do cliente são obrigatórios para PDF de orçamento.';
                        }
                        break;
                    case 'service':
                        if ( empty( $data[ 'service' ] ) ) {
                            $errors[] = 'Dados do serviço são obrigatórios para PDF de serviço.';
                        }
                        if ( empty( $data[ 'customer' ] ) ) {
                            $errors[] = 'Dados do cliente são obrigatórios para PDF de serviço.';
                        }
                        if ( empty( $data[ 'budget' ] ) ) {
                            $errors[] = 'Dados do orçamento são obrigatórios para PDF de serviço.';
                        }
                        break;
                    case 'invoice':
                        if ( empty( $data[ 'invoice' ] ) ) {
                            $errors[] = 'Dados da fatura são obrigatórios para PDF de fatura.';
                        }
                        break;
                }
            }

            if ( !empty( $errors ) ) {
                // Retorna erro de validação com a lista de erros
                return ServiceResult::error( OperationStatus::VALIDATION, 'Dados inválidos: ' . implode( ', ', $errors ) );
            }

            // Retorna sucesso se os dados forem válidos
            return ServiceResult::success( [], 'Dados válidos.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro na validação: ' . $e->getMessage() );
        }
    }

    // Métodos específicos de geração de PDFs (mantidos por compatibilidade)

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
        // Verificar se os objetos são nulos
        if ( $authenticated === null || $customer === null || $budget === null ) {
            throw new Exception( 'Dados obrigatórios ausentes para geração do PDF de orçamento.' );
        }

        $date = new \DateTime();

        $pdf_name = sprintf(
            'orcamento_%s_%s.pdf',
            $budget->code ?? 'sem_codigo',
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
     * @param object|null $latest_schedule Último agendamento.
     * @param string $verificationHash Hash de verificação.
     * @return array<string, mixed> Array com conteúdo e nome do arquivo PDF.
     */
    public function generateServicePdf( object $authenticated, object $customer, object $budget, object $service, array $serviceItems, ?object $latest_schedule, string $verificationHash ): array
    {
        // Verificar se os objetos são nulos
        if ( $authenticated === null || $customer === null || $budget === null || $service === null ) {
            throw new Exception( 'Dados obrigatórios ausentes para geração do PDF de serviço.' );
        }

        $date = new \DateTime();

        $pdf_name = sprintf(
            'servico_%s_%s.pdf',
            $service->code ?? 'sem_codigo',
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
        // Verificar se os objetos são nulos
        if ( $authenticated === null || $invoice === null ) {
            throw new Exception( 'Dados obrigatórios ausentes para geração do PDF de fatura.' );
        }

        $date = new \DateTime();

        $pdf_name = sprintf(
            'fatura_%s_%s.pdf',
            $invoice->code ?? 'sem_codigo',
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

    // Métodos auxiliares para geração de PIX (mantidos por compatibilidade)

    /**
     * Gera payload PIX real baseado nos dados do provider e fatura
     */
    private function generatePixPayload( object $authenticated, object $invoice ): string
    {
        // Verificar se os objetos são nulos
        if ( $authenticated === null || $invoice === null ) {
            throw new Exception( 'Dados obrigatórios ausentes para geração do payload PIX.' );
        }

        // Dados do recebedor (provider)
        $companyName  = $authenticated->company_name ?? null;
        $firstName    = $authenticated->first_name ?? '';
        $lastName     = $authenticated->last_name ?? '';
        $receiverName = $companyName ?? $firstName . ' ' . $lastName;
        $receiverName = $this->sanitizePixField( $receiverName, 25 );

        $city         = $authenticated->city ?? 'NAO INFORMADO';
        $receiverCity = $this->sanitizePixField( $city, 15 );

        // Chave PIX (usar CNPJ se tiver, senão CPF, senão email)
        $cnpj          = $authenticated->cnpj ?? null;
        $cpf           = $authenticated->cpf ?? null;
        $emailBusiness = $authenticated->email_business ?? null;
        $email         = $authenticated->email ?? null;

        $pixKey = $cnpj ?? $cpf ?? $emailBusiness ?? $email;

        if ( $pixKey !== null ) {
            $pixKey = preg_replace( '/[^0-9]/', '', $pixKey ); // Remove caracteres especiais para CNPJ/CPF
        }

        if ( empty( $pixKey ) || ( !$this->isValidCnpj( $pixKey ) && !$this->isValidCpf( $pixKey ) ) ) {
            $pixKey = $emailBusiness ?? $email; // Fallback para email
        }

        // Valor da fatura
        $total  = $invoice->total ?? 0;
        $amount = number_format( (float) $total, 2, '.', '' );

        // Identificador da transação
        $txId = $invoice->code ?? 'sem_codigo';

        // Montar payload PIX (EMV)
        $payload = $this->buildPixEMV( $pixKey, $receiverName, $receiverCity, $amount, $txId );

        return $payload;
    }

    /**
     * Constrói o payload PIX no formato EMV
     */
    private function buildPixEMV( ?string $pixKey, string $name, string $city, string $amount, string $txId ): string
    {
        // Verificar se a chave PIX é nula
        if ( $pixKey === null ) {
            throw new Exception( 'Chave PIX não pode ser nula.' );
        }

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
    private function sanitizePixField( ?string $field, int $maxLength ): string
    {
        // Verificar se o campo é nulo
        if ( $field === null ) {
            return '';
        }

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
    private function isValidCnpj( ?string $cnpj ): bool
    {
        if ( $cnpj === null ) {
            return false;
        }
        return strlen( $cnpj ) === 14 && ctype_digit( $cnpj );
    }

    /**
     * Valida CPF
     */
    private function isValidCpf( ?string $cpf ): bool
    {
        if ( $cpf === null ) {
            return false;
        }
        return strlen( $cpf ) === 11 && ctype_digit( $cpf );
    }

    /**
     * Gera um QR Code como uma Data URI a partir de uma string.
     */
    private function generateQrCodeDataUri( string $data ): string
    {
        // Este método foi mantido para compatibilidade, mas não está sendo usado
        // pois o BaconQrCode foi removido das dependências
        return '';
    }

}
