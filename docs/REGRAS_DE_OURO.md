# REGRAS DE OURO - ERP InLaudo

**ESTE DOCUMENTO NUNCA PODE SER VIOLADO**

Estas regras são a base da governança do sistema e devem ser lidas, avaliadas e respeitadas antes de QUALQUER alteração futura.

---

## REGRA 1 — O BANCO DE DADOS É FONTE DE VERDADE

### **NUNCA REMOVER COLUNAS EXISTENTES**
- Colunas existentes contêm dados reais de produção
- Remoção = perda de dados = quebra de funcionalidade
- Exceção: Apenas se houver migração de dados documentada

### **NUNCA RENOMEAR COLUNAS EXISTENTES**
- Renomear quebra código existente
- Renomear quebra integrações
- Renomear quebra relatórios

### **ALTERAÇÕES PERMITIDAS APENAS VIA:**
```sql
-- ✅ PERMITIDO
ALTER TABLE tabela ADD COLUMN nova_coluna VARCHAR(255);

-- ✅ PERMITIDO  
CREATE TABLE nova_tabela (...);

-- ❌ PROIBIDO
ALTER TABLE tabela DROP COLUMN coluna_existente;
ALTER TABLE tabela CHANGE coluna_antiga colova_nova VARCHAR(255);
```

### **NUNCA APAGAR DADOS EXISTENTES**
- Use soft delete (status = 'inativo')
- Preserve histórico de auditoria
- Mantenha integridade referencial

---

## REGRA 2 — AUDITORIA É NÃO-BLOQUEANTE

### **TODA CHAMADA DE AUDITORIA DEVE ESTAR EM TRY/CATCH**
```php
// ✅ CORRETO
try {
    AuditLogger::log('action_name', $context);
} catch (Exception $e) {
    // Silently fail - nunca quebrar fluxo principal
    error_log("AuditLogger Failure: " . $e->getMessage());
}

// ❌ ERRADO
AuditLogger::log('action_name', $context); // Pode quebrar se DB falhar
```

### **LOGS ACEITAM CONTEXTOS JSON FLEXÍVEIS**
- Contexto é opcional
- Estrutura flexível para diferentes ações
- Nunca armazenar dados sensíveis (senhas, tokens)

### **PADRÃO DE NOMES DE AÇÃO**
- `verbo_recurso` (ex: `create_client`, `update_user`)
- Sempre em inglês e minúsculo
- Descritivo e consistente

---

## REGRA 3 — MVC PURISTA

### **CONTROLLER: COORDENA FLUXO**
```php
// ✅ CORRETO
public function store() {
    $data = $this->validateRequest();
    $result = $this->model->create($data);
    
    if ($result) {
        AuditLogger::log('create_client', ['id' => $result]);
        $this->redirect('/clientes');
    }
}

// ❌ ERRADO
public function store() {
    $sql = "INSERT INTO clientes (...) VALUES (...)"; // SQL no Controller
    $this->db->query($sql);
}
```

### **MODEL: REGRA DE NEGÓCIO E ACESSO A DADOS**
```php
// ✅ CORRETO
public function create(array $data): string|false {
    $sql = "INSERT INTO {$this->table} (...) VALUES (...)";
    // Validações, regras de negócio
    return $this->execute($sql, $data);
}

// ❌ ERRADO
public function create(array $data) {
    echo "<div>Cliente criado!</div>"; // HTML no Model
}
```

### **VIEW: APENAS EXIBIÇÃO**
```php
// ✅ CORRETO
<h1><?php echo htmlspecialchars($cliente->razao_social); ?></h1>

// ❌ ERRADO
<h1><?php 
    $cliente = $this->model->findById($id); // Lógica na View
    echo $cliente->razao_social; 
?></h1>
```

### **PROIBIDO**
- SQL em Views
- HTML em Controllers
- Lógica de negócio em Views
- Apresentação em Models

---

## REGRA 4 — RBAC É OBRIGATÓRIO

### **NENHUMA AÇÃO SENSÍVEL SEM Auth::can()**
```php
// ✅ CORRETO
public function delete($id) {
    if (!Auth::can('delete_clients')) {
        $this->redirect('/dashboard?error=unauthorized');
        return;
    }
    // ... lógica de delete
}

// ❌ ERRADO
public function delete($id) {
    // Delete sem verificação de permissão
}
```

