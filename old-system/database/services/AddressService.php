<?php

namespace app\database\services;

use app\database\repositories\AddressRepository;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço para gerenciar operações de endereços.
 *
 * Esta classe fornece métodos para buscar, criar, atualizar e deletar endereços,
 * seguindo o padrão de design do projeto com ServiceResult.
 *
 * @package app\database\services
 */
class AddressService
{
    private AddressRepository $addressRepository;

    public function __construct( AddressRepository $addressRepository )
    {
        $this->addressRepository = $addressRepository;
    }

    /**
     * Lista todos os endereços com paginação.
     *
     * @param int $limit Limite de resultados por página
     * @param int $offset Offset para paginação
     * @return ServiceResult Resultado da operação com dados paginados
     */
    public function listAddresses( int $limit = 20, int $offset = 0 ): ServiceResult
    {
        try {
            $result = $this->addressRepository->findAllPaginated( $limit, $offset );

            return ServiceResult::success(
                $result,
                'Endereços listados com sucesso.',
            );
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao listar endereços: ' . $e->getMessage()
            );
        }
    }

}
