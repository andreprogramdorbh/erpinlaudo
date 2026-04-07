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

    // -------------------------------------------------------
    // Listagem principal
    // -------------------------------------------------------
    public function examesTabela(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros = [
                'pesquisa'   => trim((string) ($_GET['q'] ?? '')),
                'modalidade' => trim((string) ($_GET['modalidade'] ?? '')),
            ];

            // Buscar exames com TAGs DICOM incluídas
            $exames = $this->tabelaExameModel->findAllWithTagsByUsuarioId($usuarioId);

            // Aplicar filtros manualmente
            if (!empty($filtros['modalidade'])) {
                $exames = array_values(array_filter($exames, fn($e) => $e->modalidade === $filtros['modalidade']));
            }
            if (!empty($filtros['pesquisa'])) {
                $q = strtolower($filtros['pesquisa']);
                $exames = array_values(array_filter($exames, fn($e) =>
                    str_contains(strtolower($e->nome_exame ?? ''), $q) ||
                    str_contains(strtolower($e->modalidade ?? ''), $q)
                ));
            }

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

    // -------------------------------------------------------
    // Criar novo exame — valores diretos de rotina e urgência
    // -------------------------------------------------------
    public function storeExameTabela(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $nomeExame     = trim(strip_tags((string) ($_POST['nome_exame'] ?? '')));
            $modalidade    = trim((string) ($_POST['modalidade'] ?? ''));
            $valorRotina   = $this->normalizarValor((string) ($_POST['valor_rotina']   ?? '0'));
            $valorUrgencia = $this->normalizarValor((string) ($_POST['valor_urgencia'] ?? '0'));

            if ($nomeExame === '') {
                header('Location: ' . self::EXAMES_ROUTE . '?error=missing_fields');
                exit();
            }

            if (!in_array($modalidade, ['TC', 'RM', 'RX', 'US', 'MG', 'PET', 'NM', 'OUT'], true)) {
                header('Location: ' . self::EXAMES_ROUTE . '?error=invalid_modalidade');
                exit();
            }

            $id = $this->tabelaExameModel->create([
                'usuario_id'     => $usuarioId,
                'nome_exame'     => $nomeExame,
                'modalidade'     => $modalidade,
                'valor_rotina'   => $valorRotina,
                'valor_urgencia' => $valorUrgencia,
            ]);

            if ($id) {
                AuditLogger::log('create_tabela_exame', [
                    'id'         => $id,
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

    // -------------------------------------------------------
    // Editar exame (dados básicos) — via AJAX JSON
    // -------------------------------------------------------
    public function updateExameTabela(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $nomeExame     = trim(strip_tags((string) ($_POST['nome_exame'] ?? '')));
            $modalidade    = trim((string) ($_POST['modalidade'] ?? ''));
            $valorRotina   = $this->normalizarValor((string) ($_POST['valor_rotina']   ?? '0'));
            $valorUrgencia = $this->normalizarValor((string) ($_POST['valor_urgencia'] ?? '0'));

            if ($nomeExame === '' || !in_array($modalidade, ['TC', 'RM', 'RX', 'US', 'MG', 'PET', 'NM', 'OUT'], true)) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
                exit();
            }

            $ok = $this->tabelaExameModel->update($id, [
                'nome_exame'     => $nomeExame,
                'modalidade'     => $modalidade,
                'valor_rotina'   => $valorRotina,
                'valor_urgencia' => $valorUrgencia,
            ]);

            if ($ok) {
                AuditLogger::log('update_tabela_exame', ['id' => $id]);
            }

            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao editar exame: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // -------------------------------------------------------
    // Excluir exame — via AJAX JSON
    // -------------------------------------------------------
    public function deleteExameTabela(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $ok = $this->tabelaExameModel->delete($id);
            if ($ok) {
                AuditLogger::log('delete_tabela_exame', ['id' => $id]);
            }
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao excluir exame: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // -------------------------------------------------------
    // Carregar dados de configuração para o modal — AJAX JSON
    // -------------------------------------------------------
    public function getConfigExame(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $exame = $this->tabelaExameModel->findById($id);
            if (!$exame) {
                echo json_encode(['success' => false, 'message' => 'Exame não encontrado.']);
                exit();
            }
            $tags = $this->tabelaExameModel->getTagsByExameId($id);
            echo json_encode([
                'success' => true,
                'exame'   => $exame,
                'tags'    => $tags,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao carregar config exame: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // -------------------------------------------------------
    // Salvar aba Preços — AJAX JSON
    // Valores DIRETOS de rotina e urgência (médico) — sem cálculo percentual
    // -------------------------------------------------------
    public function savePrecos(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $exame = $this->tabelaExameModel->findById($id);
            if (!$exame) {
                echo json_encode(['success' => false, 'message' => 'Exame não encontrado.']);
                exit();
            }

            $data = [
                'nivel'          => trim((string) ($_POST['nivel'] ?? '')),
                'valor_rotina'   => $this->normalizarValor((string) ($_POST['valor_rotina']   ?? '0')),
                'valor_urgencia' => $this->normalizarValor((string) ($_POST['valor_urgencia'] ?? '0')),
            ];

            $ok = $this->tabelaExameModel->savePrecos($id, $data);

            echo json_encode([
                'success'        => $ok,
                'valor_rotina'   => number_format($data['valor_rotina'],   2, ',', '.'),
                'valor_urgencia' => number_format($data['valor_urgencia'], 2, ',', '.'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao salvar precos exame: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // -------------------------------------------------------
    // Salvar aba Seção — AJAX JSON
    // Encargos + margem geral + margens independentes por tipo (rotina/urgência) para venda
    // -------------------------------------------------------
    public function saveSecao(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $exame = $this->tabelaExameModel->findById($id);
            if (!$exame) {
                echo json_encode(['success' => false, 'message' => 'Exame não encontrado.']);
                exit();
            }

            $data = [
                'imposto_icms'            => $this->normalizarValor((string) ($_POST['imposto_icms']            ?? '0')),
                'imposto_ipi'             => $this->normalizarValor((string) ($_POST['imposto_ipi']             ?? '0')),
                'imposto_pis_cofins'      => $this->normalizarValor((string) ($_POST['imposto_pis_cofins']      ?? '0')),
                'imposto_simples'         => $this->normalizarValor((string) ($_POST['imposto_simples']         ?? '0')),
                'custo_comissao'          => $this->normalizarValor((string) ($_POST['custo_comissao']          ?? '0')),
                'custo_mao_obra_direta'   => $this->normalizarValor((string) ($_POST['custo_mao_obra_direta']   ?? '0')),
                'custo_mao_obra_indireta' => $this->normalizarValor((string) ($_POST['custo_mao_obra_indireta'] ?? '0')),
                'margem_lucro'            => $this->normalizarValor((string) ($_POST['margem_lucro']            ?? '0')),
                'perc_venda_rotina'       => $this->normalizarValor((string) ($_POST['perc_venda_rotina']       ?? '0')),
                'perc_venda_urgencia'     => $this->normalizarValor((string) ($_POST['perc_venda_urgencia']     ?? '0')),
            ];

            $ok = $this->tabelaExameModel->saveSecao($id, $data);

            // Recalcular para retornar ao frontend
            $valorBaseCusto = (float) ($exame->valor_rotina ?? 0);
            $totalPerc      = $data['imposto_icms'] + $data['imposto_ipi'] + $data['imposto_pis_cofins']
                            + $data['imposto_simples'] + $data['custo_comissao']
                            + $data['custo_mao_obra_direta'] + $data['custo_mao_obra_indireta'];
            $precoCusto     = $valorBaseCusto + ($valorBaseCusto * $totalPerc / 100);
            $precoVenda     = $precoCusto + ($precoCusto * $data['margem_lucro'] / 100);
            $valorVendaR    = $precoVenda + ($precoVenda * $data['perc_venda_rotina']   / 100);
            $valorVendaU    = $precoVenda + ($precoVenda * $data['perc_venda_urgencia'] / 100);

            echo json_encode([
                'success'              => $ok,
                'preco_custo'          => number_format($precoCusto,  2, ',', '.'),
                'preco_venda'          => number_format($precoVenda,  2, ',', '.'),
                'valor_venda_rotina'   => number_format($valorVendaR, 2, ',', '.'),
                'valor_venda_urgencia' => number_format($valorVendaU, 2, ',', '.'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao salvar secao exame: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // -------------------------------------------------------
    // Salvar TAGs DICOM — AJAX JSON
    // -------------------------------------------------------
    public function saveTags(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $rawTags = $_POST['tags'] ?? [];
            $tags = [];
            if (is_array($rawTags)) {
                foreach ($rawTags as $t) {
                    $nome  = trim((string) ($t['nome'] ?? ''));
                    $valor = trim((string) ($t['valor'] ?? ''));
                    if ($nome !== '') {
                        $tags[] = ['nome' => $nome, 'valor' => $valor];
                    }
                }
            }

            $ok = $this->tabelaExameModel->replaceAllTags($id, $tags);
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao salvar tags DICOM: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // -------------------------------------------------------
    // Helper
    // -------------------------------------------------------
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
