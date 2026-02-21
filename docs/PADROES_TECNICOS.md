# PADRÕES TÉCNICOS - ERP InLaudo

**Versão:** 1.0.0  
**Data:** 2026-02-03  
**Status:** Ativo  

---

## 1. PADRÃO DE LOGS DE AUDITORIA

### 1.1 Estrutura do Log
```php
AuditLogger::log('verbo_recurso', [
    'id' => $recordId,
    'field' => 'value',
    'context' => 'additional_info'
]);
```

### 1.2 Padrão de Nomes de Ação
- `create_*` - Criação de registros
- `update_*` - Atualização de registros  
- `delete_*` - Remoção de registros
- `login_success` - Login bem-sucedido
- `login_failed` - Falha de login
- `logout` - Logout do usuário
- `password_reset` - Redefinição de senha
- `permission_denied` - Acesso negado

### 1.3 Contexto Obrigatório
```php
// Sempre incluir ID do registro quando aplicável
AuditLogger::log('create_client', [
    'client_id' => $clientId,
    'razao_social' => $razaoSocial
]);

// Nunca incluir dados sensíveis
AuditLogger::log('update_user', [
    'user_id' => $userId,
    'fields_updated' => ['name', 'email'] // ❌ NÃO incluir 'password'
]);
```

### 1.4 Tratamento de Erros
```php
try {
    AuditLogger::log('action_name', $context);
} catch (Exception $e) {
    // Silently fail - nunca quebrar fluxo principal
    error_log("AuditLogger Failure: " . $e->getMessage());
}
```

---

## 2. PADRÃO DE LAYOUTS

### 2.1 Layout Público (Sem Autenticação)
**Telas:** Login, Forgot Password, Reset Password

```php
// Estrutura obrigatória
require_once dirname(__DIR__) . '/layout/public_header.php';
// ... conteúdo da página
require_once dirname(__DIR__) . '/layout/public_footer.php';
```

**Características:**
- Sem sidebar
- Sem menu interno
- Sem dados do usuário
- Design minimalista focado em autenticação

### 2.2 Layout Privado (Com Autenticação)
**Telas:** Dashboard, Módulos, Configurações

```php
// Estrutura obrigatória
require_once dirname(__DIR__) . '/layout/erp_header.php';
// ... conteúdo da página
require_once dirname(__DIR__) . '/layout/erp_footer.php';
```

**Características:**
- Sidebar de navegação
- Header com informações do usuário
- Menu contextual por módulo
- Sistema de notificações

### 2.3 Layout Enterprise Form
**Aplicação:** Formulários CRUD com abas

```php
// Configuração padrão
$formConfig = [
    'title' => 'Título do Módulo',
    'subtitle' => 'Descrição auxiliar',
    'is_edit' => false,
    'active_tab' => 0,
    'tabs' => [
        [
            'id' => 'unique-id',
            'title' => 'Título da Aba',
            'icon' => 'fas fa-icon',
            'locked' => false,
            'view' => 'module.tabs.tab-content'
        ]
    ],
    'footer_actions' => [
        [
            'type' => 'submit',
            'label' => 'Salvar',
            'color' => 'primary'
        ]
    ]
];
```

---

## 3. PADRÃO DE FORMULÁRIOS ENTERPRISE

### 3.1 Estrutura de Abas
```php
// Aba 1: Dados Gerais (sempre ativa)
[
    'id' => 'dados-gerais',
    'title' => 'Dados Gerais',
    'icon' => 'fas fa-info-circle',
    'locked' => false,
    'view' => 'module.tabs.dados-gerais'
]

// Aba 2: Relacionamentos (bloqueada até salvar)
[
    'id' => 'relacionamentos',
    'title' => 'Contatos/Relacionamentos',
    'icon' => 'fas fa-users',
    'locked' => !$isEdit,
    'locked_message' => 'Salve os dados gerais primeiro',
    'view' => 'module.tabs.relacionamentos'
]
```

### 3.2 Classes CSS Obrigatórias
```html
<!-- Container principal -->
<div class="form-container">

<!-- Seções -->
<section class="form-section">
    <h2 class="form-section-title">Título da Seção</h2>
    
    <!-- Grid responsivo -->
    <div class="form-grid form-grid-2">
        <div class="form-group">
            <label class="form-label required">Campo</label>
            <input type="text" class="form-control">
        </div>
    </div>
</section>
</div>
```

