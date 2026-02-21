# Análise e Contexto Abrangente do Sistema ERP InLaudo

**Autor:** Manus AI
**Data:** 2026-02-04
**Versão:** 1.0

## 1. Visão Geral do Projeto

O projeto ERP InLaudo é um sistema de gestão empresarial (ERP) desenvolvido como uma plataforma **SaaS (Software as a Service) multi-tenant**. Ele foi construído utilizando um **framework MVC (Model-View-Controller) customizado em PHP 8.0+**, com uma filosofia de mínimas dependências externas, focando em um núcleo de código coeso e controlado.

| Atributo | Descrição |
| :--- | :--- |
| **Tecnologia Principal** | PHP 8.0+ (Vanilla) |
| **Arquitetura** | MVC (Model-View-Controller) customizado |
| **Banco de Dados** | MySQL (compatível) com acesso via PDO |
| **Modelo de Negócio** | SaaS Multi-tenant (isolamento de dados por `usuario_id`) |
| **Princípio Central** | O Banco de Dados é a fonte da verdade e sua estrutura é imutável. |

## 2. Regras de Ouro (Restrições Invioláveis)

Estas são as diretrizes fundamentais que governam todo o desenvolvimento e manutenção do sistema. A violação destas regras pode comprometer a integridade, segurança e estabilidade da aplicação.

- **Imutabilidade do Banco de Dados**: **NUNCA** remover ou renomear colunas/tabelas existentes. Apenas adicionar novas colunas ou tabelas é permitido. A remoção de dados deve ser feita via *soft delete* (ex: `status = 'inativo'`).
- **Auditoria Não-Bloqueante**: Todas as chamadas ao `AuditLogger` **DEVEM** estar dentro de um bloco `try/catch` para garantir que falhas no log não interrompam o fluxo principal da aplicação.
- **Purismo MVC**: A separação de responsabilidades é estrita:
    - **Controllers**: Orquestram o fluxo, validam entradas e chamam Models e Views. **NÃO** devem conter SQL ou HTML.
    - **Models**: Contêm toda a lógica de negócio, acesso e manipulação de dados. **NÃO** devem gerar HTML ou qualquer tipo de apresentação.
    - **Views**: Responsáveis exclusivamente pela apresentação dos dados. **NÃO** devem conter lógica de negócio ou acesso direto ao banco de dados.
- **RBAC Obrigatório**: Nenhuma ação sensível (criar, editar, deletar) pode ser executada sem uma verificação de permissão explícita via `Auth::can('permissao')`. A validação deve ocorrer tanto no backend (Controller) quanto na UI (ocultando botões/ações).

## 3. Arquitetura e Fluxo de Requisição

O sistema segue um fluxo de requisição linear e bem definido, orquestrado pelo framework customizado.

1.  **Ponto de Entrada**: Toda requisição HTTP é direcionada para `public/index.php`.
2.  **Bootstrap**: O arquivo `app/bootstrap.php` é carregado. Ele inicializa o `Dotenv` para carregar as variáveis de ambiente do arquivo `.env`, configura o tratamento de erros (diferenciando `dev` e `prod`) e valida a existência de variáveis críticas (como credenciais do banco).
3.  **Roteamento**: O `App\Core\Router` é invocado. Ele analisa a URI e o método HTTP da requisição e busca por uma rota correspondente definida em `routes/web.php`.
4.  **Middleware**: Antes de executar a ação do Controller, o Roteador processa os `middlewares` associados à rota ou grupo de rotas. Os principais middlewares são:
    - `AuthMiddleware`: Garante que o usuário está autenticado.
    - `PermissionMiddleware`: Verifica se o usuário possui a permissão necessária para acessar o recurso (ex: `Permission:view_clients`).
5.  **Controller**: Uma vez que os middlewares são satisfeitos, o Roteador instancia o Controller especificado e chama o método correspondente (ex: `ClientesController@index`).
6.  **Model**: O Controller interage com as classes de Model (`app/Models/*`) para buscar ou manipular dados. Os Models contêm a lógica de negócio e são os únicos que devem interagir com o banco de dados através da classe `App\Core\Database` (Singleton PDO).
7.  **View**: Após obter os dados do Model, o Controller utiliza a classe `App\Core\View` para renderizar a camada de apresentação, passando os dados necessários. A View constrói o HTML final, geralmente combinando um layout principal (`header` e `footer`) com o conteúdo específico da página.

