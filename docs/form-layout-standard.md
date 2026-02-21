# ERP InLaudo - Padrão de Layout de Formulários Enterprise

## Visão Geral

Este documento descreve o padrão único de layout de formulários implementado no ERP InLaudo, criado para proporcionar uma experiência consistente, moderna e reutilizável em todos os módulos do sistema.

## Estrutura de Arquivos

```
public/assets/css/form-layout.css          # CSS principal do padrão
public/assets/js/form-tabs.js              # JavaScript de controle de abas
public/assets/js/clientes-form.js          # JavaScript específico do módulo
app/Views/components/form/enterprise-form.php # Componente reutilizável
app/Views/clientes/form-enterprise.php     # Implementação clientes
app/Views/clientes/tabs/geral-enterprise.php
app/Views/clientes/tabs/contatos-enterprise.php
```

## Arquitetura e Principípios

### 1. MVC Purista Respeitado

- **Controller**: Orquestra fluxo e permissões (sem lógica de view)
- **Model**: Regras de dados e persistência (sem lógica de apresentação)
- **View**: Apenas HTML + classes CSS + data attributes
- **JavaScript**: Modular, sem regras de negócio do backend

### 2. Componentes Reutilizáveis

#### Componente Principal: `enterprise-form.php`

```php
$formConfig = [
    'title' => 'Título do Formulário',
    'subtitle' => 'Descrição auxiliar',
    'is_edit' => false,
    'record_id' => null,
    'active_tab' => 0,
    'tabs' => [
        [
            'id' => 'tab-id',
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

### 3. Sistema de Abas Inteligente

#### Características:

- **Bloqueio por Estado**: Abas podem ser bloqueadas até condições serem satisfeitas
- **Navegação por Teclado**: Suporte a setas direcionais
- **Persistência de Estado**: Salva aba ativa em localStorage
- **Animações Suaves**: Transições CSS3 otimizadas
- **Feedback Visual**: Indicadores claros de estado

#### Estados de Aba:

```javascript
// Aba ativa
<button class="form-tab-button active">

// Aba bloqueada
<button class="form-tab-button locked" disabled data-locked="true">

// Aba normal
<button class="form-tab-button">
```

### 4. CSS Design System

#### Variáveis CSS Principais:

```css
:root {
    --form-primary: #00529B;
    --form-success: #10b981;
    --form-danger: #ef4444;
    --form-warning: #f59e0b;
    --form-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    --form-radius: 0.5rem;
    --form-transition: all 0.2s ease;
}
```

#### Classes Utilitárias:

- `.form-container` - Container principal
- `.form-header` - Cabeçalho do formulário
- `.form-tabs` - Navegação por abas
- `.form-content` - Área de conteúdo
- `.form-section` - Seção de campos
- `.form-group` - Grupo de campo + label
- `.form-actions` - Rodapé com botões

### 5. JavaScript Modular

#### FormTabs Class

```javascript
const tabs = new FormTabs(container, {
    activeTab: 0,
    saveState: true,
    onTabChange: (oldIndex, newIndex) => { /* callback */ },
    onTabLocked: (tab) => { /* callback */ }
});
```

#### Métodos Principais:

- `switchToTab(index)` - Muda para aba específica
- `lockTab(index, message)` - Bloqueia aba
- `unlockTab(index)` - Desbloqueia aba
- `getActiveTab()` - Retorna aba ativa
- `saveState()` - Salva estado atual

## Implementação - Módulo Clientes

### 1. Controller Updates

```php
// ClientesController.php
public function create()
{
    View::render('clientes/form-enterprise', [
        'title' => 'Novo Cliente',
        'cliente' => null,
        'tab' => 'geral'
    ]);
}

public function edit($id)
{
    View::render('clientes/form-enterprise', [
        'title' => 'Editar Cliente',
        'cliente' => $cliente,
        'contatos' => $contatos,
        'tab' => $_GET['tab'] ?? 'geral'
    ]);
}
```

### 2. Abas Implementadas

#### Aba 1: Dados Gerais & Endereço
- **ID**: `geral`
- **Ícone**: `fas fa-info-circle`
- **Conteúdo**: Formulário principal de cadastro
- **Validação**: Obrigatória para prosseguir

#### Aba 2: Gestão de Contatos
- **ID**: `contatos`
- **Ícone**: `fas fa-address-book`
- **Bloqueio**: Até cliente ser salvo
- **Conteúdo**: CRUD de contatos via AJAX

### 3. Funcionalidades Específicas

#### Consulta CNPJ (BrasilAPI)

```javascript
// Integrado automaticamente ao preencher CNPJ
searchCnpj() {
    fetch(`/clientes/buscar-cnpj?cnpj=${cnpj}`)
        .then(response => response.json())
        .then(data => this.fillCnpjData(data));
}
```

#### Máscaras Dinâmicas

- **CPF/CNPJ**: Detecção automática
- **CEP**: 00000-000
- **Telefones**: (00) 0000-0000 / (00) 00000-0000
- **Contatos**: Máscara baseada no tipo

#### Validação em Tempo Real

- **CPF/CNPJ**: Validação de formato
- **Email**: Validação de formato
- **Campos Obrigatórios**: Verificação antes de mudar aba

## Checklist de Conformidade

### ✅ MVC

- [ ] Controller sem lógica de view
- [ ] Model sem apresentação
- [ ] View apenas HTML + CSS
- [ ] JavaScript sem regras de negócio backend

### ✅ RBAC

- [ ] Permissões verificadas no controller
- [ ] Abas bloqueadas por estado do registro
- [ ] Botões condicionais por permissão
- [ ] Ações auditadas corretamente

### ✅ UX

- [ ] Layout responsivo (mobile-first)
- [ ] Feedback visual claro
- [ ] Animações suaves
- [ ] Navegação por teclado
- [ ] Estados de loading
- [ ] Mensagens de erro/sucesso

### ✅ Performance

- [ ] CSS otimizado (sem redundância)
- [ ] JavaScript modular e lazy-load
- [ ] Mínimas requisições AJAX
- [ ] Cache de estado no localStorage
- [ ] Sem inline styles/scripts

### ✅ Manutenibilidade

- [ ] Componentes reutilizáveis
- [ ] Nomenclatura consistente
- [ ] Documentação completa
- [ ] Código comentado
- [ ] Separação clara de responsabilidades

## Guia de Reaproveitamento

### 1. Para Novos Módulos

#### Passo 1: Criar View Principal

```php
// module/form-enterprise.php
$formConfig = [
    'title' => 'Título do Módulo',
    'tabs' => [
        [
            'id' => 'dados-gerais',
            'title' => 'Dados Gerais',
            'view' => 'module.tabs.dados-gerais'
        ]
    ]
];
include_once __DIR__ . '/../components/form/enterprise-form.php';
```

#### Passo 2: Criar Abas

```php
// module/tabs/dados-gerais.php
<section class="form-section">
    <h2 class="form-section-title">Dados Principais</h2>
    <div class="form-grid form-grid-2">
        <div class="form-group">
            <label class="form-label">Campo</label>
            <input type="text" class="form-control">
        </div>
    </div>
