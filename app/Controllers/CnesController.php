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
use App\Services\CnesImportService;

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

            // co_cnes passado como filtro extra para buscar por co_unidade OU co_cnes
            // Garante compatibilidade com diferentes versões do CSV CNES
            $coCnes = (string)($estab->co_cnes ?? '');

            if ($aba === 'equipamentos' || $aba === 'dados') {
                $filtroEquip  = [
                    'tipo'    => $_GET['tipo_equip'] ?? '',
                    'co_cnes' => $coCnes,
                ];
                $equipamentos = $this->equipModel->findByUnidade($estab->co_unidade, $filtroEquip);
                $tiposEquip   = $this->equipModel->tiposDisponiveis($estab->co_unidade, $coCnes);
            }
            if ($aba === 'profissionais') {
                $filtroProf    = [
                    'q'        => $_GET['q_prof'] ?? '',
                    'cbo'      => $_GET['cbo'] ?? '',
                    'situacao' => $_GET['situacao'] ?? '',
                    'co_cnes'  => $coCnes,
                ];
                $profissionais = $this->profModel->findByUnidade($estab->co_unidade, $filtroProf);
                $cbos          = $this->profModel->cbosDisponiveis($estab->co_unidade, $coCnes);
            }

            // Contadores para badges (busca por co_unidade OU co_cnes)
            $totalEquip = $this->equipModel->contarPorUnidade($estab->co_unidade, $coCnes);
            $totalProf  = $this->profModel->contarPorUnidade($estab->co_unidade, $coCnes);

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
        ob_start(); ob_end_clean();
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
        ob_start(); ob_end_clean();
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
        ob_start(); ob_end_clean();
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
        // Importação CNES movida para /configuracoes?tab=cnes
        header('Location: /configuracoes?tab=cnes', true, 301);
        exit();
    }


    /**
     * POST /cnes/importar/upload
     * Recebe o ZIP da base CNES e processa a importação via CnesImportService.
     * Usa INSERT em lotes — compatível com MariaDB Hostinger (sem LOAD DATA INFILE).
     * Grava progresso em storage/cnes_import_progress.json para polling do browser.
     */
    /**
     * Converte string de limite PHP (ex: '2M', '512K', '1G') para bytes.
     */
    private function parseIniBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $num  = (int) $val;
        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    public function importarUpload(): void
    {
        ob_start(); // captura output espúrio antes do JSON
        ob_end_clean();
        header('Content-Type: application/json');

        // Aumentar limites para arquivos grandes (runtime — complementa .user.ini e .htaccess)
        @set_time_limit(0);
        @ini_set('memory_limit',        '512M');
        @ini_set('upload_max_filesize', '1024M');
        @ini_set('post_max_size',       '1024M');

        try {
            // ── Detectar quando post_max_size foi excedido ────────────────────────────────
            // Quando o arquivo excede post_max_size, o PHP descarta $_POST e $_FILES
            // completamente. O Content-Length do request revela o tamanho real enviado.
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMaxBytes  = $this->parseIniBytes(ini_get('post_max_size'));
            if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
                $limiteMb  = round($postMaxBytes  / 1024 / 1024);
                $enviadoMb = round($contentLength / 1024 / 1024);
                http_response_code(413);
                echo json_encode([
                    'success'    => false,
                    'error'      => "O arquivo ({$enviadoMb} MB) excede o limite de upload do servidor ({$limiteMb} MB). "
                                  . 'Solicite ao administrador do servidor que aumente post_max_size e upload_max_filesize no php.ini '
                                  . 'para pelo menos 1024M, ou use a opção de importação via caminho do servidor (SSH/FTP).',
                    'limite_mb'  => $limiteMb,
                    'enviado_mb' => $enviadoMb,
                    'dica'       => 'Adicione ao php.ini (ou public/.user.ini): upload_max_filesize=1024M e post_max_size=1024M',
                ]);
                return;
            }

            // Verificar se é admin
            $usuario = Auth::user();
            if (!$usuario || !in_array($usuario->role ?? '', ['admin', 'superadmin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem importar a base CNES.']);
                return;
            }

            // Verificar upload
            if (!isset($_FILES['arquivo_zip']) || $_FILES['arquivo_zip']['error'] !== UPLOAD_ERR_OK) {
                $uploadMaxBytes = $this->parseIniBytes(ini_get('upload_max_filesize'));
                $uploadMaxMb    = round($uploadMaxBytes / 1024 / 1024);
                $erros = [
                    1 => "Arquivo muito grande (limite atual: upload_max_filesize={$uploadMaxMb}MB). Adicione ao public/.user.ini: upload_max_filesize=1024M",
                    2 => 'Arquivo muito grande (limite do formulário HTML)',
                    3 => 'Upload incompleto — tente novamente',
                    4 => 'Nenhum arquivo enviado',
                    6 => 'Pasta temporária não encontrada no servidor',
                    7 => 'Falha ao gravar arquivo no disco (verifique permissões de /tmp)',
                ];
                $errCode = $_FILES['arquivo_zip']['error'] ?? 4;
                $errMsg  = $erros[$errCode] ?? 'Erro desconhecido no upload (código ' . $errCode . ')';
                echo json_encode(['success' => false, 'error' => $errMsg]);
                return;
            }

            $arquivo = $_FILES['arquivo_zip'];
            $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if ($ext !== 'zip') {
                echo json_encode(['success' => false, 'error' => 'Apenas arquivos .ZIP são aceitos.']);
                return;
            }

            // Salvar ZIP no storage permanente (não no /tmp que pode ser limpado)
            $storageDir = dirname(__DIR__, 2) . '/storage';
            $zipPath    = $storageDir . '/cnes_upload_' . date('YmdHis') . '.zip';
            if (!move_uploaded_file($arquivo['tmp_name'], $zipPath)) {
                echo json_encode(['success' => false, 'error' => 'Falha ao salvar o arquivo no servidor. Verifique as permissões de escrita em /storage.']);
                return;
            }

            // Detectar competência pelo nome do arquivo (ex: BASE_DE_DADOS_CNES_202602.ZIP)
            preg_match('/(\d{6})/', $arquivo['name'], $mComp);
            $competencia  = $mComp[1] ?? date('Ym');
            $uf           = strtoupper(trim($_POST['uf'] ?? ''));
            $apenasImagem = !empty($_POST['apenas_imagem']);

            // Processar importação via CnesImportService
            $service = new CnesImportService();
            $service->importarZip($zipPath, [
                'uf'           => $uf,
                'apenas_imagem'=> $apenasImagem,
                'competencia'  => $competencia,
            ]);

            // Limpar ZIP após importação
            @unlink($zipPath);

            $progresso = $service->lerProgresso();

            echo json_encode([
                'success'      => true,
                'competencia'  => $competencia,
                'total_estab'  => $progresso['estab'] ?? 0,
                'total_equip'  => $progresso['equip'] ?? 0,
                'total_prof'   => $progresso['prof']  ?? 0,
                'message'      => 'Importação concluída com sucesso!',
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('CnesController::importarUpload - ' . $e->getMessage());
            // Gravar erro no arquivo de progresso para o polling detectar
            $progressFile = dirname(__DIR__, 2) . '/storage/cnes_import_progress.json';
            file_put_contents($progressFile, json_encode([
                'status' => 'erro',
                'etapa'  => 'Erro: ' . $e->getMessage(),
                'pct'    => 0,
                'erros'  => [$e->getMessage()],
            ]));
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /cnes/importar/status
     * Retorna o progresso atual da importação via arquivo JSON (polling AJAX).
     * Também retorna contagens do banco para exibição em tempo real.
     */
    public function importarStatus(): void
    {
        ob_start(); // captura output espúrio antes do JSON
        ob_end_clean();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        try {
            $service   = new CnesImportService();
            $progresso = $service->lerProgresso();

            // Adicionar contagens do banco se disponível
            try {
                $pdo = $this->estabModel->getPdo();
                $progresso['db_estab'] = (int)$pdo->query("SELECT COUNT(*) FROM cnes_estabelecimentos")->fetchColumn();
                $progresso['db_equip'] = (int)$pdo->query("SELECT COUNT(*) FROM cnes_equipamentos")->fetchColumn();
                $progresso['db_prof']  = (int)$pdo->query("SELECT COUNT(*) FROM cnes_profissionais")->fetchColumn();
            } catch (\Throwable $e) {
                $progresso['db_estab'] = 0;
                $progresso['db_equip'] = 0;
                $progresso['db_prof']  = 0;
            }

            echo json_encode($progresso);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'erro', 'error' => $e->getMessage()]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // Importação direta do servidor (CSVs em /tmp/cnes_base/)
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * POST /cnes/importar/servidor
     * Importa os CSVs já extraídos no servidor (sem upload de ZIP).
     * Detecta automaticamente o diretório /tmp/cnes_base/ ou usa o path informado.
     */
    public function importarDoServidor(): void
    {
        // Capturar qualquer output espúrio (erros/notices do PHP que geram HTML)
        // antes de enviar o header JSON. Isso evita o 'Unexpected token <'.
        ob_start();

        try {
            $body         = json_decode(file_get_contents('php://input'), true) ?? [];
            $uf           = strtoupper(trim($body['uf'] ?? $_POST['uf'] ?? ''));
            $apenasImagem = (bool)($body['apenas_imagem'] ?? $_POST['apenas_imagem'] ?? false);
            $competencia  = trim($body['competencia'] ?? $_POST['competencia'] ?? date('Ym'));

            // Diretórios candidatos onde os CSVs podem estar
            $candidatos = [
                $body['dir'] ?? '',
                '/tmp/cnes_base',
                dirname(__DIR__, 2) . '/tmp/cnes_base',
                '/home2/inlaud99/erp.inlaudo.com.br/tmp/cnes_base',
                sys_get_temp_dir() . '/cnes_base',
            ];

            $dirEncontrado = null;
            foreach ($candidatos as $candidato) {
                if ($candidato && is_dir($candidato) && !empty(glob($candidato . '/*.csv'))) {
                    $dirEncontrado = $candidato;
                    break;
                }
            }

            // Descartar qualquer output espúrio acumulado antes de enviar JSON
            ob_end_clean();
            header('Content-Type: application/json');

            if (!$dirEncontrado) {
                echo json_encode([
                    'success' => false,
                    'error'   => 'Nenhum diretório com CSVs CNES encontrado. Verifique se os arquivos estão em /tmp/cnes_base/',
                    'candidatos_verificados' => array_values(array_filter($candidatos)),
                ]);
                return;
            }

            // Iniciar importação em background
            ignore_user_abort(true);
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $numCsvs = count(glob($dirEncontrado . '/*.csv'));

            // Enviar resposta JSON imediatamente ao browser
            echo json_encode([
                'success'  => true,
                'message'  => 'Importação iniciada! Acompanhe o progresso abaixo.',
                'dir'      => $dirEncontrado,
                'uf'       => $uf ?: 'Todos os estados',
                'csvs'     => $numCsvs,
            ]);

            // Fechar conexão HTTP para o browser receber a resposta antes do processamento
            if (function_exists('fastcgi_finish_request')) {
                // PHP-FPM (Hostinger, servidores modernos)
                fastcgi_finish_request();
            } else {
                // Apache mod_php — tenta flush manual
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }

            // Processar importação após fechar a conexão HTTP
            $service = new \App\Services\CnesImportService();
            $service->importarDiretorio($dirEncontrado, [
                'uf'            => $uf,
                'apenas_imagem' => $apenasImagem,
                'competencia'   => $competencia,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('CnesController::importarDoServidor - ' . $e->getMessage());
            // Limpar qualquer buffer acumulado e enviar erro JSON limpo
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                ob_start(); ob_end_clean();
        header('Content-Type: application/json');
                http_response_code(500);
            }
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /cnes/importar/detectar
     * Detecta se há CSVs disponíveis no servidor e retorna informações.
     */
    public function detectarCsvs(): void
    {
        ob_start(); // captura output espúrio antes do JSON
        ob_end_clean();
        header('Content-Type: application/json');
        $candidatos = [
            '/tmp/cnes_base',
            dirname(__DIR__, 2) . '/tmp/cnes_base',
            '/home2/inlaud99/erp.inlaudo.com.br/tmp/cnes_base',
            sys_get_temp_dir() . '/cnes_base',
        ];

        $resultado = [];
        foreach ($candidatos as $dir) {
            if (is_dir($dir)) {
                $csvs = glob($dir . '/*.csv');
                $resultado[] = [
                    'dir'       => $dir,
                    'existe'    => true,
                    'total_csv' => count($csvs),
                    'csvs'      => array_map('basename', $csvs),
                    'tem_estab' => !empty(glob($dir . '/tbEstabelecimento*.csv')),
                ];
            } else {
                $resultado[] = ['dir' => $dir, 'existe' => false, 'total_csv' => 0];
            }
        }

        echo json_encode(['candidatos' => $resultado]);
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // Diagnóstico de ZIP e Reimportação Parcial
    // ────────────────────────────────────────────────────────────────────────────────

    /**
     * POST /cnes/importar/diagnostico-zip
     * Analisa o conteúdo de um ZIP enviado e lista os CSVs encontrados.
     * Não importa nada — apenas retorna o diagnóstico.
     */
    public function diagnosticarZip(): void
    {
        ob_start(); // captura output espúrio antes do JSON
        ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuario = Auth::user();
            if (!$usuario || !in_array($usuario->role ?? '', ['admin', 'superadmin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
                return;
            }

            if (!isset($_FILES['arquivo_zip']) || $_FILES['arquivo_zip']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'Nenhum arquivo ZIP enviado.']);
                return;
            }

            $arquivo = $_FILES['arquivo_zip'];
            $ext     = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if ($ext !== 'zip') {
                echo json_encode(['success' => false, 'error' => 'Apenas arquivos .ZIP são aceitos.']);
                return;
            }

            // Salvar temporariamente
            $storageDir = dirname(__DIR__, 2) . '/storage';
            $zipPath    = $storageDir . '/cnes_diag_' . date('YmdHis') . '.zip';
            if (!move_uploaded_file($arquivo['tmp_name'], $zipPath)) {
                echo json_encode(['success' => false, 'error' => 'Falha ao salvar o arquivo temporariamente.']);
                return;
            }

            $service = new CnesImportService();
            $diagn   = $service->diagnosticarZip($zipPath);

            echo json_encode(array_merge(['success' => true, 'zip_path' => $zipPath], $diagn));
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::diagnosticarZip - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /cnes/importar/parcial
     * Reimporta apenas equipamentos e/ou profissionais a partir de um ZIP já
     * salvo no servidor (após diagnóstico) ou de um diretório com CSVs.
     */
    public function importarParcial(): void
    {
        ob_start(); // captura output espúrio antes do JSON
        ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuario = Auth::user();
            if (!$usuario || !in_array($usuario->role ?? '', ['admin', 'superadmin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
                return;
            }

            @set_time_limit(0);
            @ini_set('memory_limit', '512M');

            $body          = json_decode(file_get_contents('php://input'), true) ?? [];
            $zipPath       = trim($body['zip_path']       ?? $_POST['zip_path']       ?? '');
            $dir           = trim($body['dir']            ?? $_POST['dir']            ?? '');
            $uf            = strtoupper(trim($body['uf']  ?? $_POST['uf']             ?? ''));
            $apenasImagem  = (bool)($body['apenas_imagem']  ?? $_POST['apenas_imagem']  ?? false);
            $competencia   = trim($body['competencia']    ?? $_POST['competencia']    ?? date('Ym'));
            $importarEquip = (bool)($body['importar_equip'] ?? $_POST['importar_equip'] ?? true);
            $importarProf  = (bool)($body['importar_prof']  ?? $_POST['importar_prof']  ?? true);

            $opcoes = [
                'uf'             => $uf,
                'apenas_imagem'  => $apenasImagem,
                'competencia'    => $competencia,
                'importar_equip' => $importarEquip,
                'importar_prof'  => $importarProf,
            ];

            $service = new CnesImportService();

            // Responder imediatamente ao browser antes de processar
            echo json_encode([
                'success' => true,
                'message' => 'Reimportação parcial iniciada! Acompanhe o progresso abaixo.',
                'modo'    => 'parcial',
            ]);
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                ob_end_flush();
                flush();
            }

            ignore_user_abort(true);

            // Processar após fechar a conexão HTTP
            if ($zipPath && file_exists($zipPath)) {
                $service->importarParcialZip($zipPath, $opcoes);
                @unlink($zipPath); // Limpar ZIP após uso
            } elseif ($dir && is_dir($dir)) {
                $service->importarParcialDiretorio($dir, $opcoes);
            } else {
                // Tentar detectar diretório automático
                $candidatos = [
                    '/tmp/cnes_base',
                    dirname(__DIR__, 2) . '/tmp/cnes_base',
                    '/home2/inlaud99/erp.inlaudo.com.br/tmp/cnes_base',
                    sys_get_temp_dir() . '/cnes_base',
                ];
                $dirEncontrado = null;
                foreach ($candidatos as $c) {
                    if ($c && is_dir($c) && !empty(glob($c . '/*.csv'))) {
                        $dirEncontrado = $c;
                        break;
                    }
                }
                if ($dirEncontrado) {
                    $service->importarParcialDiretorio($dirEncontrado, $opcoes);
                } else {
                    $progressFile = dirname(__DIR__, 2) . '/storage/cnes_import_progress.json';
                    file_put_contents($progressFile, json_encode([
                        'status' => 'erro',
                        'etapa'  => 'Erro: Nenhum ZIP ou diretório com CSVs encontrado para reimportação parcial.',
                        'pct'    => 0,
                        'erros'  => ['Informe zip_path ou dir, ou disponibilize os CSVs em /tmp/cnes_base/'],
                    ]));
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('CnesController::importarParcial - ' . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────────────────
    // API — BUSCA RÁPIDA (autocomplete)
    // ────────────────────────────────────────────────────────────────────────────────
    /**
     * GET /cnes/buscar?q=termo&uf=SP
     * Retorna JSON para autocomplete.
     */
    public function buscar(): void
    {
        ob_start(); // captura output espúrio antes do JSON
        ob_end_clean();
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
            $lista = array_map(function($e) {
                return [
                    'id'           => $e->id,
                    'co_cnes'      => $e->co_cnes,
                    'razao_social' => $e->no_razao_social,
                    'fantasia'     => $e->no_fantasia,
                    'uf'           => $e->co_estado_gestor,
                    'municipio'    => $e->co_municipio_gestor,
                    'cnpj'         => $e->nu_cnpj,
                    'cliente_id'   => $e->cliente_id,
                ];
            }, $resultado['registros']);
            echo json_encode($lista);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
