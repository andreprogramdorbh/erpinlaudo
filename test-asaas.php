<?php
/**
 * ERP InLaudo - Teste de Integração Asaas
 * Script para testar configurações e funcionalidades do Asaas
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AsaasService;
use App\Services\MailService;

echo "<h1>💳 Teste de Integração Asaas</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .ok { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    input, button { padding: 5px; margin: 5px; }
</style>";

// Função helper
function status($condition, $message) {
    $class = $condition ? 'ok' : 'error';
    $icon = $condition ? '✅' : '❌';
    echo "<span class='{$class}'>{$icon} {$message}</span><br>\n";
    return $condition;
}

// Verificar configurações
echo "<div class='section'>";
echo "<h2>🔍 Configurações do Asaas</h2>\n";

$envVars = [
    'ASAAS_API_KEY' => 'API Key do Asaas',
    'ASAAS_ENVIRONMENT' => 'Ambiente (sandbox/production)'
];

$allOk = true;
foreach ($envVars as $var => $desc) {
    $value = getenv($var) ?: $_ENV[$var] ?? null;
    $hasValue = !empty($value);
    if (!status($hasValue, "{$desc}: " . ($hasValue ? 'Configurado' : 'Não configurado'))) {
        $allOk = false;
    }
    echo "<small style='color: #666;'>Variável: {$var}</small><br>\n";
}

if ($allOk) {
    $environment = getenv('ASAAS_ENVIRONMENT') ?: $_ENV['ASAAS_ENVIRONMENT'] ?? 'sandbox';
    $baseUrl = $environment === 'production' 
        ? 'https://api.asaas.com/v3' 
        : 'https://sandbox.asaas.com/api/v3';
    echo "<br><small>📡 URL da API: {$baseUrl}</small><br>\n";
}
echo "</div>";

// Testar serviço
echo "<div class='section'>";
echo "<h2>🧪 Teste do Serviço Asaas</h2>\n";

try {
    $asaasConfigured = AsaasService::isConfigured();
    status($asaasConfigured, 'AsaasService configurado');
    
    if ($asaasConfigured) {
        $asaas = new AsaasService();
        
        // Testar conexão (buscar customers)
        echo "<h4>Teste de Conexão:</h4>\n";
        try {
            $customers = $asaas->buscarCliente();
            status(true, 'Conexão com API Asaas bem-sucedida');
            echo "<small>✅ API respondendo normalmente</small><br>\n";
        } catch (Exception $e) {
            status(false, 'Falha na conexão: ' . htmlspecialchars($e->getMessage()));
        }
        
        // Testar criação de cliente
        echo "<h4>Teste de Criação de Cliente:</h4>\n";
        if ($_POST['action'] === 'test_customer') {
            try {
                $testCustomer = [
                    'name' => 'Cliente Teste ERP',
                    'email' => 'teste@erp.com',
                    'cpfCnpj' => '12345678901',
                    'phone' => '11999999999',
                    'notificationDisabled' => true
                ];
                
                $customer = $asaas->criarCliente($testCustomer);
                status(true, 'Cliente criado com sucesso: ' . htmlspecialchars($customer['id']));
                echo "<pre>ID: " . htmlspecialchars($customer['id']) . "\n";
                echo "Nome: " . htmlspecialchars($customer['name']) . "\n";
                echo "Status: " . htmlspecialchars($customer['deleted'] ? 'Excluído' : 'Ativo') . "</pre>\n";
                
                // Limpar cliente de teste
                $asaas->makeRequest('DELETE', '/customers/' . $customer['id']);
                echo "<small>🧹 Cliente de teste removido</small><br>\n";
                
            } catch (Exception $e) {
                status(false, 'Erro ao criar cliente: ' . htmlspecialchars($e->getMessage()));
            }
        } else {
            echo "<form method='POST'>\n";
            echo "<input type='hidden' name='action' value='test_customer'>\n";
            echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;'>👤 Testar Criação de Cliente</button>\n";
            echo "</form>\n";
        }
        
        // Testar criação de cobrança
        echo "<h4>Teste de Criação de Cobrança:</h4>\n";
        if ($_POST['action'] === 'test_payment') {
            try {
                // Primeiro cria um cliente
                $testCustomer = [
                    'name' => 'Cliente Teste Cobrança',
                    'email' => 'cobranca@erp.com',
                    'cpfCnpj' => '98765432100',
                    'phone' => '11888888888',
                    'notificationDisabled' => true
                ];
                $customer = $asaas->criarCliente($testCustomer);
                
                // Depois cria a cobrança
                $paymentData = [
                    'customer' => $customer['id'],
                    'value' => 10.00,
                    'dueDate' => date('Y-m-d', strtotime('+7 days')),
                    'description' => 'Cobrança de Teste ERP',
                    'billingType' => 'BOLETO',
                    'postalService' => false
                ];
                
                $payment = $asaas->criarCobranca($paymentData);
                status(true, 'Cobrança criada com sucesso: ' . htmlspecialchars($payment['id']));
                
                echo "<pre>ID: " . htmlspecialchars($payment['id']) . "\n";
                echo "Valor: R$ " . number_format($payment['value'], 2, ',', '.') . "\n";
                echo "Vencimento: " . date('d/m/Y', strtotime($payment['dueDate'])) . "\n";
                echo "Status: " . htmlspecialchars($payment['status']) . "\n";
                echo "Link: " . htmlspecialchars($payment['invoiceUrl'] ?? 'N/A') . "</pre>\n";
                
                // Limpar
                $asaas->makeRequest('DELETE', '/customers/' . $customer['id']);
                echo "<small>🧹 Cliente e cobrança de teste removidos</small><br>\n";
                
            } catch (Exception $e) {
                status(false, 'Erro ao criar cobrança: ' . htmlspecialchars($e->getMessage()));
            }
        } else {
            echo "<form method='POST'>\n";
            echo "<input type='hidden' name='action' value='test_payment'>\n";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;'>💳 Testar Criação de Cobrança</button>\n";
            echo "</form>\n";
        }
    }
} catch (Exception $e) {
    status(false, 'Erro ao inicializar AsaasService: ' . htmlspecialchars($e->getMessage()));
}
echo "</div>";

// Verificar MailService para envio de links
echo "<div class='section'>";
echo "<h2>📧 Integração com E-mail</h2>\n";

$mailConfigured = MailService::isConfigured();
status($mailConfigured, 'MailService configurado para envio de links');

if ($mailConfigured) {
    echo "<small>✅ Sistema pronto para enviar links de pagamento por e-mail</small><br>\n";
} else {
    echo "<small>⚠️ Configure as variáveis MAIL_* no .env para enviar links de pagamento</small><br>\n";
}
echo "</div>";

// Informações de configuração
echo "<div class='section'>";
echo "<h2>⚙️ Como Configurar o Asaas</h2>\n";
echo "<h3>1. Obter API Key</h3>\n";
echo "<ol>\n";
echo "<li>Acesse <a href='https://sandbox.asaas.com/' target='_blank'>https://sandbox.asaas.com/</a> (testes)</li>\n";
echo "<li>Faça login ou crie uma conta gratuita</li>\n";
echo "<li>Vá em Configurações > Chaves de API</li>\n";
echo "<li>Copie a chave e cole em ASAAS_API_KEY</li>\n";
echo "</ol>\n";

echo "<h3>2. Configurar .env</h3>\n";
echo "<pre>
ASAAS_ENVIRONMENT=sandbox
ASAAS_API_KEY=sua_chave_aqui
</pre>\n";

echo "<h3>3. Meios de Pagamento Suportados</h3>\n";
echo "<ul>\n";
echo "<li><strong>BOLETO</strong> - Gera boleto bancário</li>\n";
echo "<li><strong>CREDIT_CARD</strong> - Pagamento com cartão de crédito</li>\n";
echo "<li><strong>PIX</strong> - Pagamento instantâneo via PIX</li>\n";
echo "</ul>\n";

echo "<h3>4. Fluxo de Integração</h3>\n";
echo "<ol>\n";
echo "<li>Cliente cria conta a receber com meio digital</li>\n";
echo "<li>Sistema busca/cria cliente no Asaas</li>\n";
echo "<li>Sistema cria cobrança no Asaas</li>\n";
echo "<li>Sistema envia e-mail com link de pagamento</li>\n";
echo "<li>Cliente paga através do link</li>\n";
echo "<li>Sistema sincroniza status automaticamente</li>\n";
echo "</ol>\n";
echo "</div>";

echo "<p><small>Teste executado em " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
