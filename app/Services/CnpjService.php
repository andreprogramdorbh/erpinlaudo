<?php

namespace App\Services;

use App\Core\Logger;

/**
 * CnpjService — Consulta dados de CNPJ com fallback entre múltiplas APIs.
 *
 * Ordem de tentativa:
 *   1. BrasilAPI  — https://brasilapi.com.br/api/cnpj/v1/{cnpj}
 *   2. ReceitaWS  — https://receitaws.com.br/v1/cnpj/{cnpj}
 *   3. CNPJ.ws    — https://publica.cnpj.ws/cnpj/{cnpj}
 *
 * Cada falha é registrada individualmente no log de erros.
 * Se todas as APIs falharem, o erro final também é registrado.
 */
class CnpjService
{
    private Logger $logger;

    /** Timeout por requisição em segundos */
    private int $timeout = 10;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Consulta um CNPJ tentando as APIs em sequência (fallback automático).
     *
     * @param  string $cnpj  CNPJ apenas com dígitos (14 chars)
     * @return array  Dados normalizados ou ['erro' => '...']
     */
    public function consultar(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return ['erro' => 'CNPJ inválido'];
        }

        $provedores = [
            'BrasilAPI' => fn() => $this->consultarBrasilApi($cnpj),
            'ReceitaWS' => fn() => $this->consultarReceitaWS($cnpj),
            'CNPJ.ws'   => fn() => $this->consultarCnpjWs($cnpj),
        ];

        $ultimoErro = 'Serviço de consulta de CNPJ indisponível no momento.';

        foreach ($provedores as $nome => $consultar) {
            try {
                $this->logger->info("CnpjService: tentando provedor {$nome}", ['cnpj' => $cnpj]);

                $resultado = $consultar();

                if (!empty($resultado) && !isset($resultado['erro'])) {
                    $this->logger->info("CnpjService: sucesso via {$nome}", [
                        'cnpj'         => $cnpj,
                        'razao_social' => $resultado['razao_social'] ?? 'N/A',
                    ]);
                    return $resultado;
                }

                $motivo = $resultado['erro'] ?? 'Resposta vazia ou inválida';
                $this->logger->warning("CnpjService: provedor {$nome} retornou erro", [
                    'cnpj'   => $cnpj,
                    'motivo' => $motivo,
                ]);
                $ultimoErro = $motivo;

            } catch (\Throwable $e) {
                $this->logger->error("CnpjService: exceção no provedor {$nome}", [
                    'cnpj'      => $cnpj,
                    'exception' => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
                $ultimoErro = $e->getMessage();
            }
        }

        // Todos os provedores falharam
        $this->logger->error('CnpjService: todos os provedores falharam', [
            'cnpj'        => $cnpj,
            'ultimo_erro' => $ultimoErro,
        ]);

        return ['erro' => 'Não foi possível consultar o CNPJ em nenhum dos serviços disponíveis. Tente novamente mais tarde ou preencha os dados manualmente.'];
    }

    // -------------------------------------------------------------------------
    // Provedores individuais
    // -------------------------------------------------------------------------

    /**
     * Provedor 1: BrasilAPI
     * Documentação: https://brasilapi.com.br/docs#tag/CNPJ
     */
    private function consultarBrasilApi(string $cnpj): array
    {
        $url      = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";
        $response = $this->httpGet($url);

        if ($response['http_code'] !== 200 || empty($response['body'])) {
            return ['erro' => "BrasilAPI retornou HTTP {$response['http_code']}"];
        }

        $data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return ['erro' => 'BrasilAPI: resposta JSON inválida'];
        }

        if (isset($data['message'])) {
            return ['erro' => 'BrasilAPI: ' . $data['message']];
        }

        return $this->normalizarBrasilApi($data);
    }

    /**
     * Provedor 2: ReceitaWS
     * Documentação: https://www.receitaws.com.br/
     * Limite: 3 req/min no plano gratuito — ideal como fallback.
     */
    private function consultarReceitaWS(string $cnpj): array
    {
        $url      = "https://receitaws.com.br/v1/cnpj/{$cnpj}";
        $response = $this->httpGet($url, ['Accept: application/json']);

        if ($response['http_code'] === 429) {
            return ['erro' => 'ReceitaWS: limite de requisições atingido'];
        }

        if ($response['http_code'] !== 200 || empty($response['body'])) {
            return ['erro' => "ReceitaWS retornou HTTP {$response['http_code']}"];
        }

        $data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return ['erro' => 'ReceitaWS: resposta JSON inválida'];
        }

        if (($data['status'] ?? '') === 'ERROR') {
            return ['erro' => 'ReceitaWS: ' . ($data['message'] ?? 'CNPJ não encontrado')];
        }

