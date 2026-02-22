<?php

namespace App\Services;

use App\Core\Logger;

/**
 * CepService — Consulta endereço por CEP com fallback entre múltiplas APIs.
 *
 * Ordem de tentativa:
 *   1. ViaCEP    — https://viacep.com.br/ws/{cep}/json/
 *   2. BrasilAPI — https://brasilapi.com.br/api/cep/v2/{cep}
 *   3. OpenCEP   — https://opencep.com/v1/{cep}
 *
 * Cada falha é registrada individualmente no log de erros.
 * Se todas as APIs falharem, o erro final também é registrado.
 */
class CepService
{
    private Logger $logger;

    /** Timeout por requisição em segundos */
    private int $timeout = 8;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Consulta um CEP tentando as APIs em sequência (fallback automático).
     *
     * @param  string $cep  CEP com ou sem máscara
     * @return array  Dados de endereço normalizados ou ['erro' => '...']
     */
    public function consultar(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return ['erro' => 'CEP inválido. Informe um CEP com 8 dígitos.'];
        }

        $provedores = [
            'ViaCEP'    => fn() => $this->consultarViaCep($cep),
            'BrasilAPI' => fn() => $this->consultarBrasilApi($cep),
            'OpenCEP'   => fn() => $this->consultarOpenCep($cep),
        ];

        $ultimoErro = 'Serviço de consulta de CEP indisponível no momento.';

        foreach ($provedores as $nome => $consultar) {
            try {
                $this->logger->debug("CepService: tentando provedor {$nome}", ['cep' => $cep]);

                $resultado = $consultar();

                if (!empty($resultado) && !isset($resultado['erro'])) {
                    $this->logger->info("CepService: sucesso via {$nome}", [
                        'cep'      => $cep,
                        'logradouro' => $resultado['endereco'] ?? 'N/A',
                        'cidade'   => $resultado['cidade'] ?? 'N/A',
                    ]);
                    return $resultado;
                }

                $motivo = $resultado['erro'] ?? 'Resposta vazia ou inválida';
                $this->logger->warning("CepService: provedor {$nome} retornou erro", [
                    'cep'    => $cep,
                    'motivo' => $motivo,
                ]);
                $ultimoErro = $motivo;

            } catch (\Throwable $e) {
                $this->logger->error("CepService: exceção no provedor {$nome}", [
                    'cep'       => $cep,
                    'exception' => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
                $ultimoErro = $e->getMessage();
            }
        }

        // Todos os provedores falharam
        $this->logger->error('CepService: todos os provedores falharam', [
            'cep'         => $cep,
            'ultimo_erro' => $ultimoErro,
        ]);

        return ['erro' => 'Não foi possível consultar o CEP em nenhum dos serviços disponíveis. Verifique o número ou preencha o endereço manualmente.'];
    }

    // -------------------------------------------------------------------------
    // Provedores individuais
    // -------------------------------------------------------------------------

    /**
     * Provedor 1: ViaCEP
     * Documentação: https://viacep.com.br/
     */
    private function consultarViaCep(string $cep): array
    {
        $url      = "https://viacep.com.br/ws/{$cep}/json/";
        $response = $this->httpGet($url);

        if ($response['http_code'] !== 200 || empty($response['body'])) {
            return ['erro' => "ViaCEP retornou HTTP {$response['http_code']}"];
        }

        $data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            $this->logger->debug('ViaCEP: resposta JSON inválida ou vazia', ['body' => $response['body']]);
            return ['erro' => 'ViaCEP: resposta JSON inválida'];
        }

        // ViaCEP retorna {"erro": true} para CEPs não encontrados
        if (!empty($data['erro'])) {
            return ['erro' => 'CEP não encontrado na base ViaCEP'];
        }

        return $this->normalizarViaCep($data);
    }

    /**
     * Provedor 2: BrasilAPI (v2 — retorna mais detalhes)
     * Documentação: https://brasilapi.com.br/docs#tag/CEP
     */
    private function consultarBrasilApi(string $cep): array
    {
        $url      = "https://brasilapi.com.br/api/cep/v2/{$cep}";
        $response = $this->httpGet($url);

        if ($response['http_code'] === 404) {
            return ['erro' => 'BrasilAPI: CEP não encontrado'];
        }

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
     * Provedor 3: OpenCEP
     * Documentação: https://opencep.com/
     */
    private function consultarOpenCep(string $cep): array
    {
        $url      = "https://opencep.com/v1/{$cep}";
        $response = $this->httpGet($url);

        if ($response['http_code'] === 404) {
            return ['erro' => 'OpenCEP: CEP não encontrado'];
        }

        if ($response['http_code'] !== 200 || empty($response['body'])) {
            return ['erro' => "OpenCEP retornou HTTP {$response['http_code']}"];
        }

        $data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return ['erro' => 'OpenCEP: resposta JSON inválida'];
        }

        return $this->normalizarOpenCep($data);
    }

    // -------------------------------------------------------------------------
    // Normalizadores — traduzem cada formato de API para o padrão interno
    // -------------------------------------------------------------------------

    /**
     * Normaliza resposta da ViaCEP para o formato interno do ERP.
     */
    private function normalizarViaCep(array $d): array
    {
        return [
            'cep'        => preg_replace('/\D/', '', $d['cep'] ?? ''),
            'endereco'   => $d['logradouro'] ?? '',
            'complemento' => $d['complemento'] ?? '',
            'bairro'     => $d['bairro'] ?? '',
            'cidade'     => $d['localidade'] ?? '',
            'estado'     => $d['uf'] ?? '',
            'ibge'       => $d['ibge'] ?? '',
            '_provedor'  => 'ViaCEP',
        ];
    }

    /**
     * Normaliza resposta da BrasilAPI para o formato interno do ERP.
     */
    private function normalizarBrasilApi(array $d): array
    {
        return [
            'cep'        => preg_replace('/\D/', '', $d['cep'] ?? ''),
            'endereco'   => $d['street'] ?? '',
            'complemento' => '',
            'bairro'     => $d['neighborhood'] ?? '',
            'cidade'     => $d['city'] ?? '',
            'estado'     => $d['state'] ?? '',
            'ibge'       => $d['ibge'] ?? '',
            '_provedor'  => 'BrasilAPI',
        ];
    }

    /**
     * Normaliza resposta do OpenCEP para o formato interno do ERP.
     */
    private function normalizarOpenCep(array $d): array
    {
        return [
            'cep'        => preg_replace('/\D/', '', $d['cep'] ?? ''),
            'endereco'   => $d['logradouro'] ?? '',
            'complemento' => $d['complemento'] ?? '',
            'bairro'     => $d['bairro'] ?? '',
            'cidade'     => $d['localidade'] ?? '',
            'estado'     => $d['uf'] ?? '',
            'ibge'       => $d['ibge'] ?? '',
            '_provedor'  => 'OpenCEP',
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
            $this->logger->error('CepService: erro cURL', [
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
}
