<?php

namespace App\Services;

/**
 * OpenFinanceService
 *
 * Camada de abstração sobre o PluggyService para uso no ERP.
 * Responsável por: gerar connect tokens, sincronizar transações,
 * desconectar itens e normalizar dados para o modelo interno.
 */
class OpenFinanceService
{
    private PluggyService $pluggy;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->pluggy = new PluggyService($clientId, $clientSecret);
    }

    // ----------------------------------------------------------------
    // Connect Token (para o widget Pluggy Connect)
    // ----------------------------------------------------------------

    /**
     * Gera um connect token para iniciar o fluxo de conexão no frontend.
     * Se $itemId for fornecido, gera token para reconexão.
     */
    public function gerarConnectToken(?string $itemId = null): string
    {
        $options = [];
        if ($itemId) {
            $options['itemId'] = $itemId;
        }

        $resp = $this->pluggy->criarConnectToken($options);

        if (!empty($resp['error'])) {
            throw new \RuntimeException('Pluggy: ' . $resp['error']);
        }

        return $resp['accessToken'] ?? $resp['connectToken'] ?? '';
    }

    // ----------------------------------------------------------------
    // Item (conexão bancária)
    // ----------------------------------------------------------------

    /**
     * Busca informações de um item Pluggy.
     */
    public function getItem(string $itemId): array
    {
        $resp = $this->pluggy->getItem($itemId);
        if (!empty($resp['error'])) {
            throw new \RuntimeException('Pluggy getItem: ' . $resp['error']);
        }
        return $resp;
    }

    /**
     * Desconecta (remove) um item Pluggy.
     */
    public function desconectarItem(string $itemId): void
    {
        $resp = $this->pluggy->deleteItem($itemId);
        if (!empty($resp['error'])) {
            throw new \RuntimeException('Pluggy deleteItem: ' . $resp['error']);
        }
    }

    /**
     * Atualiza (força sincronização) de um item Pluggy.
     */
    public function atualizarItem(string $itemId): array
    {
        $resp = $this->pluggy->updateItem($itemId);
        if (!empty($resp['error'])) {
            throw new \RuntimeException('Pluggy updateItem: ' . $resp['error']);
        }
        return $resp;
    }

    // ----------------------------------------------------------------
    // Contas bancárias do item
    // ----------------------------------------------------------------

    /**
     * Lista as contas bancárias de um item.
     */
    public function listarContas(string $itemId): array
    {
        $resp = $this->pluggy->getAccounts($itemId);
        return $resp['results'] ?? [];
    }

    // ----------------------------------------------------------------
    // Sincronização de transações
    // ----------------------------------------------------------------

    /**
     * Importa transações de uma conta Pluggy e salva no modelo de movimentações.
     *
     * @param string $itemId           ID do item Pluggy
     * @param string $accountId        ID da conta Pluggy
     * @param int    $contaBancariaId  ID da conta bancária no ERP
     * @param int    $usuarioId        ID do usuário
     * @param object $movModel         Instância de ContaMovimentacao
     * @param string|null $from        Data início (YYYY-MM-DD), padrão: 90 dias atrás
     * @return array ['importadas' => int, 'duplicadas' => int, 'erros' => int]
     */
    public function sincronizarTransacoes(
        string $itemId,
        string $accountId,
        int $contaBancariaId,
        int $usuarioId,
        object $movModel,
        ?string $from = null
    ): array {
        // Força atualização do item antes de buscar transações
        try {
            $this->atualizarItem($itemId);
            // Aguarda 2s para o Pluggy processar
            sleep(2);
        } catch (\Exception $e) {
            // Não crítico — continua mesmo se o update falhar
        }

        $from = $from ?? date('Y-m-d', strtotime('-90 days'));

        $transacoes = $this->pluggy->importarTransacoes($accountId, $from);

        $importadas = 0;
        $duplicadas = 0;
        $erros      = 0;

        foreach ($transacoes as $t) {
            try {
                // Verifica duplicidade pelo hash (campo origem_hash no model)
                if ($movModel->existsByHash($contaBancariaId, $t['hash_transacao'])) {
                    $duplicadas++;
                    continue;
                }

                $movModel->create([
                    'conta_bancaria_id'   => $contaBancariaId,
                    'usuario_id'          => $usuarioId,
                    'tipo'                => $t['tipo'],
                    'valor'               => $t['tipo'] === 'debito' ? -abs($t['valor']) : abs($t['valor']),
                    'data_movimentacao'   => $t['data_movimentacao'],
                    'descricao'           => $t['descricao'],
                    'descricao_original'  => $t['descricao'],
                    'categoria'           => $t['categoria'] ?? null,
                    'origem'              => 'openfinance',
                    'origem_hash'         => $t['hash_transacao'],
                    'openfinance_tx_id'   => $t['fitid'] ?? null,
                    'openfinance_data'    => isset($t['dados_extras']) ? json_decode($t['dados_extras'], true) : null,
                    'conciliada'          => 0,
                ]);

                $importadas++;
            } catch (\Exception $e) {
                $erros++;
                error_log('[OpenFinanceService] Erro ao salvar transação: ' . $e->getMessage());
            }
        }

        return [
            'importadas' => $importadas,
            'duplicadas' => $duplicadas,
            'erros'      => $erros,
        ];
    }
}
