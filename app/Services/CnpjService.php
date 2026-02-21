<?php

namespace App\Services;

class CnpjService
{
    /**
     * Consulta dados de um CNPJ na BrasilAPI.
     * 
     * @param string $cnpj
     * @return array|null
     */
    public function consultar(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return ['erro' => 'CNPJ inválido'];
        }

        $url = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return ['erro' => 'Serviço de consulta indisponível ou CNPJ não encontrado'];
    }
}
