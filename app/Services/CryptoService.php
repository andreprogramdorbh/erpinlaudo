<?php

namespace App\Services;

class CryptoService
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_BYTES = 12;
    private const TAG_BYTES = 16;

    private function getKey(): string
    {
        // Tenta obter as chaves de diferentes fontes em ordem de preferência
        $rawKey = getenv('APP_KEY') ?: getenv('APP_ENCRYPTION_KEY') ?: 
                  $_ENV['APP_KEY'] ?? $_ENV['APP_ENCRYPTION_KEY'] ?? '';
        
        // Debug: registrar tentativas de leitura (remover em produção)
        if (empty($rawKey)) {
            error_log('CryptoService: Tentando ler chaves de ambiente...');
            error_log('getenv(APP_KEY): ' . (getenv('APP_KEY') ? 'OK' : 'NULL'));
            error_log('getenv(APP_ENCRYPTION_KEY): ' . (getenv('APP_ENCRYPTION_KEY') ? 'OK' : 'NULL'));
            error_log('$_ENV[APP_KEY]: ' . (isset($_ENV['APP_KEY']) ? 'OK' : 'NULL'));
            error_log('$_ENV[APP_ENCRYPTION_KEY]: ' . (isset($_ENV['APP_ENCRYPTION_KEY']) ? 'OK' : 'NULL'));
        }
        
        if ($rawKey === '') {
            throw new \RuntimeException(
                'Criptografia não configurada. Defina APP_KEY ou APP_ENCRYPTION_KEY no arquivo .env. ' .
                'Exemplo: APP_KEY=base64:sua-chave-aqui-32-bytes'
            );
        }

        return hash('sha256', $rawKey, true);
    }

    /**
     * Verifica se a criptografia está configurada
     */
    public static function isConfigured(): bool
    {
        try {
            $instance = new self();
            $instance->getKey();
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function encryptString(string $plaintext): string
    {
        $key = $this->getKey();
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($ciphertext === false || $tag === '') {
            throw new \RuntimeException('Falha ao criptografar valor sensível.');
        }

        $payload = [
            'v' => 1,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ciphertext),
        ];

        return base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function decryptString(string $payloadB64): string
    {
        $key = $this->getKey();

        $json = base64_decode($payloadB64, true);
        if ($json === false) {
            throw new \RuntimeException('Payload criptografado inválido.');
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Payload criptografado inválido.');
        }

        $iv = base64_decode((string)($payload['iv'] ?? ''), true);
        $tag = base64_decode((string)($payload['tag'] ?? ''), true);
        $ct = base64_decode((string)($payload['ct'] ?? ''), true);

        if ($iv === false || $tag === false || $ct === false) {
            throw new \RuntimeException('Payload criptografado corrompido.');
        }

        $plaintext = openssl_decrypt(
            $ct,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Falha ao descriptografar valor sensível.');
        }

        return $plaintext;
    }
}
