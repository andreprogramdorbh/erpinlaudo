<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\Colaborador;
use App\Models\ColaboradorAnexo;
use App\Models\ColaboradorComissao;
use App\Models\ContaReceber;
use App\Models\User;
use App\Services\CnpjService;

class ColaboradorController extends Controller
{
    private Colaborador        $model;
    private ColaboradorAnexo   $anexoModel;
    private ColaboradorComissao $comissaoModel;
    private ContaReceber       $contaReceberModel;
    private User               $userModel;
    private Logger             $logger;

    public function __construct()
    {
        $this->model             = new Colaborador();
        $this->anexoModel        = new ColaboradorAnexo();
        $this->comissaoModel     = new ColaboradorComissao();
        $this->contaReceberModel = new ContaReceber();
        $this->userModel         = new User();
        $this->logger            = new Logger();
    }

    // ─── Listagem ─────────────────────────────────────────────────────────────

    public function index(): void
    {
        if (!Auth::can('view_colaboradores')) {
            header("Location: /dashboard?error=unauthorized"); exit();
        }
        $usuarioId = Auth::user()->id;
        $filtros   = [
            'pesquisa'         => $_GET['q']    ?? '',
            'status'           => $_GET['status'] ?? 'ativo',
            'tipo_contratacao' => $_GET['tipo']  ?? '',
        ];
        $colaboradores = $this->model->findByUsuarioId($usuarioId, $filtros);
        View::render('colaboradores/index', [
            'title'         => 'Colaboradores',
            'colaboradores' => $colaboradores,
            'filtros'       => $filtros,
            '_layout'       => 'erp',
        ]);
    }

    // ─── Criação ──────────────────────────────────────────────────────────────

    public function create(): void
    {
        if (!Auth::can('create_colaboradores')) {
            header("Location: /colaboradores?error=unauthorized"); exit();
        }
        View::render('colaboradores/form', [
            'title'       => 'Novo Colaborador',
            'colaborador' => null,
            'isEdit'      => false,
            '_layout'     => 'erp',
        ]);
    }

