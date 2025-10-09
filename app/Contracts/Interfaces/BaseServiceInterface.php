<?php
declare(strict_types=1);

namespace App\Contracts\Interfaces;

use App\Support\ServiceResult;

/**
 * Interface BaseServiceInterface
 *
 * Define o contrato para todas as classes de serviço no sistema.
 * Responsável por encapsular a lógica de negócios e orquestrar operações entre repositórios.
 */
interface BaseServiceInterface
{
    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult;
}