### **BOTÕES DE UI RESPEITAM PERMISSÃO**
```php
// ✅ CORRETO
<?php if (Auth::can('delete_clients')): ?>
<button class="btn btn-danger">Excluir</button>
<?php endif; ?>

// ❌ ERRADO
<button class="btn btn-danger">Excluir</button> <!-- Sempre visível -->
```

### **BACKEND SEMPRE VALIDA PERMISSÃO**
- Nunca confiar apenas na UI
- Validar em TODOS os endpoints sensíveis
- Logar tentativas não autorizadas

---

## REGRA 5 — TELAS PÚBLICAS NÃO USAM LAYOUT PRIVADO

### **TELAS PÚBLICAS (SEM AUTENTICAÇÃO)**
- Login
- Forgot Password  
- Reset Password

### **LAYOUTS PERMITIDOS**
```php
// ✅ CORRETO - Tela de login
require_once 'public_header.php';
// ... conteúdo
require_once 'public_footer.php';

// ❌ ERRADO - Tela de login com layout autenticado
require_once 'erp_header.php'; // Contém sidebar, menu interno
```

### **LAYOUT AUTENTICADO (PROIBIDO EM TELAS PÚBLICAS)**
- Sidebar de navegação
- Header com menu interno
- Dashboard components

---

## REGRA 6 — NUNCA QUEBRAR FUNCIONALIDADE EXISTENTE

### **SE ALGO JÁ FUNCIONA, DEVE CONTINUAR FUNCIONANDO**
- Testar antes de alterar
- Preservar comportamento existente
- Manter compatibilidade backward

### **REFACTORING DEVE SER INCREMENTAL**
- Pequenas mudanças por vez
- Testar cada mudança
- Manter estabilidade do sistema

### **QUALQUER MUDANÇA PRECISA DE JUSTIFICATIVA TÉCNICA**
- Por que a mudança é necessária?
- Qual problema resolve?
- Qual o impacto esperado?

---

## VIOLAÇÕES E CONSEQUÊNCIAS

### **VIOLAÇÃO GRAVE**
- Remover coluna do banco: **ROLLBACK IMEDIATO**
- Quebrar funcionalidade existente: **ROLLBACK IMEDIATO**
- Bypass de RBAC: **ROLLBACK IMEDIATO + REVISÃO DE SEGURANÇA**

### **VIOLAÇÃO MODERADA**
- SQL em View: **Correção obrigatória antes de PR**
- HTML em Controller: **Correção obrigatória antes de PR**
- Layout incorreto: **Correção obrigatória antes de PR**

### **VIOLAÇÃO LEVE**
- Code style não padrão: **Correção sugerida**
- Comentários inadequados: **Correção sugerida**

---

## PROCESSO DE MUDANÇA DE REGRAS

### **SE ALGUMA REGRA PRECISAR SER ALTERADA:**

1. **JUSTIFICATIVA**
   - Por que a regra precisa mudar?
   - Qual benefício esperado?
   - Quais riscos mitigados?

2. **IMPACTO ANALISADO**
   - O que será afetado?
   - Como mitigar riscos?
   - Testes necessários?

3. **APROVAÇÃO EXPLÍCITA**
   - Team Lead approval
   - Architecture review
   - Security review (se aplicável)

4. **VERSIONAMENTO**
   - Atualizar este documento
   - Mudar número da versão
   - Documentar mudança

5. **COMUNICAÇÃO**
   - Anunciar mudança
   - Treinar equipe
   - Atualizar documentação

---

## CHECKLIST DE VALIDAÇÃO

Antes de qualquer PR, verificar:

- [ ] **BANCO**: Não removi/renomeei colunas existentes?
- [ ] **AUDITORIA**: Try/catch em toda chamada de AuditLogger?
- [ ] **MVC**: Controller coordena, Model regra, View exibe?
- [ ] **RBAC**: Auth::can() em ações sensíveis?
- [ ] **LAYOUT**: Tela pública usa layout público?
- [ ] **FUNCIONALIDADE**: Testei que nada quebrou?
- [ ] **JUSTIFICATIVA**: Sei por que estou fazendo esta mudança?

---

**ESTE DOCUMENTO É VIVO E OBRIGATÓRIO**

Versão: 1.0.0  
Última atualização: 2026-02-03  
Responsável: Arquitetura do Sistema
