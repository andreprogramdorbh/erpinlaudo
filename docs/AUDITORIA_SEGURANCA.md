# AUDITORIA DE SEGURANÇA - Inlaudo ERP

**Data**: Fevereiro de 2026  
**Avaliação**: Código de Produção  
**Risco Geral**: ⚠️ ALTO

---

## RESUMO EXECUTIVO

O sistema apresenta **múltiplas vulnerabilidades críticas** que comprometem a segurança de dados de usuários e a integridade da aplicação. Várias práticas de segurança deixaram de ser implementadas. Recomenda-se correção imediata antes de produção.

---

## 🔴 VULNERABILIDADES CRÍTICAS

### 1. **HARDCODED SUPERADMIN OVERRIDE** ⚠️ CRÍTICO
**Arquivo**: [app/Core/Auth.php](app/Core/Auth.php) (linhas 40-41)

```php
// AJUSTE TEMPORÁRIO (DEV): Forçando superadmin para desenvolvimento do módulo Clientes
$_SESSION['user_role'] = 'superadmin';
```

**Risco**: Qualquer usuário logado é automaticamente elevado a superadmin, contornando controle de acesso.

**Impacto**: Total compromisso do sistema de permissões.

**Ação Recomendada**: 
- Remover imediatamente em produção
- Usar `$_SESSION['user_role'] = strtolower($user->profile ?? 'user');` (conforme comentário)
- Adicionar variável `.env` para modo desenvolvimento: `DEV_FORCE_ROLE=false`

---

### 2. **ARQUIVO DE DEBUG EXPOSTO** ⚠️ CRÍTICO
**Arquivo**: `public/test_env.php`, `public/DEBUG_ERRO_500.php`, `public/_envcheck.php`

**Risco**: Estes arquivos expõem informações sensíveis:
- Credenciais do banco (conexão bem-sucedida mostra database name)
- Versão PHP
- Extensões carregadas
- Estrutura de diretórios

**Impacto**: Reconhecimento de alvo para atacantes.

**Ação Recomendada**:
- Deletar `test_env.php`, `DEBUG_ERRO_500.php`, `_envcheck.php` de produção
- Usar `.gitignore` para evitar commit acidental
- Criar endpoint protegido com middleware Auth+Permission se necessário debug

---

### 3. **AUSÊNCIA DE PROTEÇÃO XSS EM VIEWS** ⚠️ ALTO
**Arquivo**: [app/Views/layout/erp_header.php](app/Views/layout/erp_header.php) (linhas 481-489)

```php
<li class="breadcrumb-item"><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
<h1 class="page-title"><?php echo $title ?? 'Dashboard'; ?></h1>
```

**Problema**: Variáveis são ecoadas sem `htmlspecialchars()`.

**Risco**: Se `$title`, `$link`, `$label` vierem de `$_GET`/`$_POST` sem sanitização, permitem injeção de JavaScript.

**Casos de Risco**:
- `/clientes?title=<script>alert(1)</script>` 
- Links com evento malicioso: `href="javascript:alert(1)"`

**Ação Recomendada**:
```php
// Substituir todas as instâncias de:
<?php echo $variable; ?>

// Por:
<?php echo htmlspecialchars($variable, ENT_QUOTES, 'UTF-8'); ?>
```

---

### 4. **AUSÊNCIA DE VALIDAÇÃO DE ENTRADA** ⚠️ ALTO
**Arquivo**: [app/Controllers/ClientesController.php](app/Controllers/ClientesController.php) (linhas 76-100)

```php
$dados = [
    'tipo' => $_POST['tipo'] ?? 'PJ',
    'cpf_cnpj' => $cpfCnpj,
    'email' => trim($_POST['email'] ?? ''),
    // ... sem validação de formato
];
```

**Problemas**:
- Email não é validado com `filter_var($email, FILTER_VALIDATE_EMAIL)`
- CNPJ/CPF não são validados (apenas dígitos removidos, sem validação de checksum)
- URLs (`website`) não são validadas
- Strings não são validadas contra padrões esperados (tamanho mín/máx)

**Risco**: Dados malformados poluem banco, causam problemas de relatório/integração.

