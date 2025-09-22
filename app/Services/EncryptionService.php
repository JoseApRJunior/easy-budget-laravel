<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

use function config;

/**
 * Serviço utilitário para operações de criptografia e descriptografia.
 *
 * Este service migra funcionalidades do legacy EncryptionService e integra
 * com Laravel's Crypt facade para maior compatibilidade e segurança.
 * Mantém métodos legacy para transição suave e oferece métodos modernos
 * usando as funcionalidades nativas do Laravel.
 *
 * @package App\Services
 */
class EncryptionService
{
    /**
     * Chave de criptografia derivada da APP_KEY.
     */
    private string $key;

    /**
     * Algoritmo de criptografia usado nos métodos legacy.
     */
    private string $cipher = 'AES-256-CBC';

    /**
     * Construtor que inicializa a chave de criptografia.
     *
     * A chave é derivada da APP_KEY do Laravel para manter compatibilidade
     * com o sistema de configuração existente.
     *
     * @throws RuntimeException Se a APP_KEY não estiver definida
     */
    public function __construct()
    {
        $key = env('APP_KEY');
        if ( empty( $key ) ) {
            throw new RuntimeException( 'A chave de criptografia (APP_KEY) não está definida no arquivo de configuração.' );
        }

        // Garante que a chave tenha o comprimento correto para AES-256
        $this->key = substr( hash( 'sha256', $key, true ), 0, 32 );
    }

    // MÉTODOS MODERNOS COM LARAVEL CRYPT FACADE

    /**
     * Criptografa dados usando Laravel's Crypt facade.
     *
     * Este método oferece criptografia moderna e segura usando as
     * configurações padrão do Laravel. Recomendado para novos projetos.
     *
     * @param mixed $data Dados a serem criptografados (será serializado se necessário)
     * @return string Dados criptografados
     * @throws Exception Se a criptografia falhar
     */
    public function encryptLaravel( mixed $data ): string
    {
        try {
            return Crypt::encrypt( $data );
        } catch ( Exception $e ) {
            throw new Exception( 'Falha ao criptografar dados com Laravel Crypt: ' . $e->getMessage(), 0, $e );
        }
    }

    /**
     * Descriptografa dados usando Laravel's Crypt facade.
     *
     * Descriptografa dados que foram criptografados com encryptLaravel()
     * ou com métodos compatíveis do Laravel.
     *
     * @param string $encryptedData Dados criptografados
     * @return mixed Dados descriptografados
     * @throws Exception Se a descriptografia falhar
     */
    public function decryptLaravel( string $encryptedData ): mixed
    {
        try {
            return Crypt::decrypt( $encryptedData );
        } catch ( Exception $e ) {
            throw new Exception( 'Falha ao descriptografar dados com Laravel Crypt: ' . $e->getMessage(), 0, $e );
        }
    }

