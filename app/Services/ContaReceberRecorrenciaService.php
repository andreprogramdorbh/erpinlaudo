<?php

namespace App\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Logger;
use App\Models\ContaReceber;

class ContaReceberRecorrenciaService
{
    private ContaReceber $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new ContaReceber();
        $this->logger = new Logger();
    }

    public function gerarProximaSeRecorrente(int $usuarioId, int $contaReceberId): void
    {
        try {
            $conta = $this->model->findById($contaReceberId);
            if (!$conta || (int)($conta->usuario_id ?? 0) !== (int)$usuarioId) {
                return;
            }

            if ((int)($conta->recorrente ?? 0) !== 1) {
                return;
            }

            if (($conta->status ?? '') !== 'recebida') {
                return;
            }

            $tipo = (string)($conta->recorrencia_tipo ?? '');
            if ($tipo === '') {
                return;
            }

            $intervalo = (int)($conta->recorrencia_intervalo ?? 0);
            if ($intervalo <= 0) {
                $intervalo = 1;
            }

            if ($tipo === 'customizada' && $intervalo <= 0) {
                return;
            }

            $dataBase = (string)($conta->data_vencimento ?? '');
            if ($dataBase === '') {
                return;
            }

            $proximoVenc = $this->calcularProximoVencimento($dataBase, $tipo, $intervalo);
            if ($proximoVenc === '') {
                return;
            }

            $rootId = $this->extrairIntTag((string)($conta->external_reference ?? ''), 'root');
            if ($rootId <= 0) {
                $rootId = (int)$contaReceberId;
            }

            if ($this->model->existsRecorrenciaGerada($usuarioId, $contaReceberId, $rootId, $proximoVenc)) {
                return;
            }

            $novoId = $this->model->create([
                'usuario_id' => $usuarioId,
                'cliente_id' => (int)($conta->cliente_id ?? 0),
                'plano_conta_id' => (int)($conta->plano_conta_id ?? 0),
                'descricao' => (string)($conta->descricao ?? ''),
                'valor' => (string)($conta->valor ?? '0.00'),
                'data_vencimento' => $proximoVenc,
                'data_recebimento' => null,
                'status' => 'aberta',
                'observacoes' => $conta->observacoes ?? null,
                'meio_pagamento' => $conta->meio_pagamento ?? null,
                'recorrente' => 1,
                'recorrencia_tipo' => $tipo,
                'recorrencia_intervalo' => $intervalo,
                'asaas_payment_id' => null,
                'asaas_subscription_id' => $conta->asaas_subscription_id ?? null,
                'external_reference' => null,
            ]);

            if (!$novoId) {
                return;
            }

            $this->model->update((int)$novoId, [
                'external_reference' => 'cr:' . (int)$novoId . '|u:' . (int)$usuarioId . '|root:' . (int)$rootId . '|prev:' . (int)$contaReceberId,
            ]);

            AuditLogger::log('create_conta_receber_recorrencia', [
                'usuario_id' => (int)$usuarioId,
                'conta_receber_id' => (int)$contaReceberId,
                'nova_conta_receber_id' => (int)$novoId,
                'root_id' => (int)$rootId,
                'data_vencimento' => $proximoVenc,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao gerar recorrencia de conta a receber: ' . $e->getMessage());
        }
    }

    private function extrairIntTag(string $externalReference, string $tag): int
    {
        if ($externalReference === '') {
            return 0;
        }

        if (preg_match('/' . preg_quote($tag, '/') . ':(\\d+)/', $externalReference, $m)) {
            return (int)$m[1];
        }

        return 0;
    }

    private function calcularProximoVencimento(string $dataBase, string $tipo, int $intervalo): string
    {
        try {
            $dt = new \DateTimeImmutable($dataBase);

            if ($tipo === 'semanal') {
                $dt2 = $dt->modify('+' . $intervalo . ' week');
            } elseif ($tipo === 'mensal') {
                $dt2 = $dt->modify('+' . $intervalo . ' month');
            } elseif ($tipo === 'anual') {
                $dt2 = $dt->modify('+' . $intervalo . ' year');
            } elseif ($tipo === 'customizada') {
                $dt2 = $dt->modify('+' . $intervalo . ' day');
            } else {
                return '';
            }

            return $dt2->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
