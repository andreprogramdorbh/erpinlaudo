<?php

use App\Core\Router;

// Rotas públicas (sem autenticação)
Router::get("/login", "AuthController@showLoginForm");
Router::post("/login", "AuthController@login");

Router::get("/forgot-password", "AuthController@showForgotPasswordForm");
Router::post("/forgot-password", "AuthController@forgotPassword");

Router::get("/reset-password/{token}", "AuthController@showResetPasswordForm");
Router::post("/reset-password/{token}", "AuthController@resetPassword");

// Webhooks Públicos
Router::post("/api/webhooks/asaas", "IntegracaoController@webhook");

// ============================================================
// Portal do Cliente — Rotas Públicas (login / primeiro acesso)
// ============================================================
Router::get("/portal/login", "PortalClienteAuthController@showLogin");
Router::post("/portal/login", "PortalClienteAuthController@login");
Router::get("/portal/primeiro-acesso/{token}", "PortalClienteAuthController@showPrimeiroAcesso");
Router::post("/portal/primeiro-acesso", "PortalClienteAuthController@salvarPrimeiroAcesso");

// ============================================================
// Portal do Cliente — Rotas Protegidas (requerem sessão do portal)
// ============================================================
Router::group(["middleware" => ["PortalCliente"]], function () {
    Router::post("/portal/logout", "PortalClienteAuthController@logout");

    // Dashboard
    Router::get("/portal/dashboard", "PortalClienteController@dashboard");
    Router::get("/portal", "PortalClienteController@dashboard"); // alias

    // Perfil
    Router::get("/portal/perfil", "PortalClienteController@perfil");
    Router::post("/portal/perfil/alterar-senha", "PortalClienteController@alterarSenha");

    // Contas a Pagar
    Router::get("/portal/contas-a-pagar", "PortalContasPagarController@index");
    Router::get("/portal/contas-a-pagar/pagar/{id}", "PortalContasPagarController@pagar");

    // Faturamento
    Router::get("/portal/faturamento/notas-fiscais", "PortalFaturamentoController@notasFiscais");
    Router::get("/portal/faturamento/nota-fiscal/xml/{id}", "PortalFaturamentoController@downloadXml");
});

