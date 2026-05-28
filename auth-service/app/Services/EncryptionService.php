<?php

namespace App\Services;

use Exception;

class EncryptionService
{
    protected string $key;
    protected string $method = 'aes-256-cbc';

    public function __construct()
    {
        $this->key = config('app.internal_encryption_key', env('INTERNAL_ENCRYPTION_KEY'));
        
        if (strlen($this->key) !== 32) {
            throw new Exception('Internal encryption key must be exactly 32 characters.');
        }
    }

    /**
     * Decrypt data received from frontend or other services.
     * Expects: base64(iv + ciphertext)
     */
    public function decrypt(string $payload): ?string
    {
        try {
            $data = base64_decode($payload);
            $ivSize = openssl_cipher_iv_length($this->method);
            
            if (strlen($data) <= $ivSize) {
                return null;
            }

            $iv = substr($data, 0, $ivSize);
            $ciphertext = substr($data, $ivSize);

            $decrypted = openssl_decrypt(
                $ciphertext,
                $this->method,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            return $decrypted ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt data for outgoing communications.
     * Returns: base64(iv + ciphertext)
     */
    public function encrypt(string $plaintext): string
    {
        $ivSize = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivSize);

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $ciphertext);
    }
}
