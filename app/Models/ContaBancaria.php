<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ContaBancaria extends Model
{
    protected string $table = 'contas_bancarias';

    // -------------------------------------------------------
    // Leitura
    // -------------------------------------------------------

    public function findById(int $id): object|false
    {
        $sql = "SELECT cb.*, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} cb
                LEFT JOIN plano_contas pc ON pc.id = cb.plano_conta_id
                WHERE cb.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['cb.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        if (isset($filtros['ativa']) && $filtros['ativa'] !== '') {
            $where[]           = 'cb.ativa = :ativa';
            $params[':ativa']  = (int) $filtros['ativa'];
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[]      = '(cb.nome LIKE :q OR cb.banco_nome LIKE :q OR cb.conta LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT cb.*
                FROM {$this->table} cb
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cb.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findAtivas(int $usuarioId): array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE usuario_id = ? AND ativa = 1 ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findByOpenFinanceAccountId(string $accountId): object|false
    {
        $sql  = "SELECT * FROM {$this->table} WHERE openfinance_account_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$accountId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // -------------------------------------------------------
    // Resumo de saldos
    // -------------------------------------------------------

    public function getSaldoTotal(int $usuarioId): float
    {
        $sql  = "SELECT COALESCE(SUM(saldo_atual), 0) FROM {$this->table}
                 WHERE usuario_id = ? AND ativa = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        return (float) $stmt->fetchColumn();
    }

    // -------------------------------------------------------
    // Escrita
    // -------------------------------------------------------

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, nome, banco_codigo, banco_nome, banco_ispb, tipo,
                 agencia, agencia_digito, conta, conta_digito, titular, cpf_cnpj,
                 saldo_inicial, saldo_atual, moeda, cor, icone, ativa, observacoes)
                VALUES
                (:usuario_id, :nome, :banco_codigo, :banco_nome, :banco_ispb, :tipo,
                 :agencia, :agencia_digito, :conta, :conta_digito, :titular, :cpf_cnpj,
                 :saldo_inicial, :saldo_atual, :moeda, :cor, :icone, :ativa, :observacoes)";

        $stmt = $this->pdo->prepare($sql);
        $saldo = (float) str_replace(['.', ','], ['', '.'], $data['saldo_inicial'] ?? '0');

        $stmt->execute([
            ':usuario_id'     => (int) $data['usuario_id'],
            ':nome'           => trim($data['nome']),
            ':banco_codigo'   => $data['banco_codigo'] ?? null,
            ':banco_nome'     => $data['banco_nome'] ?? null,
            ':banco_ispb'     => $data['banco_ispb'] ?? null,
            ':tipo'           => $data['tipo'] ?? 'corrente',
            ':agencia'        => $data['agencia'] ?? null,
            ':agencia_digito' => $data['agencia_digito'] ?? null,
            ':conta'          => $data['conta'] ?? null,
            ':conta_digito'   => $data['conta_digito'] ?? null,
            ':titular'        => $data['titular'] ?? null,
            ':cpf_cnpj'       => $data['cpf_cnpj'] ?? null,
            ':saldo_inicial'  => $saldo,
            ':saldo_atual'    => $saldo,
            ':moeda'          => $data['moeda'] ?? 'BRL',
            ':cor'            => $data['cor'] ?? '#4361ee',
            ':icone'          => $data['icone'] ?? 'fas fa-university',
            ':ativa'          => isset($data['ativa']) ? (int) $data['ativa'] : 1,
            ':observacoes'    => $data['observacoes'] ?? null,
        ]);

        return $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'nome', 'banco_codigo', 'banco_nome', 'banco_ispb', 'tipo',
            'agencia', 'agencia_digito', 'conta', 'conta_digito',
            'titular', 'cpf_cnpj', 'saldo_inicial', 'saldo_atual',
            'moeda', 'cor', 'icone', 'ativa', 'observacoes',
            'openfinance_item_id', 'openfinance_account_id', 'openfinance_provider',
            'openfinance_last_sync', 'openfinance_status', 'openfinance_config',
            'openfinance_connector',
        ];

        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]          = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql  = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateSaldo(int $id, float $novoSaldo): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET saldo_atual = ? WHERE id = ?"
        );
        return $stmt->execute([$novoSaldo, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Lista de bancos brasileiros mais comuns para o select.
     * Ordenada por código ISPB/COMPE. Inclui fintechs e plataformas de pagamento.
     */
    public static function getBancosComuns(): array
    {
        return [
            // Bancos tradicionais
            ['codigo' => '001', 'nome' => 'Banco do Brasil'],
            ['codigo' => '003', 'nome' => 'Banco da Amazônia (BASA)'],
            ['codigo' => '004', 'nome' => 'Banco do Nordeste (BNB)'],
            ['codigo' => '021', 'nome' => 'Banestes'],
            ['codigo' => '025', 'nome' => 'Banco Alfa'],
            ['codigo' => '033', 'nome' => 'Santander'],
            ['codigo' => '036', 'nome' => 'Banco Bradesco BBI'],
            ['codigo' => '037', 'nome' => 'Banco do Estado do Pará (Banpara)'],
            ['codigo' => '041', 'nome' => 'Banrisul'],
            ['codigo' => '047', 'nome' => 'Banco do Estado de Sergipe (Banese)'],
            ['codigo' => '069', 'nome' => 'Crefisa'],
            ['codigo' => '070', 'nome' => 'BRB — Banco de Brasília'],
            ['codigo' => '077', 'nome' => 'Banco Inter'],
            ['codigo' => '084', 'nome' => 'Uniprime Norte do Paraná'],
            ['codigo' => '085', 'nome' => 'Cooperativa Central de Crédito Urbano (Cecred)'],
            ['codigo' => '089', 'nome' => 'Credisan Cooperativa de Crédito'],
            ['codigo' => '097', 'nome' => 'Credisis — Central de Cooperativas de Crédito'],
            ['codigo' => '099', 'nome' => 'Uniprime Central'],
            ['codigo' => '104', 'nome' => 'Caixa Econômica Federal'],
            ['codigo' => '107', 'nome' => 'Banco Bocom BBM'],
            ['codigo' => '136', 'nome' => 'Unicred do Brasil'],
            ['codigo' => '197', 'nome' => 'Stone Pagamentos'],
            ['codigo' => '208', 'nome' => 'BTG Pactual'],
            ['codigo' => '212', 'nome' => 'Banco Original'],
            ['codigo' => '218', 'nome' => 'Banco BS2'],
            ['codigo' => '237', 'nome' => 'Bradesco'],
            ['codigo' => '243', 'nome' => 'Banco Máxima'],
            ['codigo' => '246', 'nome' => 'Banco ABC Brasil'],
            ['codigo' => '249', 'nome' => 'Banco Investcred Unibanco'],
            ['codigo' => '254', 'nome' => 'Parana Banco'],
            ['codigo' => '260', 'nome' => 'Nu Pagamentos (Nubank)'],
            ['codigo' => '290', 'nome' => 'PagBank (PagSeguro)'],
            ['codigo' => '301', 'nome' => 'BPP Instituição de Pagamento'],
            ['codigo' => '318', 'nome' => 'Banco BMG'],
            ['codigo' => '323', 'nome' => 'Mercado Pago'],
            ['codigo' => '329', 'nome' => 'QI Sociedade de Crédito'],
            ['codigo' => '336', 'nome' => 'C6 Bank'],
            ['codigo' => '341', 'nome' => 'Itaú Unibanco'],
            ['codigo' => '364', 'nome' => 'Gerencianet Pagamentos do Brasil'],
            ['codigo' => '380', 'nome' => 'PicPay'],
            ['codigo' => '383', 'nome' => 'Ebanx'],
            ['codigo' => '389', 'nome' => 'Banco Mercantil do Brasil'],
            ['codigo' => '394', 'nome' => 'Banco Bradesco Financiamentos'],
            ['codigo' => '403', 'nome' => 'Cora Sociedade de Crédito Direto'],
            ['codigo' => '404', 'nome' => 'Sumup Sociedade de Crédito Direto'],
            ['codigo' => '408', 'nome' => 'Asaas Gestão Financeira'],
            ['codigo' => '422', 'nome' => 'Banco Safra'],
            ['codigo' => '461', 'nome' => 'Asaas IP S.A.'],
            ['codigo' => '473', 'nome' => 'Banco Caixa Geral — Brasil'],
            ['codigo' => '477', 'nome' => 'Citibank'],
            ['codigo' => '505', 'nome' => 'Banco Credit Suisse'],
            ['codigo' => '536', 'nome' => 'Neon Pagamentos'],
            ['codigo' => '556', 'nome' => 'MONEY PLUS SCMEPP LTDA'],
            ['codigo' => '600', 'nome' => 'Banco Luso Brasileiro'],
            ['codigo' => '604', 'nome' => 'Banco Industrial do Brasil'],
            ['codigo' => '611', 'nome' => 'Banco Paulista'],
            ['codigo' => '612', 'nome' => 'Banco Guanabara'],
            ['codigo' => '623', 'nome' => 'Banco Pan'],
            ['codigo' => '630', 'nome' => 'Banco Smartbank'],
            ['codigo' => '633', 'nome' => 'Banco Rendimento'],
            ['codigo' => '637', 'nome' => 'Banco Sofisa'],
            ['codigo' => '643', 'nome' => 'Banco Pine'],
            ['codigo' => '652', 'nome' => 'Itaú Unibanco Holding'],
            ['codigo' => '653', 'nome' => 'Banco Indusval'],
            ['codigo' => '655', 'nome' => 'Votorantim'],
            ['codigo' => '707', 'nome' => 'Banco Daycoval'],
            ['codigo' => '712', 'nome' => 'Banco Ourinvest'],
            ['codigo' => '735', 'nome' => 'Banco Neon'],
            ['codigo' => '741', 'nome' => 'Banco Ribeirão Preto'],
            ['codigo' => '743', 'nome' => 'Banco Semear'],
            ['codigo' => '745', 'nome' => 'Citibank N.A.'],
            ['codigo' => '746', 'nome' => 'Banco Modal'],
            ['codigo' => '747', 'nome' => 'Banco Rabobank International Brasil'],
            ['codigo' => '748', 'nome' => 'Sicredi'],
            ['codigo' => '751', 'nome' => 'Scotiabank Brasil'],
            ['codigo' => '752', 'nome' => 'BNP Paribas Brasil'],
            ['codigo' => '753', 'nome' => 'Novo Banco Continental'],
            ['codigo' => '754', 'nome' => 'Banco Sistema'],
            ['codigo' => '755', 'nome' => 'Bank of America Merrill Lynch'],
            ['codigo' => '756', 'nome' => 'Sicoob'],
            ['codigo' => '757', 'nome' => 'KEB Hana do Brasil'],
            ['codigo' => '999', 'nome' => 'Outro / Não listado'],
        ];
    }
}
