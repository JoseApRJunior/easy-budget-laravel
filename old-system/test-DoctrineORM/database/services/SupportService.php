<?php

namespace app\database\services;

use app\database\entitiesORM\SupportEntity;
use app\database\models\Support;
use app\interfaces\SupportServiceInterface;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class SupportService implements SupportServiceInterface
{
    public function __construct(
        private readonly Connection $connection,
        private Support $support,
        private NotificationService $notificationService,
    ) {}

    /**
     * Cria um novo registro de suporte.
     *
     * @param array<string, mixed> $data Dados do suporte.
     * @param object $authenticated Usuário autenticado.
     * @return array<string, mixed> Resultado da operação.
     */
    public function create( array $data, object $authenticated ): array
    {
        try {
            return $this->connection->transactional( function () use ($data, $authenticated) {

                $supportEntity = SupportEntity::create( [ 
                    'tenant_id'  => $authenticated->tenant_id ?? null,
                    'first_name' => $data[ 'first_name' ],
                    'last_name'  => $data[ 'last_name' ],
                    'email'      => $data[ 'email' ],
                    'subject'    => $data[ 'subject' ],
                    'message'    => $data[ 'message' ],
                ] );

                // cria o suporte
                $result = $this->support->create( $supportEntity );
                // verifica se o support foi criado com sucesso, se não, retorna false
                if ( $result[ 'status' ] === 'error' ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Erro ao criar o suporte.',
                    ];
                }

                // Email
                // Enviar e-mail de suporte
                /** @var SupportEntity $supportEntity */
                $sendEmail = $this->notificationService->sendSupportEmail( $supportEntity );

                if ( $sendEmail === false ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Erro ao enviar o email de suporte.',
                    ];
                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Suporte enviado com sucesso.' : 'Não foi possivel enviar o suporte.',
                    'data'    => [ 
                            'id'         => $result[ 'data' ][ 'id' ],
                            'first_name' => $data[ 'first_name' ],
                            'last_name'  => $data[ 'last_name' ],
                            'email'      => $data[ 'email' ],
                            'subject'    => $data[ 'subject' ],
                            'message'    => $data[ 'message' ],
                        ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao enviar o email de suporte, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

}
