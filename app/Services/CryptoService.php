<?php

namespace App\Services;

class CryptoService
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_BYTES = 12;
    private const TAG_BYTES = 16;

    private function getKey(): string
    {
        // Dotenv::createImmutable() popula $_ENV e $_SERVER, NÃO getenv()
        // Prioridade: $_ENV > $_SERVER > getenv() (fallback para ambientes sem Dotenv)
        $rawKey = $_ENV['APP_KEY']
            ?? $_ENV['APP_ENCRYPTION_KEY']
            ?? $_SERVER['APP_KEY']
            ?? $_SERVER['APP_ENCRYPTION_KEY']
            ?? (string) getenv('APP_KEY')
            ?? '';

        if ($rawKey === '') {
            throw new \RuntimeException(
                'Criptografia não configurada. Defina APP_KEY no arquivo .env. ' .
                'Exemplo: APP_KEY=minha-chave-secreta-de-32-caracteres'
            );
        }

        // Remove prefixo "base64:" se presente (padrão Laravel/gerado automaticamente)
        if (str_starts_with($rawKey, 'base64:')) {
            $rawKey = substr($rawKey, 7);
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
