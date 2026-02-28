<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use App\Services\WhatsappLogger;
use PDO;

/**
 * Controller base para todos os endpoints da API do Bot WhatsApp.
 *
 * Fornece mÃ©todos utilitÃ¡rios para:
 *  - Responder em JSON padronizado
 *  - Obter o tenant_id injetado pelo middleware
 *  - Normalizar nÃºmeros de telefone (incluindo variaÃ§Ãµes brasileiras)
 *  - Buscar cliente por telefone com mÃºltiplas variaÃ§Ãµes de formato
 *  - Registrar logs
 *
 * â”€â”€â”€ NormalizaÃ§Ã£o de telefone brasileiro â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 *
 * O WhatsApp (Baileys) envia o nÃºmero no formato E.164:
 *   +5531927466755  â†’  DDI=55, DDD=31, nÃºmero=927466755 (9 dÃ­gitos)
 *
 * O banco pode armazenar em qualquer formato:
 *   (31) 9274-6755   â†’  10 dÃ­gitos (DDD + 8 dÃ­gitos, sem o 9 extra)
 *   (31) 92746-6755  â†’  11 dÃ­gitos (DDD + 9 dÃ­gitos, com o 9)
 *   3192746755       â†’  10 dÃ­gitos sem DDI e sem 9
 *   31927466755      â†’  11 dÃ­gitos sem DDI, com 9
 *   5531927466755    â†’  13 dÃ­gitos com DDI e 9
 *
 * A soluÃ§Ã£o Ã© gerar TODAS as variaÃ§Ãµes e buscar qualquer uma no banco.
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

    // â”€â”€â”€ Respostas JSON â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

    // â”€â”€â”€ NormalizaÃ§Ã£o de telefone â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Normaliza um nÃºmero de telefone para apenas dÃ­gitos com DDI 55.
     * Aceita: +5511999998888, 5511999998888, (11) 99999-8888, etc.
     */
    protected function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Se nÃ£o tiver DDI (â‰¤ 11 dÃ­gitos), assume Brasil (55)
        if (strlen($clean) <= 11) {
            $clean = '55' . $clean;
        }

        return $clean;
    }

    /**
     * Retorna uma expressão SQL compatível com MySQL 5.7+/MariaDB para remover caracteres comuns
     * de formatação de telefone (sem depender de REGEXP_REPLACE, que pode não existir no banco).
     *
     * Atenção: use apenas com colunas fixas/known-safe (ex: 'c.telefone', 'c.celular').
     */
    protected function sqlDigitsExpr(string $columnSql): string
    {
        $expr = $columnSql;

        // Caracteres comuns em formatações brasileiras: +55 (31) 92746-6755, 31.9274-6755, etc.
        foreach ([' ', '(', ')', '-', '+', '.', '/', "\t", "\n", "\r"] as $char) {
            $expr = "REPLACE({$expr}, " . $this->quoteSqlLiteral($char) . ", '')";
        }

        return $expr;
    }

    private function quoteSqlLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Gera todas as variaÃ§Ãµes possÃ­veis do nÃºmero para busca no banco.
     *
     * Exemplo de entrada: "5531927466755" (WhatsApp com DDI)
     * VariaÃ§Ãµes geradas:
     *   5531927466755  â†’ com DDI, com 9 (13 dÃ­gitos)
     *   553192746755   â†’ com DDI, sem 9 (12 dÃ­gitos)
     *   31927466755    â†’ sem DDI, com 9 (11 dÃ­gitos)
     *   3192746755     â†’ sem DDI, sem 9 (10 dÃ­gitos)  â† FORMATO MAIS COMUM NO BANCO
     *   927466755      â†’ sÃ³ nÃºmero, com 9 (9 dÃ­gitos)
     *   92746755       â†’ sÃ³ nÃºmero, sem 9 (8 dÃ­gitos)
     *
     * @param string $digits NÃºmero jÃ¡ limpo (somente dÃ­gitos, com DDI 55)
     * @return string[] Array de variaÃ§Ãµes Ãºnicas com 8+ dÃ­gitos
     */
    protected function buildPhoneVariants(string $digits): array
    {
        $variants = [];

        // Remove DDI 55 se presente para trabalhar com DDD + nÃºmero
        $withoutDdi = $digits;
        if (str_starts_with($withoutDdi, '55') && strlen($withoutDdi) >= 12) {
            $withoutDdi = substr($withoutDdi, 2);
        }

        // Extrai DDD (2 dÃ­gitos) e nÃºmero local
        $ddd   = substr($withoutDdi, 0, 2);
        $local = substr($withoutDdi, 2); // Ex: 927466755 (9 dÃ­gitos) ou 92746755 (8 dÃ­gitos)

        // VariaÃ§Ã£o sem o 9 inicial (para nÃºmeros antigos de 8 dÃ­gitos)
        // Regra: se o nÃºmero local tem 9 dÃ­gitos e comeÃ§a com 9, remove o primeiro 9
        $localSem9 = $local;
        if (strlen($local) === 9 && str_starts_with($local, '9')) {
            $localSem9 = substr($local, 1);
        }

        // Monta todas as combinaÃ§Ãµes possÃ­veis
        $combos = [
            '55' . $ddd . $local,     // 5531927466755 (13 dÃ­gitos, DDI + DDD + 9 dÃ­gitos)
            '55' . $ddd . $localSem9, // 553192746755  (12 dÃ­gitos, DDI + DDD + 8 dÃ­gitos)
            $ddd . $local,            // 31927466755   (11 dÃ­gitos, DDD + 9 dÃ­gitos)
            $ddd . $localSem9,        // 3192746755    (10 dÃ­gitos, DDD + 8 dÃ­gitos) â† CASO DO BUG
            $local,                   // 927466755     (9 dÃ­gitos, sÃ³ nÃºmero com 9)
            $localSem9,               // 92746755      (8 dÃ­gitos, sÃ³ nÃºmero sem 9)
        ];

        // Remove duplicatas e variaÃ§Ãµes muito curtas (< 8 dÃ­gitos)
        foreach ($combos as $v) {
            if (strlen($v) >= 8 && !in_array($v, $variants, true)) {
                $variants[] = $v;
            }
        }

        return $variants;
    }

    /**
     * Busca o cliente pelo telefone no tenant correto.
     * Tenta todas as variaÃ§Ãµes de formato do nÃºmero para mÃ¡xima compatibilidade.
     *
     * @param string $telefoneNormalizado NÃºmero normalizado (somente dÃ­gitos, com DDI)
     * @return object|false Objeto com dados do cliente ou false se nÃ£o encontrado
     */
    protected function findClienteByPhone(string $telefoneNormalizado): object|false
    {
        $pdo      = Database::getInstance();
        $variants = $this->buildPhoneVariants($telefoneNormalizado);

        if (empty($variants)) {
            return false;
        }

        // Monta os placeholders para o IN (uma variaÃ§Ã£o por placeholder)
        $telefoneExpr = $this->sqlDigitsExpr('c.telefone');
        $celularExpr  = $this->sqlDigitsExpr('c.celular');

        // Monta os placeholders para o IN (uma variaÃ§Ã£o por placeholder).
        // Importante: nÃ£o reutilizar o mesmo placeholder nomeado na mesma query (alguns drivers/PDO geram HY093).
        $telPlaceholders = [];
        $celPlaceholders = [];
        $params          = [':tenant_id' => $this->tenantId];

        foreach ($variants as $i => $variant) {
            $telKey            = ':tel_' . $i;
            $celKey            = ':cel_' . $i;
            $telPlaceholders[] = $telKey;
            $celPlaceholders[] = $celKey;
            $params[$telKey]   = $variant;
            $params[$celKey]   = $variant;
        }

        $telInClause = implode(', ', $telPlaceholders);
        $celInClause = implode(', ', $celPlaceholders);

        /*
         * REPLACE remove toda formataÃ§Ã£o do banco (parÃªnteses, traÃ§os, espaÃ§os, +).
         * O IN com mÃºltiplas variaÃ§Ãµes garante que qualquer formato salvo seja encontrado.
         *
         * Nota: portal_clientes pode nÃ£o existir para todos os clientes.
         * Usamos LEFT JOIN para buscar clientes mesmo sem acesso ao portal,
         * mas priorizamos aqueles com portal ativo.
         */
        $sql = "
            SELECT c.id AS cliente_id, c.razao_social, c.nome_fantasia,
                   c.cpf_cnpj, c.telefone, c.celular,
                   pc.email, pc.ativo AS portal_ativo
            FROM clientes c
            LEFT JOIN portal_clientes pc ON pc.cliente_id = c.id AND pc.ativo = 1
            WHERE c.usuario_id = :tenant_id
              AND c.status = 'ativo'
              AND (
                  {$telefoneExpr} IN ({$telInClause})
                  OR {$celularExpr} IN ({$celInClause})
              )
            ORDER BY pc.ativo DESC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    // â”€â”€â”€ RequisiÃ§Ã£o â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * ObtÃ©m o telefone do corpo da requisiÃ§Ã£o (POST JSON ou form-data).
     */
    protected function getRequestPhone(): string
    {
        $body  = $this->getRequestBody();
        $phone = $body['telefone'] ?? '';
        if (empty($phone)) {
            $this->error('O campo "telefone" Ã© obrigatÃ³rio.', 422);
        }
        return $this->normalizePhone($phone);
    }

    /**
     * ObtÃ©m o corpo da requisiÃ§Ã£o como array.
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

    // â”€â”€â”€ FormataÃ§Ã£o â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Formata um valor monetÃ¡rio para exibiÃ§Ã£o (ex: 1500.50 â†’ "R$ 1.500,50")
     */
    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * Formata uma data do banco (Y-m-d) para exibiÃ§Ã£o (d/m/Y)
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
