<?php

namespace design_patern\design_pattern_with_tenant\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

/**
 * Padrão de FormRequest WithTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Herda de AbstractFormRequest - Estrutura padrão do projeto
 * ✅ Usa Respect\Validation - Sistema de validação do projeto
 * ✅ Método execute() - Implementação obrigatória
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Validação obrigatória de tenant_id - Controle multi-tenant
 * ✅ Métodos específicos validateForCreate() e validateForUpdate() - Flexibilidade
 * ✅ Validação de propriedade do tenant - Segurança adicional
 *
 * BENEFÍCIOS:
 * - Centralização das regras de validação multi-tenant
 * - Reutilização em múltiplos controllers
 * - Mensagens de erro padronizadas
 * - Validação consistente de dados
 * - Segurança rigorosa de isolamento entre tenants
 * - Validação específica por contexto (create/update)
 */
class DesignPatternWithTenantFormRequest extends AbstractFormRequest
{
    /**
     * Executa a validação dos dados do formulário.
     *
     * @return bool Retorna true se os dados forem válidos, false caso contrário
     */
    protected function execute(): bool
    {
        // Cria o validador principal
        $validator = v::create();

        // Validação obrigatória do campo 'tenant_id'
        $validator->addRule(
            new Key(
                'tenant_id',
                new AllOf(
                    v::notEmpty()->setTemplate( 'O ID do tenant é obrigatório.' ),
                    v::intType()->setTemplate( 'O ID do tenant deve ser um número inteiro.' ),
                    v::min( 1 )->setTemplate( 'O ID do tenant deve ser um número positivo.' ),
                ),
            ),
        );

        // Validação do campo 'name' (obrigatório, entre 2 e 100 caracteres)
        $validator->addRule(
            new Key(
                'name',
                new AllOf(
                    v::notEmpty()->setTemplate( 'O nome da entidade é obrigatório.' ),
                    v::stringType()->setTemplate( 'O nome deve ser um texto válido.' ),
                    v::length( 2, 100 )->setTemplate( 'O nome deve ter entre 2 e 100 caracteres.' ),
                ),
            ),
        );

        // Validação do campo 'slug' (opcional, formato específico)
        $validator->addRule(
            new Key(
                'slug',
                v::optional(
                    new AllOf(
                        v::stringType()->setTemplate( 'O slug deve ser um texto válido.' ),
                        v::length( 2, 100 )->setTemplate( 'O slug deve ter entre 2 e 100 caracteres.' ),
                        v::regex( '/^[a-z0-9-]+$/' )->setTemplate( 'O slug deve conter apenas letras minúsculas, números e hífens.' ),
                    ),
                ),
            ),
        );

        // Validação do campo 'description' (opcional, máximo 500 caracteres)
        $validator->addRule(
            new Key(
                'description',
                v::optional(
                    new AllOf(
                        v::stringType()->setTemplate( 'A descrição deve ser um texto válido.' ),
                        v::length( null, 500 )->setTemplate( 'A descrição deve ter no máximo 500 caracteres.' ),
                    ),
                ),
            ),
        );

        // Validação do campo 'active' (opcional, boolean)
        $validator->addRule(
            new Key(
                'active',
                v::optional(
                    v::in( [ '0', '1', 0, 1, true, false ] )->setTemplate( 'O status ativo deve ser verdadeiro ou falso.' ),
                ),
            ),
        );

        // Executa a validação usando o método da classe pai
        return $this->isValidated( $validator );
    }

    /**
     * Validação específica para criação de entidade.
     *
     * IMPORTANTE: Usado quando é necessário validação adicional para criação.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param int $expectedTenantId ID do tenant esperado para validação de segurança
     * @return bool true se válido, false caso contrário
     */
    public static function validateForCreate( array $data, int $expectedTenantId ): bool
    {
        // Validação básica
        $request = new self();
        $request->setData( $data );

        if ( !$request->execute() ) {
            return false;
        }

        // Validação adicional para criação
        return self::validateTenantOwnership( $data, $expectedTenantId );
    }

    /**
     * Validação específica para atualização de entidade.
     *
     * IMPORTANTE: Usado quando é necessário validação adicional para atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param int $expectedTenantId ID do tenant esperado para validação de segurança
     * @return bool true se válido, false caso contrário
     */
    public static function validateForUpdate( array $data, int $expectedTenantId ): bool
    {
        // Para atualização, tenant_id pode não vir no formulário
        // mas se vier, deve ser validado

        // Validação básica (removendo obrigatoriedade do tenant_id para update)
        $validator = v::create();

        // Validação do campo 'name' (obrigatório)
        $validator->addRule(
            new Key(
                'name',
                new AllOf(
                    v::notEmpty()->setTemplate( 'O nome da entidade é obrigatório.' ),
                    v::stringType()->setTemplate( 'O nome deve ser um texto válido.' ),
                    v::length( 2, 100 )->setTemplate( 'O nome deve ter entre 2 e 100 caracteres.' ),
                ),
            ),
        );

        // Validação do campo 'slug' (opcional)
        $validator->addRule(
            new Key(
                'slug',
                v::optional(
                    new AllOf(
                        v::stringType()->setTemplate( 'O slug deve ser um texto válido.' ),
                        v::length( 2, 100 )->setTemplate( 'O slug deve ter entre 2 e 100 caracteres.' ),
                        v::regex( '/^[a-z0-9-]+$/' )->setTemplate( 'O slug deve conter apenas letras minúsculas, números e hífens.' ),
                    ),
                ),
            ),
        );

        // Validação do campo 'description' (opcional)
        $validator->addRule(
            new Key(
                'description',
                v::optional(
                    new AllOf(
                        v::stringType()->setTemplate( 'A descrição deve ser um texto válido.' ),
                        v::length( null, 500 )->setTemplate( 'A descrição deve ter no máximo 500 caracteres.' ),
                    ),
                ),
            ),
        );

        // Validação do campo 'active' (opcional)
        $validator->addRule(
            new Key(
                'active',
                v::optional(
                    v::in( [ '0', '1', 0, 1, true, false ] )->setTemplate( 'O status ativo deve ser verdadeiro ou falso.' ),
                ),
            ),
        );

        // Executar validação básica
        $request = new self();
        $request->setData( $data );

        try {
            $isValid = $validator->validate( $data );
        } catch ( \Exception $e ) {
            return false;
        }

        if ( !$isValid ) {
            return false;
        }

        // Validação adicional para atualização
        return self::validateTenantOwnership( $data, $expectedTenantId );
    }

