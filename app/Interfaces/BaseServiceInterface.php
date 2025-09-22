<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface base para todos os services do sistema
 *
 * Esta interface define o contrato fundamental que todos os services
 * devem implementar, estabelecendo a base para a hierarquia de services
 * do sistema Easy Budget. Define apenas o método de validação que é
 * comum a todos os tipos de service (com e sem tenant).
 */
interface BaseServiceInterface
{
    /**
     * Valida dados antes de criar ou atualizar uma entidade
     *
     * Este método é responsável por validar os dados de entrada conforme
     * as regras de negócio específicas de cada entidade. Deve verificar
     * campos obrigatórios, formatos, unicidade e outras validações
     * necessárias antes de permitir operações de CRUD.
     *
     * @param array $data Dados a serem validados em formato snake_case
     * @param bool $isUpdate Define se a validação é para atualização (true) ou criação (false)
     * @return ServiceResult Resultado da validação contendo sucesso/erro e dados validados
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult;
}
