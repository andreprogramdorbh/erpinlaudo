<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model ConfigNfs
 *
 * Gerencia as configurações de emissão de NFS-e via Asaas (Portal Nacional).
 * Suporta dois layouts:
 *   - padrão:       envia apenas valor + data + campos fixos configurados aqui
 *   - personalizado: usa um JSON template com placeholders dinâmicos
 */
class ConfigNfs extends Model
{
    protected string $table = 'config_nfs';

    /**
     * Busca a configuração de NF-s de um tenant.
     * Se não existir, retorna um objeto com valores padrão.
     */
    public function findByUsuarioId(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE usuario_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $usuarioId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($row) {
            return $row;
        }

        // Retorna objeto com valores padrão se não houver config salva
        return (object) [
            'id'                     => null,
            'usuario_id'             => $usuarioId,
            'layout_tipo'            => 'padrao',
            'service_description'    => 'SERVIÇOS DE LAUDO',
            'observations'           => '',
            'municipal_service_name' => 'Serviços de Saúde / Radiologia',
            'municipal_service_code' => '',
            'municipal_service_id'   => '',
            'cnae'                   => '8640205',
            'deductions'             => 0.00,
            'retain_iss'             => 0,
            'iss_aliquota'           => 0.00,
            'pis_aliquota'           => 0.00,
            'cofins_aliquota'        => 0.00,
            'csll_aliquota'          => 0.00,
            'inss_aliquota'          => 0.00,
            'ir_aliquota'            => 0.00,
            'json_template'          => '',
            'emite_portal_nacional'  => 1,
            'serie_nf'               => '',
            'ativo'                  => 1,
        ];
    }

