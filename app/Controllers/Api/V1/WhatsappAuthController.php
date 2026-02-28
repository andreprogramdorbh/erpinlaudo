<?php
namespace App\Controllers\Api\V1;

/**
 * WhatsappAuthController
 *
 * Endpoint: POST /api/v1/whatsapp/identificar
 *
 * Identifica o cliente pelo número de telefone do WhatsApp.
 * A lógica de normalização e busca está centralizada em WhatsappBaseController::findClienteByPhone().
 *
 * Payload esperado:
 * {
 *   "telefone": "+5531927466755"
 * }
 *
 * Resposta de sucesso:
 * {
 *   "status": "success",
 *   "message": "Cliente identificado com sucesso.",
 *   "data": {
 *     "cliente_id": 42,
 *     "nome": "Empresa Exemplo LTDA",
 *     "cpf_cnpj": "12.345.678/0001-99",
 *     "email": "contato@empresa.com"
 *   }
 * }
 */
class WhatsappAuthController extends WhatsappBaseController
{
    public function identificar(): void
    {
        $telefone = $this->getRequestPhone();
        $endpoint = '/api/v1/whatsapp/identificar';

        $cliente = $this->findClienteByPhone($telefone);

        if (!$cliente) {
            $this->logger->log(
                $telefone, $endpoint, 'identificar',
                'error', 'Cliente não encontrado',
                $this->tenantId, $this->integracaoId
            );
            $this->error(
                'Cliente não encontrado para o telefone informado. Verifique o número ou entre em contato com o suporte.',
                404
            );
        }

        $this->logger->log(
            $telefone, $endpoint, 'identificar',
            'success', "Cliente ID={$cliente->cliente_id} identificado",
            $this->tenantId, $this->integracaoId
        );

        $this->success('Cliente identificado com sucesso.', [
            'cliente_id' => (int) $cliente->cliente_id,
            'nome'       => $cliente->razao_social ?? $cliente->nome_fantasia ?? 'N/A',
            'cpf_cnpj'   => $cliente->cpf_cnpj ?? '',
            'email'      => $cliente->email ?? '',
        ]);
    }
}
