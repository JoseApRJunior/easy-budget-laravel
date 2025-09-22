<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço para criptografia de dados sensíveis.
 */
class EncryptionService implements ServiceNoTenantInterface
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        $key = env( 'APP_KEY' );
        if ( empty( $key ) ) {
            throw new Exception( 'A chave de criptografia (APP_KEY) não está definida no seu arquivo de ambiente.' );
        }
        // Garante que a chave tenha o comprimento correto para AES-256
        $hashedKey = hash( 'sha256', $key, true );
        if ( $hashedKey === false ) {
            throw new Exception( 'Falha ao gerar chave de criptografia.' );
        }
        $this->key = substr( $hashedKey, 0, 32 );
    }

    /**
     * Busca um item criptografado pelo ID.
     *
     * @param int $id ID do item
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método getById não aplicável para EncryptionService.' );
    }

    /**
     * Lista itens criptografados.
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método list não aplicável para EncryptionService.' );
    }

    /**
     * Cria um novo item criptografado.
     *
     * @param array<string, mixed> $data Dados para criptografar
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            if ( !isset( $data[ 'plaintext' ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Texto plano é obrigatório.' );
            }

            $encrypted = $this->encrypt( $data[ 'plaintext' ] );
            if ( $encrypted === false ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criptografar texto.' );
            }

            return ServiceResult::success( [ 
                'encrypted' => $encrypted,
                'plaintext' => $data[ 'plaintext' ]
            ], 'Texto criptografado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criptografar texto: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um item criptografado.
     *
     * @param int $id ID do item
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método update não aplicável para EncryptionService.' );
    }

    /**
     * Remove um item criptografado.
     *
     * @param int $id ID do item
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Método delete não aplicável para EncryptionService.' );
    }

    /**
     * Valida os dados de entrada.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( !isset( $data[ 'plaintext' ] ) || empty( $data[ 'plaintext' ] ) ) {
            $errors[] = "Texto plano é obrigatório.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Criptografa um texto.
     *
     * @param string $data Texto a ser criptografado
     * @return string|false Texto criptografado ou false se falhar
     */
    public function encrypt( string $data ): string|false
    {
        $ivlen = openssl_cipher_iv_length( $this->cipher );
        if ( $ivlen === false ) {
            return false;
        }

        $iv = openssl_random_pseudo_bytes( $ivlen );
        if ( $iv === false ) {
            return false;
        }

        $ciphertext_raw = openssl_encrypt( $data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv );
        if ( $ciphertext_raw === false ) {
            return false;
        }

        $hmac = hash_hmac( 'sha256', $ciphertext_raw, $this->key, true );
        if ( $hmac === false ) {
            return false;
        }

        return base64_encode( $iv . $hmac . $ciphertext_raw );
    }

    /**
     * Descriptografa um texto.
     *
     * @param string $data Texto criptografado
     * @return string|null Texto descriptografado ou null se falhar
     */
    public function decrypt( string $data ): ?string
    {
        $c = base64_decode( $data );
        if ( $c === false ) {
            return null;
        }

        $ivlen = openssl_cipher_iv_length( $this->cipher );
        if ( $ivlen === false ) {
            return null;
        }

        // Verificar se o comprimento do IV é válido
        if ( strlen( $c ) < $ivlen ) {
            return null;
        }

        $iv = substr( $c, 0, $ivlen );
        if ( $iv === false ) {
            return null;
        }

        // Verificar se há dados suficientes para o HMAC e ciphertext
        if ( strlen( $c ) < $ivlen + 32 ) {
            return null;
        }

        $hmac = substr( $c, $ivlen, 32 );
        if ( $hmac === false ) {
            return null;
        }

        $ciphertext_raw = substr( $c, $ivlen + 32 );
        if ( $ciphertext_raw === false ) {
            return null;
        }

        $original_plaintext = openssl_decrypt( $ciphertext_raw, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv );
        if ( $original_plaintext === false ) {
            return null;
        }

        $calcmac = hash_hmac( 'sha256', $ciphertext_raw, $this->key, true );
        if ( $calcmac === false ) {
            return null;
        }

        if ( hash_equals( $hmac, $calcmac ) ) {
            return $original_plaintext;
        }

        return null;
    }

}
