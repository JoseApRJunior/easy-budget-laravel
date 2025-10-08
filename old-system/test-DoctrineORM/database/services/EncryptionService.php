<?php

namespace app\database\services;

use RuntimeException;

class EncryptionService
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        $key = env('APP_KEY');
        if (empty($key)) {
            throw new RuntimeException('A chave de criptografia (APP_KEY) não está definida no seu arquivo de ambiente.');
        }
        // Garante que a chave tenha o comprimento correto para AES-256
        $this->key = substr(hash('sha256', $key, true), 0, 32);
    }

    public function encrypt(string $data): string
    {
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $this->key, true);

        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    public function decrypt(string $data): ?string
    {
        $c = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, 32);
        $ciphertext_raw = substr($c, $ivlen + 32);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $this->key, true);
        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        }

        return null;
    }

}