## 4. Estrutura do Banco de Dados e Migrações

A estrutura do banco de dados é definida principalmente pelos arquivos de migração em `database/migrations/`. As tabelas seguem um padrão de nomenclatura `snake_case` e plural (ex: `clientes`, `contas_pagar`).

### Tabelas Centrais:

| Tabela | Propósito | Chave Estrangeira Principal |
| :--- | :--- | :--- |
| `users` | Armazena todos os usuários do sistema. | N/A |
| `clientes` | Gerencia os clientes de cada usuário. | `usuario_id` |
| `fornecedores` | Gerencia os fornecedores de cada usuário. | `usuario_id` |
| `plano_contas` | Estrutura o plano de contas financeiro. | `usuario_id` |
| `contas_pagar` | Registra as contas a pagar. | `usuario_id`, `fornecedor_id`, `plano_conta_id` |
| `contas_receber` | Registra as contas a receber. | `usuario_id`, `cliente_id`, `plano_conta_id` |
| `notas_fiscais` | Gerencia as notas fiscais emitidas ou importadas. | `usuario_id`, `cliente_id` |
| `audit_logs` | Grava um registro de todas as ações importantes. | `user_id` |

O isolamento de dados é garantido pela onipresença da coluna `usuario_id` em quase todas as tabelas. **Toda consulta que lida com dados de um tenant DEVE, obrigatoriamente, filtrar por `usuario_id`**. 

## 5. Padrões de Código e Lógica de Negócio

O código segue padrões estritos para garantir consistência e manutenibilidade.

### Padrões de Nomenclatura:

- **PHP**: Classes em `PascalCase`, métodos e variáveis em `camelCase`.
- **Banco de Dados**: Tabelas e colunas em `snake_case`.
- **CSS**: Classes em `kebab-case`.

### Lógica de Negócio nos Models:

Os Models são o coração da aplicação. Por exemplo, o `ContaPagar.php` model não apenas faz o CRUD básico, mas também lida com:

- **Filtros complexos**: Permite buscar contas por status, descrição, fornecedor, etc.
- **Relacionamentos**: Realiza `JOIN`s para buscar nomes de fornecedores e planos de contas em uma única consulta.
- **Tratamento de Nulos**: Lida com campos opcionais como `fornecedor_id` e `data_pagamento` de forma segura.
- **Recorrência**: Possui campos para gerenciar contas recorrentes (`recorrente`, `recorrencia_tipo`, `recorrencia_intervalo`).

### Autenticação e Permissões (RBAC):

- **Autenticação**: A classe `App\Core\Auth` gerencia o ciclo de vida da sessão. O login é feito com email e senha, e a senha é verificada usando `PASSWORD_ARGON2ID`.
- **Autorização (RBAC)**: A classe `App\Core\Permission` define um mapa de papéis (`roles`) e suas permissões associadas (ex: `superadmin`, `admin`, `financeiro`). O método `Auth::can('permissao')` consulta este mapa para determinar se o usuário logado pode executar uma ação, tornando o controle de acesso centralizado e explícito.

## 6. Documentação e Padrões Adicionais

O projeto contém uma rica documentação interna que formaliza os padrões a serem seguidos:

- **`docs/PADROES_TECNICOS.md`**: Define padrões para logs, layouts, formulários, JavaScript, validação, APIs, nomenclatura, segurança e performance.
- **`docs/REGRAS_DE_OURO.md`**: Documento de governança com as regras invioláveis do projeto.
- **`.github/copilot-instructions.md`**: Um guia detalhado para a IA, explicando o fluxo da aplicação, padrões críticos e tarefas comuns, servindo como uma base para este documento.

Este documento consolidado serve como o contexto definitivo para qualquer análise ou desenvolvimento futuro no sistema ERP InLaudo, garantindo que as decisões sejam tomadas com pleno conhecimento da arquitetura, regras e padrões estabelecidos.
