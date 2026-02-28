<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use App\Services\WhatsappLogger;
use PDO;

/**
 * Controller base para todos os endpoints da API do Bot WhatsApp.
 *
 * Fornece métodos utilitários para:
 *  - Responder em JSON padronizado
 *  - Obter o tenant_id injetado pelo middleware
 *  - Normalizar números de telefone para o padrão E.164 sem '+'
 *  - Buscar cliente por telefone (busca direta + fallback com variações)
 *  - Registrar logs
 *
 * ─── Padrão de armazenamento de telefone ──────────────────────────────────────
 *
 * A partir de 2026-02-28, o ClientesController normaliza o telefone antes
 * de gravar no banco, sempre no formato E.164 sem '+' (DDI 55 incluído):
 *
 *   (31) 99274-6755  →  5531992746755  (13 dígitos: DDI+DDD+9dígitos)
 *   (31) 9274-6755   →  5531927466755  (13 dígitos: DDI+DDD+9 inserido)
 *
 * O WhatsApp (Baileys) também envia neste mesmo formato:
 *   +5531992746755  →  normalizado para  5531992746755
 *
 * Portanto a busca primária é DIRETA (igualdade exata).
 * O fallback com múltiplas variações mantém compatibilidade com registros
 * gravados antes da normalização.
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

    // ─── Respostas JSON ───────────────────────────────────────────────────────

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

    // ─── Normalização de telefone ─────────────────────────────────────────────

    /**
     * Normaliza um número de telefone para apenas dígitos com DDI 55.
     * Aceita: +5511999998888, 5511999998888, (11) 99999-8888, etc.
     */
    protected function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Se não tiver DDI (≤ 11 dígitos), assume Brasil (55)
        if (strlen($clean) <= 11) {
            $clean = '55' . $clean;
        }

        return $clean;
    }

    /**
     * Gera todas as variações possíveis do número para busca no banco.
     *
     * Exemplo de entrada: "5531927466755" (WhatsApp com DDI)
     * Variações geradas:
     *   5531927466755  → com DDI, com 9 (13 dígitos)
     *   553192746755   → com DDI, sem 9 (12 dígitos)
     *   31927466755    → sem DDI, com 9 (11 dígitos)
     *   3192746755     → sem DDI, sem 9 (10 dígitos)  ← FORMATO MAIS COMUM NO BANCO
     *   927466755      → só número, com 9 (9 dígitos)
     *   92746755       → só número, sem 9 (8 dígitos)
     *
     * @param string $digits Número já limpo (somente dígitos, com DDI 55)
     * @return string[] Array de variações únicas com 8+ dígitos
     */
    protected function buildPhoneVariants(string $digits): array
    {
        $variants = [];

        // Remove DDI 55 se presente para trabalhar com DDD + número
        $withoutDdi = $digits;
        if (str_starts_with($withoutDdi, '55') && strlen($withoutDdi) >= 12) {
            $withoutDdi = substr($withoutDdi, 2);
        }

        // Extrai DDD (2 dígitos) e número local
        $ddd   = substr($withoutDdi, 0, 2);
        $local = substr($withoutDdi, 2); // Ex: 927466755 (9 dígitos) ou 92746755 (8 dígitos)

        // Variação sem o 9 inicial (para números antigos de 8 dígitos)
        // Regra: se o número local tem 9 dígitos e começa com 9, remove o primeiro 9
        $localSem9 = $local;
        if (strlen($local) === 9 && str_starts_with($local, '9')) {
            $localSem9 = substr($local, 1);
        }

        // Monta todas as combinações possíveis
        $combos = [
            '55' . $ddd . $local,     // 5531927466755 (13 dígitos, DDI + DDD + 9 dígitos)
            '55' . $ddd . $localSem9, // 553192746755  (12 dígitos, DDI + DDD + 8 dígitos)
            $ddd . $local,            // 31927466755   (11 dígitos, DDD + 9 dígitos)
            $ddd . $localSem9,        // 3192746755    (10 dígitos, DDD + 8 dígitos) ← CASO DO BUG
            $local,                   // 927466755     (9 dígitos, só número com 9)
            $localSem9,               // 92746755      (8 dígitos, só número sem 9)
        ];

        // Remove duplicatas e variações muito curtas (< 8 dígitos)
        foreach ($combos as $v) {
            if (strlen($v) >= 8 && !in_array($v, $variants, true)) {
                $variants[] = $v;
            }
        }

        return $variants;
    }

    /**
     * Busca o cliente pelo telefone no tenant correto.
     *
     * Estratégia em duas etapas:
     *  1. Busca DIRETA: compara o número normalizado exatamente como está no banco.
     *     Funciona para todos os registros gravados após 2026-02-28 (com DDI 55).
     *  2. Fallback com VARIAÇÕES: gera múltiplas representações do número e
     *     busca qualquer uma delas. Garante compatibilidade com registros antigos.
     *
     * @param string $telefoneNormalizado Número normalizado (somente dígitos, com DDI 55)
     * @return object|false Objeto com dados do cliente ou false se não encontrado
     */
    protected function findClienteByPhone(string $telefoneNormalizado): object|false
    {
        $pdo = Database::getInstance();

        $baseQuery = "
            SELECT c.id AS cliente_id, c.razao_social, c.nome_fantasia,
                   c.cpf_cnpj, c.telefone, c.celular,
                   pc.email, pc.ativo AS portal_ativo
            FROM clientes c
            LEFT JOIN portal_clientes pc ON pc.cliente_id = c.id AND pc.ativo = 1
            WHERE c.usuario_id = :tenant_id
              AND c.status = 'ativo'
        ";

        // ─── Etapa 1: Busca direta (formato padronizado com DDI 55) ─────────────────
        // O banco agora armazena sempre com DDI 55 (ex: 5531992746755).
        // O WhatsApp também envia neste formato — a busca direta é suficiente.
        $sqlDireto = $baseQuery . "
              AND (
                  REGEXP_REPLACE(c.telefone, '[^0-9]', '') = :phone
                  OR REGEXP_REPLACE(c.celular,  '[^0-9]', '') = :phone
              )
            ORDER BY pc.ativo DESC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sqlDireto);
        $stmt->execute([':tenant_id' => $this->tenantId, ':phone' => $telefoneNormalizado]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        if ($result) {
            return $result;
        }

        // ─── Etapa 2: Fallback com variações (compatibilidade com registros antigos) ───
        // Registros gravados antes da normalização podem estar em outros formatos.
        $variants = $this->buildPhoneVariants($telefoneNormalizado);

        if (empty($variants)) {
            return false;
        }

        $placeholders = [];
        $params       = [':tenant_id' => $this->tenantId];

        foreach ($variants as $i => $variant) {
            $key            = ':phone_' . $i;
            $placeholders[] = $key;
            $params[$key]   = $variant;
        }

        $inClause = implode(', ', $placeholders);

        $sqlFallback = $baseQuery . "
              AND (
                  REGEXP_REPLACE(c.telefone, '[^0-9]', '') IN ({$inClause})
                  OR REGEXP_REPLACE(c.celular,  '[^0-9]', '') IN ({$inClause})
              )
            ORDER BY pc.ativo DESC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sqlFallback);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    // ─── Requisição ───────────────────────────────────────────────────────────

    /**
     * Obtém o telefone do corpo da requisição (POST JSON ou form-data).
     */
    protected function getRequestPhone(): string
    {
        $body  = $this->getRequestBody();
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
            $raw     = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    // ─── Formatação ───────────────────────────────────────────────────────────

    /**
     * Formata um valor monetário para exibição (ex: 1500.50 → "R$ 1.500,50")
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

    /**
     * Normaliza mensagens para log (1 linha, sem excesso de tamanho).
     */
    protected function safeLogMessage(string $message, int $limit = 200): string
    {
        $oneLine = trim(preg_replace('/\s+/', ' ', $message));
        if (strlen($oneLine) <= $limit) {
            return $oneLine;
        }
        return substr($oneLine, 0, $limit - 3) . '...';
    }
}
