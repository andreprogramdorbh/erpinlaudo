<?php

namespace App\Models;

use App\Core\Model;
use App\Services\CryptoService;
use PDO;

class Integracao extends Model
{
    protected string $table = "integracoes";

    private function decryptIfNeeded(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $crypto = new CryptoService();
            return $crypto->decryptString($value);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function encryptIfNeeded(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $crypto = new CryptoService();
        return $crypto->encryptString($value);
    }

    public function findByNomeAndUsuarioId(string $nome, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE nome = :nome AND usuario_id = :usuario_id LIMIT 1");
        $stmt->execute([
            ':nome' => $nome,
            ':usuario_id' => $usuarioId,
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function upsertConfigJson(string $nome, int $usuarioId, array $data): bool
    {
        $existing = $this->findByNomeAndUsuarioId($nome, $usuarioId);

        $tipo = $data['tipo'] ?? 'API';
        $status = $data['status'] ?? 'ativo';
        $config = $data['config'] ?? [];

        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
        if ($configJson === false) {
            return false;
        }

        if ($existing) {
            $sql = "UPDATE {$this->table} 
                    SET tipo = :tipo, status = :status, config_json = :config_json, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':tipo' => $tipo,
                ':status' => $status,
                ':config_json' => $configJson,
                ':id' => (int) $existing->id,
                ':usuario_id' => $usuarioId,
            ]);
        }

        $sql = "INSERT INTO {$this->table} (usuario_id, nome, tipo, status, config_json)
                VALUES (:usuario_id, :nome, :tipo, :status, :config_json)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':status' => $status,
            ':config_json' => $configJson,
        ]);
    }

    public function getDecodedConfig(object $row): array
    {
        $config = [];
        if (!empty($row->config_json)) {
            $decoded = json_decode((string) $row->config_json, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
        return $config;
    }

    public function findByProvider(string $provider, ?int $usuarioId = null): object|false
    {
        $usuarioId = $usuarioId ?? (int)($_SESSION['user_id'] ?? 0);
        if ($usuarioId <= 0) {
            return false;
        }

        $row = $this->findByNomeAndUsuarioId($provider, $usuarioId);
        if (!$row) {
            return false;
        }

        $config = $this->getDecodedConfig($row);

        if (!empty($config['api_key_enc'])) {
            $row->api_key = $this->decryptIfNeeded((string) $config['api_key_enc']);
        } else {
            $row->api_key = $config['api_key'] ?? '';
        }

        if (!empty($config['webhook_token_enc'])) {
            $row->webhook_token = $this->decryptIfNeeded((string) $config['webhook_token_enc']);
        } else {
            $row->webhook_token = $config['webhook_token'] ?? '';
        }

        $row->environment = $config['environment'] ?? 'sandbox';

        $status = $row->status ?? 'ativo';
        $row->status = ($status === 'inativo') ? 'inactive' : 'active';
        $row->last_test_at = $config['last_test_at'] ?? null;

        return $row;
    }

    public function updateConfig(string $provider, array $data)
    {
        $usuarioId = (int)($_SESSION['user_id'] ?? 0);
        if ($usuarioId <= 0) {
            return false;
        }

        $existing = $this->findByNomeAndUsuarioId($provider, $usuarioId);
        $existingConfig = $existing ? $this->getDecodedConfig($existing) : [];

        $apiKey = $data['api_key'] ?? '';

        if ($apiKey === '********') {
            if (!empty($existingConfig['api_key_enc'])) {
                $apiKeyEnc = (string) $existingConfig['api_key_enc'];
            } elseif (!empty($existingConfig['api_key'])) {
                $apiKeyEnc = $this->encryptIfNeeded((string) $existingConfig['api_key']);
            } else {
                $apiKeyEnc = '';
            }
        } else {
            $apiKeyEnc = $this->encryptIfNeeded((string) $apiKey);
        }

        $webhookToken = (string)($data['webhook_token'] ?? '');
        if ($webhookToken === '' && !empty($existingConfig['webhook_token_enc'])) {
            $webhookTokenEnc = (string) $existingConfig['webhook_token_enc'];
        } else {
            $webhookTokenEnc = $webhookToken !== '' ? $this->encryptIfNeeded($webhookToken) : '';
        }

        $config = array_merge($existingConfig, [
            'api_key_enc' => $apiKeyEnc,
            'webhook_token_enc' => $webhookTokenEnc,
            'environment' => $data['environment'] ?? 'sandbox',
        ]);

        unset($config['api_key'], $config['webhook_token']);

        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inativo' : 'ativo';

        return $this->upsertConfigJson($provider, $usuarioId, [
            'tipo' => 'Financeira',
            'status' => $status,
            'config' => $config,
        ]);
    }

    public function updateLastTest(string $provider)
    {
        $usuarioId = (int)($_SESSION['user_id'] ?? 0);
        if ($usuarioId <= 0) {
            return false;
        }

        $existing = $this->findByNomeAndUsuarioId($provider, $usuarioId);
        if (!$existing) {
            return false;
        }

        $config = $this->getDecodedConfig($existing);
        $config['last_test_at'] = date('Y-m-d H:i:s');

        return $this->upsertConfigJson($provider, $usuarioId, [
            'tipo' => $existing->tipo ?? 'API',
            'status' => $existing->status ?? 'ativo',
            'config' => $config,
        ]);
    }
}