**Ação Recomendada**: Criar classe `Validator`:
```php
class Validator {
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function cnpj(string $cnpj): bool {
        // Validar checksum real
    }
}
```

---

### 5. **ROUTE PARAMETER INJECTION** ⚠️ MÉDIO-ALTO
**Arquivo**: [app/Core/Router.php](app/Core/Router.php) (linhas 74-85)

```php
$parts = explode('/', $uri);
$id = end($parts);

if (is_numeric($id)) {
    call_user_func([$instance, $action], $id);
} else {
    call_user_func([$instance, $action]);
}
```

**Problema**: Assume que ID é sempre o último segmento. Rotas como `/clientes/edit/123/extra` podem resultar em comportamento inesperado.

**Risco**: Contorno de lógica de negócio, acesso não autorizado.

**Ação Recomendada**: Implementar parser de rota com regex:
```php
Router::get('/clientes/edit/:id(\d+)', 'ClientesController@edit');
```

---

### 6. **AUSÊNCIA DE RATE LIMITING** ⚠️ MÉDIO
**Arquivo**: Nenhuma implementação encontrada

**Risco**: Brute force em login (`/login` POST), enumeração de usuários, DDoS.

**Ação Recomendada**: Middleware com Redis ou arquivo de lock:
```php
class RateLimitMiddleware extends Middleware {
    public function handle(): void {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:{$ip}";
        // Verificar tentativas nos últimos 5 minutos
    }
}
```

---

### 7. **SESSION FIXATION VULNERABILITY** ⚠️ MÉDIO
**Arquivo**: [app/Core/Auth.php](app/Core/Auth.php) (linha 102)

```php
private static function regenerateSession(): void {
    session_regenerate_id(true);
}
```

**Problema**: Método é `private`, nunca é chamado. A session ID não é regenerada após login.

**Verifiação**: A chamada está comentada/não executada no método `login()`.

**Risco**: Session fixation attack - atacante força vítima usar session ID conhecido.

**Ação Recomendada**: Verificar se `regenerateSession()` é de fato chamada em `login()`.

---

## 🟠 VULNERABILIDADES MÉDIAS

### 8. **AUSÊNCIA DE SECURITY HEADERS** ⚠️ MÉDIO
**Arquivo**: Nenhum arquivo define headers HTTP de segurança

**Headers Faltantes**:
```php
// Adicionar em public/index.php ou middleware global:
header('X-Content-Type-Options: nosniff');          // Previne MIME sniffing
header('X-Frame-Options: SAMEORIGIN');              // Previne clickjacking
header('X-XSS-Protection: 1; mode=block');          // Proteção XSS legada
header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); // HSTS
header('Content-Security-Policy: default-src \'self\''); // CSP
```

**Risco**: Ataques MIME sniffing, clickjacking, downgrade HTTPS.

---

### 9. **TIMEZONE HARDCODING** ⚠️ BAIXO-MÉDIO
**Arquivo**: [app/Core/Logger.php](app/Core/Logger.php)

**Problema**: Não há `date_default_timezone_set()`. Timestamps podem estar errados se servidor tiver timezone diferente.

**Ação Recomendada**:
```php
// Em app/bootstrap.php após .env load:
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
```

---

### 10. **SENHA TEMPORÁRIA EM AUDITLOG** ⚠️ MÉDIO
**Arquivo**: [app/Core/Auth.php](app/Core/Auth.php) (linha 43)

```php
AuditLogger::log('login_success', ['user_id' => $user->id, 'email' => $email, 'dev_override' => 'superadmin']);
```

**Problema**: Gera logs sensíveis que podem ser lidos por atacantes que ganhem acesso ao arquivo.

**Ação Recomendada**: Nunca logar informações sensíveis, apenas IDs:
```php
AuditLogger::log('login_success', ['user_id' => $user->id]);
```

---

## 🟡 VULNERABILIDADES BAIXAS

### 11. **CSRF TOKEN NÃO INICIALIZADO** ⚠️ BAIXO
**Arquivo**: [app/Middlewares/CsrfMiddleware.php](app/Middlewares/CsrfMiddleware.php)

