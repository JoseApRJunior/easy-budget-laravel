<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço de cache para armazenamento temporário de dados.
 */
class CacheService implements ServiceNoTenantInterface
{
    private string $cachePath;

    public function __construct()
    {
        $this->cachePath = BASE_PATH . '/storage/cache/';
        if ( !is_dir( $this->cachePath ) ) {
            mkdir( $this->cachePath, 0755, true );
        }
    }

    /**
     * Busca um item de cache pelo ID (chave).
     *
     * @param int $id ID do cache (será convertido para string)
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $key    = (string) $id;
            $cached = $this->get( $key );

            if ( $cached !== null ) {
                return ServiceResult::success( $cached, 'Item de cache encontrado.' );
            } else {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Item de cache não encontrado.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar item de cache: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todos os arquivos de cache.
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $cacheFiles = [];

            if ( is_dir( $this->cachePath ) ) {
                $files = scandir( $this->cachePath );
                if ( $files === false ) {
                    return ServiceResult::error( OperationStatus::ERROR, 'Erro ao ler diretório de cache.' );
                }

                foreach ( $files as $file ) {
                    if ( $file !== '.' && $file !== '..' && str_ends_with( $file, '.cache' ) ) {
                        $filePath = $this->cachePath . DIRECTORY_SEPARATOR . $file;

                        // Verificar se o arquivo existe antes de obter informações
                        if ( !file_exists( $filePath ) ) {
                            continue;
                        }

                        // Verificar se filesize retorna um valor válido
                        $fileSize = filesize( $filePath );
                        if ( $fileSize === false ) {
                            continue;
                        }

                        // Verificar se filemtime retorna um valor válido
                        $fileModified = filemtime( $filePath );
                        if ( $fileModified === false ) {
                            continue;
                        }

                        $cacheFiles[] = [ 
                            'file'     => $file,
                            'path'     => $filePath,
                            'size'     => $fileSize,
                            'modified' => $fileModified,
                        ];
                    }
                }
            }

            return ServiceResult::success( $cacheFiles, 'Arquivos de cache listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar cache: ' . $e->getMessage() );
        }
    }

    /**
     * Cria um novo item de cache.
     *
     * @param array<string, mixed> $data Dados do cache (deve conter 'key' e 'value')
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            if ( !isset( $data[ 'key' ] ) || !isset( $data[ 'value' ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Chave e valor são obrigatórios.' );
            }

            $ttl    = $data[ 'ttl' ] ?? 3600; // 1 hora por padrão
            $result = $this->put( $data[ 'key' ], $data[ 'value' ], $ttl );

            if ( $result ) {
                return ServiceResult::success( [ 
                    'key'   => $data[ 'key' ],
                    'value' => $data[ 'value' ],
                    'ttl'   => $ttl,
                ], 'Item de cache criado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar item de cache.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar item de cache: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um item de cache existente.
     *
     * @param int $id ID do cache (chave)
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            $key = (string) $id;

            if ( !isset( $data[ 'value' ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Valor é obrigatório para atualização.' );
            }

            $ttl    = $data[ 'ttl' ] ?? 3600; // 1 hora por padrão
            $result = $this->put( $key, $data[ 'value' ], $ttl );

            if ( $result ) {
                return ServiceResult::success( [ 
                    'key'   => $key,
                    'value' => $data[ 'value' ],
                    'ttl'   => $ttl,
                ], 'Item de cache atualizado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar item de cache.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar item de cache: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um item de cache.
     *
     * @param int $id ID do cache (chave)
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            $key  = (string) $id;
            $file = $this->getFilePath( $key );

            if ( file_exists( $file ) ) {
                if ( unlink( $file ) ) {
                    return ServiceResult::success( null, 'Item de cache removido com sucesso.' );
                } else {
                    return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover item de cache.' );
                }
            } else {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Item de cache não encontrado.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir item de cache: ' . $e->getMessage() );
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( !isset( $data[ 'key' ] ) ) {
            $errors[] = "Chave é obrigatória.";
        }

        if ( !isset( $data[ 'value' ] ) ) {
            $errors[] = "Valor é obrigatório.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Recupera um item do cache com suporte a callback.
     *
     * @param string $key Chave do cache
     * @param int $ttl Tempo de vida em segundos
     * @param callable|null $callback Função para gerar o valor se não existir
     * @return ServiceResult Resultado da operação
     */
    public function remember( string $key, int $ttl, ?callable $callback = null ): ServiceResult
    {
        try {
            $cached = $this->get( $key );

            if ( $cached !== null ) {
                return ServiceResult::success( $cached, 'Valor recuperado do cache.' );
            }

            if ( $callback !== null ) {
                $result = $callback();
                $this->put( $key, $result, $ttl );
                return ServiceResult::success( $result, 'Valor gerado e armazenado em cache.' );
            }

            return ServiceResult::error( OperationStatus::NOT_FOUND, 'Valor não encontrado no cache e nenhum callback fornecido.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao recuperar do cache: ' . $e->getMessage() );
        }
    }

    /**
     * Recupera um item do cache.
     *
     * @param string $key Chave do cache
     * @return mixed Dados do cache ou null se não encontrado/expirado
     */
    private function get( string $key ): mixed
    {
        $file = $this->getFilePath( $key );

        if ( !file_exists( $file ) ) {
            return null;
        }

        $fileContent = file_get_contents( $file );
        if ( $fileContent === false ) {
            // Se não foi possível ler o arquivo, tentar removê-lo
            @unlink( $file );
            return null;
        }

        $data = unserialize( $fileContent );

        // Verificar se a desserialização foi bem sucedida
        if ( $data === false ) {
            // Se a desserialização falhou, remover o arquivo corrompido
            @unlink( $file );
            return null;
        }

        // Verificar se expirou
        if ( $data[ 'expires' ] < time() ) {
            unlink( $file );
            return null;
        }

        return $data[ 'value' ];
    }

    /**
     * Armazena um item no cache.
     *
     * @param string $key Chave do cache
     * @param mixed $value Valor a ser armazenado
     * @param int $ttl Tempo de vida em segundos
     * @return bool Sucesso da operação
     */
    private function put( string $key, mixed $value, int $ttl ): bool
    {
        $data = [ 
            'value'   => $value,
            'expires' => time() + $ttl,
        ];

        $result = file_put_contents(
            $this->getFilePath( $key ),
            serialize( $data ),
        );

        // Verificar se a operação foi bem sucedida
        if ( $result === false ) {
            return false;
        }

        return true;
    }

    /**
     * Gera o caminho completo do arquivo de cache.
     *
     * @param string $key Chave do cache
     * @return string Caminho do arquivo
     */
    private function getFilePath( string $key ): string
    {
        return $this->cachePath . DIRECTORY_SEPARATOR . md5( $key ) . '.cache';
    }

}
