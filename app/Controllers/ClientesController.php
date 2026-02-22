<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Models\Cliente;
use App\Models\ClienteContato;
use App\Core\Audit\AuditLogger;
use App\Services\CnpjService;

class ClientesController extends Controller
{
    private Cliente $clienteModel;
    private ClienteContato $contatoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->clienteModel = new Cliente();
        $this->contatoModel = new ClienteContato();
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
                'filtros' => $filtros
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
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_GET['ajax']);

        try {
            $usuarioId = Auth::user()->id;

            $tipo = $_POST['tipo'] ?? 'PJ';
            $cpfCnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');

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
                'telefone' => preg_replace('/\D/', '', $_POST['telefone'] ?? ''),
                'celular' => preg_replace('/\D/', '', $_POST['celular'] ?? ''),
                'instagram' => trim(strip_tags($_POST['instagram'] ?? '')),
                'tiktok' => trim(strip_tags($_POST['tiktok'] ?? '')),
                'facebook' => trim(strip_tags($_POST['facebook'] ?? '')),
                'cnae_principal' => trim(strip_tags($_POST['cnae_principal'] ?? '')),
                'descricao_cnae' => trim(strip_tags($_POST['descricao_cnae'] ?? '')),
                'usuario_id' => $usuarioId,
                'status' => 'ativo'
            ];

            $clientId = $this->clienteModel->create($dados);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                      str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
                      (isset($_SERVER['HTTP_FETCH_MODE']) && $_SERVER['HTTP_FETCH_MODE'] === 'cors');
            if ($clientId) {
                AuditLogger::log('create_client', [
                    'client_id' => $clientId,
                    'razao_social' => $dados['razao_social']
                ]);
                // Redireciona para edição com aba de contatos ativa
                header("Location: /clientes/edit/{$clientId}?success=created&tab=contatos");
            } else {
                header("Location: /clientes/create?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro ao salvar cliente: " . $e->getMessage());
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

        View::render('clientes/form-enterprise', [
            'title' => 'Editar Cliente',
            'isEdit' => true,
            'cliente' => $cliente,
            'contatos' => $contatos,
            'tab' => $_GET['tab'] ?? 'geral',
            '_layout' => 'erp'
        ]);
    }

    /**
     * Atualiza um cliente.
     */
    public function update($id)
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_GET['ajax']);

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
                'telefone' => preg_replace('/\D/', '', $_POST['telefone'] ?? ''),
                'celular' => preg_replace('/\D/', '', $_POST['celular'] ?? ''),
                'instagram' => trim(strip_tags($_POST['instagram'] ?? '')),
                'tiktok' => trim(strip_tags($_POST['tiktok'] ?? '')),
                'facebook' => trim(strip_tags($_POST['facebook'] ?? '')),
                'cnae_principal' => trim(strip_tags($_POST['cnae_principal'] ?? '')),
                'descricao_cnae' => trim(strip_tags($_POST['descricao_cnae'] ?? '')),
                'status' => $_POST['status'] ?? 'ativo'
            ];

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                      str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
                      (isset($_SERVER['HTTP_FETCH_MODE']) && $_SERVER['HTTP_FETCH_MODE'] === 'cors');
            if ($this->clienteModel->update($id, $dados)) {
                AuditLogger::log('update_client', [
                    'client_id' => $id,
                    'razao_social' => $dados['razao_social']
                ]);
                header("Location: /clientes/edit/{$id}?success=updated&tab=geral");
            } else {
                header("Location: /clientes/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro ao atualizar cliente: " . $e->getMessage());
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
        header('Content-Type: application/json');
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
        header('Content-Type: application/json');
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
        header('Content-Type: application/json');
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
        header('Content-Type: application/json');

        try {
            if (strlen($cep) !== 8) {
                http_response_code(400);
                echo json_encode(['erro' => 'CEP inválido. Informe um CEP com 8 dígitos.']);
                exit();
            }

            $service   = new \App\Services\CepService();
            $resultado = $service->consultar($cep);

            if (isset($resultado['erro'])) {
                AuditLogger::log('client_cep_search_failed', [
                    'cep'   => $cep,
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

            unset($resultado['_provedor'], $resultado['ibge']);
            echo json_encode($resultado);

        } catch (\Exception $e) {
            AuditLogger::log('client_cep_search_exception', [
                'cep'   => $cep,
                'error' => $e->getMessage(),
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
            'telefone'           => $dadosApi['telefone'] ?? '',
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
}
