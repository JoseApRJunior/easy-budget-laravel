<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\EmailVariable;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Log;

class VariableProcessor
{
    private const VARIABLE_PATTERN = '/\{\{(\w+)\}\}/';

    /**
     * Processa texto substituindo variáveis pelos valores fornecidos.
     */
    public function processText( string $text, array $data ): string
    {
        return preg_replace_callback( self::VARIABLE_PATTERN, function ( $matches ) use ( $data ) {
            $variable = $matches[ 1 ];
            return $data[ $variable ] ?? $matches[ 0 ];
        }, $text );
    }

    /**
     * Extrai variáveis utilizadas no conteúdo.
     */
    public function extractVariables( string $content ): array
    {
        preg_match_all( self::VARIABLE_PATTERN, $content, $matches );
        return array_unique( $matches[ 1 ] );
    }

    /**
     * Valida variáveis utilizadas em relação às disponíveis.
     */
    public function validateVariables( string $content, array $availableVariables ): array
    {
        $usedVariables    = $this->extractVariables( $content );
        $invalidVariables = array_diff( $usedVariables, $availableVariables );

        return [
            'valid'   => empty( $invalidVariables ),
            'used'    => $usedVariables,
            'invalid' => array_values( $invalidVariables )
        ];
    }

    /**
     * Obtém variáveis disponíveis para um tenant.
     */
    public function getAvailableVariables( int $tenantId ): array
    {
        try {
            $variables = EmailVariable::where( 'tenant_id', $tenantId )
                ->active()
                ->ordered()
                ->get();

            $grouped = [];
            foreach ( $variables as $variable ) {
                $grouped[ $variable->category ][ $variable->slug ] = [
                    'name'          => $variable->name,
                    'description'   => $variable->description,
                    'data_type'     => $variable->data_type,
                    'default_value' => $variable->default_value,
                ];
            }

            return [
                'system'   => $grouped[ 'system' ] ?? [],
                'user'     => $grouped[ 'user' ] ?? [],
                'customer' => $grouped[ 'customer' ] ?? [],
                'budget'   => $grouped[ 'budget' ] ?? [],
                'invoice'  => $grouped[ 'invoice' ] ?? [],
                'company'  => $grouped[ 'company' ] ?? [],
            ];
        } catch ( Exception $e ) {
            Log::error( 'Erro ao obter variáveis disponíveis', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return [];
        }
    }

    /**
     * Cria variáveis padrão do sistema para um tenant.
     */
    public function createSystemVariables( int $tenantId ): ServiceResult
    {
        try {
            $systemVariables = [
                [
                    'name'          => 'Nome da Empresa',
                    'slug'          => 'company_name',
                    'description'   => 'Nome da empresa/organização',
                    'category'      => 'company',
                    'data_type'     => 'string',
                    'default_value' => 'Easy Budget',
                    'is_system'     => true,
                    'is_active'     => true,
                    'sort_order'    => 1,
                ],
                [
                    'name'          => 'Email da Empresa',
                    'slug'          => 'company_email',
                    'description'   => 'Email de contato da empresa',
                    'category'      => 'company',
                    'data_type'     => 'string',
                    'default_value' => 'contato@easybudget.com',
                    'is_system'     => true,
                    'is_active'     => true,
                    'sort_order'    => 2,
                ],
                [
                    'name'          => 'Telefone da Empresa',
                    'slug'          => 'company_phone',
                    'description'   => 'Telefone de contato da empresa',
                    'category'      => 'company',
                    'data_type'     => 'string',
                    'default_value' => '(11) 99999-9999',
                    'is_system'     => true,
                    'is_active'     => true,
                    'sort_order'    => 3,
                ],
                [
                    'name'        => 'Data Atual',
                    'slug'        => 'current_date',
                    'description' => 'Data atual no formato DD/MM/YYYY',
                    'category'    => 'system',
                    'data_type'   => 'date',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 1,
                ],
                [
                    'name'        => 'Ano Atual',
                    'slug'        => 'current_year',
                    'description' => 'Ano atual (YYYY)',
                    'category'    => 'system',
                    'data_type'   => 'number',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 2,
                ],
                [
                    'name'        => 'Nome do Usuário',
                    'slug'        => 'user_name',
                    'description' => 'Nome completo do usuário logado',
                    'category'    => 'user',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 1,
                ],
                [
                    'name'        => 'Email do Usuário',
                    'slug'        => 'user_email',
                    'description' => 'Email do usuário logado',
                    'category'    => 'user',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 2,
                ],
                [
                    'name'        => 'Cargo do Usuário',
                    'slug'        => 'user_position',
                    'description' => 'Cargo/função do usuário',
                    'category'    => 'user',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 3,
                ],
                [
                    'name'        => 'Nome do Cliente',
                    'slug'        => 'customer_name',
                    'description' => 'Nome completo do cliente',
                    'category'    => 'customer',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 1,
                ],
                [
                    'name'        => 'Email do Cliente',
                    'slug'        => 'customer_email',
                    'description' => 'Email do cliente',
                    'category'    => 'customer',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 2,
                ],
                [
                    'name'        => 'Empresa do Cliente',
                    'slug'        => 'customer_company',
                    'description' => 'Nome da empresa do cliente',
                    'category'    => 'customer',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 3,
                ],
                [
                    'name'        => 'Telefone do Cliente',
                    'slug'        => 'customer_phone',
                    'description' => 'Telefone do cliente',
                    'category'    => 'customer',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 4,
                ],
                [
                    'name'        => 'Título do Orçamento',
                    'slug'        => 'budget_title',
                    'description' => 'Título ou descrição do orçamento',
                    'category'    => 'budget',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 1,
                ],
                [
                    'name'        => 'Número do Orçamento',
                    'slug'        => 'budget_number',
                    'description' => 'Código único do orçamento',
                    'category'    => 'budget',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 2,
                ],
                [
                    'name'        => 'Valor do Orçamento',
                    'slug'        => 'budget_value',
                    'description' => 'Valor total do orçamento',
                    'category'    => 'budget',
                    'data_type'   => 'number',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 3,
                ],
                [
                    'name'        => 'Prazo do Orçamento',
                    'slug'        => 'budget_deadline',
                    'description' => 'Data limite para aprovação do orçamento',
                    'category'    => 'budget',
                    'data_type'   => 'date',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 4,
                ],
                [
                    'name'        => 'Lista de Itens',
                    'slug'        => 'budget_items',
                    'description' => 'Lista formatada dos itens do orçamento',
                    'category'    => 'budget',
                    'data_type'   => 'array',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 5,
                ],
                [
                    'name'        => 'Link do Orçamento',
                    'slug'        => 'budget_link',
                    'description' => 'URL para visualização do orçamento',
                    'category'    => 'budget',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 6,
                ],
                [
                    'name'        => 'Número da Fatura',
                    'slug'        => 'invoice_number',
                    'description' => 'Número único da fatura',
                    'category'    => 'invoice',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 1,
                ],
                [
                    'name'        => 'Data da Fatura',
                    'slug'        => 'invoice_date',
                    'description' => 'Data de emissão da fatura',
                    'category'    => 'invoice',
                    'data_type'   => 'date',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 2,
                ],
                [
                    'name'        => 'Vencimento da Fatura',
                    'slug'        => 'invoice_due_date',
                    'description' => 'Data de vencimento da fatura',
                    'category'    => 'invoice',
                    'data_type'   => 'date',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 3,
                ],
                [
                    'name'        => 'Valor da Fatura',
                    'slug'        => 'invoice_amount',
                    'description' => 'Valor total da fatura',
                    'category'    => 'invoice',
                    'data_type'   => 'number',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 4,
                ],
                [
                    'name'        => 'Link da Fatura',
                    'slug'        => 'invoice_link',
                    'description' => 'URL para visualização da fatura',
                    'category'    => 'invoice',
                    'data_type'   => 'string',
                    'is_system'   => true,
                    'is_active'   => true,
                    'sort_order'  => 5,
                ],
            ];

            foreach ( $systemVariables as $variableData ) {
                $variableData[ 'tenant_id' ] = $tenantId;

                EmailVariable::firstOrCreate(
                    [ 'tenant_id' => $tenantId, 'slug' => $variableData[ 'slug' ] ],
                    $variableData,
                );
            }

            return ServiceResult::success( null, 'Variáveis do sistema criadas com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar variáveis do sistema', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage()
            ] );

            return ServiceResult::error( 'Erro interno ao criar variáveis do sistema: ' . $e->getMessage() );
        }
    }