### 3.3 Grid System
```css
.form-grid-1 { grid-template-columns: 1fr; }
.form-grid-2 { grid-template-columns: repeat(2, 1fr); }
.form-grid-3 { grid-template-columns: repeat(3, 1fr); }
.form-grid-4 { grid-template-columns: repeat(4, 1fr); }
```

### 3.4 Estados de Campos
```html
<!-- Normal -->
<input class="form-control">

<!-- Obrigatório -->
<label class="form-label required">Campo *</label>

<!-- Erro -->
<input class="form-control is-invalid">
<div class="form-feedback invalid">Mensagem de erro</div>

<!-- Sucesso -->
<input class="form-control is-success">
<div class="form-feedback success">Campo válido</div>

<!-- Desabilitado -->
<input class="form-control" disabled>
```

---

## 4. PADRÃO DE JAVASCRIPT

### 4.1 Módulos Principais
```javascript
// form-tabs.js - Sistema de abas
class FormTabs {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.init();
    }
    
    switchToTab(index) { /* ... */ }
    lockTab(index, message) { /* ... */ }
    unlockTab(index) { /* ... */ }
}

// module-form.js - Lógica específica do módulo
class ModuleForm {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.tabs = new FormTabs(container);
        this.init();
    }
    
    setupValidation() { /* ... */ }
    setupMasks() { /* ... */ }
    setupAjax() { /* ... */ }
}
```

### 4.2 Inicialização Padrão
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const formContainer = document.querySelector('.form-container');
    if (formContainer) {
        new ModuleForm(formContainer, {
            moduleId: 'clientes',
            apiEndpoints: {
                save: '/clientes/store',
                update: '/clientes/update'
            }
        });
    }
});
```

---

## 5. PADRÃO DE VALIDAÇÃO

### 5.1 Validação Frontend
```javascript
function validateForm(form) {
    const errors = [];
    
    // Validação de campos obrigatórios
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            errors.push(`${field.name} é obrigatório`);
        }
    });
    
    // Validação de email
    const emailField = form.querySelector('[type="email"]');
    if (emailField && !isValidEmail(emailField.value)) {
        errors.push('Email inválido');
    }
    
    return errors;
}
```

### 5.2 Validação Backend
```php
// No Controller
$data = $this->validateRequest([
    'razao_social' => 'required|string|max:255',
    'email' => 'required|email',
    'cpf_cnpj' => 'required|cnpj'
]);

if ($errors = $this->validator->getErrors()) {
    return $this->json(['errors' => $errors], 422);
}
```

---

## 6. PADRÃO DE API

### 6.1 Endpoints REST
```php
// Padrão de rotas
GET    /clientes           - Listar
GET    /clientes/{id}      - Visualizar
GET    /clientes/create    - Formulário de criação
POST   /clientes           - Criar
GET    /clientes/{id}/edit - Formulário de edição
PUT    /clientes/{id}      - Atualizar
DELETE /clientes/{id}      - Remover
```

### 6.2 Respostas JSON
```php
// Sucesso
return $this->json([
    'success' => true,
    'data' => $result,
    'message' => 'Operação realizada com sucesso'
]);

// Erro
return $this->json([
    'success' => false,
    'errors' => $errors,
    'message' => 'Erro na validação'
], 422);
```

---

## 7. PADRÃO DE NOMENCLATURA

### 7.1 Banco de Dados
- **Tabelas:** snake_case plural (`clientes`, `users`, `audit_logs`)
- **Colunas:** snake_case (`razao_social`, `data_cadastro`, `usuario_id`)
- **Índices:** `idx_` + nome (`idx_cpf_cnpj`, `idx_usuario_id`)
- **FKs:** `fk_` + tabela + `_id` (`fk_clientes_usuario_id`)

### 7.2 PHP
- **Classes:** PascalCase (`ClienteController`, `AuditLogger`)
- **Métodos:** camelCase (`findByEmail`, `createClient`)
- **Variáveis:** camelCase (`$clienteId`, `$userData`)
- **Constantes:** UPPER_SNAKE_CASE (`DEFAULT_ROLE`, `MAX_FILE_SIZE`)

### 7.3 JavaScript
- **Classes:** PascalCase (`FormTabs`, `ClientesForm`)
- **Métodos:** camelCase (`switchToTab`, `validateForm`)
- **Variáveis:** camelCase (`formContainer`, `activeTabIndex`)
- **Constantes:** UPPER_SNAKE_CASE (`API_ENDPOINTS`, `VALIDATION_RULES`)

### 7.4 CSS
- **Classes:** kebab-case (`form-container`, `form-section-title`)
- **Variáveis:** kebab-case (`--form-primary`, --form-success`)
- **IDs:** kebab-case (`cliente-form`, `tab-contatos`)

