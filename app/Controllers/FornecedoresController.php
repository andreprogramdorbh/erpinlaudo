<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\Fornecedor;

class FornecedoresController extends Controller
{
    private const BASE_ROUTE = '/fornecedores';

    private Fornecedor $model;
    private Logger     $logger;

    public function __construct()
    {
        $this->model  = new Fornecedor();
        $this->logger = new Logger();
    }

    // ---------------------------------------------------------------
    // LISTAGEM
    // ---------------------------------------------------------------

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros   = [
                'status'   => $_GET['status'] ?? 'ativo',
                'pesquisa' => $_GET['q']      ?? '',
            ];
            $fornecedores = $this->model->findByUsuarioId($usuarioId, $filtros);
            View::render('fornecedores/index', [
                '_layout'      => 'erp',
                'title'        => 'Fornecedores',
                'breadcrumb'   => [
                    'Cadastros' => '#',
                    0           => 'Fornecedores',
                ],
                'fornecedores' => $fornecedores,
                'filtros'      => $filtros,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar fornecedores: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    // ---------------------------------------------------------------
    // CRIACAO
    // ---------------------------------------------------------------

    public function create(): void
    {
        View::render('fornecedores/form-enterprise', [
            '_layout'    => 'erp',
            'title'      => 'Novo Fornecedor',
            'breadcrumb' => [
                'Cadastros'    => '#',
                'Fornecedores' => self::BASE_ROUTE,
                0              => 'Novo Fornecedor',
            ],
            'fornecedor' => null,
            'tab'        => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $nome      = trim($_POST['nome'] ?? '');

            if ($nome === '') {
                header('Location: ' . self::BASE_ROUTE . '/create?error=missing_fields');
                exit();
            }

            $dados               = $this->extrairDadosPost();
            $dados['usuario_id'] = $usuarioId;
            $dados['nome']       = $nome;

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_fornecedor', ['id' => $id, 'nome' => $nome]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=created");
            } else {
                header('Location: ' . self::BASE_ROUTE . '/create?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar fornecedor: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '/create?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // EDICAO
    // ---------------------------------------------------------------

    public function edit($id): void
    {
        $usuarioId  = Auth::user()->id;
        $isAdmin    = Auth::isAdmin();
        $fornecedor = $this->model->findById((int)$id);

        if (!$fornecedor || ((int)$fornecedor->usuario_id !== (int)$usuarioId && !$isAdmin)) {
            header('Location: ' . self::BASE_ROUTE . '?error=not_found');
            exit();
        }

        $historico = $this->model->getHistorico((int)$id);

        View::render('fornecedores/form-enterprise', [
            '_layout'    => 'erp',
            'title'      => 'Editar Fornecedor',
            'breadcrumb' => [
                'Cadastros'    => '#',
                'Fornecedores' => self::BASE_ROUTE,
                0              => 'Editar Fornecedor',
            ],
            'fornecedor' => $fornecedor,
            'historico'  => $historico,
            'tab'        => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId  = Auth::user()->id;
            $isAdmin    = Auth::isAdmin();
            $fornecedor = $this->model->findById((int)$id);

            if (!$fornecedor || ((int)$fornecedor->usuario_id !== (int)$usuarioId && !$isAdmin)) {
                header('Location: ' . self::BASE_ROUTE . '?error=unauthorized');
                exit();
            }

            $nome = trim($_POST['nome'] ?? '');
            if ($nome === '') {
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=missing_fields");
                exit();
            }

            $dados         = $this->extrairDadosPost();
            $dados['nome'] = $nome;

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_fornecedor', ['id' => (int)$id, 'nome' => $nome]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=updated");
            } else {
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar fornecedor: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=fatal");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // EXCLUSAO
    // ---------------------------------------------------------------

    public function delete($id): void
    {
        try {
            $usuarioId  = Auth::user()->id;
            $isAdmin    = Auth::isAdmin();
            $fornecedor = $this->model->findById((int)$id);

            if (!$fornecedor || ((int)$fornecedor->usuario_id !== (int)$usuarioId && !$isAdmin)) {
                header('Location: ' . self::BASE_ROUTE . '?error=unauthorized');
                exit();
            }

            if ($this->model->delete((int)$id)) {
                AuditLogger::log('delete_fornecedor', ['id' => (int)$id, 'nome' => $fornecedor->nome ?? null]);
                header('Location: ' . self::BASE_ROUTE . '?success=deleted');
            } else {
                header('Location: ' . self::BASE_ROUTE . '?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar fornecedor: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // HISTORICO
    // ---------------------------------------------------------------

    /**
     * GET /fornecedores/{id}/historico
     * Exibe a aba de historico do fornecedor (pedidos de compra + movimentacoes).
     */
    public function historico($id): void
    {
        $usuarioId  = Auth::user()->id;
        $isAdmin    = Auth::isAdmin();
        $fornecedor = $this->model->findById((int)$id);

        if (!$fornecedor || ((int)$fornecedor->usuario_id !== (int)$usuarioId && !$isAdmin)) {
            header('Location: ' . self::BASE_ROUTE . '?error=not_found');
            exit();
        }

        $historico = $this->model->getHistorico((int)$id);

        View::render('fornecedores/form-enterprise', [
            '_layout'    => 'erp',
            'title'      => 'Historico - ' . ($fornecedor->nome ?? ''),
            'breadcrumb' => [
                'Cadastros'    => '#',
                'Fornecedores' => self::BASE_ROUTE,
                0              => 'Historico',
            ],
            'fornecedor' => $fornecedor,
            'historico'  => $historico,
            'tab'        => 'historico',
        ]);
    }

    // ---------------------------------------------------------------
    // API: BUSCAR CNPJ
    // ---------------------------------------------------------------

    /**
     * GET /fornecedores/buscar-cnpj?cnpj={cnpj}
     * Proxy backend para BrasilAPI/ReceitaWS — retorna JSON com dados do CNPJ.
     */
    public function buscarCnpj(): void
    {
        $cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (strlen($cnpj) !== 14) {
                AuditLogger::log('fornecedor_cnpj_search_failed', [
                    'cnpj'  => $cnpj,
                    'error' => 'CNPJ invalido - formato incorreto',
                ]);
                http_response_code(400);
                echo json_encode(['erro' => 'CNPJ invalido. Digite um CNPJ completo com 14 digitos.']);
                exit();
            }

            if (!$this->validaCnpj($cnpj)) {
                AuditLogger::log('fornecedor_cnpj_search_failed', [
                    'cnpj'  => $cnpj,
                    'error' => 'CNPJ invalido - digitos verificadores',
                ]);
                http_response_code(400);
                echo json_encode(['erro' => 'CNPJ invalido. Os digitos verificadores nao conferem.']);
                exit();
            }

            $service   = new \App\Services\CnpjService();
            $resultado = $service->consultar($cnpj);

            if (isset($resultado['erro'])) {
                AuditLogger::log('fornecedor_cnpj_search_failed', [
                    'cnpj'  => $cnpj,
                    'error' => $resultado['erro'],
                ]);
                http_response_code(404);
                echo json_encode(['erro' => $resultado['erro']]);
                exit();
            }

            $dadosMapeados = $this->mapearDadosCnpj($resultado);

            AuditLogger::log('fornecedor_cnpj_search_success', [
                'cnpj'         => $cnpj,
                'razao_social' => $resultado['razao_social'] ?? 'N/A',
            ]);

            echo json_encode($dadosMapeados);
        } catch (\Exception $e) {
            $this->logger->error('[Fornecedores] buscarCnpj exception: ' . $e->getMessage());
            AuditLogger::log('fornecedor_cnpj_search_exception', [
                'cnpj'  => $cnpj,
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao processar a consulta. Tente novamente em alguns minutos.']);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // API: BUSCAR CEP
    // ---------------------------------------------------------------

    /**
     * GET /fornecedores/buscar-cep?cep={cep}
     * Retorna JSON com dados do endereco para preenchimento automatico.
     */
    public function buscarCep(): void
    {
        $cep = preg_replace('/\D/', '', $_GET['cep'] ?? '');
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (strlen($cep) !== 8) {
                http_response_code(400);
                echo json_encode(['erro' => 'CEP invalido. Informe um CEP com 8 digitos.']);
                exit();
            }

            $this->logger->debug('[Fornecedores] buscarCep iniciado', [
                'cep'     => $cep,
                'user_id' => Auth::user()->id ?? null,
            ]);

            $service   = new \App\Services\CepService();
            $resultado = $service->consultar($cep);

            if (isset($resultado['erro'])) {
                AuditLogger::log('fornecedor_cep_search_failed', [
                    'cep'   => $cep,
                    'error' => $resultado['erro'],
                ]);
                http_response_code(404);
                echo json_encode(['erro' => $resultado['erro']]);
                exit();
            }

            AuditLogger::log('fornecedor_cep_search_success', [
                'cep'       => $cep,
                'cidade'    => $resultado['cidade']    ?? 'N/A',
                '_provedor' => $resultado['_provedor'] ?? 'N/A',
            ]);

            unset($resultado['ibge']);
            echo json_encode($resultado);
        } catch (\Throwable $e) {
            $this->logger->error('[Fornecedores] buscarCep exception: ' . $e->getMessage());
            AuditLogger::log('fornecedor_cep_search_exception', [
                'cep'   => $cep,
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao consultar o CEP. Tente novamente.']);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // AUXILIARES PRIVADOS
    // ---------------------------------------------------------------

    /**
     * Extrai e sanitiza os dados do POST para criacao/edicao.
     */
    private function extrairDadosPost(): array
    {
        $nullIfEmpty = function (string $key): ?string {
            $val = trim($_POST[$key] ?? '');
            return $val !== '' ? $val : null;
        };

        return [
            'tipo'                => $_POST['tipo']   ?? 'PJ',
            'documento'           => $nullIfEmpty('documento'),
            'nome_fantasia'       => $nullIfEmpty('nome_fantasia'),
            'email'               => $nullIfEmpty('email'),
            'telefone'            => $nullIfEmpty('telefone'),
            'celular'             => $nullIfEmpty('celular'),
            'contato_nome'        => $nullIfEmpty('contato_nome'),
            'website'             => $nullIfEmpty('website'),
            'cep'                 => $nullIfEmpty('cep'),
            'endereco'            => $nullIfEmpty('endereco'),
            'numero'              => $nullIfEmpty('numero'),
            'complemento'         => $nullIfEmpty('complemento'),
            'bairro'              => $nullIfEmpty('bairro'),
            'cidade'              => $nullIfEmpty('cidade'),
            'estado'              => $nullIfEmpty('estado'),
            'inscricao_estadual'  => $nullIfEmpty('inscricao_estadual'),
            'inscricao_municipal' => $nullIfEmpty('inscricao_municipal'),
            'prazo_pagamento'     => $nullIfEmpty('prazo_pagamento'),
            'cnae_principal'      => $nullIfEmpty('cnae_principal'),
            'descricao_cnae'      => $nullIfEmpty('descricao_cnae'),
            'observacoes'         => $nullIfEmpty('observacoes'),
            'status'              => $_POST['status'] ?? 'ativo',
        ];
    }

    /**
     * Mapeia dados normalizados pelo CnpjService para os campos do formulario.
     */
    private function mapearDadosCnpj(array $dadosApi): array
    {
        return [
            'razao_social'       => $dadosApi['razao_social']       ?? '',
            'nome_fantasia'      => $dadosApi['nome_fantasia']      ?? '',
            'email'              => $dadosApi['email']              ?? '',
            'cep'                => $dadosApi['cep']                ?? '',
            'endereco'           => $dadosApi['endereco']           ?? '',
            'numero'             => $dadosApi['numero']             ?? '',
            'complemento'        => $dadosApi['complemento']        ?? '',
            'bairro'             => $dadosApi['bairro']             ?? '',
            'cidade'             => $dadosApi['cidade']             ?? '',
            'estado'             => $dadosApi['estado']             ?? '',
            'telefone'           => $this->normalizarTelefone($dadosApi['telefone'] ?? ''),
            'cnae_principal'     => $dadosApi['cnae_principal']     ?? '',
            'descricao_cnae'     => $dadosApi['descricao_cnae']     ?? '',
            'situacao_cadastral' => $dadosApi['situacao_cadastral'] ?? '',
        ];
    }

    /**
     * Valida os digitos verificadores do CNPJ.
     */
    private function validaCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $mult = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int)$cnpj[$i] * $mult[$i];
        }
        $resto   = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        if ($digito1 != (int)$cnpj[12]) {
            return false;
        }

        $mult = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int)$cnpj[$i] * $mult[$i];
        }
        $resto   = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return $digito2 == (int)$cnpj[13];
    }

    /**
     * Normaliza numero de telefone para formato E.164 (55XXXXXXXXXXX).
     */
    private function normalizarTelefone(string $telefone): string
    {
        $digits = preg_replace('/\D/', '', $telefone);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }
        if (strlen($digits) === 11) {
            return '55' . $digits;
        }
        if (strlen($digits) === 10) {
            $ddd    = substr($digits, 0, 2);
            $numero = substr($digits, 2);
            return '55' . $ddd . '9' . $numero;
        }
        return $telefone;
    }
}