    /**
     * Processa dados dinâmicos para substituição de variáveis.
     */
    public function processDynamicData( array $data, int $tenantId ): array
    {
        $processed = [];

        // Adicionar dados do sistema
        $processed[ 'current_date' ] = now()->format( 'd/m/Y' );
        $processed[ 'current_year' ] = now()->year;

        // Adicionar dados da empresa (se disponível)
        $processed[ 'company_name' ]  = $data[ 'company_name' ] ?? 'Easy Budget';
        $processed[ 'company_email' ] = $data[ 'company_email' ] ?? 'contato@easybudget.com';
        $processed[ 'company_phone' ] = $data[ 'company_phone' ] ?? '(11) 99999-9999';

        // Processar dados específicos baseados no contexto
        if ( isset( $data[ 'context' ] ) ) {
            switch ( $data[ 'context' ] ) {
                case 'budget':
                    $processed = array_merge( $processed, $this->processBudgetData( $data ) );
                    break;
                case 'invoice':
                    $processed = array_merge( $processed, $this->processInvoiceData( $data ) );
                    break;
                case 'customer':
                    $processed = array_merge( $processed, $this->processCustomerData( $data ) );
                    break;
            }
        }

        return $processed;
    }

    /**
     * Processa dados específicos de orçamento.
     */
    private function processBudgetData( array $data ): array
    {
        return [
            'budget_title'    => $data[ 'budget_title' ] ?? 'Orçamento',
            'budget_number'   => $data[ 'budget_number' ] ?? '',
            'budget_value'    => $data[ 'budget_value' ] ?? '0,00',
            'budget_deadline' => isset( $data[ 'budget_deadline' ] ) ? \Carbon\Carbon::parse( $data[ 'budget_deadline' ] )->format( 'd/m/Y' ) : '',
            'budget_items'    => $data[ 'budget_items' ] ?? '',
            'budget_link'     => $data[ 'budget_link' ] ?? '',
        ];
    }

    /**
     * Processa dados específicos de fatura.
     */
    private function processInvoiceData( array $data ): array
    {
        return [
            'invoice_number'   => $data[ 'invoice_number' ] ?? '',
            'invoice_date'     => isset( $data[ 'invoice_date' ] ) ? \Carbon\Carbon::parse( $data[ 'invoice_date' ] )->format( 'd/m/Y' ) : '',
            'invoice_due_date' => isset( $data[ 'invoice_due_date' ] ) ? \Carbon\Carbon::parse( $data[ 'invoice_due_date' ] )->format( 'd/m/Y' ) : '',
            'invoice_amount'   => $data[ 'invoice_amount' ] ?? '0,00',
            'invoice_link'     => $data[ 'invoice_link' ] ?? '',
        ];
    }

    /**
     * Processa dados específicos de cliente.
     */
    private function processCustomerData( array $data ): array
    {
        return [
            'customer_name'    => $data[ 'customer_name' ] ?? '',
            'customer_email'   => $data[ 'customer_email' ] ?? '',
            'customer_company' => $data[ 'customer_company' ] ?? '',
            'customer_phone'   => $data[ 'customer_phone' ] ?? '',
        ];
    }

}
