<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\Contrato;
use App\Models\ContratoAnexo;
use App\Models\Apuracao;
use App\Models\ApuracaoItem;
use App\Models\LayoutExame;
use App\Models\Medico;
use App\Models\Cliente;
use App\Models\TabelaExame;
use App\Models\MedicoExame;
use App\Models\ContratoExame;
use App\Models\ContaReceber;
use App\Models\PlanoConta;
use App\Services\ContaReceberRecorrenciaService;

class ContratosController extends Controller
{
    private Contrato      $contratoModel;
    private ContratoAnexo $anexoModel;
    private Apuracao      $apuracaoModel;
    private ApuracaoItem  $itemModel;
    private ContratoExame $contratoExameModel;
    private Logger        $logger;

    public function __construct()
    {
        $this->contratoModel      = new Contrato();
        $this->anexoModel         = new ContratoAnexo();
        $this->apuracaoModel      = new Apuracao();
        $this->itemModel          = new ApuracaoItem();
        $this->contratoExameModel = new ContratoExame();
        $this->logger             = new Logger();
    }

    // =========================================================
    // LISTAGEM
    // =========================================================
    public function index(): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $filtros   = [
            'q'          => trim($_GET['q'] ?? ''),
            'tipo_parte' => $_GET['tipo_parte'] ?? '',
            'status'     => $_GET['status'] ?? '',
        ];
        $contratos = $this->contratoModel->findByUsuarioId($usuarioId, $filtros);
        View::render('contratos/index', [
            'title'     => 'Contratos',
            'contratos' => $contratos,
            'filtros'   => $filtros,
            '_layout' => 'erp',
        ]);
    }

    // =========================================================
    // CRIAR
    // =========================================================
    public function create(): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $medicos   = (new Medico())->findByUsuarioId($usuarioId, ['status' => 'ativo']);
        $clientes  = (new Cliente())->findByUsuarioId($usuarioId);
        $exames    = (new TabelaExame())->findByUsuarioId($usuarioId);
        View::render('contratos/form', [
            'title'    => 'Novo Contrato',
            'contrato' => null,
            'medicos'  => $medicos,
            'clientes' => $clientes,
            'exames'   => $exames,
            'modalidades_contrato' => [],
            'active_tab' => 'dados',
            '_layout' => 'erp',
        ]);
    }

    // =========================================================
    // SALVAR (store)
    // =========================================================
    public function store(): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        $data = [
            'usuario_id'    => $usuarioId,
            'numero'        => $this->contratoModel->gerarNumero(),
            'nome'          => trim($_POST['nome'] ?? ''),
            'tipo_parte'    => $_POST['tipo_parte'] ?? 'medico',
            'medico_id'     => (int) ($_POST['medico_id'] ?? 0) ?: null,
            'cliente_id'    => (int) ($_POST['cliente_id'] ?? 0) ?: null,
            'data_inicio'   => $_POST['data_inicio'] ?? '',
            'data_fim'      => $_POST['data_fim'] ?? null,
            'vigencia_tipo' => $_POST['vigencia_tipo'] ?? 'determinado',
            'recorrencia'   => $_POST['recorrencia'] ?? 'mensal',
            'valor'         => (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0'),
            'observacoes'   => trim($_POST['observacoes'] ?? ''),
            'status'        => $_POST['status'] ?? 'ativo',
        ];

        if (empty($data['nome']) || empty($data['data_inicio'])) {
            header("Location: /contratos/create?error=campos_obrigatorios");
            exit();
        }

        $id = $this->contratoModel->create($data);
        if (!$id) {
            $this->logger->error('[Contratos] Falha ao criar contrato', $data);
            header("Location: /contratos/create?error=db_error");
            exit();
        }

        if ($data['tipo_parte'] === 'medico') {
            $modalidades = $this->parseModalidades();
            $this->contratoModel->saveModalidades((int) $id, $modalidades);
        }

        AuditLogger::log('contrato_criado', ['contrato_id' => $id, 'numero' => $data['numero']]);
        header("Location: /contratos/edit/{$id}?success=created&tab=anexos");
        exit();
    }

    // =========================================================
    // EDITAR
    // =========================================================
    public function edit(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $contrato  = $this->contratoModel->findById((int) $id);

        if (!$contrato || $contrato->usuario_id != $usuarioId) {
            header("Location: /contratos?error=not_found");
            exit();
        }

        $medicos   = (new Medico())->findByUsuarioId($usuarioId, ['status' => 'ativo']);
        $clientes  = (new Cliente())->findByUsuarioId($usuarioId);
        $exames    = (new TabelaExame())->findByUsuarioId($usuarioId);
        $anexos    = $this->anexoModel->findByContratoId((int) $id, $usuarioId);
        $filtrosApur = [
            'contrato_id_raw' => (int) $id,
            'status'          => $_GET['filtro_status'] ?? '',
            'periodo_inicio'  => $_GET['filtro_periodo_inicio'] ?? '',
            'periodo_fim'     => $_GET['filtro_periodo_fim'] ?? '',
        ];
        $apuracoes = $this->apuracaoModel->findByUsuarioId($usuarioId, $filtrosApur);
        $modalidades_contrato = $this->contratoModel->getModalidades((int) $id);
        $layouts   = (new LayoutExame())->findByUsuarioId($usuarioId);
        $contrato_exames = $this->contratoExameModel->findByContratoId((int) $id);

        View::render('contratos/form', [
            'title'    => 'Editar Contrato — ' . $contrato->nome,
            'contrato' => $contrato,
            'medicos'  => $medicos,
            'clientes' => $clientes,
            'exames'   => $exames,
            'anexos'   => $anexos ?? [],
            'apuracoes'=> $apuracoes ?? [],
            'modalidades_contrato' => $modalidades_contrato,
            'layouts'  => $layouts ?? [],
            'contrato_exames' => $contrato_exames ?? [],
            'active_tab' => $_GET['tab'] ?? 'dados',
            '_layout' => 'erp',
        ]);
    }

    // =========================================================
    // ATUALIZAR (update)
    // =========================================================
    public function update(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        $data = [
            'usuario_id'    => $usuarioId,
            'nome'          => trim($_POST['nome'] ?? ''),
            'tipo_parte'    => $_POST['tipo_parte'] ?? 'medico',
            'medico_id'     => (int) ($_POST['medico_id'] ?? 0) ?: null,
            'cliente_id'    => (int) ($_POST['cliente_id'] ?? 0) ?: null,
            'data_inicio'   => $_POST['data_inicio'] ?? '',
            'data_fim'      => $_POST['data_fim'] ?? null,
            'vigencia_tipo' => $_POST['vigencia_tipo'] ?? 'determinado',
            'recorrencia'   => $_POST['recorrencia'] ?? 'mensal',
            'valor'         => (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0'),
            'observacoes'   => trim($_POST['observacoes'] ?? ''),
            'status'        => $_POST['status'] ?? 'ativo',
        ];

        $this->contratoModel->update((int) $id, $data);

        if ($data['tipo_parte'] === 'medico') {
            $modalidades = $this->parseModalidades();
            $this->contratoModel->saveModalidades((int) $id, $modalidades);
        }

        AuditLogger::log('contrato_atualizado', ['contrato_id' => $id]);
        header("Location: /contratos/edit/{$id}?success=updated&tab=" . ($_POST['active_tab'] ?? 'dados'));
        exit();
    }

    // =========================================================
    // EXCLUIR
    // =========================================================
    public function delete(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $this->contratoModel->delete((int) $id, $usuarioId);
        AuditLogger::log('contrato_excluido', ['contrato_id' => $id]);
        header("Location: /contratos?success=deleted");
        exit();
    }

    // =========================================================
    // UPLOAD DE ANEXO
    // =========================================================
    public function uploadAnexo(): void
    {
        $user       = Auth::user();
        $usuarioId  = (int) $user->id;
        $contratoId = (int) ($_POST['contrato_id'] ?? 0);

        if (!$contratoId) { header("Location: /contratos?error=invalid"); exit(); }

        $contrato = $this->contratoModel->findById($contratoId);
        if (!$contrato || $contrato->usuario_id != $usuarioId) {
            header("Location: /contratos?error=not_found"); exit();
        }

        if (!isset($_FILES['anexo'])) {
            header("Location: /contratos/edit/{$contratoId}?error=upload_failed&tab=anexos"); exit();
        }

        $files = $_FILES['anexo'];
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmps  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
        $types = is_array($files['type']) ? $files['type'] : [$files['type']];

        $baseDir = BASE_PATH . '/storage/uploads/contratos/' . $usuarioId . '/' . $contratoId;
        if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

        foreach ($names as $i => $name) {
            if (empty($tmps[$i]) || $tmps[$i] === 'none') continue;
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            $destPath = $baseDir . '/' . $safeName;
            if (move_uploaded_file($tmps[$i], $destPath)) {
                $relativePath = 'storage/uploads/contratos/' . $usuarioId . '/' . $contratoId . '/' . $safeName;
                $this->anexoModel->create([
                    'contrato_id'   => $contratoId,
                    'usuario_id'    => $usuarioId,
                    'file_path'     => $relativePath,
                    'original_name' => $name,
                    'file_size'     => $sizes[$i] ?? null,
                    'mime_type'     => $types[$i] ?? null,
                ]);
            }
        }

        header("Location: /contratos/edit/{$contratoId}?success=upload_ok&tab=anexos");
        exit();
    }

    // =========================================================
    // EXCLUIR ANEXO
    // =========================================================
    public function deleteAnexo(string $anexoId): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $anexo     = $this->anexoModel->delete((int) $anexoId, $usuarioId);
        if ($anexo && !empty($anexo->file_path)) {
            $fullPath = BASE_PATH . '/' . $anexo->file_path;
            if (file_exists($fullPath)) unlink($fullPath);
        }
        $contratoId = $_GET['contrato_id'] ?? 0;
        header("Location: /contratos/edit/{$contratoId}?success=anexo_deleted&tab=anexos");
        exit();
    }

    // =========================================================
    // NOVA APURAÇÃO (criar rascunho)
    // =========================================================
    public function novaApuracao(): void
    {
        $user       = Auth::user();
        $usuarioId  = (int) $user->id;
        $contratoId = (int) ($_POST['contrato_id'] ?? 0);

        $contrato = $this->contratoModel->findById($contratoId);
        if (!$contrato || $contrato->usuario_id != $usuarioId) {
            header("Location: /contratos?error=not_found"); exit();
        }

        $numero = $this->apuracaoModel->gerarNumero();
        $tipo   = ($contrato->tipo_parte === 'medico') ? 'prestador' : 'cliente';

        $apuracaoId = $this->apuracaoModel->create([
            'usuario_id'  => $usuarioId,
            'contrato_id' => $contratoId,
            'numero'      => $numero,
            'tipo'        => $tipo,
            'medico_id'   => $contrato->medico_id,
            'cliente_id'  => $contrato->cliente_id,
            'status'      => 'rascunho',
            'origem'      => 'manual',
        ]);

        AuditLogger::log('apuracao_criada', ['apuracao_id' => $apuracaoId, 'numero' => $numero]);
        header("Location: /contratos/edit/{$contratoId}?success=apuracao_criada&tab=apuracao&apuracao_id={$apuracaoId}");
        exit();
    }

    // =========================================================
    // IMPORTAR ARQUIVO DE APURAÇÃO
    // =========================================================
    public function importarApuracao(): void
    {
        $user       = Auth::user();
        $usuarioId  = (int) $user->id;
        $apuracaoId = (int) ($_POST['apuracao_id'] ?? 0);
        $layoutId   = (int) ($_POST['layout_id'] ?? 0);

        $apuracao = $this->apuracaoModel->findById($apuracaoId);
        if (!$apuracao || $apuracao->usuario_id != $usuarioId) {
            $this->jsonError('Apuração não encontrada'); return;
        }

        if (!isset($_FILES['arquivo_apuracao'])) {
            $this->jsonError('Nenhum arquivo enviado'); return;
        }

        $file    = $_FILES['arquivo_apuracao'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['xlsx', 'xls', 'csv'];
        if (!in_array($ext, $allowed)) {
            $this->jsonError('Formato inválido. Use XLSX, XLS ou CSV.'); return;
        }

        $baseDir = BASE_PATH . '/storage/uploads/apuracoes/' . $usuarioId . '/' . $apuracaoId;
        if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
        $safeName = 'import_' . time() . '.' . $ext;
        $destPath = $baseDir . '/' . $safeName;
        move_uploaded_file($file['tmp_name'], $destPath);

        $relativePath  = 'storage/uploads/apuracoes/' . $usuarioId . '/' . $apuracaoId . '/' . $safeName;
        $periodoInicio = trim($_POST['periodo_inicio'] ?? '');
        $periodoFim    = trim($_POST['periodo_fim'] ?? '');
        $clienteId     = (int) ($_POST['cliente_id'] ?? 0);
        // Validar formato YYYY-MM-DD
        $periodoInicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodoInicio) ? $periodoInicio : null;
        $periodoFim    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodoFim)    ? $periodoFim    : null;
        $updateData = [
            'usuario_id'     => $usuarioId,
            'arquivo_import' => $relativePath,
            'status'         => 'rascunho',
        ];
        if ($periodoInicio)  $updateData['periodo_inicio'] = $periodoInicio;
        if ($periodoFim)     $updateData['periodo_fim']    = $periodoFim;
        if ($clienteId > 0)  $updateData['cliente_id']    = $clienteId;
        $this->apuracaoModel->update($apuracaoId, $updateData);

        $layoutModel = new LayoutExame();
        $layout      = $layoutId ? $layoutModel->findById($layoutId) : $layoutModel->findAtivo($usuarioId);
        $mapeamento  = $layout ? json_decode($layout->mapeamento_json, true) : $this->mapeamentoPadrao();

        $preview = $this->lerArquivo($destPath, $ext, $mapeamento, 5);

        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'arquivo'      => $relativePath,
            'apuracao_id'  => $apuracaoId,
            'preview'      => $preview['linhas'],
            'total_linhas' => $preview['total_linhas'] ?? 0,
        ]);
        exit();
    }

    // =========================================================
    // EXECUTAR APURAÇÃO
    // =========================================================
    public function executarApuracao(): void
    {
        $user       = Auth::user();
        $usuarioId  = (int) $user->id;
        $apuracaoId = (int) ($_POST['apuracao_id'] ?? 0);
        $layoutId   = (int) ($_POST['layout_id'] ?? 0);

        $apuracao = $this->apuracaoModel->findById($apuracaoId);
        if (!$apuracao || $apuracao->usuario_id != $usuarioId) {
            $this->jsonError('Apuração não encontrada'); return;
        }
        if (empty($apuracao->arquivo_import)) {
            $this->jsonError('Importe um arquivo antes de executar'); return;
        }

        $this->apuracaoModel->update($apuracaoId, ['usuario_id' => $usuarioId, 'status' => 'processando']);

        try {
            $filePath   = BASE_PATH . '/' . $apuracao->arquivo_import;
            $ext        = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $layoutModel = new LayoutExame();
            $layout     = $layoutId ? $layoutModel->findById($layoutId) : $layoutModel->findAtivo($usuarioId);
            $mapeamento = $layout ? json_decode($layout->mapeamento_json, true) : $this->mapeamentoPadrao();

            $dados = $this->lerArquivo($filePath, $ext, $mapeamento);

            // Buscar tabela de exames com tags DICOM para matching
            $tabelaExameModel = new TabelaExame();
            $exames = $tabelaExameModel->findAllWithTagsByUsuarioId($usuarioId);

            // Índice 1: por modalidade da tabela (ex: RX, TC)
            $examesPorModalidade = [];
            // Índice 2: por TAG DICOM (ex: CR, DR, RX) — prioridade máxima
            $examesPorTagDicom = [];
            foreach ($exames as $ex) {
                $mod = strtoupper(trim($ex->modalidade ?? ''));
                $examesPorModalidade[$mod][] = $ex;
                // Indexar por cada valor de TAG DICOM cadastrada
                foreach ($ex->tags_dicom as $tagVal) {
                    $examesPorTagDicom[$tagVal][] = $ex;
                }
            }

            // Buscar valores do CONTRATO (PRIORIDADE MÁXIMA — sobrepõe tudo)
            // Índice: tabela_exame_id → {rotina, urgencia, venda_rotina, venda_urgencia}
            $contratoId = (int) ($apuracao->contrato_id ?? 0);
            $valoresContrato = []; // [tabela_exame_id => objeto]
            if ($contratoId > 0) {
                $valoresContrato = $this->contratoExameModel->findMapByContratoId($contratoId);
            }

            // Buscar valores específicos do médico (PRIORIDADE 1 — abaixo do contrato)
            // Índice: tabela_exame_id → {valor_rotina, valor_urgencia, usa_valor_custom}
            $medicoExameModel = new MedicoExame();
            $medicoId = (int) ($apuracao->medico_id ?? 0);
            $valoresMedico = []; // [tabela_exame_id => {rotina, urgencia}]
            if ($medicoId > 0) {
                $examesMedico = $medicoExameModel->findByMedicoId($medicoId);
                foreach ($examesMedico as $me) {
                    $exId = (int) $me->tabela_exame_id;
                    if ($me->usa_valor_custom) {
                        $valoresMedico[$exId] = [
                            'rotina'   => (float) $me->valor_rotina,
                            'urgencia' => (float) $me->valor_urgencia,
                            'fonte'    => 'medico_custom',
                        ];
                    } else {
                        $valoresMedico[$exId] = [
                            'rotina'   => (float) $me->tabela_valor_rotina,
                            'urgencia' => (float) $me->tabela_valor_urgencia,
                            'fonte'    => 'medico_tabela',
                        ];
                    }
                }
            }

            // Determinar tipo da apuração para lógica de preços
            // Prestador: usa preco_custo (repasse ao médico)
            // Cliente:   usa preco_venda (cobrado do cliente) = valor_rotina/valor_urgencia
            $tipoApuracao = $apuracao->tipo ?? 'prestador';

            $this->itemModel->deleteByApuracaoId($apuracaoId);
            $itens           = [];
            $totalNormal     = 0;
            $totalUrgencia   = 0;
            $valorTotal      = 0.0; // valor de custo (prestador)
            $valorVendaTotal = 0.0; // valor de venda (cliente)
            $log             = [];
            $semMatch        = 0;

            foreach ($dados['linhas'] as $linha) {
                $modalidade = strtoupper(trim($linha['modalidade'] ?? ''));
                $studyDesc  = trim($linha['study_description'] ?? '');
                $prioridade = strtolower(trim($linha['prioridade'] ?? 'normal'));
                $isUrgencia = str_contains($prioridade, 'urgent') || $prioridade === 'urgente';
                $tipoPrior  = $isUrgencia ? 'urgencia' : 'normal';

                $exameMatch = null;
                $valorCalc  = 0.0;
                $statusItem = 'sem_match';
                $obsItem    = 'Sem correspondência na tabela de exames';

                // -------------------------------------------------------
                // PRIORIDADE 1: Match por TAG DICOM (tag_valor = modalidade importada)
                // Ex: modalidade=CR → busca exame com TAG DICOM valor=CR
                // -------------------------------------------------------
                if (!empty($examesPorTagDicom[$modalidade])) {
                    $candidatos = $examesPorTagDicom[$modalidade];
                    // Tentar match exato por nome do exame dentro dos candidatos por TAG
                    foreach ($candidatos as $ex) {
                        $nomeEx = strtolower(trim($ex->nome_exame ?? ''));
                        $nomeSD = strtolower($studyDesc);
                        if ($nomeEx && $nomeSD && (str_contains($nomeSD, $nomeEx) || str_contains($nomeEx, $nomeSD))) {
                            $exameMatch = $ex;
                            $obsItem    = 'Match por TAG DICOM + nome';
                            break;
                        }
                    }
                    if (!$exameMatch) {
                        // Fallback: primeiro exame com essa TAG DICOM
                        $exameMatch = $candidatos[0];
                        $obsItem    = 'Match por TAG DICOM (modalidade=' . $modalidade . ')';
                    }
                }

                // -------------------------------------------------------
                // PRIORIDADE 2: Match por modalidade da tabela (campo modalidade)
                // Ex: modalidade=RX → busca exame com modalidade=RX
                // -------------------------------------------------------
                if (!$exameMatch && !empty($examesPorModalidade[$modalidade])) {
                    foreach ($examesPorModalidade[$modalidade] as $ex) {
                        $nomeEx = strtolower(trim($ex->nome_exame ?? ''));
                        $nomeSD = strtolower($studyDesc);
                        if ($nomeEx && $nomeSD && (str_contains($nomeSD, $nomeEx) || str_contains($nomeEx, $nomeSD))) {
                            $exameMatch = $ex;
                            $obsItem    = 'Match por modalidade + nome';
                            break;
                        }
                    }
                    if (!$exameMatch) {
                        $exameMatch = $examesPorModalidade[$modalidade][0];
                        $obsItem    = 'Match por modalidade (sem correspondência exata de nome)';
                    }
                }

                $valorCalcVenda = 0.0; // valor de venda por item

                if ($exameMatch) {
                    $exId = (int) $exameMatch->id;

                    // -------------------------------------------------------
                    // NOVA LÓGICA DE PREÇOS:
                    // - valor_rotina / valor_urgencia = valores DIRETOS do médico (prestador)
                    // - valor_venda_rotina / valor_venda_urgencia = valores de venda (cliente)
                    // -------------------------------------------------------

                    // -------------------------------------------------------
                    // HIERARQUIA DE PREÇOS (do maior para menor prioridade):
                    // P0: Valores do CONTRATO (contrato_exames) — base contábil definitiva
                    // P1: Valores do MÉDICO (medico_exames) — override individual
                    // P2: Valores da TABELA DE EXAMES — padrão do sistema
                    // -------------------------------------------------------

                    // VALOR DE VENDA BASE (tabela de exames)
                    $valorCalcVenda = $isUrgencia
                        ? (float) ($exameMatch->valor_venda_urgencia ?: $exameMatch->valor_urgencia ?: 0)
                        : (float) ($exameMatch->valor_venda_rotina  ?: $exameMatch->valor_rotina  ?: 0);

                    // P0: Contrato tem valores definidos para este exame?
                    if (!empty($valoresContrato[$exId]) && $valoresContrato[$exId]->usa_valor_custom) {
                        $vc = $valoresContrato[$exId];
                        if ($tipoApuracao === 'cliente') {
                            // Contrato cliente: usa valor_venda_rotina/urgencia do contrato
                            $valorCalc      = $isUrgencia ? (float)$vc->valor_venda_urgencia : (float)$vc->valor_venda_rotina;
                            $valorCalcVenda = $valorCalc; // venda = o próprio valor do contrato cliente
                        } else {
                            // Contrato prestador: usa valor_rotina/urgencia do contrato
                            $valorCalc = $isUrgencia ? (float)$vc->valor_urgencia : (float)$vc->valor_rotina;
                            // Venda: se o contrato também tiver venda definida, usa; senão usa tabela
                            if ((float)$vc->valor_venda_rotina > 0 || (float)$vc->valor_venda_urgencia > 0) {
                                $valorCalcVenda = $isUrgencia ? (float)$vc->valor_venda_urgencia : (float)$vc->valor_venda_rotina;
                            }
                        }
                        $obsItem = ($obsItem ? $obsItem . ' | ' : '') . 'Valor: contrato_custom';

                    // P1: Médico tem valores específicos? (apenas para prestador)
                    } elseif ($tipoApuracao !== 'cliente' && !empty($valoresMedico[$exId])) {
                        $vm = $valoresMedico[$exId];
                        $valorCalc = $isUrgencia ? $vm['urgencia'] : $vm['rotina'];
                        $obsItem   = ($obsItem ? $obsItem . ' | ' : '') . 'Valor: ' . $vm['fonte'];

                    // P2: Tabela de exames (padrão)
                    } elseif ($tipoApuracao === 'cliente') {
                        // Apuração cliente: usa valor de venda da tabela
                        $valorCalc = $valorCalcVenda;
                    } else {
                        // Apuração prestador: usa valor_rotina/urgencia DIRETOS da tabela
                        $valorCalc = $isUrgencia
                            ? (float) ($exameMatch->valor_urgencia ?: 0)
                            : (float) ($exameMatch->valor_rotina  ?: 0);
                    }

                    $statusItem = 'ok';
                    if ($obsItem === 'Sem correspondência na tabela de exames') $obsItem = null;
                } else {
                    $semMatch++;
                    $log[] = "Linha {$linha['linha_original']}: sem match — modalidade={$modalidade}, exame={$studyDesc}";
                }

                if ($isUrgencia) $totalUrgencia++; else $totalNormal++;
                $valorTotal      += $valorCalc;
                $valorVendaTotal += $valorCalcVenda;

                $itens[] = [
                    ':apuracao_id'        => $apuracaoId,
                    ':linha_original'     => $linha['linha_original'] ?? null,
                    ':unidade'            => $linha['unidade'] ?? null,
                    ':medico_nome'        => $linha['medico'] ?? null,
                    ':medico_crm'         => $linha['crm'] ?? null,
                    ':revisor'            => $linha['revisor'] ?? null,
                    ':data_revisao'       => $this->formatDatetime($linha['data_revisao'] ?? null),
                    ':modalidade'         => $modalidade ?: null,
                    ':study_description'  => $studyDesc ?: null,
                    ':paciente_nome'      => $linha['paciente'] ?? null,
                    ':paciente_id'        => $linha['paciente_id'] ?? null,
                    ':prioridade'         => $linha['prioridade'] ?? null,
                    ':origem'             => $linha['origem'] ?? null,
                    ':registro'           => $linha['registro'] ?? null,
                    ':data_estudo'        => $this->formatDatetime($linha['data_estudo'] ?? null),
                    ':data_conclusao'     => $this->formatDatetime($linha['data_conclusao'] ?? null),
                    ':sla'                => $linha['sla'] ?? null,
                    ':accession_number'   => $linha['accession_number'] ?? null,
                    ':visita'             => $linha['visita'] ?? null,
                    ':convenio'           => $linha['convenio'] ?? null,
                    ':valor_importado'    => $this->parseMoeda((string)($linha['valor'] ?? '0')),
                    ':valor_exame_import' => $this->parseMoeda((string)($linha['valor_exame'] ?? '0')),
                    ':exame_id'           => $exameMatch ? $exameMatch->id : null,
                    ':valor_calculado'       => $valorCalc,
                    ':valor_calculado_venda'  => $valorCalcVenda,
                    ':tipo_prioridade'        => $tipoPrior,
                    ':status_item'        => $statusItem,
                    ':obs_item'           => $obsItem,
                ];
            }

            $this->itemModel->insertBatch($itens);

            $totalExames = $totalNormal + $totalUrgencia;
            $logStr = empty($log) ? 'Apuração concluída sem erros.' : implode("\n", $log);

            // Atualiza apuração-mãe com status concluído
            $this->apuracaoModel->update($apuracaoId, [
                'usuario_id'       => $usuarioId,
                'status'           => 'concluido',
                'total_exames'     => $totalExames,
                'total_normal'     => $totalNormal,
                'total_urgencia'   => $totalUrgencia,
                'valor_total'      => $valorTotal,
                'valor_venda_total'=> $valorVendaTotal,
                'log_execucao'     => $logStr,
            ]);

            // =================================================================
            // NOVO FLUXO: Se a apuração é do tipo CLIENTE, gerar automaticamente
            // sub-apurações de PRESTADOR para cada médico encontrado na planilha.
            // =================================================================
            $subApuracoesCriadas = [];
            if ($tipoApuracao === 'cliente') {
                // Remove sub-apurações anteriores (re-execução)
                $this->apuracaoModel->deleteSubApuracoesByMaeId($apuracaoId, $usuarioId);

                // Agrupar itens por CRM do médico
                $itensPorCrm = [];
                foreach ($itens as $item) {
                    $crm = trim((string)($item[':medico_crm'] ?? ''));
                    if ($crm === '') $crm = '__sem_crm__';
                    $itensPorCrm[$crm][] = $item;
                }

                $medicoModel   = new Medico();
                $contratoModel = new Contrato();

                foreach ($itensPorCrm as $crm => $itensMedico) {
                    // Identificar o médico cadastrado pelo CRM
                    $medicoObj             = null;
                    $contratoPrestador     = null;
                    $valoresContratoPrest  = [];

                    if ($crm !== '__sem_crm__') {
                        $medicoObj = $medicoModel->findByCrm($usuarioId, $crm);
                        // Fallback: se não encontrou pelo CRM, tentar pelo nome do médico
                        if (!$medicoObj) {
                            $nomeMedicoFallback = trim((string)($itensMedico[0][':medico_nome'] ?? ''));
                            if ($nomeMedicoFallback !== '') {
                                $medicoObj = $medicoModel->findByNome($usuarioId, $nomeMedicoFallback);
                                if ($medicoObj) {
                                    $log[] = "[AVISO] Médico não encontrado pelo CRM '{$crm}' — vinculado pelo nome '{$nomeMedicoFallback}' (ID: {$medicoObj->id})";
                                } else {
                                    $log[] = "[AVISO] Médico não encontrado pelo CRM '{$crm}' nem pelo nome '{$nomeMedicoFallback}' — sub-apuração criada sem vínculo de médico";
                                }
                            } else {
                                $log[] = "[AVISO] Médico não encontrado pelo CRM '{$crm}' e nome não disponível — sub-apuração criada sem vínculo de médico";
                            }
                        }
                    }

                    if ($medicoObj) {
                        // Buscar contrato ativo do médico
                        $contratoPrestador = $contratoModel->findAtivoByMedicoId($usuarioId, (int)$medicoObj->id);
                        if ($contratoPrestador) {
                            $valoresContratoPrest = $this->contratoExameModel->findMapByContratoId((int)$contratoPrestador->id);
                        }
                    }

                    // Recalcular valores de custo (prestador) para cada item
                    $itensPrestador = [];
                    $subTotal       = 0.0;
                    $subNormal      = 0;
                    $subUrgencia    = 0;

                    foreach ($itensMedico as $item) {
                        $exId  = $item[':exame_id'] ? (int)$item[':exame_id'] : null;
                        $isUrg = ($item[':tipo_prioridade'] === 'urgencia');

                        // Calcular valor de custo (prestador) para este item
                        $valorCusto = 0.0;
                        if ($exId) {
                            // P0: Contrato do prestador
                            if (!empty($valoresContratoPrest[$exId]) && (bool)$valoresContratoPrest[$exId]->usa_valor_custom) {
                                $vcp = $valoresContratoPrest[$exId];
                                $valorCusto = $isUrg
                                    ? (float)($vcp->valor_urgencia ?: 0)
                                    : (float)($vcp->valor_rotina  ?: 0);
                            }
                            // P1: Valores do médico (medico_exames)
                            if ($valorCusto == 0.0 && $medicoObj && !empty($valoresMedico[$exId])) {
                                $vm = $valoresMedico[$exId];
                                $valorCusto = $isUrg
                                    ? (float)($vm['urgencia'] ?: 0)
                                    : (float)($vm['rotina']   ?: 0);
                            }
                            // P2: Tabela de exames (valor_rotina/urgencia = custo)
                            if ($valorCusto == 0.0) {
                                foreach ($exames as $ex) {
                                    if ((int)$ex->id === $exId) {
                                        $valorCusto = $isUrg
                                            ? (float)($ex->valor_urgencia ?: 0)
                                            : (float)($ex->valor_rotina  ?: 0);
                                        break;
                                    }
                                }
                            }
                        }

                        $subTotal += $valorCusto;
                        if ($isUrg) $subUrgencia++; else $subNormal++;

                        // Item da sub-apuração usa valor_calculado = custo, valor_calculado_venda = 0
                        $itensPrestador[] = array_merge($item, [
                            ':valor_calculado'       => $valorCusto,
                            ':valor_calculado_venda' => 0.0,
                        ]);
                    }

                    // Criar a sub-apuração de prestador
                    $subNumero = $this->apuracaoModel->gerarNumero();
                    $subId = $this->apuracaoModel->createSubApuracao([
                        'usuario_id'      => $usuarioId,
                        'contrato_id'     => $contratoPrestador ? (int)$contratoPrestador->id : (int)$apuracao->contrato_id,
                        'apuracao_mae_id' => $apuracaoId,
                        'numero'          => $subNumero,
                        'medico_id'       => $medicoObj ? (int)$medicoObj->id : null,
                        'cliente_id'      => null,
                        'periodo_inicio'  => $apuracao->periodo_inicio,
                        'periodo_fim'     => $apuracao->periodo_fim,
                        'total_exames'    => $subNormal + $subUrgencia,
                        'total_normal'    => $subNormal,
                        'total_urgencia'  => $subUrgencia,
                        'valor_total'     => $subTotal,
                        'status'          => 'concluido',
                        'arquivo_import'  => $apuracao->arquivo_import,
                        'log_execucao'    => 'Sub-apuração gerada automaticamente a partir de ' . $apuracao->numero,
                    ]);

                    if ($subId) {
                        // Inserir itens da sub-apuração com o ID correto
                        $itensPrestadorComId = array_map(function($it) use ($subId) {
                            $it[':apuracao_id'] = (int)$subId;
                            return $it;
                        }, $itensPrestador);
                        $this->itemModel->insertBatch($itensPrestadorComId);

                        $nomeMedico = $medicoObj ? $medicoObj->nome : ($crm === '__sem_crm__' ? 'Sem CRM' : $crm);
                        $subApuracoesCriadas[] = [
                            'id'     => $subId,
                            'numero' => $subNumero,
                            'medico' => $nomeMedico,
                            'total'  => $subNormal + $subUrgencia,
                            'valor'  => 'R$ ' . number_format($subTotal, 2, ',', '.'),
                        ];
                        $log[] = "Sub-apuração prestador: {$subNumero} — {$nomeMedico} — " . ($subNormal + $subUrgencia) . " exames — R$ " . number_format($subTotal, 2, ',', '.');
                    }
                }

                // Atualizar log da apuração-mãe com as sub-apurações criadas
                $this->apuracaoModel->update($apuracaoId, [
                    'usuario_id'   => $usuarioId,
                    'log_execucao' => implode("\n", $log),
                ]);
            }

            AuditLogger::log('apuracao_executada', [
                'apuracao_id'   => $apuracaoId,
                'total_exames'  => $totalExames,
                'valor_total'   => $valorTotal,
                'valor_venda'   => $valorVendaTotal,
                'sem_match'     => $semMatch,
                'sub_apuracoes' => count($subApuracoesCriadas),
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success'        => true,
                'total_exames'   => $totalExames,
                'total_normal'   => $totalNormal,
                'total_urgencia' => $totalUrgencia,
                'valor_total'    => 'R$ ' . number_format($valorTotal, 2, ',', '.'),
                'valor_venda'    => 'R$ ' . number_format($valorVendaTotal, 2, ',', '.'),
                'sem_match'      => $semMatch,
                'sub_apuracoes'  => $subApuracoesCriadas,
                'log'            => implode("\n", $log),
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('[Apuracao] Erro ao executar', [
                'apuracao_id' => $apuracaoId,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            $this->apuracaoModel->update($apuracaoId, [
                'usuario_id'   => $usuarioId,
                'status'       => 'erro',
                'log_execucao' => $e->getMessage(),
            ]);
            $this->jsonError('Erro ao processar: ' . $e->getMessage());
        }
        exit();
    }

    // =========================================================
    // HELPERS PRIVADOS
    // =========================================================
    private function parseModalidades(): array
    {
        $modalidades = $_POST['modalidades'] ?? [];
        $exames      = $_POST['modalidade_exame_id'] ?? [];
        $result = [];
        foreach ($modalidades as $i => $mod) {
            if (empty($mod)) continue;
            $result[] = [
                'modalidade' => strtoupper(trim($mod)),
                'exame_id'   => !empty($exames[$i]) ? (int) $exames[$i] : null,
            ];
        }
        return $result;
    }

    private function mapeamentoPadrao(): array
    {
        return [
            'col_seq' => 'A', 'col_unidade' => 'B', 'col_id' => 'C',
            'col_medico' => 'D', 'col_crm' => 'E', 'col_revisor' => 'F',
            'col_data_revisao' => 'G', 'col_modalidade' => 'H',
            'col_study_description' => 'I', 'col_paciente' => 'J',
            'col_paciente_id' => 'K', 'col_prioridade' => 'L',
            'col_origem' => 'M', 'col_registro' => 'N',
            'col_data_estudo' => 'O', 'col_data_conclusao' => 'P',
            'col_sla' => 'Q', 'col_accession_number' => 'R',
            'col_visita' => 'S', 'col_convenio' => 'T',
            'col_valor' => 'U', 'col_valor_exame' => 'V',
            'linha_inicio' => 2,
            'campo_prioridade_urgencia' => 'Urgente',
            'campo_prioridade_normal'   => 'Normal',
        ];
    }

    private function lerArquivo(string $path, string $ext, array $mapeamento, int $limit = 0): array
    {
        if (!file_exists($path)) return ['linhas' => [], 'total_linhas' => 0];
        if ($ext === 'csv') return $this->lerCsv($path, $mapeamento, $limit);
        return $this->lerXlsx($path, $mapeamento, $limit);
    }

    /**
     * Lê um arquivo XLSX usando PHP nativo (ZipArchive + SimpleXML).
     * Não requer PhpSpreadsheet nem nenhuma dependência externa.
     */
    private function lerXlsx(string $path, array $mapeamento, int $limit = 0): array
    {
        if (!class_exists('ZipArchive')) {
            return ['linhas' => [], 'total_linhas' => 0, 'erro' => 'ZipArchive não disponível no servidor'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return ['linhas' => [], 'total_linhas' => 0, 'erro' => 'Não foi possível abrir o arquivo XLSX'];
        }

        // Carregar strings compartilhadas (sharedStrings.xml)
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($ssXml);
            if ($ss) {
                foreach ($ss->si as $si) {
                    // Concatenar todos os nós <t> dentro de <si> (rich text)
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    if ($text === '' && isset($si->t)) {
                        $text = (string) $si->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Carregar a primeira planilha
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return ['linhas' => [], 'total_linhas' => 0, 'erro' => 'Planilha não encontrada no arquivo XLSX'];
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) {
            return ['linhas' => [], 'total_linhas' => 0, 'erro' => 'Erro ao parsear XML da planilha'];
        }

        // Converter XML em array bidimensional [rowNum][colIndex] = valor
        $grid = [];
        $ns   = $sheet->getNamespaces(true);
        $rows = $sheet->sheetData->row ?? [];

        foreach ($rows as $rowNode) {
            $rowNum = (int) $rowNode['r'];
            foreach ($rowNode->c as $cell) {
                $cellRef = (string) $cell['r'];                    // ex: "A1"
                $colLet  = preg_replace('/[0-9]/', '', $cellRef);  // ex: "A"
                $colIdx  = $this->colIndex($colLet);               // ex: 1
                $type    = (string) ($cell['t'] ?? '');
                $rawVal  = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's') {
                    // Shared string
                    $val = $sharedStrings[(int) $rawVal] ?? '';
                } elseif ($type === 'str' || $type === 'inlineStr') {
                    $val = isset($cell->is->t) ? (string) $cell->is->t : $rawVal;
                } else {
                    $val = $rawVal;
                }

                $grid[$rowNum][$colIdx] = $val;
            }
        }

        return $this->processarGrid($grid, $mapeamento, $limit);
    }

    /**
     * Processa o grid bidimensional extraído do XLSX nativo.
     */
    private function processarGrid(array $grid, array $mapeamento, int $limit = 0): array
    {
        $linhaInicio = (int) ($mapeamento['linha_inicio'] ?? 2);
        $linhas      = [];
        $count       = 0;

        $colMap = [
            'seq'               => $this->colIndex($mapeamento['col_seq'] ?? 'A'),
            'unidade'           => $this->colIndex($mapeamento['col_unidade'] ?? 'B'),
            'id'                => $this->colIndex($mapeamento['col_id'] ?? 'C'),
            'medico'            => $this->colIndex($mapeamento['col_medico'] ?? 'D'),
            'crm'               => $this->colIndex($mapeamento['col_crm'] ?? 'E'),
            'revisor'           => $this->colIndex($mapeamento['col_revisor'] ?? 'F'),
            'data_revisao'      => $this->colIndex($mapeamento['col_data_revisao'] ?? 'G'),
            'modalidade'        => $this->colIndex($mapeamento['col_modalidade'] ?? 'H'),
            'study_description' => $this->colIndex($mapeamento['col_study_description'] ?? 'I'),
            'paciente'          => $this->colIndex($mapeamento['col_paciente'] ?? 'J'),
            'paciente_id'       => $this->colIndex($mapeamento['col_paciente_id'] ?? 'K'),
            'prioridade'        => $this->colIndex($mapeamento['col_prioridade'] ?? 'L'),
            'origem'            => $this->colIndex($mapeamento['col_origem'] ?? 'M'),
            'registro'          => $this->colIndex($mapeamento['col_registro'] ?? 'N'),
            'data_estudo'       => $this->colIndex($mapeamento['col_data_estudo'] ?? 'O'),
            'data_conclusao'    => $this->colIndex($mapeamento['col_data_conclusao'] ?? 'P'),
            'sla'               => $this->colIndex($mapeamento['col_sla'] ?? 'Q'),
            'accession_number'  => $this->colIndex($mapeamento['col_accession_number'] ?? 'R'),
            'visita'            => $this->colIndex($mapeamento['col_visita'] ?? 'S'),
            'convenio'          => $this->colIndex($mapeamento['col_convenio'] ?? 'T'),
            'valor'             => $this->colIndex($mapeamento['col_valor'] ?? 'U'),
            'valor_exame'       => $this->colIndex($mapeamento['col_valor_exame'] ?? 'V'),
        ];

        $maxRow = empty($grid) ? 0 : max(array_keys($grid));

        for ($row = $linhaInicio; $row <= $maxRow; $row++) {
            if (!isset($grid[$row])) continue;
            $seq = $grid[$row][$colMap['seq']] ?? '';
            if ($seq === '' || $seq === null) continue;

            $linha = ['linha_original' => $row];
            foreach ($colMap as $campo => $colIdx) {
                $val = $grid[$row][$colIdx] ?? null;

                // Converter serial de data do Excel para string legível
                if (in_array($campo, ['data_revisao', 'data_estudo', 'data_conclusao'])
                    && is_numeric($val) && (float) $val > 1000) {
                    $val = $this->excelSerialToDate((float) $val);
                }

                $linha[$campo] = $val;
            }
            $linhas[] = $linha;
            $count++;
            if ($limit > 0 && $count >= $limit) break;
        }

        return ['linhas' => $linhas, 'total_linhas' => $limit > 0 ? $count : ($maxRow - $linhaInicio + 1)];
    }

    /**
     * Converte serial numérico de data do Excel para string 'Y-m-d H:i:s'.
     * Fórmula: dias desde 1900-01-01 (com bug de 1900 como bissexto).
     */
    private function excelSerialToDate(float $serial): string
    {
        // Excel conta 1900-01-01 como dia 1, com bug do dia 60 (1900-02-29 inexistente)
        $unixTimestamp = ($serial - 25569) * 86400;
        return gmdate('Y-m-d H:i:s', (int) $unixTimestamp);
    }

    private function lerCsv(string $path, array $mapeamento, int $limit = 0): array
    {
        $linhas      = [];
        $handle      = fopen($path, 'r');
        if (!$handle) return ['linhas' => [], 'total_linhas' => 0];
        $linhaInicio = (int) ($mapeamento['linha_inicio'] ?? 2);
        $row = 0; $count = 0;
        while (($data = fgetcsv($handle, 2000, ';')) !== false) {
            $row++;
            if ($row < $linhaInicio) continue;
            $linhas[] = $this->mapearLinhaCsv($data, $mapeamento, $row);
            $count++;
            if ($limit > 0 && $count >= $limit) break;
        }
        fclose($handle);
        return ['linhas' => $linhas, 'total_linhas' => $count];
    }

    // processarSheet() foi substituído por processarGrid() acima (leitura nativa XLSX)

    private function mapearLinhaCsv(array $data, array $mapeamento, int $row): array
    {
        $campos = ['seq','unidade','id','medico','crm','revisor','data_revisao','modalidade',
                   'study_description','paciente','paciente_id','prioridade','origem','registro',
                   'data_estudo','data_conclusao','sla','accession_number','visita','convenio','valor','valor_exame'];
        $linha = ['linha_original' => $row];
        foreach ($campos as $i => $campo) {
            $linha[$campo] = $data[$i] ?? null;
        }
        return $linha;
    }

    private function colIndex(string $col): int
    {
        $col    = strtoupper(trim($col));
        $result = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $result = $result * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $result;
    }

    private function parseMoeda(string $val): float
    {
        $val = preg_replace('/[^\d,.]/', '', $val);
        $val = str_replace(',', '.', $val);
        return (float) $val;
    }

    private function formatDatetime(mixed $val): ?string
    {
        if (empty($val)) return null;
        if ($val instanceof \DateTime) return $val->format('Y-m-d H:i:s');
        if (is_string($val)) {
            // Tentar converter formatos comuns
            $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d'];
            foreach ($formats as $fmt) {
                $dt = \DateTime::createFromFormat($fmt, $val);
                if ($dt) return $dt->format('Y-m-d H:i:s');
            }
        }
        return (string) $val;
    }

    private function jsonError(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    // =========================================================
    // GERAR COBRANÇAS (Contas a Receber) a partir do contrato
    // =========================================================
    /**
     * POST /contratos/gerar-cobrancas/{id}
     */
    public function gerarCobrancas(string $id): void
    {
        ob_start();
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        try {
            $contrato = $this->contratoModel->findById((int)$id);
            if (!$contrato || (int)$contrato->usuario_id !== $usuarioId) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Contrato não encontrado.']);
                exit();
            }

            $planoContaId    = (int)($_POST['plano_conta_id'] ?? 0);
            $meioPagamento   = trim($_POST['meio_pagamento'] ?? '');
            $totalParcelas   = (int)($_POST['total_parcelas'] ?? 0);
            $dataVencInicial = trim($_POST['data_vencimento_inicial'] ?? '');

            if ($planoContaId <= 0) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Selecione um Plano de Conta.']); exit();
            }
            if ($totalParcelas <= 0) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Informe o número de parcelas.']); exit();
            }
            if (empty($dataVencInicial) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataVencInicial)) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Informe a data do primeiro vencimento.']); exit();
            }

            // Verificar se já existem cobranças
            $contaReceberModel   = new ContaReceber();
            $cobrancasExistentes = $contaReceberModel->findByContratoId($usuarioId, (int)$id);
            if (!empty($cobrancasExistentes) && (int)($_POST['forcar'] ?? 0) !== 1) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode([
                    'success'            => false,
                    'message'            => 'Já existem ' . count($cobrancasExistentes) . ' cobrança(s) para este contrato. Envie forcar=1 para regenerar.',
                    'existentes'         => count($cobrancasExistentes),
                    'requer_confirmacao' => true,
                ]); exit();
            }

            $recorrenciaService = new ContaReceberRecorrenciaService();
            $resultado = $recorrenciaService->gerarParcelasDeContrato(
                $usuarioId, $contrato, $planoContaId, $meioPagamento, $totalParcelas, $dataVencInicial
            );

            if ($resultado['sucesso']) {
                $this->contratoModel->update((int)$id, [
                    'cobrancas_geradas'    => 1,
                    'cobrancas_geradas_em' => date('Y-m-d H:i:s'),
                    'plano_conta_id'       => $planoContaId,
                    'meio_pagamento'       => $meioPagamento ?: null,
                    'num_parcelas'         => $totalParcelas,
                ]);
                AuditLogger::log('contrato_cobrancas_geradas', [
                    'contrato_id' => (int)$id, 'usuario_id' => $usuarioId,
                    'total' => $resultado['geradas'], 'grupo' => $resultado['grupo'],
                ]);
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $resultado['geradas'] . ' parcela(s) gerada(s) com sucesso!',
                    'geradas' => $resultado['geradas'],
                    'grupo'   => $resultado['grupo'],
                    'ids'     => $resultado['ids'],
                    'erros'   => $resultado['erros'],
                ]);
            } else {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => implode('; ', $resultado['erros']) ?: 'Falha ao gerar cobranças.',
                    'erros'   => $resultado['erros'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Contratos] Erro ao gerar cobranças: ' . $e->getMessage(), ['contrato_id' => $id]);
            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit();
    }

    /**
     * GET /contratos/cobrancas/{id}
     */
    public function listarCobrancas(string $id): void
    {
        ob_start();
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        try {
            $contrato = $this->contratoModel->findById((int)$id);
            if (!$contrato || (int)$contrato->usuario_id !== $usuarioId) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'cobrancas' => []]); exit();
            }

            $contaReceberModel = new ContaReceber();
            $cobrancas = $contaReceberModel->findByContratoId($usuarioId, (int)$id);
            $hoje = date('Y-m-d');

            $lista = array_map(function($c) use ($hoje) {
                $venc     = $c->data_vencimento ?? '';
                $status   = $c->status ?? 'aberta';
                $atrasada = ($status === 'aberta' && $venc < $hoje);
                return [
                    'id'                  => (int)$c->id,
                    'numero_parcela'      => (int)($c->numero_parcela ?? 0),
                    'total_parcelas'      => (int)($c->total_parcelas ?? 0),
                    'descricao'           => $c->descricao ?? '',
                    'valor'               => number_format((float)($c->valor ?? 0), 2, ',', '.'),
                    'data_vencimento'     => $venc,
                    'data_vencimento_fmt' => $venc ? date('d/m/Y', strtotime($venc)) : '',
                    'status'              => $status,
                    'atrasada'            => $atrasada,
                    'edit_url'            => '/financeiro/contas-a-receber/edit/' . (int)$c->id,
                ];
            }, $cobrancas);

            $totalValor = array_sum(array_map(fn($c) => (float)($c->valor ?? 0), $cobrancas));
            $totalPagas = count(array_filter($cobrancas, fn($c) => ($c->status ?? '') === 'recebida'));

            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode([
                'success'     => true,
                'cobrancas'   => $lista,
                'total'       => count($lista),
                'total_valor' => number_format($totalValor, 2, ',', '.'),
                'total_pagas' => $totalPagas,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Contratos] Erro ao listar cobranças: ' . $e->getMessage());
            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'cobrancas' => []]);
        }
        exit();
    }

    // =========================================================
    // EXAMES DO CONTRATO (Serviços/Exames)
    // =========================================================

    /**
     * POST /contratos/exames/salvar
     * Salva (upsert) um exame vinculado ao contrato com valores customizados.
     */
    public function salvarExameContrato(): void
    {
        ob_start();
        try {
            $user      = Auth::user();
            $usuarioId = (int) $user->id;

            $contratoId    = (int) ($_POST['contrato_id'] ?? 0);
            $tabelaExameId = (int) ($_POST['tabela_exame_id'] ?? 0);

            if (!$contratoId || !$tabelaExameId) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
                exit();
            }

            // Verificar se o contrato pertence ao usuário
            $contrato = $this->contratoModel->findById($contratoId);
            if (!$contrato || $contrato->usuario_id != $usuarioId) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Contrato não encontrado.']);
                exit();
            }

            $parseMoeda = fn($v) => (float) str_replace(['.', ','], ['', '.'], $v ?? '0');

            $data = [
                'usuario_id'           => $usuarioId,
                'contrato_id'          => $contratoId,
                'tabela_exame_id'      => $tabelaExameId,
                'valor_rotina'         => $parseMoeda($_POST['valor_rotina'] ?? '0'),
                'valor_urgencia'       => $parseMoeda($_POST['valor_urgencia'] ?? '0'),
                'valor_venda_rotina'   => $parseMoeda($_POST['valor_venda_rotina'] ?? '0'),
                'valor_venda_urgencia' => $parseMoeda($_POST['valor_venda_urgencia'] ?? '0'),
                'usa_valor_custom'     => (int) ($_POST['usa_valor_custom'] ?? 0),
                'observacoes'          => trim($_POST['observacoes'] ?? ''),
            ];

            $ok = $this->contratoExameModel->upsert($data);

            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Exame salvo com sucesso.' : 'Erro ao salvar exame.',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Contratos] Erro ao salvar exame do contrato: ' . $e->getMessage());
            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit();
    }

    /**
     * POST /contratos/exames/remover
     * Remove um exame vinculado ao contrato.
     */
    public function removerExameContrato(): void
    {
        ob_start();
        try {
            $user      = Auth::user();
            $usuarioId = (int) $user->id;

            $contratoId    = (int) ($_POST['contrato_id'] ?? 0);
            $tabelaExameId = (int) ($_POST['tabela_exame_id'] ?? 0);

            if (!$contratoId || !$tabelaExameId) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
                exit();
            }

            // Verificar se o contrato pertence ao usuário
            $contrato = $this->contratoModel->findById($contratoId);
            if (!$contrato || $contrato->usuario_id != $usuarioId) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Contrato não encontrado.']);
                exit();
            }

            $ok = $this->contratoExameModel->deleteByContratoAndExame($contratoId, $tabelaExameId);

            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Exame removido.' : 'Erro ao remover exame.',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Contratos] Erro ao remover exame do contrato: ' . $e->getMessage());
            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit();
    }

    /**
     * GET /contratos/exames/buscar-tabela
     * Retorna os dados de um exame da tabela para pré-preencher o formulário.
     */
    public function buscarExameTabela(): void
    {
        ob_start();
        try {
            $user      = Auth::user();
            $usuarioId = (int) $user->id;
            $exameId   = (int) ($_GET['exame_id'] ?? 0);

            if (!$exameId) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false]);
                exit();
            }

            $exame = (new TabelaExame())->findById($exameId);
            if (!$exame) {
                ob_end_clean(); header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Exame não encontrado.']);
                exit();
            }

            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode([
                'success'              => true,
                'nome_exame'           => $exame->nome_exame,
                'modalidade'           => $exame->modalidade,
                'valor_rotina'         => number_format((float)($exame->valor_rotina ?? 0), 2, '.', ''),
                'valor_urgencia'       => number_format((float)($exame->valor_urgencia ?? 0), 2, '.', ''),
                'valor_venda_rotina'   => number_format((float)($exame->valor_venda_rotina ?? 0), 2, '.', ''),
                'valor_venda_urgencia' => number_format((float)($exame->valor_venda_urgencia ?? 0), 2, '.', ''),
            ]);
        } catch (\Throwable $e) {
            ob_end_clean(); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}
