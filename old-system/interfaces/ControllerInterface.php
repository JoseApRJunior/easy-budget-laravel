<?php

namespace app\interfaces;

use core\library\Response;
use http\Request;

/**
 * Interface para padronização dos controllers do sistema.
 *
 * Define os métodos essenciais que todos os controllers devem implementar,
 * garantindo consistência na arquitetura e facilitando a manutenção.
 *
 * @package app\interfaces
 * @author Easy Budget System
 * @since 1.0.0
 */
interface ControllerInterface
{
    /**
     * Registra uma atividade no sistema para auditoria.
     *
     * Este método é fundamental para rastreabilidade e compliance,
     * registrando todas as ações importantes realizadas pelos usuários.
     *
     * @param int $tenant_id ID do inquilino (tenant).
     * @param int $user_id ID do usuário que realizou a ação.
     * @param string $action_type Tipo de ação realizada (ex: 'created', 'updated', 'deleted').
     * @param string $entity_type Tipo da entidade relacionada (ex: 'customer', 'service', 'invoice').
     * @param int $entity_id ID da entidade relacionada.
     * @param string $description Descrição legível da atividade realizada.
     * @param array<string, mixed> $metadata Metadados adicionais da atividade (dados relevantes).
     *
     * @return void
     */
    public function activityLogger(
        int $tenant_id,
        int $user_id,
        string $action_type,
        string $entity_type,
        int $entity_id,
        string $description,
        array $metadata = [],
    ): void;

    /**
     * Valida se o usuário tem permissão para acessar o recurso.
     *
     * Método para verificação de autorização baseada em tenant,
     * garantindo que usuários só acessem dados de seu próprio tenant.
     *
     * @param int $tenant_id ID do tenant do recurso.
     * @param int $user_tenant_id ID do tenant do usuário autenticado.
     *
     * @return bool True se autorizado, false caso contrário.
     */
    public function validateTenantAccess( int $tenant_id, int $user_tenant_id ): bool;

    /**
     * Sanitiza e valida parâmetros de entrada.
     *
     * Método para garantir que todos os dados de entrada sejam
     * devidamente sanitizados antes do processamento.
     *
     * @param mixed $value Valor a ser sanitizado.
     * @param string $type Tipo esperado ('int', 'string', 'email', etc.).
     *
     * @return mixed Valor sanitizado.
     */
    public function sanitizeInput( mixed $value, string $type ): mixed;

    /**
     * Cria uma resposta padronizada de erro.
     *
     * Método para padronizar as respostas de erro em todo o sistema,
     * facilitando o tratamento no frontend e debugging.
     *
     * @param string $message Mensagem de erro.
     * @param int $code Código HTTP do erro (padrão: 400).
     * @param array<string, mixed> $data Dados adicionais do erro.
     *
     * @return Response Resposta formatada de erro.
     */
    public function errorResponse( string $message, int $code = 400, array $data = [] ): Response;

    /**
     * Cria uma resposta padronizada de sucesso.
     *
     * Método para padronizar as respostas de sucesso em todo o sistema,
     * mantendo consistência na estrutura de dados retornados.
     *
     * @param string $message Mensagem de sucesso.
     * @param array<string, mixed> $data Dados de retorno.
     * @param int $code Código HTTP de sucesso (padrão: 200).
     *
     * @return Response Resposta formatada de sucesso.
     */
    public function successResponse( string $message, array $data = [], int $code = 200 ): Response;

    /**
     * Valida se a requisição é do tipo esperado.
     *
     * Método para validar o método HTTP da requisição,
     * garantindo que apenas métodos permitidos sejam processados.
     *
     * @param Request $request Objeto da requisição.
     * @param array<string> $allowedMethods Métodos HTTP permitidos.
     *
     * @return bool True se o método é permitido, false caso contrário.
     */
    public function validateRequestMethod( Request $request, array $allowedMethods ): bool;

    /**
     * Registra logs de erro de forma padronizada.
     *
     * Método para logging consistente de erros em todo o sistema,
     * facilitando debugging e monitoramento.
     *
     * @param \Throwable $exception Exceção capturada.
     * @param string $context Contexto onde o erro ocorreu.
     * @param array<string, mixed> $additionalData Dados adicionais para o log.
     *
     * @return void
     */
    public function logError( \Throwable $exception, string $context, array $additionalData = [] ): void;
}
