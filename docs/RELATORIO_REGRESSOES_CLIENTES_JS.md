# 📋 Relatório de Estabilidade (Clientes /create) - Regressões JS

## 1) Sintomas reportados

- `SyntaxError: Identifier 'sidebar' has already been declared`
- `SyntaxError: Identifier 'FormTabs' has already been declared`
- `SyntaxError: Identifier 'ClientesForm' has already been declared`
- `TypeError: this.activateTab is not a function`

## 2) Causas raiz encontradas

### 2.1) Injeção inline no `erp_footer.php`

- Existia um bloco `<script>` no footer declarando:
  - `const sidebar = document.getElementById('mainSidebar')`
  - `const layoutWrapper = document.querySelector('.layout-wrapper')`
  - funções e handlers relacionados

Quando o layout/HTML era reusado (ou re-renderizado), esse bloco era avaliado novamente, causando **redeclaração**.

### 2.2) Duplicação de header/footer via componente Enterprise

- O componente `app/Views/components/form/enterprise-form.php` incluía `erp_header.php` e `erp_footer.php` por conta própria.
- Ao mesmo tempo, `App\Core\View::render()` também inclui header/footer ao usar `_layout => 'erp'`.

Resultado: scripts e inicializações duplicadas, gerando **redeclarações** e comportamento inconsistente.

### 2.3) Protótipo incompleto do `FormTabs`

- No `public/assets/js/form-tabs.js` havia chamada `this.activateTab(...)` dentro de `init()`, porém o método `activateTab` não existia no arquivo.
- Isso explica diretamente o erro:
  - `TypeError: this.activateTab is not a function`

## 3) Correções aplicadas (sem quebrar Layout Enterprise)

### 3.1) Footer: remover injeção inline e impor ordem de dependências

Arquivo: `app/Views/layout/erp_footer.php`

- Removido todo JS inline do footer.
- Adicionada ordem de carregamento:
  1. `/assets/js/sidebar.js`
  2. `/assets/js/form-tabs.js`
  3. scripts específicos por página (ex: `/assets/js/clientes-form.js`)

### 3.2) Sidebar: extração para arquivo dedicado e idempotente

Arquivo criado: `public/assets/js/sidebar.js`

- Implementada inicialização idempotente via flag `window.__erpSidebarInitialized`.
- Funções globais expostas apenas se ainda não existirem:
  - `window.toggleSidebar`
  - `window.confirmLogout`

### 3.3) View rendering: sinalizar renderização com layout

Arquivo: `app/Core/View.php`

- `ERP_VIEW_RENDERING` é definido antes de carregar a view.
- Isso permite que componentes detectem o contexto e **não incluam header/footer** indevidamente.

### 3.4) Enterprise component: não incluir header/footer quando `_layout = erp`

Arquivo: `app/Views/components/form/enterprise-form.php`

- Header/Footer só são incluídos quando **não** estiver em renderização via `View::render()`.
- Inicialização das abas ajustada para usar `new window.FormTabs(...)`.

### 3.5) FormTabs: blindagem total e correção do método ausente

Arquivo: `public/assets/js/form-tabs.js`

- Definição idempotente:
  - `if (!window.FormTabs) { window.FormTabs = class FormTabs { ... } }`
- Implementado `activateTab()`.
- Auto-init registrado uma única vez (`window.__formTabsAutoInit`).
- Helpers globais só são definidos se não existirem.

### 3.6) ClientesForm: blindagem e dependência correta

Arquivo: `public/assets/js/clientes-form.js`

- Definição idempotente:
  - `if (!window.ClientesForm) { window.ClientesForm = class ClientesForm { ... } }`
- Instanciação corrigida:
  - `new window.FormTabs(...)`
  - `new window.ClientesForm(...)`

## 4) Arquivos envolvidos e duplicidades eliminadas

- **Duplicidade eliminada (causa):** JS inline do footer (`erp_footer.php`).
- **Duplicidade eliminada (causa):** include indevido de header/footer pelo componente enterprise (`enterprise-form.php`) durante `_layout='erp'`.

## 5) Caminho padrão de assets

- Padrão aplicado: `"/assets/js/..."` (sem prefixo `/public`).

## 6) Como validar

- Acesse `/clientes/create`.
- Abra o console (F12).
- Verifique:
  - **Sem** `Identifier already declared`
  - **Sem** `this.activateTab is not a function`
- Teste abas (troca sem reload) e máscaras (CPF/CNPJ).

## Status

- Objetivo de estabilização: **implementado**.
