<?php
namespace App\Controllers\Api\V1;

use App\Services\WhatsappLogger;

/**
 * Controller base para todos os endpoints da API do Bot WhatsApp.
 *
 * Fornece métodos utilitários para:
 *  - Responder em JSON padronizado
 *  - Obter o tenant_id injetado pelo middleware
 *  - Normalizar números de telefone
 *  - Registrar logs
 */
abstract class WhatsappBaseController
{
    protected WhatsappLogger $logger;
    protected int $tenantId;
    protected int $integracaoId;

    public function __construct()
    {
        $this->logger       = new WhatsappLogger();
        $this->tenantId     = (int) ($_REQUEST['_bot_tenant_id']     ?? 0);
        $this->integracaoId = (int) ($_REQUEST['_bot_integracao_id'] ?? 0);

        // Garante que o Content-Type seja JSON em todas as respostas
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Retorna uma resposta JSON de sucesso padronizada.
     */
    protected function success(string $message, array $data = [], int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Retorna uma resposta JSON de erro padronizada.
     */
    protected function error(string $message, int $code = 400, array $data = []): void
    {
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => $data ?: null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Normaliza um número de telefone para o formato E.164 sem o '+'.
     * Aceita formatos: +5511999998888, 5511999998888, (11) 99999-8888, etc.
     */
    protected function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Se não tiver DDI, assume Brasil (55)
        if (strlen($clean) <= 11) {
            $clean = '55' . $clean;
        }

        return $clean;
    }

    /**
     * Retorna uma expressão SQL compatível com MySQL 5.7+ para remover caracteres comuns de formatação
     * de telefones (sem depender de REGEXP_REPLACE, que só existe no MySQL 8+).
     *
     * Uso: $this->sqlDigitsExpr('c.telefone') => REPLACE(REPLACE(...c.telefone...),' ',''), ...
     */
    protected function sqlDigitsExpr(string $columnSql): string
    {
        // Não é um sanitizador genérico. Deve ser usado apenas com colunas fixas (ex: 'c.telefone').
        $expr = $columnSql;

        // Caracteres comuns em formatações brasileiras: +55 (11) 99999-8888, 11.9999-8888, etc.
        foreach ([' ', '(', ')', '-', '+', '.', '/', "\t", "\n", "\r"] as $char) {
            $expr = "REPLACE({$expr}, " . $this->pdoQuoteLiteral($char) . ", '')";
        }

        return $expr;
    }

    /**
     * Quote simples para literais SQL (somente para caracteres controlados localmente).
     */
    private function pdoQuoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Obtém o telefone do corpo da requisição (POST JSON ou form-data).
     */
    protected function getRequestPhone(): string
    {
        $body = $this->getRequestBody();
        $phone = $body['telefone'] ?? '';
        if (empty($phone)) {
            $this->error('O campo "telefone" é obrigatório.', 422);
        }
        return $this->normalizePhone($phone);
    }

    /**
     * Obtém o corpo da requisição como array.
     * Suporta JSON e form-data.
     */
    protected function getRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    /**
     * Formata um valor monetário para exibição (ex: 1500.50 -> "R$ 1.500,50")
     */
    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * Formata uma data do banco (Y-m-d) para exibição (d/m/Y)
     */
    protected function formatDate(?string $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : $date;
    }
}
