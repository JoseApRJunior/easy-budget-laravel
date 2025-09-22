<?php

namespace app\controllers;

use app\controllers\AbstractController;
use app\database\servicesORM\AddressService;
use app\database\repositories\AddressRepository;
use Exception;

/**
 * Controlador para gerenciar operações de endereços.
 *
 * Esta classe fornece endpoints para buscar, criar, atualizar e deletar endereços,
 * seguindo o padrão de design do projeto.
 *
 * @package app\controllers
 */
class AddressController extends AbstractController
{
    private AddressService $addressService;

    public function __construct()
    {
        // Inicializar repositório e serviço
        $addressRepository = new AddressRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata( '\app\database\entitiesORM\AddressEntity' ),
        );

        $this->addressService = new AddressService( $addressRepository );
    }

    /**
     * Lista todos os endereços com paginação.
     *
     * @param array<string, mixed> $data Dados da requisição
     * @return array<string, mixed> Resposta formatada
     */
    public function list( array $data ): array
    {
        try {
            $limit  = (int) ( $data[ 'limit' ] ?? 20 );
            $page   = (int) ( $data[ 'page' ] ?? 1 );
            $offset = ( $page - 1 ) * $limit;

            $serviceResult = $this->addressService->listAddresses( $limit, $offset );

            if ( $serviceResult->isSuccess() ) {
                return [ 
                    'success' => true,
                    'message' => $serviceResult->message,
                    'data'    => $serviceResult->data
                ];
            } else {
                return [ 
                    'success' => false,
                    'message' => $serviceResult->message,
                    'data'    => null
                ];
            }
        } catch ( Exception $e ) {
            return [ 
                'success' => false,
                'message' => 'Falha ao listar endereços: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

}
