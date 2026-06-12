<?php
/**
 * ERP InLaudo - Gerador de Chaves de Criptografia
 * Gera chaves seguras para o arquivo .env
 */

echo "<h1>🔑 Gerador de Chaves de Criptografia</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .key { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    .copy-btn { background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

// Gera chave base64 (32 bytes)
function generateBase64Key(): string
{
    return base64_encode(random_bytes(32));
}

// Gera chave hexadecimal (64 chars)
function generateHexKey(): string
{
    return bin2hex(random_bytes(32));
}

// Gera chave base32 (52 chars)
function generateBase32Key(): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $key = '';
    $bytes = random_bytes(32);
    
    for ($i = 0; $i < 52; $i++) {
        $byte = ord($bytes[$i % 32]);
        $key .= $alphabet[$byte % 32];
    }
    
    return $key;
}

echo "<div class='section'>";
echo "<h2>🔐 Chaves Geradas</h2>\n";

$base64Key = generateBase64Key();
$hexKey = generateHexKey();
$base32Key = generateBase32Key();

echo "<h3>Formato Base64 (Recomendado)</h3>\n";
echo "<div class='key'>APP_KEY=base64:{$base64Key}</div>\n";
echo "<button class='copy-btn' onclick='copyToClipboard(\"APP_KEY=base64:{$base64Key}\")'>📋 Copiar</button>\n";

echo "<h3>Formato Hexadecimal</h3>\n";
echo "<div class='key'>APP_KEY=hex:{$hexKey}</div>\n";
echo "<button class='copy-btn' onclick='copyToClipboard(\"APP_KEY=hex:{$hexKey}\")'>📋 Copiar</button>\n";

echo "<h3>Formato Base32</h3>\n";
echo "<div class='key'>APP_KEY=base32:{$base32Key}</div>\n";
echo "<button class='copy-btn' onclick='copyToClipboard(\"APP_KEY=base32:{$base32Key}\")'>📋 Copiar</button>\n";

echo "</div>";

echo "<div class='section'>";
echo "<h2>📝 Como Usar</h2>\n";
echo "<ol>\n";
echo "<li>Escolha um dos formatos acima (Base64 é recomendado)</li>\n";
echo "<li>Copie a linha correspondente</li>\n";
echo "<li>Cole no seu arquivo .env</li>\n";
echo "<li>Repita o processo para APP_ENCRYPTION_KEY (use a mesma chave ou outra diferente)</li>\n";
echo "</ol>\n";
echo "</div>";

echo "<div class='section'>";
echo "<h2>⚠️ Importante</h2>\n";
echo "<ul>\n";
echo "<li><strong>NUNCA</strong> compartilhe estas chaves</li>\n";
echo "<li><strong>NUNCA</strong> coloque-as no controle de versão (Git)</li>\n";
echo "<li>Guarde uma cópia segura das chaves</li>\n";
echo "<li>Se perder as chaves, dados criptografados serão perdidos</li>\n";
echo "</ul>\n";
echo "</div>";

echo "<div class='section'>";
echo "<h2>🔧 Exemplo de .env</h2>\n";
echo "<div class='key'># Chaves de Criptografia\n";
echo "APP_KEY=base64:{$base64Key}\n";
echo "APP_ENCRYPTION_KEY=base64:" . generateBase64Key() . "\n\n";
echo "# Outras configurações...\n";
echo "DB_HOST=localhost\n";
echo "DB_DATABASE=erp_inlaudo\n";
echo "DB_USERNAME=seu_usuario\n";
echo "DB_PASSWORD=sua_senha\n";
echo "MAIL_HOST=smtp.gmail.com\n";
echo "MAIL_USERNAME=seu@email.com\n";
echo "MAIL_PASSWORD=sua_senha_app</div>\n";
echo "</div>";

echo "<script>\n";
echo "function copyToClipboard(text) {\n";
echo "    navigator.clipboard.writeText(text).then(function() {\n";
echo "        alert('Chave copiada para a área de transferência!');\n";
echo "    }, function(err) {\n";
echo "        console.error('Erro ao copiar: ', err);\n";
echo "    });\n";
echo "}\n";
echo "</script>\n";

echo "<p><small>Chaves geradas em " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
