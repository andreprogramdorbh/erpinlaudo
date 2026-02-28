<?php
use App\Core\Router;

/**
 * Rotas da API do Bot WhatsApp
 *
 * Todas as rotas abaixo são protegidas pelo WhatsappApiAuthMiddleware,
 * que valida o token secreto no cabeçalho X-API-Key.
 *
 * Base URL: /api/v1/whatsapp
 *
 * Endpoints disponíveis:
 *  POST /api/v1/whatsapp/identificar      → Identifica o cliente pelo telefone
 *  POST /api/v1/whatsapp/resumo           → Resumo financeiro do cliente
 *  POST /api/v1/whatsapp/faturas          → Lista faturas do cliente
 *  POST /api/v1/whatsapp/notas-fiscais    → Lista notas fiscais do cliente
 *  POST /api/v1/whatsapp/logs             → Lista logs do bot (auditoria)
 */

Router::group(['middleware' => ['WhatsappApiAuth']], function () {
    Router::post('/api/v1/whatsapp/identificar',   'Api\V1\WhatsappAuthController@identificar');
    Router::post('/api/v1/whatsapp/resumo',        'Api\V1\WhatsappResumoController@index');
    Router::post('/api/v1/whatsapp/faturas',       'Api\V1\WhatsappFaturasController@index');
    Router::post('/api/v1/whatsapp/notas-fiscais', 'Api\V1\WhatsappNotasFiscaisController@index');
    Router::post('/api/v1/whatsapp/logs',          'Api\V1\WhatsappLogsController@index');
});