    /**
     * Salva ou atualiza a configuração de NF-s de um tenant.
     */
    public function upsert(int $usuarioId, array $data): bool
    {
        $existing = $this->pdo->prepare(
            "SELECT id FROM {$this->table} WHERE usuario_id = :uid LIMIT 1"
        );
        $existing->execute([':uid' => $usuarioId]);
        $row = $existing->fetch(\PDO::FETCH_OBJ);

        $fields = [
            'layout_tipo'            => $data['layout_tipo'] ?? 'padrao',
            'service_description'    => $data['service_description'] ?? 'SERVIÇOS DE LAUDO',
            'observations'           => $data['observations'] ?? '',
            'municipal_service_name' => $data['municipal_service_name'] ?? 'Serviços de Saúde / Radiologia',
            'municipal_service_code' => $data['municipal_service_code'] ?? '',
            'municipal_service_id'   => $data['municipal_service_id'] ?? '',
            'cnae'                   => $data['cnae'] ?? '8640205',
            'deductions'             => (float) ($data['deductions'] ?? 0),
            'retain_iss'             => (int) ($data['retain_iss'] ?? 0),
            'iss_aliquota'           => (float) ($data['iss_aliquota'] ?? 0),
            'pis_aliquota'           => (float) ($data['pis_aliquota'] ?? 0),
            'cofins_aliquota'        => (float) ($data['cofins_aliquota'] ?? 0),
            'csll_aliquota'          => (float) ($data['csll_aliquota'] ?? 0),
            'inss_aliquota'          => (float) ($data['inss_aliquota'] ?? 0),
            'ir_aliquota'            => (float) ($data['ir_aliquota'] ?? 0),
            'json_template'          => $data['json_template'] ?? '',
            'emite_portal_nacional'  => (int) ($data['emite_portal_nacional'] ?? 1),
            'serie_nf'               => $data['serie_nf'] ?? '',
        ];

        if ($row) {
            $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($fields)));
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET {$sets}, updated_at = CURRENT_TIMESTAMP WHERE usuario_id = :usuario_id"
            );
            $fields['usuario_id'] = $usuarioId;
            return $stmt->execute($fields);
        }

        $fields['usuario_id'] = $usuarioId;
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($fields)));
        $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($fields)));
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$vals})"
        );
        return $stmt->execute($fields);
    }

    /**
     * Monta o payload JSON para o Asaas com base na configuração salva.
     *
     * @param object $config     Configuração do tenant (resultado de findByUsuarioId)
     * @param float  $valor      Valor da conta a receber
     * @param string $dataEmissao Data de emissão (Y-m-d)
     * @param string $paymentId  ID do pagamento no Asaas (asaas_payment_id)
     * @param string $descricao  Descrição da conta (usado no layout personalizado)
     * @return array             Payload pronto para enviar ao Asaas
     */
    public function montarPayload(
        object $config,
        float  $valor,
        string $dataEmissao,
        string $paymentId = '',
        string $descricao = ''
    ): array {
        if ($config->layout_tipo === 'personalizado' && !empty($config->json_template)) {
            return $this->montarPayloadPersonalizado($config, $valor, $dataEmissao, $paymentId, $descricao);
        }

        return $this->montarPayloadPadrao($config, $valor, $dataEmissao, $paymentId, $descricao);
    }

    /**
     * Layout Padrão: envia campos fixos configurados + valor e data dinâmicos.
     */
    private function montarPayloadPadrao(
        object $config,
        float  $valor,
        string $dataEmissao,
        string $paymentId,
        string $descricao
    ): array {
        $payload = [
            'serviceDescription'   => $config->service_description ?: ($descricao ?: 'SERVIÇOS DE LAUDO'),
            'observations'         => $config->observations ?: ('NF-s emitida via portal. Ref: ' . $descricao),
            'value'                => $valor,
            'deductions'           => (float) ($config->deductions ?? 0),
            'effectiveDate'        => $dataEmissao,
            'municipalServiceName' => $config->municipal_service_name ?: 'Serviços de Saúde / Radiologia',
            'taxes'                => [
                'retainIss' => (bool) ($config->retain_iss ?? false),
                'iss'       => (float) ($config->iss_aliquota ?? 0),
                'cofins'    => (float) ($config->cofins_aliquota ?? 0),
                'csll'      => (float) ($config->csll_aliquota ?? 0),
                'inss'      => (float) ($config->inss_aliquota ?? 0),
                'ir'        => (float) ($config->ir_aliquota ?? 0),
                'pis'       => (float) ($config->pis_aliquota ?? 0),
            ],
        ];

        // Código de serviço municipal: ID tem prioridade sobre code
        if (!empty($config->municipal_service_id)) {
            $payload['municipalServiceId'] = $config->municipal_service_id;
        } elseif (!empty($config->municipal_service_code)) {
            $payload['municipalServiceCode'] = $config->municipal_service_code;
        }

        // CNAE (opcional mas recomendado para Portal Nacional)
        if (!empty($config->cnae)) {
            $payload['cnae'] = preg_replace('/\D/', '', $config->cnae);
        }

        // Vínculo com pagamento Asaas
        if (!empty($paymentId)) {
            $payload['payment'] = $paymentId;
        }

        return $payload;
    }

    /**
     * Layout Personalizado: usa o JSON template com substituição de placeholders.
     * Placeholders suportados:
     *   {{value}}         → valor numérico
     *   {{effectiveDate}} → data de emissão (Y-m-d)
     *   {{payment}}       → ID do pagamento Asaas
     *   {{descricao}}     → descrição da conta
     */
    private function montarPayloadPersonalizado(
        object $config,
        float  $valor,
        string $dataEmissao,
        string $paymentId,
        string $descricao
    ): array {
        $template = $config->json_template;

        // Substituir placeholders
        $template = str_replace('{{value}}',         (string) $valor,       $template);
        $template = str_replace('{{effectiveDate}}', $dataEmissao,           $template);
        $template = str_replace('{{payment}}',       $paymentId,             $template);
        $template = str_replace('{{descricao}}',     $descricao,             $template);
        $template = str_replace('{{dataHoje}}',      date('Y-m-d'),          $template);

        $payload = json_decode($template, true);

        if (!is_array($payload)) {
            // Fallback para padrão se o JSON for inválido
            return $this->montarPayloadPadrao($config, $valor, $dataEmissao, $paymentId, $descricao);
        }

        // Garantir que value e effectiveDate são dinâmicos mesmo no personalizado
        $payload['value']         = $valor;
        $payload['effectiveDate'] = $dataEmissao;

        // Adicionar payment se não estiver no template
        if (!empty($paymentId) && empty($payload['payment'])) {
            $payload['payment'] = $paymentId;
        }

        return $payload;
    }
}
