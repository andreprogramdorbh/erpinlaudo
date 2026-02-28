<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use PDO;

/**
 * WhatsappNotasFiscaisController
 *
 * Endpoint: POST /api/v1/whatsapp/notas-fiscais
 *
 * Retorna as notas fiscais emitidas para o cliente identificado pelo telefone.
 *
 * Payload esperado:
 * {
 *   "telefone": "+5511999998888",
 *   "limite": 5  // opcional, padrão: 5, máximo: 10
 * }
 */
class WhatsappNotasFiscaisController extends WhatsappBaseController
{
    public function index(): void
    {
        $body     = $this->getRequestBody();
        $telefone = $this->getRequestPhone();
        $limite   = min((int) ($body['limite'] ?? 5), 10);
        $endpoint = '/api/v1/whatsapp/notas-fiscais';

        $cliente = $this->findClienteByPhone($telefone);
        if (!$cliente) {
            $this->logger->log($telefone, $endpoint, 'get_notas_fiscais', 'error', 'Cliente não encontrado', $this->tenantId, $this->integracaoId);
            $this->error('Cliente não encontrado para o telefone informado.', 404);
        }

        $clienteId = (int) $cliente->cliente_id;
        $pdo       = Database::getInstance();

        $stmt = $pdo->prepare(
            "SELECT nf.id, nf.numero_nf, nf.serie, nf.data_emissao,
                    nf.valor_total, nf.status, nf.descricao_servico,
                    nf.pdf_url, nf.xml_url
             FROM notas_fiscais nf
             WHERE nf.cliente_id = :cliente_id
               AND nf.usuario_id = :tenant_id
               AND nf.status IN ('emitida', 'importada')
             ORDER BY nf.data_emissao DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id',  $this->tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':limite',     $limite, PDO::PARAM_INT);
        $stmt->execute();
        $notas = $stmt->fetchAll(PDO::FETCH_OBJ);

        $notasFormatadas = [];
        foreach ($notas as $nota) {
            $notasFormatadas[] = [
                'id'                => (int) $nota->id,
                'numero'            => $nota->numero_nf ?? 'N/A',
                'serie'             => $nota->serie ?? '',
                'data_emissao'      => $this->formatDate($nota->data_emissao),
                'valor'             => $this->formatMoney((float) $nota->valor_total),
                'status'            => $nota->status,
                'descricao_servico' => $nota->descricao_servico ?? '',
                'pdf_url'           => !empty($nota->pdf_url) ? $nota->pdf_url : null,
                'xml_url'           => !empty($nota->xml_url) ? $nota->xml_url : null,
            ];
        }

        $total   = count($notasFormatadas);
        $summary = "{$total} nota(s) fiscal(is) encontrada(s)";

        $this->logger->log($telefone, $endpoint, 'get_notas_fiscais', 'success', $summary, $this->tenantId, $this->integracaoId);

        $this->success(
            $total > 0 ? "{$total} nota(s) fiscal(is) encontrada(s)." : 'Nenhuma nota fiscal encontrada.',
            [
                'cliente' => [
                    'nome'     => $cliente->razao_social ?? $cliente->nome_fantasia ?? 'N/A',
                    'cpf_cnpj' => $cliente->cpf_cnpj ?? '',
                ],
                'total'         => $total,
                'notas_fiscais' => $notasFormatadas,
            ]
        );
    }

    private function findClienteByPhone(string $telefoneNormalizado): object|false
    {
        $pdo        = Database::getInstance();
        $phoneShort = substr($telefoneNormalizado, -11);

        $telefoneExpr = $this->sqlDigitsExpr('c.telefone');
        $celularExpr  = $this->sqlDigitsExpr('c.celular');

        $stmt = $pdo->prepare(
            "SELECT pc.cliente_id, c.razao_social, c.nome_fantasia, c.cpf_cnpj
             FROM portal_clientes pc
             INNER JOIN clientes c ON c.id = pc.cliente_id
             WHERE c.usuario_id = :tenant_id
               AND pc.ativo = 1
               AND (
                   {$telefoneExpr} LIKE :phone_like
                   OR {$celularExpr} LIKE :phone_like
               )
             LIMIT 1"
        );
        $stmt->execute([':tenant_id' => $this->tenantId, ':phone_like' => '%' . $phoneShort]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }
}
