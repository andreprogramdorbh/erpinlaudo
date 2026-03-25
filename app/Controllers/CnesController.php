<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Models\CnesEstabelecimento;
use App\Models\CnesEquipamento;
use App\Models\CnesProfissional;
use App\Models\Cliente;

/**
 * Controller do módulo CNES Global.
 * Gerencia listagem, detalhe, abas e importação de estabelecimentos CNES.
 */
class CnesController extends Controller
{
    private CnesEstabelecimento $estabModel;
    private CnesEquipamento     $equipModel;
    private CnesProfissional    $profModel;
    private Cliente             $clienteModel;
    private Logger              $logger;

    public function __construct()
    {
        $this->estabModel   = new CnesEstabelecimento();
        $this->equipModel   = new CnesEquipamento();
        $this->profModel    = new CnesProfissional();
        $this->clienteModel = new Cliente();
        $this->logger       = new Logger();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LISTAGEM PRINCIPAL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /cnes
     * Lista todos os estabelecimentos CNES com filtros e paginação.
     */
    public function index(): void
    {
        try {
            $filtros = [
                'q'          => trim($_GET['q'] ?? ''),
                'uf'         => $_GET['uf'] ?? '',
                'municipio'  => $_GET['municipio'] ?? '',
                'tp_unidade' => $_GET['tp_unidade'] ?? '',
                'importado'  => $_GET['importado'] ?? '',
            ];
            $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
            $porPagina = 50;

            $baseImportada = $this->estabModel->baseImportada();
            $resultado     = $baseImportada
                ? $this->estabModel->buscar($filtros, $pagina, $porPagina)
                : ['registros' => [], 'total' => 0, 'pagina' => 1, 'por_pagina' => $porPagina, 'total_paginas' => 0];

            $ufs           = $baseImportada ? $this->estabModel->listarUfs() : [];
            $tiposUnidade  = $baseImportada ? $this->estabModel->listarTiposUnidade() : [];

            View::render('cnes/index', [
                'title'        => 'CNES Global',
                'breadcrumb'   => ['Clientes' => '/clientes', 0 => 'CNES Global'],
                '_layout'      => 'erp',
                'baseImportada'=> $baseImportada,
                'resultado'    => $resultado,
                'filtros'      => $filtros,
                'ufs'          => $ufs,
                'tiposUnidade' => $tiposUnidade,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::index - ' . $e->getMessage());
            View::render('cnes/index', [
                'title'        => 'CNES Global',
                'breadcrumb'   => ['Clientes' => '/clientes', 0 => 'CNES Global'],
                '_layout'      => 'erp',
                'baseImportada'=> false,
                'resultado'    => ['registros' => [], 'total' => 0, 'pagina' => 1, 'por_pagina' => 50, 'total_paginas' => 0],
                'filtros'      => [],
                'ufs'          => [],
                'tiposUnidade' => [],
                'erro'         => 'Erro ao carregar a base CNES. Execute a migration e o script de importação.',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETALHE DO ESTABELECIMENTO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /cnes/{cnes}
     * Exibe o detalhe de um estabelecimento com abas.
     */
    public function show(string $cnes): void
    {
        try {
            $estab = $this->estabModel->findByCnes($cnes);
            if (!$estab) {
                header('Location: /cnes?erro=not_found');
                exit();
            }

            $aba = $_GET['aba'] ?? 'dados';

            // Dados da aba ativa
            $equipamentos  = [];
            $profissionais = [];
            $tiposEquip    = [];
            $cbos          = [];

            if ($aba === 'equipamentos' || $aba === 'dados') {
                $filtroEquip  = ['tipo' => $_GET['tipo_equip'] ?? ''];
                $equipamentos = $this->equipModel->findByUnidade($estab->co_unidade, $filtroEquip);
                $tiposEquip   = $this->equipModel->tiposDisponiveis($estab->co_unidade);
            }
            if ($aba === 'profissionais') {
                $filtroProf    = [
                    'q'        => $_GET['q_prof'] ?? '',
                    'cbo'      => $_GET['cbo'] ?? '',
                    'situacao' => $_GET['situacao'] ?? '',
                ];
                $profissionais = $this->profModel->findByUnidade($estab->co_unidade, $filtroProf);
                $cbos          = $this->profModel->cbosDisponiveis($estab->co_unidade);
            }

            // Contadores para badges
            $totalEquip = count($this->equipModel->findByUnidade($estab->co_unidade));
            $totalProf  = $this->profModel->contarPorUnidade($estab->co_unidade);

            // Cliente vinculado
            $clienteVinculado = null;
            if ($estab->cliente_id) {
                $clienteVinculado = $this->clienteModel->findById((int)$estab->cliente_id);
            }

            View::render('cnes/show', [
                'title'            => 'CNES: ' . ($estab->no_fantasia ?: $estab->no_razao_social),
                'breadcrumb'       => [
                    'Clientes'     => '/clientes',
                    'CNES Global'  => '/cnes',
                    0              => $estab->co_cnes,
                ],
                '_layout'          => 'erp',
                'estab'            => $estab,
                'aba'              => $aba,
                'equipamentos'     => $equipamentos,
                'tiposEquip'       => $tiposEquip,
                'profissionais'    => $profissionais,
                'cbos'             => $cbos,
                'totalEquip'       => $totalEquip,
                'totalProf'        => $totalProf,
                'clienteVinculado' => $clienteVinculado,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::show - ' . $e->getMessage());
            header('Location: /cnes?erro=1');
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API — ATUALIZAR EQUIPAMENTO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /cnes/equipamento/{id}/atualizar
     * Atualiza fabricante, modelo e ano_instalacao de um equipamento.
     */
    public function atualizarEquipamento(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $dados = [
                'fabricante'     => trim($_POST['fabricante'] ?? ''),
                'modelo'         => trim($_POST['modelo'] ?? ''),
                'ano_instalacao' => $_POST['ano_instalacao'] ?? null,
                'observacoes'    => trim($_POST['observacoes'] ?? ''),
            ];
            $ok = $this->equipModel->atualizarExtras($id, $dados);
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::atualizarEquipamento - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API — ATUALIZAR PROFISSIONAL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /cnes/profissional/{id}/atualizar
     * Atualiza email, contato e situação de um profissional.
     */
    public function atualizarProfissional(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $dados = [
                'email'      => trim($_POST['email'] ?? ''),
                'contato'    => trim($_POST['contato'] ?? ''),
                'situacao'   => in_array($_POST['situacao'] ?? '', ['ativo', 'inativo']) ? $_POST['situacao'] : 'ativo',
                'observacoes'=> trim($_POST['observacoes'] ?? ''),
            ];
            $ok = $this->profModel->atualizarContato($id, $dados);
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::atualizarProfissional - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IMPORTAR COMO CLIENTE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /cnes/{cnes}/importar-cliente
     * Importa um estabelecimento CNES como cliente no ERP.
     */
    public function importarComoCliente(string $cnes): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $estab     = $this->estabModel->findByCnes($cnes);

            if (!$estab) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Estabelecimento não encontrado.']);
                return;
            }

            // Verificar se já foi importado
            if ($estab->cliente_id) {
                echo json_encode([
                    'success'    => true,
                    'ja_existe'  => true,
                    'cliente_id' => $estab->cliente_id,
                    'message'    => 'Este estabelecimento já foi importado como cliente.',
                ]);
                return;
            }

            // Montar dados do cliente a partir do CNES
            $razaoSocial = $estab->no_razao_social ?? 'Estabelecimento CNES';
            $nomeFantasia = $estab->no_fantasia ?: $estab->no_fantasia_abrev ?: null;
            $cnpj         = $estab->nu_cnpj ? preg_replace('/\D/', '', $estab->nu_cnpj) : null;
            $tipo         = ($estab->tp_pfpj == '1') ? 'PF' : 'PJ';

            // Formatar CEP
            $cep = $estab->co_cep ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', preg_replace('/\D/', '', $estab->co_cep)) : null;

            // Inserir cliente
            $clienteId = $this->clienteModel->create([
                'tipo'         => $tipo,
                'cpf_cnpj'     => $cnpj ?? '',
                'razao_social' => $razaoSocial,
                'nome_fantasia'=> $nomeFantasia,
                'email'        => $estab->no_email ?? '',
                'website'      => $estab->no_url ?? null,
                'endereco'     => $estab->no_logradouro ?? null,
                'numero'       => $estab->nu_endereco ?? null,
                'complemento'  => $estab->no_complemento ?? null,
                'bairro'       => $estab->no_bairro ?? null,
                'cidade'       => null, // Município por código — não temos o nome aqui
                'estado'       => $estab->co_estado_gestor ?? null,
                'cep'          => $cep,
                'telefone'     => $estab->nu_telefone ?? null,
                'status'       => 'ativo',
                'usuario_id'   => $usuarioId,
            ]);

            // Vincular o estabelecimento ao cliente criado
            $this->estabModel->vincularCliente((int)$estab->id, (int)$clienteId);

            $this->logger->info("CNES {$cnes} importado como cliente #{$clienteId} pelo usuário #{$usuarioId}");

            echo json_encode([
                'success'    => true,
                'cliente_id' => $clienteId,
                'message'    => "Estabelecimento importado com sucesso como cliente #{$clienteId}.",
                'redirect'   => "/clientes/{$clienteId}",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::importarComoCliente - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao importar: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API — BUSCA RÁPIDA (autocomplete)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /cnes/buscar?q=termo&uf=SP
     * Retorna JSON para autocomplete.
     */
    public function buscar(): void
    {
        header('Content-Type: application/json');
        try {
            $q   = trim($_GET['q'] ?? '');
            $uf  = trim($_GET['uf'] ?? '');
            if (strlen($q) < 2) {
                echo json_encode([]);
                return;
            }
            $resultado = $this->estabModel->buscar(
                ['q' => $q, 'uf' => $uf],
                1,
                20
            );
            $lista = array_map(fn($e) => [
                'id'           => $e->id,
                'co_cnes'      => $e->co_cnes,
                'razao_social' => $e->no_razao_social,
                'fantasia'     => $e->no_fantasia,
                'uf'           => $e->co_estado_gestor,
                'municipio'    => $e->co_municipio_gestor,
                'cnpj'         => $e->nu_cnpj,
                'cliente_id'   => $e->cliente_id,
            ], $resultado['registros']);
            echo json_encode($lista);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