---

## 8. PADRÃO DE SEGURANÇA

### 8.1 Validação de Permissões
```php
// Em todo Controller
public function store() {
    if (!Auth::can('create_clients')) {
        return $this->redirect('/dashboard?error=unauthorized');
    }
    
    // ... lógica do método
}
```

### 8.2 Proteção CSRF
```php
// Em todo formulário
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Validação no Controller
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    return $this->redirect('/dashboard?error=csrf');
}
```

### 8.3 Sanitização de Dados
```php
// Sempre sanitizar entrada de dados
$name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

// Usar prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

---

## 9. PADRÃO DE PERFORMANCE

### 9.1 Queries Otimizadas
```php
// ✅ CORRETO - Usar índices
$stmt = $pdo->prepare("SELECT id, razao_social FROM clientes WHERE usuario_id = ? AND status = ?");

// ✅ CORRETO - Limitar resultados
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario_id = ? ORDER BY id DESC LIMIT 20");

// ❌ ERRADO - SELECT sem necessidade
$stmt = $pdo->prepare("SELECT * FROM clientes"); // Pode ser muito pesado
```

### 9.2 Cache de Consultas
```php
// Cache de dados que mudam pouco
public function getPermissionsForRole($role) {
    $cacheKey = "permissions_{$role}";
    if (apcu_exists($cacheKey)) {
        return apcu_fetch($cacheKey);
    }
    
    $permissions = $this->loadPermissions($role);
    apcu_store($cacheKey, $permissions, 3600); // 1 hora
    
    return $permissions;
}
```

---

## 10. PADRÃO DE ERROS

### 10.1 Tratamento de Exceções
```php
try {
    $result = $this->model->create($data);
    AuditLogger::log('create_client', ['id' => $result]);
    return $this->redirect('/clientes?success=created');
} catch (PDOException $e) {
    $this->logger->error("Database error: " . $e->getMessage());
    return $this->redirect('/clientes/create?error=db_failure');
} catch (Exception $e) {
    $this->logger->error("General error: " . $e->getMessage());
    return $this->redirect('/clientes/create?error=general');
}
```

### 10.2 Mensagens de Erro
```php
// Padrão de mensagens
$errorMessages = [
    'db_failure' => 'Erro ao salvar no banco de dados',
    'validation' => 'Dados inválidos',
    'unauthorized' => 'Você não tem permissão para esta ação',
    'not_found' => 'Registro não encontrado',
    'csrf' => 'Sessão expirada, tente novamente'
];
```

---

## 11. PADRÃO DE TESTES

### 11.1 Estrutura de Testes
```php
// tests/Unit/ClienteTest.php
class ClienteTest extends TestCase {
    public function testCreateCliente() {
        $cliente = new Cliente();
        $result = $cliente->create([
            'razao_social' => 'Test Company',
            'email' => 'test@example.com'
        ]);
        
        $this->assertIsString($result);
        $this->assertGreaterThan(0, $result);
    }
}
```

### 11.2 Testes de Integração
```php
// tests/Integration/ClientesControllerTest.php
class ClientesControllerTest extends IntegrationTest {
    public function testCreateClienteFlow() {
        $this->loginAsUser();
        
        $response = $this->post('/clientes', [
            'razao_social' => 'Test Company',
            'email' => 'test@example.com'
        ]);
        
        $this->assertRedirect('/clientes');
        $this->assertDatabaseHas('clientes', [
            'razao_social' => 'Test Company'
        ]);
    }
}
```

---

## 12. CONCLUSÃO

Estes padrões técnicos garantem:

✅ **Consistência** em todo o código  
✅ **Manutenibilidade** a longo prazo  
✅ **Segurança** das aplicações  
✅ **Performance** otimizada  
✅ **Escalabilidade** do sistema  

**Todo novo código deve seguir estes padrões sem exceção.**

---

**Próxima Revisão:** 2026-03-03  
**Responsável:** Arquitetura do Sistema