    public function store(): void
    {
        if (!Auth::can('create_colaboradores')) {
            header("Location: /colaboradores?error=unauthorized"); exit();
        }
        try {
            $usuarioId = Auth::user()->id;
            $dados     = $this->extrairDadosPost($usuarioId);

            // Validação básica
            if (empty($dados['nome']) || empty($dados['cpf_cnpj'])) {
                header("Location: /colaboradores/create?error=missing_fields"); exit();
            }

            // Unicidade CPF/CNPJ por tenant
            if ($this->model->findByCpfCnpjAndUsuarioId($dados['cpf_cnpj'], $usuarioId)) {
                header("Location: /colaboradores/create?error=cpf_cnpj_exists"); exit();
            }

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_colaborador', ['id' => $id, 'nome' => $dados['nome']]);
                $this->logger->info('[Colaborador] store OK', ['id' => $id]);
                header("Location: /colaboradores/edit/{$id}?success=created&tab=geral");
            } else {
                header("Location: /colaboradores/create?error=create_failed");
            }
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] store exception: ' . $e->getMessage());
            header("Location: /colaboradores/create?error=exception");
        }
        exit();
    }

    // ─── Edição ───────────────────────────────────────────────────────────────

    public function edit(int $id): void
    {
        if (!Auth::can('edit_colaboradores')) {
            header("Location: /colaboradores?error=unauthorized"); exit();
        }
        $usuarioId   = Auth::user()->id;
        $colaborador = $this->model->findByIdAndUsuarioId($id, $usuarioId);
        if (!$colaborador) {
            header("Location: /colaboradores?error=not_found"); exit();
        }
        $activeTab  = $_GET['tab'] ?? 'geral';
        $anexos     = $this->anexoModel->findByColaboradorId($id, $usuarioId);
        $comissoes  = $this->comissaoModel->findByColaboradorId($id, $usuarioId);
        $faturamentos = $this->buscarFaturamentoColaborador($id, $usuarioId);
        $usuarios   = $this->userModel->findAll();

        View::render('colaboradores/form', [
            'title'         => 'Editar Colaborador',
            'colaborador'   => $colaborador,
            'isEdit'        => true,
            'activeTab'     => $activeTab,
            'anexos'        => $anexos,
            'comissoes'     => $comissoes,
            'faturamentos'  => $faturamentos,
            'usuarios'      => $usuarios,
            '_layout'       => 'erp',
        ]);
    }

    public function update(int $id): void
    {
        if (!Auth::can('edit_colaboradores')) {
            header("Location: /colaboradores?error=unauthorized"); exit();
        }
        $usuarioId   = Auth::user()->id;
        $colaborador = $this->model->findByIdAndUsuarioId($id, $usuarioId);
        if (!$colaborador) {
            header("Location: /colaboradores?error=not_found"); exit();
        }
        try {
            $dados = $this->extrairDadosPost($usuarioId);
            // Verifica unicidade CPF/CNPJ excluindo o próprio registro
            $existente = $this->model->findByCpfCnpjAndUsuarioId($dados['cpf_cnpj'], $usuarioId);
            if ($existente && (int)$existente->id !== $id) {
                header("Location: /colaboradores/edit/{$id}?error=cpf_cnpj_exists&tab=geral"); exit();
            }
            if ($this->model->update($id, $dados)) {
                AuditLogger::log('update_colaborador', ['id' => $id, 'nome' => $dados['nome']]);
                header("Location: /colaboradores/edit/{$id}?success=updated&tab=geral");
            } else {
                header("Location: /colaboradores/edit/{$id}?error=update_failed&tab=geral");
            }
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] update exception: ' . $e->getMessage());
            header("Location: /colaboradores/edit/{$id}?error=exception&tab=geral");
        }
        exit();
    }

    // ─── Exclusão ─────────────────────────────────────────────────────────────

    public function delete(int $id): void
    {
        if (!Auth::can('delete_colaboradores')) {
            header("Location: /colaboradores?error=unauthorized"); exit();
        }
        $usuarioId = Auth::user()->id;
        if ($this->model->delete($id, $usuarioId)) {
            AuditLogger::log('delete_colaborador', ['id' => $id]);
        }
        header("Location: /colaboradores?success=deleted");
        exit();
    }

    // ─── Busca CNPJ (Receita Federal) ─────────────────────────────────────────

    public function buscarCnpj(): void
    {
        $cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');
        header('Content-Type: application/json');
        try {
            if (strlen($cnpj) !== 14) {
                http_response_code(400);
                echo json_encode(['erro' => 'CNPJ inválido. Digite 14 dígitos.']);
                exit();
            }
            if (!$this->validaCnpj($cnpj)) {
                http_response_code(400);
                echo json_encode(['erro' => 'CNPJ inválido. Dígitos verificadores incorretos.']);
                exit();
            }
            $service   = new CnpjService();
            $resultado = $service->consultar($cnpj);
            if (isset($resultado['erro'])) {
                http_response_code(404);
                echo json_encode(['erro' => $resultado['erro']]);
                exit();
            }
            $mapeado = [
                'nome'               => $resultado['razao_social']   ?? '',
                'nome_social'        => $resultado['nome_fantasia']  ?? '',
                'email'              => $resultado['email']          ?? '',
                'cep'                => $resultado['cep']            ?? '',
                'endereco'           => $resultado['endereco']       ?? '',
                'numero'             => $resultado['numero']         ?? '',
                'complemento'        => $resultado['complemento']    ?? '',
                'bairro'             => $resultado['bairro']         ?? '',
                'cidade'             => $resultado['cidade']         ?? '',
                'estado'             => $resultado['estado']         ?? '',
                'telefone'           => $resultado['telefone']       ?? '',
                'cnae_principal'     => $resultado['cnae_principal'] ?? '',
                'descricao_cnae'     => $resultado['descricao_cnae'] ?? '',
            ];
            AuditLogger::log('colaborador_cnpj_search', ['cnpj' => $cnpj]);
            echo json_encode(['success' => true, 'dados' => $mapeado]);
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] buscarCnpj: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao consultar CNPJ.']);
        }
        exit();
    }

    // ─── Anexos ───────────────────────────────────────────────────────────────

    public function addAnexo(): void
    {
        try {
            if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('Nenhum arquivo enviado ou erro no upload.');
            }
            $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
            $nomeAnexo     = trim(strip_tags($_POST['nome_anexo'] ?? ''));
            if ($colaboradorId <= 0) {
                throw new \Exception('ID do colaborador inválido.');
            }
            if (empty($nomeAnexo)) {
                throw new \Exception('Informe um nome/descrição para o anexo.');
            }
            $usuarioId = Auth::user()->id;
            $file      = $_FILES['arquivo'];

            // Limite de 10 MB
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new \Exception('Arquivo excede o limite de 10 MB.');
            }

            $uploadDir = BASE_PATH . "/public/uploads/colaboradores/{$colaboradorId}";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('col_') . '.' . $ext;
            $filePath = "/public/uploads/colaboradores/{$colaboradorId}/{$fileName}";
            $fullPath = BASE_PATH . $filePath;

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new \Exception('Falha ao mover arquivo para o servidor.');
            }
            $this->anexoModel->create([
                'colaborador_id' => $colaboradorId,
                'usuario_id'     => $usuarioId,
                'nome_anexo'     => $nomeAnexo,
                'file_path'      => $filePath,
                'original_name'  => $file['name'],
                'file_size'      => $file['size'],
                'mime_type'      => $file['type'] ?: 'application/octet-stream',
            ]);
            AuditLogger::log('colaborador_add_anexo', ['colaborador_id' => $colaboradorId, 'nome' => $nomeAnexo]);
            header("Location: /colaboradores/edit/{$colaboradorId}?success=uploaded&tab=anexos");
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] addAnexo: ' . $e->getMessage());
            $id = (int)($_POST['colaborador_id'] ?? 0);
            header("Location: " . ($id > 0 ? "/colaboradores/edit/{$id}?error=upload&tab=anexos" : "/colaboradores"));
        }
        exit();
    }

    public function downloadAnexo(int $id): void
    {
        try {
            $anexo = $this->anexoModel->findById($id);
            if (!$anexo || (int)$anexo->usuario_id !== (int)Auth::user()->id) {
                throw new \Exception('Acesso negado ou anexo não encontrado.');
            }
            $fullPath = BASE_PATH . $anexo->file_path;
            if (!is_file($fullPath)) {
                throw new \Exception('Arquivo físico não encontrado.');
            }
            header('Content-Type: ' . $anexo->mime_type);
            header('Content-Disposition: attachment; filename="' . addslashes($anexo->original_name) . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] downloadAnexo: ' . $e->getMessage());
            echo 'Erro ao baixar arquivo.';
        }
    }

    public function removeAnexo(): void
    {
        header('Content-Type: application/json');
        try {
            $id        = (int)($_POST['id'] ?? 0);
            $usuarioId = Auth::user()->id;
            $anexo     = $this->anexoModel->findById($id);
            if (!$anexo || (int)$anexo->usuario_id !== $usuarioId) {
                throw new \Exception('Acesso negado ou anexo inválido.');
            }
            $fullPath = BASE_PATH . $anexo->file_path;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            $this->anexoModel->delete($id, $usuarioId);
            AuditLogger::log('colaborador_remove_anexo', ['id' => $id]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] removeAnexo: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // ─── Comissões ────────────────────────────────────────────────────────────

    public function storeComissao(): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId     = Auth::user()->id;
            $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
            if ($colaboradorId <= 0 || !$this->model->findByIdAndUsuarioId($colaboradorId, $usuarioId)) {
                throw new \Exception('Colaborador inválido.');
            }
            $id = $this->comissaoModel->create([
                'colaborador_id'  => $colaboradorId,
                'usuario_id'      => $usuarioId,
                'descricao'       => trim(strip_tags($_POST['descricao'] ?? '')),
                'tipo'            => $_POST['tipo'] ?? 'percentual',
                'valor'           => (float)str_replace(',', '.', $_POST['valor'] ?? '0'),
                'base_calculo'    => $_POST['base_calculo'] ?? 'faturamento_bruto',
                'vigencia_inicio' => $_POST['vigencia_inicio'] ?? '',
                'vigencia_fim'    => $_POST['vigencia_fim'] ?? '',
                'ativo'           => 1,
                'observacoes'     => trim(strip_tags($_POST['observacoes'] ?? '')),
            ]);
            if ($id) {
                AuditLogger::log('colaborador_add_comissao', ['colaborador_id' => $colaboradorId, 'id' => $id]);
                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                throw new \Exception('Falha ao salvar regra de comissão.');
            }
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] storeComissao: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    public function updateComissao(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $dados     = [
                'usuario_id'      => $usuarioId,
                'descricao'       => trim(strip_tags($_POST['descricao'] ?? '')),
                'tipo'            => $_POST['tipo'] ?? 'percentual',
                'valor'           => (float)str_replace(',', '.', $_POST['valor'] ?? '0'),
                'base_calculo'    => $_POST['base_calculo'] ?? 'faturamento_bruto',
                'vigencia_inicio' => $_POST['vigencia_inicio'] ?? '',
                'vigencia_fim'    => $_POST['vigencia_fim'] ?? '',
                'ativo'           => (int)($_POST['ativo'] ?? 1),
                'observacoes'     => trim(strip_tags($_POST['observacoes'] ?? '')),
            ];
            if ($this->comissaoModel->update($id, $dados)) {
                AuditLogger::log('colaborador_update_comissao', ['id' => $id]);
                echo json_encode(['success' => true]);
            } else {
                throw new \Exception('Falha ao atualizar regra de comissão.');
            }
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] updateComissao: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    public function deleteComissao(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $this->comissaoModel->delete($id, $usuarioId);
            AuditLogger::log('colaborador_delete_comissao', ['id' => $id]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] deleteComissao: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // ─── Vínculo com Usuário ──────────────────────────────────────────────────

    public function vincularUsuario(int $id): void
    {
        header('Content-Type: application/json');
        try {
            if (!Auth::can('manage_settings')) {
                throw new \Exception('Sem permissão para vincular usuários.');
            }
            $usuarioId = Auth::user()->id;
            $userId    = (int)($_POST['user_id'] ?? 0);

            // Verifica se o user_id já está vinculado a outro colaborador
            if ($userId > 0) {
                $existente = $this->model->findByUserId($userId);
                if ($existente && (int)$existente->id !== $id) {
                    throw new \Exception('Este usuário já está vinculado a outro colaborador.');
                }
                $this->model->vincularUsuario($id, $userId, $usuarioId);
                AuditLogger::log('colaborador_vincular_usuario', ['colaborador_id' => $id, 'user_id' => $userId]);
            } else {
                $this->model->desvincularUsuario($id, $usuarioId);
                AuditLogger::log('colaborador_desvincular_usuario', ['colaborador_id' => $id]);
            }
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] vincularUsuario: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function extrairDadosPost(int $usuarioId): array
    {
        $tipo = $_POST['tipo_contratacao'] ?? 'CLT';
        return [
            'usuario_id'          => $usuarioId,
            'tipo_contratacao'    => $tipo,
            'cpf_cnpj'            => preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? ''),
            'nome'                => trim(strip_tags($_POST['nome'] ?? '')),
            'nome_social'         => trim(strip_tags($_POST['nome_social'] ?? '')),
            // CLT
            'data_nascimento'     => $_POST['data_nascimento'] ?? '',
            'rg'                  => trim(strip_tags($_POST['rg'] ?? '')),
            'orgao_emissor'       => trim(strip_tags($_POST['orgao_emissor'] ?? '')),
            'pis_pasep'           => preg_replace('/\D/', '', $_POST['pis_pasep'] ?? ''),
            'ctps'                => trim(strip_tags($_POST['ctps'] ?? '')),
            'ctps_serie'          => trim(strip_tags($_POST['ctps_serie'] ?? '')),
            'estado_civil'        => $_POST['estado_civil'] ?? '',
            'escolaridade'        => trim(strip_tags($_POST['escolaridade'] ?? '')),
            // PJ
            'inscricao_estadual'  => trim(strip_tags($_POST['inscricao_estadual'] ?? '')),
            'inscricao_municipal' => trim(strip_tags($_POST['inscricao_municipal'] ?? '')),
            'cnae_principal'      => trim(strip_tags($_POST['cnae_principal'] ?? '')),
            'descricao_cnae'      => trim(strip_tags($_POST['descricao_cnae'] ?? '')),
            'nome_responsavel'    => trim(strip_tags($_POST['nome_responsavel'] ?? '')),
            'cpf_responsavel'     => preg_replace('/\D/', '', $_POST['cpf_responsavel'] ?? ''),
            // Contato
            'email'               => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'telefone'            => preg_replace('/\D/', '', $_POST['telefone'] ?? ''),
            'celular'             => preg_replace('/\D/', '', $_POST['celular'] ?? ''),
            // Endereço
            'cep'                 => preg_replace('/\D/', '', $_POST['cep'] ?? ''),
            'endereco'            => trim(strip_tags($_POST['endereco'] ?? '')),
            'numero'              => trim(strip_tags($_POST['numero'] ?? '')),
            'complemento'         => trim(strip_tags($_POST['complemento'] ?? '')),
            'bairro'              => trim(strip_tags($_POST['bairro'] ?? '')),
            'cidade'              => trim(strip_tags($_POST['cidade'] ?? '')),
            'estado'              => strtoupper(trim($_POST['estado'] ?? '')),
            // Profissional
            'cargo'               => trim(strip_tags($_POST['cargo'] ?? '')),
            'departamento'        => trim(strip_tags($_POST['departamento'] ?? '')),
            'data_admissao'       => $_POST['data_admissao'] ?? '',
            'data_demissao'       => $_POST['data_demissao'] ?? '',
            'salario_base'        => (float)str_replace(',', '.', str_replace('.', '', $_POST['salario_base'] ?? '0')),
            'banco'               => trim(strip_tags($_POST['banco'] ?? '')),
            'agencia'             => trim(strip_tags($_POST['agencia'] ?? '')),
            'conta'               => trim(strip_tags($_POST['conta'] ?? '')),
            'tipo_conta'          => $_POST['tipo_conta'] ?? '',
            'chave_pix'           => trim(strip_tags($_POST['chave_pix'] ?? '')),
            // Status
            'status'              => $_POST['status'] ?? 'ativo',
            'observacoes'         => trim(strip_tags($_POST['observacoes'] ?? '')),
        ];
    }

    private function buscarFaturamentoColaborador(int $colaboradorId, int $usuarioId): array
    {
        try {
            $pdo = $this->contaReceberModel->getPdo();
            // Verifica se a coluna colaborador_id existe na tabela contas_receber
            $check = $pdo->query("SHOW COLUMNS FROM `contas_receber` LIKE 'colaborador_id'");
            if (!$check || $check->rowCount() === 0) {
                return [];
            }
            $stmt = $pdo->prepare(
                "SELECT cr.*, c.razao_social AS cliente_nome
                   FROM contas_receber cr
              LEFT JOIN clientes c ON c.id = cr.cliente_id
                  WHERE cr.colaborador_id = ? AND cr.usuario_id = ?
                  ORDER BY cr.data_vencimento DESC, cr.id DESC
                  LIMIT 200"
            );
            $stmt->execute([$colaboradorId, $usuarioId]);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $this->logger->error('[Colaborador] buscarFaturamento: ' . $e->getMessage());
            return [];
        }
    }

    private function validaCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        $mult1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $soma  = 0;
        for ($i = 0; $i < 12; $i++) $soma += $cnpj[$i] * $mult1[$i];
        $resto = $soma % 11;
        if (($resto < 2 ? 0 : 11 - $resto) != $cnpj[12]) return false;
        $mult2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $soma  = 0;
        for ($i = 0; $i < 13; $i++) $soma += $cnpj[$i] * $mult2[$i];
        $resto = $soma % 11;
        return ($resto < 2 ? 0 : 11 - $resto) == $cnpj[13];
    }
}