// Rotas protegidas (requerem autenticação)
Router::group(["middleware" => ["Auth"]], function () {
    // Página inicial redireciona para dashboard
    Router::get("/", "HomeController@index");

    Router::get("/dashboard", "DashboardController@index");
    Router::post("/logout", "AuthController@logout");

    // Módulo Clientes (Enterprise Ready)
    Router::group(["middleware" => ["Permission:view_clients"]], function () {
        Router::get("/clientes", "ClientesController@index");
        Router::get("/clientes/buscar-cnpj", "ClientesController@buscarCnpj"); // API Helper
        Router::get("/clientes/buscar-cep", "ClientesController@buscarCep");   // API Helper
    });

    Router::group(["middleware" => ["Permission:create_clients"]], function () {
        Router::get("/clientes/create", "ClientesController@create");
        Router::post("/clientes", "ClientesController@store");
    });

    Router::group(["middleware" => ["Permission:edit_clients"]], function () {
        Router::get("/clientes/edit/{id}", "ClientesController@edit");
        Router::post("/clientes/update/{id}", "ClientesController@update"); // Same logic for ID

        // Gestão de Contatos (AJAX)
        Router::post("/clientes/add-contato",   "ClientesController@addContato");
        Router::post("/clientes/update-contato", "ClientesController@updateContato");
        Router::get("/clientes/get-contato",     "ClientesController@getContato");
        Router::post("/clientes/remove-contato", "ClientesController@removeContato");
    });

    Router::group(["middleware" => ["Permission:delete_clients"]], function () {
        Router::post("/clientes/delete/{id}", "ClientesController@delete");
    });

    // Financeiro
    Router::group(["middleware" => ["Permission:view_finance"]], function () {
    });

    // Plano de Contas (Financeiro)
    Router::group(["middleware" => ["Permission:view_plano_contas"]], function () {
        Router::get("/financeiro/plano-contas", "PlanoContasController@index");
    });

    Router::group(["middleware" => ["Permission:create_plano_contas"]], function () {
        Router::get("/financeiro/plano-contas/create", "PlanoContasController@create");
        Router::post("/financeiro/plano-contas", "PlanoContasController@store");
    });

    Router::group(["middleware" => ["Permission:edit_plano_contas"]], function () {
        Router::get("/financeiro/plano-contas/edit/{id}", "PlanoContasController@edit");
        Router::post("/financeiro/plano-contas/update/{id}", "PlanoContasController@update");
    });

    Router::group(["middleware" => ["Permission:delete_plano_contas"]], function () {
        Router::post("/financeiro/plano-contas/delete/{id}", "PlanoContasController@delete");
    });

    // Fornecedores
    Router::group(["middleware" => ["Permission:view_fornecedores"]], function () {
        Router::get("/financeiro/fornecedores", "FornecedoresController@index");
    });

    Router::group(["middleware" => ["Permission:create_fornecedores"]], function () {
        Router::get("/financeiro/fornecedores/create", "FornecedoresController@create");
        Router::post("/financeiro/fornecedores", "FornecedoresController@store");
    });

    Router::group(["middleware" => ["Permission:edit_fornecedores"]], function () {
        Router::get("/financeiro/fornecedores/edit/{id}", "FornecedoresController@edit");
        Router::post("/financeiro/fornecedores/update/{id}", "FornecedoresController@update");
    });

    Router::group(["middleware" => ["Permission:delete_fornecedores"]], function () {
        Router::post("/financeiro/fornecedores/delete/{id}", "FornecedoresController@delete");
    });

    // Contas a Pagar
    Router::group(["middleware" => ["Permission:view_contas_pagar"]], function () {
        Router::get("/financeiro/pagar", "ContasPagarController@index");
        Router::get("/financeiro/contas-a-pagar", "ContasPagarController@index");
        Router::get("/financeiro/contas-a-pagar/anexos/download/{id}", "ContasPagarController@downloadAnexo");
    });

    Router::group(["middleware" => ["Permission:create_contas_pagar"]], function () {
        Router::get("/financeiro/contas-a-pagar/create", "ContasPagarController@create");
        Router::post("/financeiro/contas-a-pagar", "ContasPagarController@store");
    });

    Router::group(["middleware" => ["Permission:edit_contas_pagar"]], function () {
        Router::get("/financeiro/contas-a-pagar/edit/{id}", "ContasPagarController@edit");
        Router::post("/financeiro/contas-a-pagar/update/{id}", "ContasPagarController@update");
        Router::post("/financeiro/contas-a-pagar/anexos/upload", "ContasPagarController@uploadAnexo");
        Router::post("/financeiro/contas-a-pagar/anexos/delete/{id}", "ContasPagarController@deleteAnexo");
    });

    Router::group(["middleware" => ["Permission:delete_contas_pagar"]], function () {
        Router::post("/financeiro/contas-a-pagar/delete/{id}", "ContasPagarController@delete");
    });

    // Contas a Receber
    Router::group(["middleware" => ["Permission:view_contas_receber"]], function () {
        Router::get("/financeiro/receber", "ContasReceberController@index");
        Router::get("/financeiro/contas-a-receber", "ContasReceberController@index");
    });

    Router::group(["middleware" => ["Permission:create_contas_receber"]], function () {
        Router::get("/financeiro/contas-a-receber/create", "ContasReceberController@create");
        Router::post("/financeiro/contas-a-receber", "ContasReceberController@store");
    });

    Router::group(["middleware" => ["Permission:edit_contas_receber"]], function () {
        Router::get("/financeiro/contas-a-receber/edit/{id}", "ContasReceberController@edit");
        Router::post("/financeiro/contas-a-receber/update/{id}", "ContasReceberController@update");
    });

    Router::group(["middleware" => ["Permission:delete_contas_receber"]], function () {
        Router::post("/financeiro/contas-a-receber/delete/{id}", "ContasReceberController@delete");
    });

    // Faturamento
    Router::group(["middleware" => ["Permission:view_faturamento"]], function () {
        Router::get("/faturamento", "FaturamentoController@index");
    });

    // Notas Fiscais (Faturamento)
    Router::group(["middleware" => ["Permission:view_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais", "NotasFiscaisController@index");
    });

    Router::group(["middleware" => ["Permission:create_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais/create", "NotasFiscaisController@create");
        Router::post("/faturamento/notas-fiscais", "NotasFiscaisController@store");
    });

    Router::group(["middleware" => ["Permission:edit_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais/edit/{id}", "NotasFiscaisController@edit");
        Router::post("/faturamento/notas-fiscais/update/{id}", "NotasFiscaisController@update");
    });

    Router::group(["middleware" => ["Permission:delete_notas_fiscais"]], function () {
        Router::post("/faturamento/notas-fiscais/delete/{id}", "NotasFiscaisController@delete");
    });

    Router::group(["middleware" => ["Permission:import_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais/importar", "NotasFiscaisController@importForm");
        Router::post("/faturamento/notas-fiscais/importar", "NotasFiscaisController@importStore");
    });

    // Integrações
    Router::group(["middleware" => ["Permission:manage_settings"]], function () {
        Router::get("/integracao/asaas", "IntegracaoController@asaas");
        Router::post("/integracao/asaas/save", "IntegracaoController@saveAsaas");
        Router::post("/integracao/asaas/test", "IntegracaoController@testAsaas");

        Router::get("/integracao/email", "IntegracaoController@email");
        Router::post("/integracao/email/save", "IntegracaoController@saveEmail");
        Router::post("/integracao/email/test", "IntegracaoController@testEmail");
    });

    // Perfil do usuário
    Router::group(["middleware" => ["Permission:view_profile"]], function () {
        Router::get("/perfil", "PerfilController@index");
    });

    // Gestão de usuários (superadmin/admin)
    Router::group(["middleware" => ["Permission:manage_users"]], function () {
        Router::get("/usuarios", "UsuariosController@index");
    });

    Router::group(["middleware" => ["Permission:manage_settings"]], function () {
        Router::get("/configuracoes", "ConfiguracoesController@index");
    });
});