        return $this->normalizarReceitaWS($data);
    }

    /**
     * Provedor 3: CNPJ.ws (API pública, sem autenticação)
     * Documentação: https://www.cnpj.ws/
     */
    private function consultarCnpjWs(string $cnpj): array
    {
        $url      = "https://publica.cnpj.ws/cnpj/{$cnpj}";
        $response = $this->httpGet($url, ['Accept: application/json']);

        if ($response['http_code'] === 429) {
            return ['erro' => 'CNPJ.ws: limite de requisições atingido'];
        }

        if ($response['http_code'] !== 200 || empty($response['body'])) {
            return ['erro' => "CNPJ.ws retornou HTTP {$response['http_code']}"];
        }

        $data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return ['erro' => 'CNPJ.ws: resposta JSON inválida'];
        }

        return $this->normalizarCnpjWs($data);
    }

    // -------------------------------------------------------------------------
    // Normalizadores — traduzem cada formato de API para o padrão interno
    // -------------------------------------------------------------------------

    /**
     * Normaliza resposta da BrasilAPI para o formato interno do ERP.
     */
    private function normalizarBrasilApi(array $d): array
    {
        return [
            'razao_social'       => $d['razao_social'] ?? '',
            'nome_fantasia'      => $d['nome_fantasia'] ?? '',
            'email'              => $d['email'] ?? '',
            'cep'                => preg_replace('/\D/', '', $d['cep'] ?? ''),
            'endereco'           => $d['logradouro'] ?? '',
            'numero'             => $d['numero'] ?? '',
            'complemento'        => $d['complemento'] ?? '',
            'bairro'             => $d['bairro'] ?? '',
            'cidade'             => $d['municipio'] ?? '',
            'estado'             => $d['uf'] ?? '',
            'telefone'           => $this->formatarTelefone($d['ddd_telefone_1'] ?? ''),
            'cnae_principal'     => (string)($d['cnae_fiscal'] ?? ''),
            'descricao_cnae'     => $d['cnae_fiscal_descricao'] ?? '',
            'situacao_cadastral' => $d['descricao_situacao_cadastral'] ?? '',
            '_provedor'          => 'BrasilAPI',
        ];
    }

    /**
     * Normaliza resposta da ReceitaWS para o formato interno do ERP.
     */
    private function normalizarReceitaWS(array $d): array
    {
        $cnaeCod  = '';
        $cnaeDesc = '';
        if (!empty($d['atividade_principal'][0])) {
            $cnaeCod  = $d['atividade_principal'][0]['code'] ?? '';
            $cnaeDesc = $d['atividade_principal'][0]['text'] ?? '';
        }

        return [
            'razao_social'       => $d['nome'] ?? '',
            'nome_fantasia'      => $d['fantasia'] ?? '',
            'email'              => $d['email'] ?? '',
            'cep'                => preg_replace('/\D/', '', $d['cep'] ?? ''),
            'endereco'           => trim(($d['tipo_logradouro'] ?? '') . ' ' . ($d['logradouro'] ?? '')),
            'numero'             => $d['numero'] ?? '',
            'complemento'        => $d['complemento'] ?? '',
            'bairro'             => $d['bairro'] ?? '',
            'cidade'             => $d['municipio'] ?? '',
            'estado'             => $d['uf'] ?? '',
            'telefone'           => $this->formatarTelefone(($d['ddd'] ?? '') . ($d['telefone'] ?? '')),
            'cnae_principal'     => $cnaeCod,
            'descricao_cnae'     => $cnaeDesc,
            'situacao_cadastral' => $d['situacao'] ?? '',
            '_provedor'          => 'ReceitaWS',
        ];
    }

    /**
     * Normaliza resposta do CNPJ.ws para o formato interno do ERP.
     */
    private function normalizarCnpjWs(array $d): array
    {
        $est      = $d['estabelecimento'] ?? $d;
        $cnaeCod  = (string)($d['cnae_fiscal'] ?? '');
        $cnaeDesc = $d['cnae_fiscal_descricao'] ?? ($d['cnaes_secundarios'][0]['descricao'] ?? '');

        $telefone = '';
        if (!empty($est['telefone1'])) {
            $telefone = $this->formatarTelefone(($est['ddd1'] ?? '') . $est['telefone1']);
        }

        return [
            'razao_social'       => $d['razao_social'] ?? ($est['nome_fantasia'] ?? ''),
            'nome_fantasia'      => $est['nome_fantasia'] ?? '',
            'email'              => $est['email'] ?? '',
            'cep'                => preg_replace('/\D/', '', $est['cep'] ?? ''),
            'endereco'           => trim(($est['tipo_logradouro'] ?? '') . ' ' . ($est['logradouro'] ?? '')),
            'numero'             => $est['numero'] ?? '',
            'complemento'        => $est['complemento'] ?? '',
            'bairro'             => $est['bairro'] ?? '',
            'cidade'             => $est['cidade']['nome'] ?? ($est['municipio'] ?? ''),
            'estado'             => $est['estado']['sigla'] ?? ($est['uf'] ?? ''),
            'telefone'           => $telefone,
            'cnae_principal'     => $cnaeCod,
            'descricao_cnae'     => $cnaeDesc,
            'situacao_cadastral' => $est['situacao_cadastral'] ?? '',
            '_provedor'          => 'CNPJ.ws',
        ];
    }

    // -------------------------------------------------------------------------
    // Utilitários
    // -------------------------------------------------------------------------

    /**
     * Executa uma requisição GET com cURL.
     *
     * @param  string   $url
     * @param  string[] $headers  Headers HTTP adicionais
     * @return array{http_code: int, body: string, curl_error: string}
     */
    private function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'ERP-InLaudo/1.0 (+https://erp.inlaudo.com.br)',
            CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
        ]);

        $body      = curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->logger->error('CnpjService: erro cURL', [
                'url'        => $url,
                'curl_error' => $curlError,
            ]);
        }

        return [
            'http_code'  => $httpCode,
            'body'       => $body ?: '',
            'curl_error' => $curlError,
        ];
    }

    /**
     * Formata número de telefone bruto para (XX) XXXX-XXXX ou (XX) XXXXX-XXXX.
     */
    private function formatarTelefone(string $telefone): string
    {
        $tel = preg_replace('/\D/', '', $telefone);
        $len = strlen($tel);

        if ($len === 11) {
            return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
        }
        if ($len === 10) {
            return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
        }
        return $telefone;
    }
}
