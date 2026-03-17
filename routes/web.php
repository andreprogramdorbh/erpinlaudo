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
Router::get("/api/webhooks/asaas/ping", "IntegracaoController@webhookPing");

// ============================================================
// Primeiro Acesso — Clientes do Portal (via login unificado /login)
// ============================================================
Router::get("/primeiro-acesso", "AuthController@showPrimeiroAcesso");
Router::post("/primeiro-acesso", "AuthController@processarPrimeiroAcesso");
Router::post("/primeiro-acesso/salvar", "AuthController@salvarPrimeiroAcesso");
// Redireciona /portal/login para /login (compatibilidade)
Router::get("/portal/login", "AuthController@showLoginForm");

// ============================================================
// Portal do Cliente — Rotas Protegidas (requerem sessão do portal)
// ============================================================
Router::group(["middleware" => ["PortalCliente"]], function () {
    Router::post("/portal/logout", "AuthController@logout");

    // Dashboard
    Router::get("/portal/dashboard", "PortalClienteController@dashboard");
    Router::get("/portal", "PortalClienteController@dashboard"); // alias
    Router::get("/portal/pagamentos/dashboard", "PortalClienteController@dashboardPagamentos");

    // Perfil
    Router::get("/portal/perfil", "PortalClienteController@perfil");
    Router::post("/portal/perfil/alterar-senha", "PortalClienteController@alterarSenha");

    // Contas a Pagar
    Router::get("/portal/contas-a-pagar", "PortalContasPagarController@index");
    Router::get("/portal/contas-a-pagar/pagar/{id}", "PortalContasPagarController@pagar");
    Router::get("/portal/contas-a-pagar/status/{id}", "PortalContasPagarController@statusCheck");
    Router::get("/portal/contas-a-pagar/sync/{id}", "PortalContasPagarController@syncStatus");
    Router::get("/portal/contas-a-pagar/link/{id}", "PortalContasPagarController@getLink");
    Router::get("/portal/contas-a-pagar/anexos/download/{id}", "PortalContasPagarController@downloadAnexo");

    // Faturamento
    Router::get("/portal/faturamento/notas-fiscais", "PortalFaturamentoController@notasFiscais");
    Router::post("/portal/faturamento/emitir-nfs/{id}", "PortalFaturamentoController@emitirNfs");
    Router::get("/portal/faturamento/nota-fiscal/pdf/{id}", "PortalFaturamentoController@downloadPdf");
    Router::get("/portal/faturamento/nota-fiscal/xml/{id}", "PortalFaturamentoController@downloadXml");
    Router::get("/portal/faturamento/nota-fiscal/anexo/{id}", "PortalFaturamentoController@downloadAnexo");
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

        // Anexos de Clientes
        Router::post("/clientes/anexos/add", "ClientesController@addAnexo");
        Router::get("/clientes/anexos/download/{id}", "ClientesController@downloadAnexo");
        Router::post("/clientes/anexos/remove", "ClientesController@removeAnexo");
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
        Router::get("/fornecedores", "FornecedoresController@index");
        Router::get("/financeiro/fornecedores", "FornecedoresController@index");
    });

    Router::group(["middleware" => ["Permission:create_fornecedores"]], function () {
        Router::get("/fornecedores/create", "FornecedoresController@create");
        Router::post("/fornecedores", "FornecedoresController@store");
        Router::get("/financeiro/fornecedores/create", "FornecedoresController@create");
        Router::post("/financeiro/fornecedores", "FornecedoresController@store");
    });

    Router::group(["middleware" => ["Permission:edit_fornecedores"]], function () {
        Router::get("/fornecedores/edit/{id}", "FornecedoresController@edit");
        Router::post("/fornecedores/update/{id}", "FornecedoresController@update");
        Router::get("/financeiro/fornecedores/edit/{id}", "FornecedoresController@edit");
        Router::post("/financeiro/fornecedores/update/{id}", "FornecedoresController@update");
    });

    Router::group(["middleware" => ["Permission:delete_fornecedores"]], function () {
        Router::post("/fornecedores/delete/{id}", "FornecedoresController@delete");
        Router::post("/financeiro/fornecedores/delete/{id}", "FornecedoresController@delete");
    });

    // Corpo Clinico
    Router::get("/medicos", "MedicosController@index");
    Router::get("/medicos/create", "MedicosController@create");
    Router::post("/medicos/store", "MedicosController@store");
    Router::get("/medicos/edit/{id}", "MedicosController@edit");
    Router::post("/medicos/update/{id}", "MedicosController@update");

    Router::get("/especialidades", "EspecialidadesController@index");
    Router::get("/especialidades/create", "EspecialidadesController@create");
    Router::post("/especialidades/store", "EspecialidadesController@store");

    Router::get("/escalas", "CorpoClinicoController@escalas");

    Router::get("/exames-tabela", "CorpoClinicoController@examesTabela");
    Router::post("/exames-tabela/store", "CorpoClinicoController@storeExameTabela");

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
        Router::get("/financeiro/contas-a-receber/anexos/download/{id}", "ContasReceberController@downloadAnexo");
    });

    Router::group(["middleware" => ["Permission:create_contas_receber"]], function () {
        Router::get("/financeiro/contas-a-receber/create", "ContasReceberController@create");
        Router::post("/financeiro/contas-a-receber", "ContasReceberController@store");
    });

    Router::group(["middleware" => ["Permission:edit_contas_receber"]], function () {
        Router::get("/financeiro/contas-a-receber/edit/{id}", "ContasReceberController@edit");
        Router::post("/financeiro/contas-a-receber/update/{id}", "ContasReceberController@update");
        Router::post("/financeiro/contas-a-receber/anexos/upload", "ContasReceberController@uploadAnexo");
        Router::post("/financeiro/contas-a-receber/anexos/delete/{id}", "ContasReceberController@deleteAnexo");
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
        Router::get("/faturamento/notas-fiscais/anexos/download/{id}", "NotasFiscaisController@downloadAnexo");
        Router::post("/faturamento/notas-fiscais/anexos/upload", "NotasFiscaisController@uploadAnexo");
        Router::post("/faturamento/notas-fiscais/anexos/delete/{id}", "NotasFiscaisController@deleteAnexo");
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

        Router::get("/integracao/email", "IntegracaoController@emailComAlertas");
        Router::post("/integracao/email/save", "IntegracaoController@saveEmail");
        Router::post("/integracao/email/test", "IntegracaoController@testEmail");
        Router::post("/integracao/email/gerar-chave", "IntegracaoController@gerarChaveEmail");
        Router::post("/integracao/email/alertas/toggle", "IntegracaoController@emailAlertasToggle");
        Router::post("/integracao/email/alertas/salvar", "IntegracaoController@emailAlertasSalvar");
        Router::post("/integracao/email/alertas/disparar", "IntegracaoController@emailAlertasDisparar");

        // Integração Bot WhatsApp
        Router::get("/integracao/whatsapp",               "IntegracaoWhatsappController@index");
        Router::post("/integracao/whatsapp/gerar-token",  "IntegracaoWhatsappController@gerarToken");
        Router::post("/integracao/whatsapp/revogar",      "IntegracaoWhatsappController@revogar");
        Router::get("/integracao/whatsapp/logs/export",   "IntegracaoWhatsappController@exportLogs");
    });

    // ===== Módulo CRM =====
    Router::group(["middleware" => ["Permission:view_crm"]], function () {
        Router::get("/crm/funil",         "CrmFunilController@index");
        Router::get("/crm/leads",         "CrmLeadsController@index");
        Router::get("/crm/oportunidades", "CrmOportunidadesController@index");
    });
    Router::group(["middleware" => ["Permission:manage_leads"]], function () {
        Router::get("/crm/leads/create",                  "CrmLeadsController@create");
        Router::post("/crm/leads",                        "CrmLeadsController@store");
        Router::get("/crm/leads/edit/{id}",               "CrmLeadsController@edit");
        Router::post("/crm/leads/update/{id}",            "CrmLeadsController@update");
        Router::post("/crm/leads/delete/{id}",            "CrmLeadsController@delete");
        Router::get("/crm/leads/converter/{id}",          "CrmLeadsController@converter");
        Router::post("/crm/leads/interacao/add",          "CrmLeadsController@addInteracao");
        Router::post("/crm/leads/interacao/delete/{id}",  "CrmLeadsController@deleteInteracao");
        Router::get("/crm/leads/buscar-cnpj",             "CrmLeadsController@buscarCnpj");
    });
    Router::group(["middleware" => ["Permission:manage_oportunidades"]], function () {
        Router::get("/crm/oportunidades/create",                   "CrmOportunidadesController@create");
        Router::post("/crm/oportunidades",                         "CrmOportunidadesController@store");
        Router::get("/crm/oportunidades/edit/{id}",                "CrmOportunidadesController@edit");
        Router::post("/crm/oportunidades/update/{id}",             "CrmOportunidadesController@update");
        Router::post("/crm/oportunidades/delete/{id}",             "CrmOportunidadesController@delete");
        Router::post("/crm/oportunidades/mover",                   "CrmOportunidadesController@moverEtapa");
        Router::post("/crm/oportunidades/interacao/add",           "CrmOportunidadesController@addInteracao");
        Router::post("/crm/oportunidades/interacao/delete/{id}",   "CrmOportunidadesController@deleteInteracao");
    });

    // Logging de Erros do Frontend
    Router::post("/api/log/error", "LogController@saveClientError");

    // Diagnóstico temporário (REMOVER APÓS USO)
    Router::get("/diagnostico/upload-info", "DiagnosticoController@uploadInfo");

    // Perfil do usuário
    Router::group(["middleware" => ["Permission:view_profile"]], function () {
        Router::get("/perfil", "PerfilController@index");
        Router::post("/perfil/update", "PerfilController@update");
        Router::post("/perfil/change-password", "PerfilController@changePassword");
    });

    // Configurações (inclui gestão de usuários)
    Router::group(["middleware" => ["Permission:manage_settings"]], function () {
        Router::get("/configuracoes", "ConfiguracoesController@index");
        Router::get("/configuracoes/usuarios/create", "ConfiguracoesController@usuariosCreate");
        Router::post("/configuracoes/usuarios", "ConfiguracoesController@usuariosStore");
        Router::get("/configuracoes/usuarios/edit/{id}", "ConfiguracoesController@usuariosEdit");
        Router::post("/configuracoes/usuarios/update/{id}", "ConfiguracoesController@usuariosUpdate");
        Router::post("/configuracoes/usuarios/reset-password/{id}", "ConfiguracoesController@usuariosResetPassword");
        Router::post("/configuracoes/usuarios/toggle-status/{id}", "ConfiguracoesController@usuariosToggleStatus");
        // Configurações NFS-e Nacional
        Router::post("/configuracoes/nfs/salvar", "ConfiguracoesController@nfsSalvar");
    });
});