```php
if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
```

**Problema**: `$_SESSION['csrf_token']` nunca é gerado. Precisa ser criada no bootstrap ou login.

**Ação Recomendada**:
```php
// Em app/bootstrap.php:
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

---

### 12. **AUSÊNCIA DE PREPARED STATEMENT EM ALGUNS LUGARES** ⚠️ BAIXO
**Arquivo**: Verificação geral

**Status**: Bom! O projeto usa prepared statements com `?` e `:named`.

**Verificação**: Todas as queries encontradas usam prepared statements:
```php
$stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
$stmt->execute([$id]);
```

✅ **Sem vulnerabilidades SQL injection detectadas**.

---

### 13. **PERMISSÃO DE ARQUIVO SENSÍVEL** ⚠️ BAIXO
**Arquivo**: `.env`

**Problema**: Arquivo não possui restrições de acesso no .htaccess.

**Ação Recomendada**: Adicionar ao `public/.htaccess`:
```apache
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

Ou melhor ainda, colocar `.env` **fora** de `public_html`.

---

## 📋 RECOMENDAÇÕES POR PRIORIDADE

### 🔴 IMEDIATO (Fazer em 24h)
1. **Remover hardcoded superadmin** em Auth.php
2. **Deletar arquivos de debug** (test_env.php, DEBUG_ERRO_500.php)
3. **Adicionar htmlspecialchars()** em todas as views
4. **Implementar validação de entrada** em ClientesController

### 🟠 URGENTE (Semana 1)
5. Adicionar security headers em bootstrap
6. Implementar rate limiting no login
7. Gerar CSRF token no bootstrap
8. Melhorar parser de rotas

### 🟡 IMPORTANTE (Semana 2-3)
9. Criar classe Validator reutilizável
10. Adicionar timezone config
11. Revisar AuditLogger para não logar dados sensíveis
12. Proteger `.env` com .htaccess

---

## 📝 CHECKLIST DE SEGURANÇA

- [ ] Remover override de role em Auth.php
- [ ] Deletar test_env.php, DEBUG_ERRO_500.php, _envcheck.php
- [ ] Adicionar htmlspecialchars em erp_header.php
- [ ] Implementar validação de email/CNPJ
- [ ] Gerar CSRF token em bootstrap
- [ ] Adicionar security headers (X-Frame-Options, X-Content-Type-Options, CSP)
- [ ] Implementar rate limiting
- [ ] Proteger .env com .htaccess
- [ ] Revisar AuditLogger para dados sensíveis
- [ ] Testar Session Timeout Middleware
- [ ] Configurar timezone
- [ ] Testar SQL injection em todas as queries
- [ ] Revisar permissões de acesso multi-tenant

---

## 🧪 TESTES DE SEGURANÇA RECOMENDADOS

```bash
# 1. Tentar acessar test_env.php em produção
curl https://seu-dominio.com/test_env.php

# 2. Tentar XSS em title
curl "https://seu-dominio.com/dashboard?title=<script>alert(1)</script>"

# 3. Tentar brute force em login
for i in {1..100}; do curl -X POST https://seu-dominio.com/login -d "email=test@test.com&password=wrong"; done

# 4. Verificar security headers
curl -I https://seu-dominio.com/dashboard

# 5. Testar CSRF sem token
curl -X POST https://seu-dominio.com/clientes/store -d "razao_social=teste"
```

---

## 🛡️ DEFESAS IMPLEMENTADAS (✅ Bom)

1. ✅ **Prepared Statements**: Todas as queries usam `?` ou `:named`
2. ✅ **Argon2ID Hashing**: Senhas usam algoritmo forte
3. ✅ **Session Timeout**: Middleware implementado (3600s)
4. ✅ **Multi-tenant Isolation**: `usuario_id` em todas as queries (se seguido)
5. ✅ **CSRF Middleware**: Implementado (porém token não é gerado)
6. ✅ **Permission Middleware**: Role-based access control
7. ✅ **AuditLogger**: Registro de ações críticas

---

**Próximos Passos**: Solicitar confirmação de prioridades e iniciar correções imediatas.
