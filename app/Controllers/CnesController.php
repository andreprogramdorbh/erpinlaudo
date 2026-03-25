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

    // ────────────────────────────────────────────────────────────────────────────────
    // IMPORTAÇÃO VIA UPLOAD DE ZIP
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * GET /cnes/importar
     * Exibe a tela de importação da base CNES via upload de ZIP.
     */
    public function importarForm(): void
    {
        try {
            // Buscar histórico de importações
            $pdo = $this->estabModel->getPdo();
            $historico = [];
            try {
                $stmt = $pdo->query("SELECT * FROM cnes_importacoes ORDER BY iniciado_em DESC LIMIT 12");
                $historico = $stmt->fetchAll(\PDO::FETCH_OBJ);
            } catch (\Throwable $e) {
                // Tabela ainda não existe
            }

            $totalEstab = 0;
            try {
                $totalEstab = (int)$pdo->query("SELECT COUNT(*) FROM cnes_estabelecimentos")->fetchColumn();
            } catch (\Throwable $e) {}

            View::render('cnes/importar', [
                'title'      => 'Importar Base CNES',
                'breadcrumb' => ['Clientes' => '/clientes', 'CNES Global' => '/cnes', 0 => 'Importar'],
                '_layout'    => 'erp',
                'historico'  => $historico,
                'totalEstab' => $totalEstab,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::importarForm - ' . $e->getMessage());
            header('Location: /cnes');
            exit();
        }
    }

    /**
     * POST /cnes/importar/upload
     * Recebe o ZIP da base CNES, extrai e executa o script de importação.
     * Suporta importação inicial e atualização mensal (UPSERT).
     */
    public function importarUpload(): void
    {
        header('Content-Type: application/json');
        try {
            // Verificar se é admin
            $usuario = Auth::user();
            if (!$usuario || !in_array($usuario->role ?? '', ['admin', 'superadmin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem importar a base CNES.']);
                return;
            }

            // Verificar upload
            if (!isset($_FILES['arquivo_zip']) || $_FILES['arquivo_zip']['error'] !== UPLOAD_ERR_OK) {
                $erros = [1=>'Arquivo muito grande (php.ini)', 2=>'Arquivo muito grande (form)', 3=>'Upload incompleto', 4=>'Nenhum arquivo enviado'];
                $erro  = $erros[$_FILES['arquivo_zip']['error'] ?? 4] ?? 'Erro desconhecido no upload';
                echo json_encode(['success' => false, 'error' => $erro]);
                return;
            }

            $arquivo = $_FILES['arquivo_zip'];
            $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if ($ext !== 'zip') {
                echo json_encode(['success' => false, 'error' => 'Apenas arquivos .ZIP são aceitos.']);
                return;
            }

            // Criar diretório temporário para extracção
            $tmpDir = sys_get_temp_dir() . '/cnes_import_' . time();
            mkdir($tmpDir, 0755, true);

            // Mover ZIP para tmp
            $zipPath = $tmpDir . '/base_cnes.zip';
            move_uploaded_file($arquivo['tmp_name'], $zipPath);

            // Extrair ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                // Tentar com 7zip se ZipArchive falhar
                $output = shell_exec("7z e " . escapeshellarg($zipPath) . " -o" . escapeshellarg($tmpDir) . " *.csv -y 2>&1");
                if (!$output || !glob($tmpDir . '/*.csv')) {
                    echo json_encode(['success' => false, 'error' => 'Não foi possível extrair o ZIP. Verifique se o arquivo é válido.']);
                    return;
                }
            } else {
                $zip->extractTo($tmpDir);
                $zip->close();
            }

            // Verificar se os CSVs principais existem
            $csvEstab = glob($tmpDir . '/tbEstabelecimento*.csv');
            if (!$csvEstab) {
                // Tentar em subdiretório
                $csvEstab = glob($tmpDir . '/**/tbEstabelecimento*.csv');
                if ($csvEstab) {
                    $tmpDir = dirname($csvEstab[0]);
                }
            }

            if (!$csvEstab) {
                echo json_encode(['success' => false, 'error' => 'Arquivo tbEstabelecimento*.csv não encontrado no ZIP. Verifique se é a base CNES correta.']);
                return;
            }

            // Filtros opcionais
            $uf          = strtoupper(trim($_POST['uf'] ?? ''));
            $apenasImagem = !empty($_POST['apenas_imagem']);

            // Executar script de importação em background
            $scriptPath = dirname(__DIR__, 2) . '/database/importar_cnes.php';
            $phpBin     = PHP_BINARY ?: 'php';
            $cmd        = sprintf(
                '%s %s --dir=%s %s %s > %s/import.log 2>&1 &',
                escapeshellarg($phpBin),
                escapeshellarg($scriptPath),
                escapeshellarg($tmpDir),
                $uf ? '--uf=' . escapeshellarg($uf) : '',
                $apenasImagem ? '--apenas-imagem' : '',
                escapeshellarg($tmpDir)
            );

            // Registrar importação como 'processando'
            $pdo = $this->estabModel->getPdo();
            // Detectar competência pelo nome do arquivo
            preg_match('/(\d{6})\.csv$/', basename($csvEstab[0]), $mComp);
            $competencia = $mComp[1] ?? date('Ym');

            try {
                $pdo->prepare("INSERT INTO cnes_importacoes (competencia, status, usuario_id, log) VALUES (?, 'processando', ?, ?) ON DUPLICATE KEY UPDATE status='processando', iniciado_em=NOW(), log=NULL, usuario_id=?")
                    ->execute([$competencia, $usuario->id, "Iniciado em " . date('d/m/Y H:i:s'), $usuario->id]);
            } catch (\Throwable $e) {
                // Tabela pode não existir ainda
            }

            // Salvar caminho do tmp para o processo em background usar
            file_put_contents($tmpDir . '/config.json', json_encode([
                'dir'          => $tmpDir,
                'uf'           => $uf,
                'apenas_imagem'=> $apenasImagem,
                'competencia'  => $competencia,
                'usuario_id'   => $usuario->id,
            ]));

            exec($cmd);

            echo json_encode([
                'success'     => true,
                'competencia' => $competencia,
                'message'     => "Importação iniciada para competência {$competencia}. Acompanhe o progresso abaixo.",
                'log_url'     => '/cnes/importar/status?competencia=' . $competencia,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::importarUpload - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /cnes/importar/status?competencia=202602
     * Retorna o status atual da importação (polling AJAX).
     */
    public function importarStatus(): void
    {
        header('Content-Type: application/json');
        try {
            $competencia = trim($_GET['competencia'] ?? '');
            $pdo = $this->estabModel->getPdo();

            if ($competencia) {
                $stmt = $pdo->prepare("SELECT * FROM cnes_importacoes WHERE competencia = ?");
                $stmt->execute([$competencia]);
            } else {
                $stmt = $pdo->query("SELECT * FROM cnes_importacoes ORDER BY iniciado_em DESC LIMIT 1");
            }

            $importacao = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$importacao) {
                echo json_encode(['status' => 'nao_iniciado', 'message' => 'Nenhuma importação encontrada.']);
                return;
            }

            // Contar registros atuais
            $totalEstab = (int)$pdo->query("SELECT COUNT(*) FROM cnes_estabelecimentos")->fetchColumn();

            echo json_encode([
                'status'       => $importacao->status,
                'competencia'  => $importacao->competencia,
                'total_estab'  => $importacao->total_estab ?: $totalEstab,
                'total_equip'  => $importacao->total_equip,
                'total_prof'   => $importacao->total_prof,
                'log'          => $importacao->log,
                'iniciado_em'  => $importacao->iniciado_em,
                'concluido_em' => $importacao->concluido_em,
                'message'      => match($importacao->status) {
                    'processando' => 'Importação em andamento... (' . number_format($totalEstab) . ' estabelecimentos até agora)',
                    'concluido'   => 'Importação concluída com sucesso!',
                    'erro'        => 'Erro durante a importação.',
                    default       => 'Status desconhecido',
                },
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'erro', 'error' => $e->getMessage()]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // API — BUSCA RÁPIDA (autocomplete)
    // ────────────────────────────────────────────────────────────────────────────────/**
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