    /**
     * Criptografa string usando Laravel's Crypt facade.
     *
     * Versão específica para strings, retornando ServiceResult para
     * compatibilidade com o padrão do projeto.
     *
     * @param string $plaintext Texto plano a ser criptografado
     * @return ServiceResult Resultado da operação
     */
    public function encryptStringLaravel( string $plaintext ): ServiceResult
    {
        try {
            if ( empty( $plaintext ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Texto plano não pode estar vazio.' );
            }

            $encrypted = $this->encryptLaravel( $plaintext );
            return $this->success( [
                'encrypted' => $encrypted,
                'plaintext' => $plaintext
            ], 'Texto criptografado com sucesso usando Laravel Crypt.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao criptografar texto: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Descriptografa string usando Laravel's Crypt facade.
     *
     * Versão específica para strings, retornando ServiceResult para
     * compatibilidade com o padrão do projeto.
     *
     * @param string $encryptedData Dados criptografados
     * @return ServiceResult Resultado da operação
     */
    public function decryptStringLaravel( string $encryptedData ): ServiceResult
    {
        try {
            if ( empty( $encryptedData ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Dados criptografados não podem estar vazios.' );
            }

            $decrypted = $this->decryptLaravel( $encryptedData );
            return $this->success( [
                'decrypted' => $decrypted,
                'encrypted' => $encryptedData
            ], 'Texto descriptografado com sucesso usando Laravel Crypt.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao descriptografar texto: ' . $e->getMessage(), null, $e );
        }
    }

    // MÉTODOS LEGACY MIGRADOS (MANTÉM COMPATIBILIDADE)

    /**
     * Criptografa texto usando algoritmo AES-256-CBC (método legacy migrado).
     *
     * Este método mantém compatibilidade com o EncryptionService legacy,
     * permitindo transição suave para aplicações existentes.
     *
     * @param string $data Texto a ser criptografado
     * @return string|false Texto criptografado em base64 ou false se falhar
     */
    public function encrypt( string $data ): string|false
    {
        try {
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
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Descriptografa texto usando algoritmo AES-256-CBC (método legacy migrado).
     *
     * Compatível com dados criptografados pelo método encrypt() legacy.
     *
     * @param string $data Texto criptografado em base64
     * @return string|null Texto descriptografado ou null se falhar
     */
    public function decrypt( string $data ): ?string
    {
        try {
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
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Criptografa texto com ServiceResult (método legacy migrado).
     *
     * Versão do método encrypt() que retorna ServiceResult para
     * compatibilidade com o padrão do projeto.
     *
     * @param string $plaintext Texto plano a ser criptografado
     * @return ServiceResult Resultado da operação
     */
    public function encryptLegacy( string $plaintext ): ServiceResult
    {
        try {
            if ( empty( $plaintext ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Texto plano não pode estar vazio.' );
            }

            $encrypted = $this->encrypt( $plaintext );
            if ( $encrypted === false ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao criptografar texto.' );
            }

            return $this->success( [
                'encrypted' => $encrypted,
                'plaintext' => $plaintext
            ], 'Texto criptografado com sucesso (método legacy).' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao criptografar texto: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Descriptografa texto com ServiceResult (método legacy migrado).
     *
     * Versão do método decrypt() que retorna ServiceResult para
     * compatibilidade com o padrão do projeto.
     *
     * @param string $encryptedData Dados criptografados
     * @return ServiceResult Resultado da operação
     */
    public function decryptLegacy( string $encryptedData ): ServiceResult
    {
        try {
            if ( empty( $encryptedData ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Dados criptografados não podem estar vazios.' );
            }

            $decrypted = $this->decrypt( $encryptedData );
            if ( $decrypted === null ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao descriptografar texto: dados inválidos ou corrompidos.' );
            }

            return $this->success( [
                'decrypted' => $decrypted,
                'encrypted' => $encryptedData
            ], 'Texto descriptografado com sucesso (método legacy).' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao descriptografar texto: ' . $e->getMessage(), null, $e );
        }
    }

    // MÉTODOS UTILITÁRIOS

    /**
     * Verifica se uma string está criptografada com o método legacy.
     *
     * Útil para determinar se dados precisam ser migrados ou
     * se já estão no formato correto.
     *
     * @param string $data Dados a verificar
     * @return bool True se parece estar criptografado com método legacy
     */
    public function isLegacyEncrypted( string $data ): bool
    {
        try {
            // Tenta descriptografar - se conseguir, provavelmente é legacy
            $decoded = base64_decode( $data );
            if ( $decoded === false ) {
                return false;
            }

            $ivlen = openssl_cipher_iv_length( $this->cipher );
            return strlen( $decoded ) >= $ivlen + 32; // IV + HMAC + ciphertext
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Verifica se uma string está criptografada com Laravel Crypt.
     *
     * @param string $data Dados a verificar
     * @return bool True se parece estar criptografado com Laravel Crypt
     */
    public function isLaravelEncrypted( string $data ): bool
    {
        try {
            // Tenta descriptografar com Laravel - se conseguir, é do Laravel
            $this->decryptLaravel( $data );
            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Migra dados do formato legacy para Laravel Crypt.
     *
     * Útil para transição de dados existentes para o novo formato.
     *
     * @param string $legacyEncryptedData Dados criptografados no formato legacy
     * @return ServiceResult Resultado da migração
     */
    public function migrateFromLegacy( string $legacyEncryptedData ): ServiceResult
    {
        try {
            if ( empty( $legacyEncryptedData ) ) {
                return $this->error( OperationStatus::INVALID_DATA, 'Dados criptografados não podem estar vazios.' );
            }

            // Primeiro descriptografa no formato legacy
            $decrypted = $this->decrypt( $legacyEncryptedData );
            if ( $decrypted === null ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao descriptografar dados legacy.' );
            }

            // Depois criptografa no formato Laravel
            $laravelEncrypted = $this->encryptLaravel( $decrypted );

            return $this->success([
                'original'  => $legacyEncryptedData,
                'decrypted' => $decrypted,
                'migrated'  => $laravelEncrypted
            ], 'Dados migrados do formato legacy para Laravel Crypt com sucesso.');
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao migrar dados: ' . $e->getMessage(), null, $e );
        }
    }

    // MÉTODOS AUXILIARES PARA COMPATIBILIDADE COM SERVICERESULT

    /**
     * Cria um ServiceResult de sucesso.
     *
     * @param mixed $data Dados do resultado
     * @param string $message Mensagem de sucesso
     * @return ServiceResult
     */
    private function success( mixed $data = null, string $message = 'Operação realizada com sucesso.' ): ServiceResult
    {
        return new ServiceResult( OperationStatus::SUCCESS, $message, $data );
    }

    /**
     * Cria um ServiceResult de erro.
     *
     * @param OperationStatus $status Status da operação
     * @param string $message Mensagem de erro
     * @param mixed $data Dados adicionais (opcional)
     * @param Exception|null $exception Exceção original (opcional)
     * @return ServiceResult
     */
    private function error( OperationStatus $status, string $message, mixed $data = null, ?Exception $exception = null ): ServiceResult
    {
        return new ServiceResult( $status, $message, $data, $exception );
    }

}
