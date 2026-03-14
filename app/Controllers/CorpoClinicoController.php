<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Core\Audit\AuditLogger;
use App\Models\TabelaExame;

class CorpoClinicoController extends Controller
{
    private const EXAMES_ROUTE = '/exames-tabela';

    private TabelaExame $tabelaExameModel;
    private Logger $logger;

    public function __construct()
    {
        $this->tabelaExameModel = new TabelaExame();
        $this->logger = new Logger();
    }

    public function escalas(): void
    {
        View::render('escalas/index', [
            '_layout' => 'erp',
            'title' => 'Escalas',
            'breadcrumb' => [
                'Cadastros' => '#',
                'Corpo Clínico' => '#',
                0 => 'Escalas',
            ],
        ]);
    }

    public function examesTabela(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros = [
                'pesquisa' => trim((string) ($_GET['q'] ?? '')),
                'modalidade' => trim((string) ($_GET['modalidade'] ?? '')),
            ];

            $exames = $this->tabelaExameModel->findByUsuarioId($usuarioId, $filtros);

            View::render('exames_tabela/index', [
                '_layout' => 'erp',
                'title' => 'Tabela de Exames',
                'breadcrumb' => [
                    'Cadastros' => '#',
                    'Corpo Clínico' => '#',
                    0 => 'Tabela de Exames',
                ],
                'exames' => $exames,
                'filtros' => $filtros,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar tabela de exames: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function storeExameTabela(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $nomeExame = trim(strip_tags((string) ($_POST['nome_exame'] ?? '')));
            $modalidade = trim((string) ($_POST['modalidade'] ?? ''));
            $valorPadrao = $this->normalizarValor((string) ($_POST['valor_padrao'] ?? '0'));

            if ($nomeExame === '') {
                header('Location: ' . self::EXAMES_ROUTE . '?error=missing_fields');
                exit();
            }

            if (!in_array($modalidade, ['TC', 'RM', 'RX', 'US'], true)) {
                header('Location: ' . self::EXAMES_ROUTE . '?error=invalid_modalidade');
                exit();
            }

            if ($valorPadrao < 0) {
                header('Location: ' . self::EXAMES_ROUTE . '?error=invalid_valor');
                exit();
            }

            $id = $this->tabelaExameModel->create([
                'usuario_id' => $usuarioId,
                'nome_exame' => $nomeExame,
                'modalidade' => $modalidade,
                'valor_padrao' => $valorPadrao,
            ]);

            if ($id) {
                AuditLogger::log('create_tabela_exame', [
                    'id' => $id,
                    'nome_exame' => $nomeExame,
                    'modalidade' => $modalidade,
                ]);
                header('Location: ' . self::EXAMES_ROUTE . '?success=created');
            } else {
                header('Location: ' . self::EXAMES_ROUTE . '?error=db_failure');
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar exame da tabela: ' . $e->getMessage());
            header('Location: ' . self::EXAMES_ROUTE . '?error=fatal');
        }
        exit();
    }

    private function normalizarValor(string $valor): float
    {
        $valor = trim($valor);
        $valor = preg_replace('/[^\d,.]/', '', $valor);

        if (substr_count($valor, ',') === 1 && substr_count($valor, '.') >= 1) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (substr_count($valor, ',') === 1) {
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }
}