    /**
     * Valida se o tenant_id fornecido corresponde ao esperado.
     *
     * IMPORTANTE: Método de segurança crucial para prevenir manipulação cross-tenant.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param int $expectedTenantId ID do tenant esperado
     * @return bool true se válido, false caso contrário
     */
    private static function validateTenantOwnership( array $data, int $expectedTenantId ): bool
    {
        // Se tenant_id está presente nos dados, deve corresponder ao esperado
        if ( isset( $data[ 'tenant_id' ] ) ) {
            $providedTenantId = (int) $data[ 'tenant_id' ];

            if ( $providedTenantId !== $expectedTenantId ) {
                // Log de segurança para tentativa de manipulação
                error_log(
                    "SECURITY VIOLATION: Tentativa de manipulação cross-tenant detectada. " .
                    "Esperado: {$expectedTenantId}, Fornecido: {$providedTenantId}. " .
                    "IP: " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' ) . ", " .
                    "User-Agent: " . ( $_SERVER[ 'HTTP_USER_AGENT' ] ?? 'unknown' )
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Define os dados para validação.
     *
     * Método auxiliar para permitir validação programática.
     *
     * @param array<string, mixed> $data Dados a serem definidos
     */
    private function setData( array $data ): void
    {
        // Simular request com dados fornecidos
        $_POST    = $data;
        $_REQUEST = $data;
    }

}

/*
EXEMPLOS DE USO:

// 1. Uso simples no Controller (método store)
public function store(): Response
{
    $tenant_id = $this->getTenantIdFromAuth();

    if ( !DesignPatternWithTenantFormRequest::validate( $this->request ) ) {
        return Redirect::redirect( '/admin/design-patterns-tenant/create' )
            ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
    }

    $data = $this->request->all();
    // Validação adicional de segurança é feita automaticamente
    // ... continuar com criação
}

// 2. Uso no método update com validação específica
public function update( string $id ): Response
{
    $tenant_id = $this->getTenantIdFromAuth();
    $data = $this->request->all();

    if ( !DesignPatternWithTenantFormRequest::validateForUpdate( $data, $tenant_id ) ) {
        return Redirect::redirect( "/admin/design-patterns-tenant/{$id}/edit" )
            ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
    }

    $result = $this->designPatternService->updateByIdAndTenantId( (int) $id, $tenant_id, $data, $authenticated );
    // ... continuar
}

// 3. Validação programática (sem Request object)
$data = [
    'tenant_id' => $tenant_id,
    'name' => 'Nova Entidade',
    'slug' => 'nova-entidade',
    'description' => 'Descrição da entidade',
    'active' => true
];

if ( DesignPatternWithTenantFormRequest::validateForCreate( $data, $tenant_id ) ) {
    // Dados válidos e seguros
    $result = $service->createByTenantId( $data, $tenant_id, $user );
} else {
    // Dados inválidos ou tentativa de manipulação
    throw new InvalidArgumentException( 'Dados inválidos ou violação de segurança detectada.' );
}

// 4. Validação específica para API endpoints
class DesignPatternApiController {
    public function store( Request $request ) {
        $tenant_id = $this->getTenantFromJWT( $request );
        $data = $request->json()->all();

        if ( !DesignPatternWithTenantFormRequest::validateForCreate( $data, $tenant_id ) ) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Invalid data or security violation detected'
            ], 422);
        }

        // Processar dados seguros
    }
}

// 5. Validação em background jobs/tasks
class ProcessEntityJob {
    public function handle( array $entityData, int $tenant_id ) {
        // Validar dados mesmo em background
        if ( !DesignPatternWithTenantFormRequest::validateForCreate( $entityData, $tenant_id ) ) {
            throw new InvalidArgumentException( 'Invalid entity data for background processing' );
        }

        // Processar com segurança
    }
}

BENEFÍCIOS DO PADRÃO WITHTENANT:

✅ SEGURANÇA MÁXIMA
- Validação obrigatória de tenant_id
- Detecção de tentativas de manipulação cross-tenant
- Log de violações de segurança
- Validação específica por contexto (create/update)

✅ FLEXIBILIDADE
- Métodos específicos para diferentes cenários
- Validação programática sem Request object
- Reutilização em APIs e background jobs
- Adaptação para diferentes fluxos de trabalho

✅ AUDITORIA INTEGRADA
- Log automático de violações de segurança
- Rastreamento de tentativas de acesso indevido
- Informações contextuais (IP, User-Agent)
- Compatibilidade com sistemas de monitoramento

✅ FACILIDADE DE USO
- Interface familiar com segurança adicional
- Mensagens de erro contextuais
- Validação transparente para desenvolvedores
- Integração simples com controllers existentes
*/
