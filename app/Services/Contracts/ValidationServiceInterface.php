<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\Support\ServiceResult;

/**
 * Interface ValidationServiceInterface
 *
 * Contrato para operações de Validação e Verificação de Regras.
 */
interface ValidationServiceInterface
{
    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     * * @throws \InvalidArgumentException Quando dados obrigatórios estão ausentes
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult;

    /**
     * Verifica se uma entidade existe com base nos critérios.
     */
    public function exists( array $criteria ): ServiceResult;

    /**
     * Valida regras de negócio específicas do domínio.
     */
    public function validateBusinessRules( array $data, array $context = [] ): ServiceResult;
}