</section>
```

#### Passo 3: Criar JavaScript Específico

```javascript
// module-form.js
class ModuleForm {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.init();
    }
    
    init() {
        this.setupFormTabs();
        this.setupValidation();
        // Lógica específica do módulo
    }
}
```

#### Passo 4: Atualizar Controller

```php
public function create()
{
    View::render('module/form-enterprise', $data);
}
```

### 2. Boas Práticas

#### Nomenclatura

- **Views**: `module/form-enterprise.php`
- **Tabs**: `module/tabs/tab-name-enterprise.php`
- **JS**: `module-form.js`
- **CSS**: Usar classes do `form-layout.css`

#### Estrutura de Dados

```php
$formConfig = [
    'title' => '',           // Obrigatório
    'subtitle' => '',       // Opcional
    'is_edit' => false,      // Boolean
    'record_id' => null,     // ID do registro
    'active_tab' => 0,       // Índice ou ID
    'tabs' => [],           // Array de abas (obrigatório)
    'actions' => [],        // Ações do header
    'footer_actions' => []  // Botões do rodapé
];
```

#### Configuração de Abas

```php
'tabs' => [
    [
        'id' => 'unique-id',           // Obrigatório
        'title' => 'Título da Aba',    // Obrigatório
        'icon' => 'fas fa-icon',       // Opcional
        'locked' => false,             // Opcional
        'locked_message' => '',       // Opcional
        'view' => 'path.to.view',     // Opcional
        'content' => function() {},   // Opcional
        'html' => '<html></html>'     // Opcional
    ]
]
```

## Testes e Validação

### 1. Testes Automatizados (Futuro)

```javascript
// Testes do sistema de abas
describe('FormTabs', () => {
    it('deve inicializar corretamente', () => {
        const tabs = new FormTabs(container);
        expect(tabs.getTotalTabs()).toBe(2);
    });
    
    it('deve bloquear aba quando solicitado', () => {
        tabs.lockTab(1, 'Test message');
        expect(tabs.getTab(1).locked).toBe(true);
    });
});
```

### 2. Validação Manual

#### Fluxo Básico

1. **Acesso**: `/clientes/create`
2. **Preenchimento**: Formulário completo
3. **Validação**: Campos obrigatórios
4. **Salvamento**: Redirecionamento automático
5. **Desbloqueio**: Aba de contatos liberada

#### Casos de Teste

- [ ] Novo cliente fluxo completo
- [ ] Edição de cliente existente
- [ ] Tentativa acessar aba bloqueada
- [ ] Validação de campos
- [ ] Consulta CNPJ
- [ ] CRUD de contatos
- [ ] Responsividade mobile
- [ ] Navegação por teclado

## Manutenção e Evolução

### 1. Versionamento

- **CSS**: `form-layout.css` v1.0.0
- **JS**: `form-tabs.js` v1.0.0
- **Componente**: `enterprise-form.php` v1.0.0

### 2. Roadmap Futuro

#### v1.1 (Planejado)
- [ ] Tema escuro automático
- [ ] Mais animações e micro-interações
- [ ] Validação avançada de formulários
- [ ] Upload de arquivos integrado

#### v1.2 (Futuro)
- [ ] Formulários multi-step
- [ ] Auto-save automático
- [ ] Campo de busca em abas
- [ ] Exportação de dados

### 3. Suporte e Debug

#### Logs de Erro

```javascript
// Em produção
window.formTabsErrors = [];

// Em desenvolvimento
console.log('FormTabs initialized:', tabs);
```

#### Debug Mode

```javascript
// Ativar debug
new FormTabs(container, {
    debug: true,
    onTabChange: (old, new_) => {
        console.log(`Tab changed: ${old} -> ${new_}`);
    }
});
```

## Conclusão

O padrão de layout de formulários Enterprise do ERP InLaudo atende a todos os requisitos:

✅ **MVC Purista**: Separação clara de responsabilidades  
✅ **RBAC Integrado**: Controle de acesso por permissões  
✅ **UX Moderna**: Interface intuitiva e responsiva  
✅ **Reutilizável**: Fácil aplicação em outros módulos  
✅ **Performance**: Otimizado e sem redundâncias  
✅ **Auditável**: Logs completos de ações  

A implementação no módulo Clientes serve como referência para futuras expansões, garantindo consistência em toda a aplicação.
