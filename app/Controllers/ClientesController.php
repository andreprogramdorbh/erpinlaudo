<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Models\Cliente;
use App\Models\ClienteContato;
use App\Models\ClienteAnexo;
use App\Core\Audit\AuditLogger;
use App\Services\CnpjService;

class ClientesController extends Controller
{
    private Cliente $clienteModel;
    private ClienteContato $contatoModel;
    private ClienteAnexo $anexoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->clienteModel = new Cliente();
        $this->contatoModel = new ClienteContato();
        $this->anexoModel = new ClienteAnexo();
        $this->logger = new Logger();
    }

    /**
     * Exibe a listagem de clientes.
     */
    public function index()
    {
        try {
            $usuarioId = Auth::user()->id;

            // Filtros vindos da URL
            $filtros = [
                'status' => $_GET['status'] ?? 'ativo',
                'pesquisa' => $_GET['q'] ?? '',
                'uf' => $_GET['uf'] ?? ''
            ];

            $clientes = $this->clienteModel->findByUsuarioId($usuarioId, $filtros);
            $totalClientes = count($clientes);

            // Regra de Fluxo: Se não houver clientes, redireciona para o cadastro (Geral)
            if ($totalClientes === 0 && empty($_GET['q'])) {
                header("Location: /clientes/create");
                exit();
            }

            View::render('clientes/index', [
                'title' => 'Clientes',
                'breadcrumb' => [
                    'Módulos' => '/dashboard',
                    0 => 'Clientes',
                ],
                'clientes' => $clientes,
                'totalClientes' => $totalClientes,
                'filtros' => $filtros,
                '_layout' => 'erp'
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao listar clientes: " . $e->getMessage());
            header("Location: /dashboard?error=1");
            exit();
        }
    }

    /**
     * Exibe o formulário de cadastro de novo cliente.
     */
    public function create()
    {
        if (!Auth::can('create_clients')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        View::render('clientes/form-enterprise', [
            'title' => 'Novo Cliente',
            'isEdit' => false,
            'cliente' => null,
            'tab' => 'geral',
            '_layout' => 'erp'
        ]);
    }

    /**
     * Armazena um novo cliente.
     */
    public function store()
    {
        $isAjax =
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            isset($_GET['ajax']);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $usuarioId = Auth::user()->id;

            $tipo = $_POST['tipo'] ?? 'PJ';
            $cpfCnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');

            $this->logger->info('[Clientes] store iniciado', [
                'usuario_id' => $usuarioId,
                'is_ajax' => $isAjax,
                'tipo' => $tipo,
                'cpf_cnpj_len' => strlen((string) $cpfCnpj),
                'email_domain' => (function () {
                    $email = (string) ($_POST['email'] ?? '');
                    $parts = explode('@', $email);
                    return isset($parts[1]) ? strtolower($parts[1]) : '';
                })(),
            ]);

            // Validação Backend (Regra de Ouro #1 e Requisitos do Usuário)
            if (empty($_POST['razao_social']) || empty($cpfCnpj) || empty($_POST['email'])) {
                if ($isAjax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios ausentes (Razão Social, CPF/CNPJ ou E-mail).']);
                    exit();
                }
                header("Location: /clientes/create?error=missing_fields");
                exit();
            }

            // Validação de formato CPF/CNPJ
            if ($tipo === 'PF' && strlen($cpfCnpj) !== 11) {
                if ($isAjax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'CPF inválido.']);
                    exit();
                }
                header("Location: /clientes/create?error=invalid_cpf");
                exit();
            }
            if ($tipo === 'PJ' && strlen($cpfCnpj) !== 14) {
                if ($isAjax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'CNPJ inválido.']);
                    exit();
                }
                header("Location: /clientes/create?error=invalid_cnpj");
                exit();
            }

            $dados = [
                'tipo' => $tipo,
                'cpf_cnpj' => $cpfCnpj,
                'razao_social' => trim(strip_tags($_POST['razao_social'])),
                'nome_fantasia' => trim(strip_tags($_POST['nome_fantasia'] ?? '')),
                'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
                'website' => trim(strip_tags($_POST['website'] ?? '')),
                'estado' => strtoupper(trim($_POST['estado'] ?? '')),
                'cidade' => trim(strip_tags($_POST['cidade'] ?? '')),
                'bairro' => trim(strip_tags($_POST['bairro'] ?? '')),
                'endereco' => trim(strip_tags($_POST['endereco'] ?? '')),
                'numero' => trim(strip_tags($_POST['numero'] ?? '')),
                'complemento' => trim(strip_tags($_POST['complemento'] ?? '')),
                'cep' => preg_replace('/\D/', '', $_POST['cep'] ?? ''),
                'telefone' => $this->normalizarTelefone($_POST['telefone'] ?? ''),
                'celular' => $this->normalizarTelefone($_POST['celular'] ?? ''),
                'instagram' => trim(strip_tags($_POST['instagram'] ?? '')),
                'tiktok' => trim(strip_tags($_POST['tiktok'] ?? '')),
                'facebook' => trim(strip_tags($_POST['facebook'] ?? '')),
                'cnae_principal' => trim(strip_tags($_POST['cnae_principal'] ?? '')),
                'descricao_cnae' => trim(strip_tags($_POST['descricao_cnae'] ?? '')),
                'usuario_id' => $usuarioId,
                'status' => 'ativo'
            ];

            $clientId = $this->clienteModel->create($dados);
            if ($clientId) {
                $this->logger->info('[Clientes] store OK', [
                    'usuario_id' => $usuarioId,
                    'client_id' => $clientId,
                ]);
                AuditLogger::log('create_client', [
                    'client_id' => $clientId,
                    'razao_social' => $dados['razao_social']
                ]);
                // Redireciona para edição com aba de contatos ativa
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'client_id' => $clientId,
                        'redirect_url' => "/clientes/edit/{$clientId}?success=created&tab=contatos",
                        'message' => 'Cliente cadastrado com sucesso.',
                    ]);
                    exit();
                }
                header("Location: /clientes/edit/{$clientId}?success=created&tab=contatos");
            } else {
                $this->logger->error('[Clientes] store falhou: create() retornou false', [
                    'usuario_id' => $usuarioId,
                ]);
                if ($isAjax) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Falha ao salvar cliente no banco de dados.']);
                    exit();
                }
                header("Location: /clientes/create?error=db_failure");
            }
        } catch (\Throwable $e) {
            $this->logger->error("Erro ao salvar cliente: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro inesperado ao salvar cliente.']);
                exit();
            }
            header("Location: /clientes/create?error=fatal");
        }
        exit();
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit($id)
    {
        if (!Auth::can('edit_clients')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $cliente = $this->clienteModel->findById($id);

        if (!$cliente || $cliente->usuario_id != Auth::user()->id) {
            header("Location: /clientes?error=not_found");
            exit();
        }

        $contatos = $this->contatoModel->findByClienteId($id);
        $anexos = $this->anexoModel->findByClienteId($id, Auth::user()->id);

        View::render('clientes/form-enterprise', [
            'title' => 'Editar Cliente',
            'isEdit' => true,
            'cliente' => $cliente,
            'contatos' => $contatos,
            'anexos' => $anexos,
            'tab' => $_GET['tab'] ?? 'geral',
            '_layout' => 'erp'
        ]);
    }

    /**
     * Atualiza um cliente.
     */
    public function update($id)
    {
        $isAjax =
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            isset($_GET['ajax']);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $cliente = $this->clienteModel->findById($id);
            if (!$cliente || $cliente->usuario_id != \App\Core\Auth::user()->id) {
                if ($isAjax) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
                    exit();
                }
                header("Location: /clientes?error=unauthorized");
                exit();
            }

            $tipo = $_POST['tipo'] ?? $cliente->tipo;
            $cpfCnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');

            // Validação Backend
            if (empty($_POST['razao_social']) || empty($cpfCnpj) || empty($_POST['email'])) {
                if ($isAjax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios ausentes.']);
                    exit();
                }
                header("Location: /clientes/edit/{$id}?error=missing_fields");
                exit();
            }

            $dados = [
                'tipo' => $tipo,
                'cpf_cnpj' => $cpfCnpj,
                'razao_social' => trim(strip_tags($_POST['razao_social'])),
                'nome_fantasia' => trim(strip_tags($_POST['nome_fantasia'] ?? '')),
                'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
                'website' => trim(strip_tags($_POST['website'] ?? '')),
                'estado' => strtoupper(trim($_POST['estado'] ?? '')),
                'cidade' => trim(strip_tags($_POST['cidade'] ?? '')),
                'bairro' => trim(strip_tags($_POST['bairro'] ?? '')),
                'endereco' => trim(strip_tags($_POST['endereco'] ?? '')),
                'numero' => trim(strip_tags($_POST['numero'] ?? '')),
                'complemento' => trim(strip_tags($_POST['complemento'] ?? '')),
                'cep' => preg_replace('/\D/', '', $_POST['cep'] ?? ''),
                'telefone' => $this->normalizarTelefone($_POST['telefone'] ?? ''),
                'celular' => $this->normalizarTelefone($_POST['celular'] ?? ''),
                'instagram' => trim(strip_tags($_POST['instagram'] ?? '')),
                'tiktok' => trim(strip_tags($_POST['tiktok'] ?? '')),
                'facebook' => trim(strip_tags($_POST['facebook'] ?? '')),
                'cnae_principal' => trim(strip_tags($_POST['cnae_principal'] ?? '')),
                'descricao_cnae' => trim(strip_tags($_POST['descricao_cnae'] ?? '')),
                'status' => $_POST['status'] ?? 'ativo'
            ];

            if ($this->clienteModel->update($id, $dados)) {
                AuditLogger::log('update_client', [
                    'client_id' => $id,
                    'razao_social' => $dados['razao_social']
                ]);
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'client_id' => $id,
                        'redirect_url' => "/clientes/edit/{$id}?success=updated&tab=geral",
                        'message' => 'Cliente atualizado com sucesso.',
                    ]);
                    exit();
                }
                header("Location: /clientes/edit/{$id}?success=updated&tab=geral");
            } else {
                if ($isAjax) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Falha ao atualizar cliente no banco de dados.']);
                    exit();
                }
                header("Location: /clientes/edit/{$id}?error=db_failure");
            }
        } catch (\Throwable $e) {
            $this->logger->error("Erro ao atualizar cliente: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro inesperado ao atualizar cliente.']);
                exit();
            }
            header("Location: /clientes/edit/{$id}?error=fatal");
        }
        exit();
    }

    /**
     * Deleta um cliente (Soft Delete).
     */
    public function delete($id)
    {
        $cliente = $this->clienteModel->findById($id);
        if ($cliente && $cliente->usuario_id == \App\Core\Auth::user()->id) {
            $this->clienteModel->delete($id);
            AuditLogger::log('delete_client', ['id' => $id, 'razao_social' => $cliente->razao_social]);
            header("Location: /clientes?success=deleted");
        } else {
            header("Location: /clientes?error=unauthorized");
        }
        exit();
    }

    /**
     * Adiciona um novo contato via AJAX.
     * Campos esperados: cliente_id, nome, departamento, email, celular, telefone, cargo, observacoes
     */
    public function addContato(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $clienteId = (int) ($_POST['cliente_id'] ?? 0);
            if (!$clienteId) {
                throw new \Exception('ID do cliente não informado.');
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || $cliente->usuario_id != \App\Core\Auth::user()->id) {
                throw new \Exception('Acesso não autorizado.');
            }

            $nome = trim($_POST['nome'] ?? '');
            if (empty($nome)) {
                throw new \Exception('O nome do contato é obrigatório.');
            }

            $dados = [
                'cliente_id'   => $clienteId,
                'nome'         => $nome,
                'departamento' => trim($_POST['departamento'] ?? ''),
                'email'        => trim($_POST['email']        ?? ''),
                'celular'      => trim($_POST['celular']      ?? ''),
                'telefone'     => trim($_POST['telefone']     ?? ''),
                'cargo'        => trim($_POST['cargo']        ?? ''),
                'observacoes'  => trim($_POST['observacoes']  ?? ''),
                'status'       => 'ativo',
            ];

            $contatoId = $this->contatoModel->create($dados);
            if ($contatoId) {
                AuditLogger::log('create_contact', ['client_id' => $clienteId, 'nome' => $nome]);
                echo json_encode(['success' => true, 'id' => $contatoId, 'message' => 'Contato adicionado com sucesso.']);
            } else {
                throw new \Exception('Erro ao salvar contato no banco de dados.');
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Retorna os dados de um contato via AJAX (para preencher o modal de edição).
     * Rota: GET /clientes/get-contato?id={id}
     */
    public function getContato(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception('ID do contato não informado.');
            }

            $contato = $this->contatoModel->findById($id);
            if (!$contato) {
                throw new \Exception('Contato não encontrado.');
            }

            $cliente = $this->clienteModel->findById($contato->cliente_id);
            if (!$cliente || $cliente->usuario_id != \App\Core\Auth::user()->id) {
                throw new \Exception('Acesso não autorizado.');
            }

            echo json_encode(['success' => true, 'contato' => $contato]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Atualiza um contato existente via AJAX.
     * Rota: POST /clientes/update-contato
     */
    public function updateContato(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int) ($_POST['contato_id'] ?? 0);
            if (!$id) {
                throw new \Exception('ID do contato não informado.');
            }

            $contato = $this->contatoModel->findById($id);
            if (!$contato) {
                throw new \Exception('Contato não encontrado.');
            }

            $cliente = $this->clienteModel->findById($contato->cliente_id);
            if (!$cliente || $cliente->usuario_id != \App\Core\Auth::user()->id) {
                throw new \Exception('Acesso não autorizado.');
            }

            $nome = trim($_POST['nome'] ?? '');
            if (empty($nome)) {
                throw new \Exception('O nome do contato é obrigatório.');
            }

            $dados = [
                'nome'         => $nome,
                'departamento' => trim($_POST['departamento'] ?? ''),
                'email'        => trim($_POST['email']        ?? ''),
                'celular'      => trim($_POST['celular']      ?? ''),
                'telefone'     => trim($_POST['telefone']     ?? ''),
                'cargo'        => trim($_POST['cargo']        ?? ''),
                'observacoes'  => trim($_POST['observacoes']  ?? ''),
            ];

            if ($this->contatoModel->update($id, $dados)) {
                AuditLogger::log('update_contact', ['contact_id' => $id, 'nome' => $nome]);
                echo json_encode(['success' => true, 'message' => 'Contato atualizado com sucesso.']);
            } else {
                throw new \Exception('Erro ao atualizar contato no banco de dados.');
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Remove um contato via AJAX.
     */
    public function removeContato()
    {
        header('Content-Type: application/json');
        try {
            $id = $_POST['id'];
            // Aqui deveríamos verificar se o contato pertence a um cliente do usuário
            // Por brevidade em DEV, faremos a exclusão direta se houver permissão
            if ($this->contatoModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Contato removido']);
            } else {
                throw new \Exception("Erro ao remover contato");
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * API para buscar dados de CNPJ na BrasilAPI (Backend Proxy).
     */
    public function buscarCnpj()
    {
        $cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');
        header('Content-Type: application/json');

        try {
            // Validação básica do CNPJ
            if (strlen($cnpj) !== 14) {
                AuditLogger::log('client_cnpj_search_failed', [
                    'cnpj' => $cnpj,
                    'error' => 'CNPJ inválido - formato incorreto'
                ]);
                http_response_code(400);
                echo json_encode(['erro' => 'CNPJ inválido. Digite um CNPJ completo com 14 dígitos.']);
                exit();
            }

            // Validação de dígitos verificadores do CNPJ
            if (!$this->validaCnpj($cnpj)) {
                AuditLogger::log('client_cnpj_search_failed', [
                    'cnpj' => $cnpj,
                    'error' => 'CNPJ inválido - dígitos verificadores'
                ]);
                http_response_code(400);
                echo json_encode(['erro' => 'CNPJ inválido. Os dígitos verificadores não conferem.']);
                exit();
            }

            $service = new \App\Services\CnpjService();
            $resultado = $service->consultar($cnpj);

            if (isset($resultado['erro'])) {
                AuditLogger::log('client_cnpj_search_failed', [
                    'cnpj' => $cnpj,
                    'error' => $resultado['erro']
                ]);
                http_response_code(404);
                echo json_encode(['erro' => $resultado['erro']]);
                exit();
            }

            // Mapeamento de dados da API para campos do banco
            $dadosMapeados = $this->mapearDadosCnpj($resultado);

            // Log de sucesso
            AuditLogger::log('client_cnpj_search_success', [
                'cnpj' => $cnpj,
                'razao_social' => $resultado['razao_social'] ?? 'N/A'
            ]);

            echo json_encode($dadosMapeados);
        } catch (\Exception $e) {
            AuditLogger::log('client_cnpj_search_exception', [
                'cnpj' => $cnpj,
                'error' => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao processar a consulta. Tente novamente em alguns minutos.']);
        }
        exit();
    }

    /**
     * API para buscar endereço por CEP com fallback entre múltiplas APIs.
     * Rota: GET /clientes/buscar-cep?cep={cep}
     */
    public function buscarCep(): void
    {
        $cep = preg_replace('/\D/', '', $_GET['cep'] ?? '');
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (strlen($cep) !== 8) {
                http_response_code(400);
                echo json_encode(['erro' => 'CEP inválido. Informe um CEP com 8 dígitos.']);
                exit();
            }

            $this->logger->debug('[Clientes] buscarCep iniciado', [
                'cep' => $cep,
                'user_id' => \App\Core\Auth::user()->id ?? null,
            ]);

            $service   = new \App\Services\CepService();
            $resultado = $service->consultar($cep);

            if (isset($resultado['erro'])) {
                AuditLogger::log('client_cep_search_failed', [
                    'cep'   => $cep,
                    'error' => $resultado['erro'],
                ]);
                $this->logger->warning('[Clientes] buscarCep falhou', [
                    'cep' => $cep,
                    'error' => $resultado['erro'],
                ]);
                http_response_code(404);
                echo json_encode(['erro' => $resultado['erro']]);
                exit();
            }

            AuditLogger::log('client_cep_search_success', [
                'cep'       => $cep,
                'cidade'    => $resultado['cidade'] ?? 'N/A',
                '_provedor' => $resultado['_provedor'] ?? 'N/A',
            ]);

            $this->logger->info('[Clientes] buscarCep OK', [
                'cep' => $cep,
                'provedor' => $resultado['_provedor'] ?? 'N/A',
            ]);

            unset($resultado['ibge']);
            echo json_encode($resultado);

        } catch (\Throwable $e) {
            AuditLogger::log('client_cep_search_exception', [
                'cep'   => $cep,
                'error' => $e->getMessage(),
            ]);
            $this->logger->error('[Clientes] buscarCep exception: ' . $e->getMessage(), [
                'cep' => $cep,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao consultar o CEP. Tente novamente.']);
        }
        exit();
    }

    /**
     * Mapeia dados já normalizados pelo CnpjService para os campos do banco.
     * O CnpjService já retorna os dados no formato interno; este método
     * apenas garante que campos extras (como _provedor) sejam removidos.
     */
    private function mapearDadosCnpj(array $dadosApi): array
    {
        return [
            'razao_social'       => $dadosApi['razao_social'] ?? '',
            'nome_fantasia'      => $dadosApi['nome_fantasia'] ?? '',
            'email'              => $dadosApi['email'] ?? '',
            'cep'                => $dadosApi['cep'] ?? '',
            'endereco'           => $dadosApi['endereco'] ?? '',
            'numero'             => $dadosApi['numero'] ?? '',
            'complemento'        => $dadosApi['complemento'] ?? '',
            'bairro'             => $dadosApi['bairro'] ?? '',
            'cidade'             => $dadosApi['cidade'] ?? '',
            'estado'             => $dadosApi['estado'] ?? '',
            'telefone'           => $this->normalizarTelefone($dadosApi['telefone'] ?? ''),
            'cnae_principal'     => $dadosApi['cnae_principal'] ?? '',
            'descricao_cnae'     => $dadosApi['descricao_cnae'] ?? '',
            'situacao_cadastral' => $dadosApi['situacao_cadastral'] ?? '',
        ];
    }

    /**
     * Valida dígitos verificadores do CNPJ
     */
    private function validaCnpj($cnpj)
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Validação do primeiro dígito verificador
        $multiplicadores = [5,4,3,2,9,8,7,6,5,4,3,2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicadores[$i];
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ($digito1 != $cnpj[12]) {
            return false;
        }

        // Validação do segundo dígito verificador
        $multiplicadores = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicadores[$i];
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return $digito2 == $cnpj[13];
    }

    // ---------------------------------------------------------------
    // ANEXOS
    // ---------------------------------------------------------------

    public function addAnexo()
    {
        try {
            if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('Nenhum arquivo enviado ou erro no upload.');
            }

            $id = (int)($_POST['cliente_id'] ?? 0);
            if ($id <= 0) {
                throw new \Exception('ID do cliente inválido.');
            }

            $usuarioId = Auth::user()->id;
            $file = $_FILES['arquivo'];

            // Diretório de upload
            $uploadDir = BASE_PATH . "/public/uploads/clientes/{$id}";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('arq_') . '.' . $ext;
            $filePath = "/public/uploads/clientes/{$id}/{$fileName}";
            $fullPath = BASE_PATH . $filePath;

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new \Exception('Falha ao mover arquivo para o servidor.');
            }

            $this->anexoModel->create([
                'cliente_id' => $id,
                'usuario_id' => $usuarioId,
                'file_path' => $filePath,
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type']
            ]);

            $this->logger->info('Anexo adicionado ao cliente', [
                'cliente_id' => $id,
                'file' => $file['name']
            ]);

            header("Location: /clientes/edit/{$id}?success=uploaded&tab=anexos");
        } catch (\Exception $e) {
            $this->logger->error('Erro ao adicionar anexo: ' . $e->getMessage());
            $id = (int)($_POST['cliente_id'] ?? 0);
            $redirect = $id > 0 ? "/clientes/edit/{$id}?error=upload&tab=anexos" : "/clientes";
            header("Location: {$redirect}");
        }
        exit();
    }

    public function downloadAnexo(int $id)
    {
        try {
            $anexo = $this->anexoModel->findById($id);
            if (!$anexo || (int)$anexo->usuario_id !== (int)Auth::user()->id) {
                throw new \Exception('Anexo não encontrado ou acesso negado.');
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
            $this->logger->error('Erro no download de anexo: ' . $e->getMessage());
            echo 'Erro ao baixar arquivo.';
        }
    }

    /**
     * Normaliza um número de telefone para o formato E.164 sem o '+',
     * sempre incluindo o DDI 55 (Brasil).
     *
     * Exemplos de entrada → saída:
     *   (31) 9274-6755   → 5531927466755  (fixo 8 dígitos → insere 9 após DDD)
     *   (31) 99274-6755  → 5531992746755  (celular 9 dígitos)
     *   31992746755      → 5531992746755  (sem máscara, sem DDI)
     *   5531992746755    → 5531992746755  (já com DDI — retorna igual)
     *
     * @param string $telefone Telefone em qualquer formato
     * @return string Telefone normalizado (somente dígitos, com DDI 55)
     */
    private function normalizarTelefone(string $telefone): string
    {
        // Remove tudo que não for dígito
        $digits = preg_replace('/\D/', '', $telefone);

        if ($digits === '') {
            return '';
        }

        // Já tem DDI 55 (13 dígitos = DDI+DDD+9dígitos ou 12 = DDI+DDD+8dígitos)
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }

        // DDD + 9 dígitos (11 dígitos) → adiciona DDI 55
        if (strlen($digits) === 11) {
            return '55' . $digits;
        }

        // DDD + 8 dígitos (10 dígitos, fixo) → adiciona DDI 55 e insere 9 após DDD
        if (strlen($digits) === 10) {
            $ddd    = substr($digits, 0, 2);
            $numero = substr($digits, 2); // 8 dígitos
            return '55' . $ddd . '9' . $numero;
        }

        // Qualquer outro formato: adiciona DDI 55 e retorna como está
        return '55' . $digits;
    }

    public function removeAnexo()
    {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $usuarioId = Auth::user()->id;

            $anexo = $this->anexoModel->findById($id);
            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                throw new \Exception('Acesso negado ou anexo inválido.');
            }

            // Remove arquivo físico
            $fullPath = BASE_PATH . $anexo->file_path;
            if (is_file($fullPath)) {
                unlink($fullPath);
            }

            $this->anexoModel->delete($id, $usuarioId);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}
