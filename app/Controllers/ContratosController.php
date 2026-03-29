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

class ContratosController extends Controller
{
    private Contrato      $contratoModel;
    private ContratoAnexo $anexoModel;
    private Apuracao      $apuracaoModel;
    private ApuracaoItem  $itemModel;
    private Logger        $logger;

    public function __construct()
    {
        $this->contratoModel = new Contrato();
        $this->anexoModel    = new ContratoAnexo();
        $this->apuracaoModel = new Apuracao();
        $this->itemModel     = new ApuracaoItem();
        $this->logger        = new Logger();
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
        $apuracoes = $this->apuracaoModel->findByUsuarioId($usuarioId, ['contrato_id_raw' => (int) $id]);
        $modalidades_contrato = $this->contratoModel->getModalidades((int) $id);
        $layouts   = (new LayoutExame())->findByUsuarioId($usuarioId);

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

        $relativePath = 'storage/uploads/apuracoes/' . $usuarioId . '/' . $apuracaoId . '/' . $safeName;
        $this->apuracaoModel->update($apuracaoId, [
            'usuario_id'     => $usuarioId,
            'arquivo_import' => $relativePath,
            'status'         => 'rascunho',
        ]);

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

            // Buscar tabela de exames para matching
            $tabelaExameModel = new TabelaExame();
            $exames = $tabelaExameModel->findByUsuarioId($usuarioId);
            $examesPorModalidade = [];
            foreach ($exames as $ex) {
                $mod = strtoupper(trim($ex->modalidade ?? ''));
                $examesPorModalidade[$mod][] = $ex;
            }

            $this->itemModel->deleteByApuracaoId($apuracaoId);
            $itens         = [];
            $totalNormal   = 0;
            $totalUrgencia = 0;
            $valorTotal    = 0.0;
            $log           = [];
            $semMatch      = 0;

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

                if (!empty($examesPorModalidade[$modalidade])) {
                    foreach ($examesPorModalidade[$modalidade] as $ex) {
                        $nomeEx = strtolower(trim($ex->nome_exame ?? ''));
                        $nomeSD = strtolower($studyDesc);
                        if ($nomeEx && $nomeSD && (str_contains($nomeSD, $nomeEx) || str_contains($nomeEx, $nomeSD))) {
                            $exameMatch = $ex; break;
                        }
                    }
                    if (!$exameMatch) {
                        $exameMatch = $examesPorModalidade[$modalidade][0];
                        $obsItem    = 'Match por modalidade (sem correspondência exata de nome)';
                    }
                }

                if ($exameMatch) {
                    $valorCalc  = $isUrgencia
                        ? (float) ($exameMatch->valor_urgencia ?: $exameMatch->valor_padrao)
                        : (float) ($exameMatch->valor_rotina  ?: $exameMatch->valor_padrao);
                    $statusItem = 'ok';
                    $obsItem    = null;
                } else {
                    $semMatch++;
                    $log[] = "Linha {$linha['linha_original']}: sem match — modalidade={$modalidade}, exame={$studyDesc}";
                }

                if ($isUrgencia) $totalUrgencia++; else $totalNormal++;
                $valorTotal += $valorCalc;

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
                    ':valor_calculado'    => $valorCalc,
                    ':tipo_prioridade'    => $tipoPrior,
                    ':status_item'        => $statusItem,
                    ':obs_item'           => $obsItem,
                ];
            }

            $this->itemModel->insertBatch($itens);

            $totalExames = $totalNormal + $totalUrgencia;
            $logStr = empty($log) ? 'Apuração concluída sem erros.' : implode("\n", $log);

            $this->apuracaoModel->update($apuracaoId, [
                'usuario_id'     => $usuarioId,
                'status'         => 'concluido',
                'total_exames'   => $totalExames,
                'total_normal'   => $totalNormal,
                'total_urgencia' => $totalUrgencia,
                'valor_total'    => $valorTotal,
                'log_execucao'   => $logStr,
            ]);

            AuditLogger::log('apuracao_executada', [
                'apuracao_id'  => $apuracaoId,
                'total_exames' => $totalExames,
                'valor_total'  => $valorTotal,
                'sem_match'    => $semMatch,
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success'        => true,
                'total_exames'   => $totalExames,
                'total_normal'   => $totalNormal,
                'total_urgencia' => $totalUrgencia,
                'valor_total'    => 'R$ ' . number_format($valorTotal, 2, ',', '.'),
                'sem_match'      => $semMatch,
                'log'            => $logStr,
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

    private function lerXlsx(string $path, array $mapeamento, int $limit = 0): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            require_once BASE_PATH . '/vendor/autoload.php';
        }
        $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $spreadsheet = $reader->load($path);
        $ws          = $spreadsheet->getActiveSheet();
        return $this->processarSheet($ws, $mapeamento, $limit);
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

    private function processarSheet($ws, array $mapeamento, int $limit = 0): array
    {
        $linhaInicio = (int) ($mapeamento['linha_inicio'] ?? 2);
        $maxRow      = $ws->getHighestRow();
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

        for ($row = $linhaInicio; $row <= $maxRow; $row++) {
            $seq = $ws->getCellByColumnAndRow($colMap['seq'], $row)->getValue();
            if (empty($seq) && $seq !== '0') continue;

            $linha = ['linha_original' => $row];
            foreach ($colMap as $campo => $colIdx) {
                $val = $ws->getCellByColumnAndRow($colIdx, $row)->getValue();
                if (in_array($campo, ['data_revisao', 'data_estudo', 'data_conclusao']) && is_numeric($val) && $val > 0) {
                    try {
                        $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) { /* mantém */ }
                }
                $linha[$campo] = $val;
            }
            $linhas[] = $linha;
            $count++;
            if ($limit > 0 && $count >= $limit) break;
        }

        return ['linhas' => $linhas, 'total_linhas' => $limit > 0 ? $count : ($maxRow - $linhaInicio + 1)];
    }

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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
}
