# Especificação Funcional e Técnica — Módulo CRM

**Autor**: Manus AI
**Data**: 27 de Fevereiro de 2026
**Status**: Para Validação

## 1. Visão Geral e Objetivos

Este documento detalha a proposta de implementação de um novo módulo de **CRM (Customer Relationship Management)** dentro do sistema **ERP InLaudo**. O objetivo é fornecer uma ferramenta robusta e integrada para gerenciar todo o ciclo de vida do cliente, desde a prospecção (Lead) até a conversão em uma venda efetiva (Oportunidade), culminando no cadastro final como Cliente.

O módulo será projetado para ser intuitivo, visual e totalmente alinhado com a identidade e arquitetura existentes do sistema, aproveitando componentes de UI, padrões de código e a estrutura de banco de dados já estabelecida. O foco principal é a área da **saúde, com especialização em radiologia**, direcionando os campos e fluxos para esta realidade.

## 2. Estrutura do Módulo e Menus

O módulo CRM será acessado através de um novo item de menu principal na barra lateral esquerda do ERP, denominado **"CRM"**, com um ícone apropriado (ex: `fas fa-headset` ou `fas fa-users-cog`). Ao clicar, um submenu será expandido com as seguintes seções:

1.  **Funil de Vendas**: Uma visão geral e interativa do pipeline de vendas.
2.  **Leads**: O ponto de entrada para todos os contatos comerciais iniciais.
3.  **Oportunidades**: Leads qualificados que representam uma chance real de negócio.

## 3. Modelagem de Dados (Tabelas e Campos)

Para suportar o novo módulo, as seguintes tabelas serão criadas no banco de dados, seguindo o padrão de nomenclatura e estrutura existentes.

### 3.1. Tabela: `crm_leads`

Armazena todos os contatos brutos e não qualificados. A ideia é que um Lead possa ser convertido em uma Oportunidade e, posteriormente, seus dados sejam migrados para a tabela `clientes`.

| Coluna | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | Não | Chave primária. |
| `usuario_id` | `INT` | Não | Chave estrangeira para `users.id` (Tenant). |
| `nome_lead` | `VARCHAR(255)` | Não | Nome do contato ou da empresa. |
| `email` | `VARCHAR(255)` | Sim | E-mail principal do lead. |
| `telefone` | `VARCHAR(20)` | Sim | Telefone principal do lead. |
| `cnpj` | `VARCHAR(18)` | Sim | CNPJ do lead (se aplicável). |
| `origem` | `VARCHAR(100)` | Sim | Como o lead foi gerado (ex: Indicação, Site, Evento). |
| `status_lead` | `ENUM('novo', 'contatado', 'qualificado', 'descartado')` | Não | Status atual do lead no processo. |
| `segmento_principal` | `VARCHAR(100)` | Sim | Segmento de atuação (ex: Clínica de Imagem, Hospital). |
| `especialidades_interesse` | `TEXT` | Sim | JSON ou texto com especialidades (ex: `["Tomografia", "Ressonância"]`). |
| `volume_exames_mes` | `INT` | Sim | Estimativa do volume mensal de exames do lead. |
| `equipamentos_possui` | `TEXT` | Sim | Descrição dos equipamentos que o lead já possui. |
| `responsavel_nome` | `VARCHAR(255)` | Sim | Nome do decisor ou contato principal. |
| `responsavel_cargo` | `VARCHAR(100)` | Sim | Cargo do decisor (ex: Diretor Clínico, Gestor de TI). |
| `data_proximo_contato` | `DATE` | Sim | Data agendada para o próximo follow-up. |
| `observacoes` | `TEXT` | Sim | Campo aberto para anotações gerais. |
| `created_at` | `TIMESTAMP` | Não | Data de criação do registro. |
| `updated_at` | `TIMESTAMP` | Não | Data da última atualização. |

### 3.2. Tabela: `crm_oportunidades`

Representa um lead que foi qualificado e tem potencial real de se tornar um cliente. Esta tabela gerencia o progresso da negociação.

| Coluna | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | Não | Chave primária. |
| `lead_id` | `INT` | Sim | Chave estrangeira para `crm_leads.id` (se originado de um lead). |
| `cliente_id` | `INT` | Sim | Chave estrangeira para `clientes.id` (se for up-sell/cross-sell). |
| `usuario_id` | `INT` | Não | Chave estrangeira para `users.id` (Tenant). |
| `titulo_oportunidade` | `VARCHAR(255)` | Não | Nome descritivo da oportunidade (ex: "Contrato Laudos TC"). |
| `etapa_funil` | `ENUM('qualificacao', 'proposta', 'negociacao', 'fechamento')` | Não | Posição atual no funil de vendas. |
| `valor_estimado` | `DECIMAL(10, 2)` | Sim | Valor monetário estimado do contrato/venda. |
| `data_fechamento_prevista` | `DATE` | Sim | Data prevista para a conclusão do negócio. |
| `probabilidade_sucesso` | `INT` | Sim | Percentual (0-100) de chance de fechar o negócio. |
| `status_oportunidade` | `ENUM('aberta', 'ganha', 'perdida')` | Não | Resultado final da oportunidade. |
| `motivo_perda` | `VARCHAR(255)` | Sim | Justificativa caso a oportunidade seja perdida. |
| `created_at` | `TIMESTAMP` | Não | Data de criação do registro. |
| `updated_at` | `TIMESTAMP` | Não | Data da última atualização. |

### 3.3. Tabela: `crm_interacoes`

Uma tabela polimórfica para registrar todo e qualquer contato com Leads ou Oportunidades, criando um histórico completo.

| Coluna | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | Não | Chave primária. |
| `usuario_id` | `INT` | Não | Chave estrangeira para `users.id` (Quem realizou a interação). |
| `related_id` | `INT` | Não | ID do registro relacionado (`crm_leads.id` ou `crm_oportunidades.id`). |
| `related_type` | `ENUM('lead', 'oportunidade')` | Não | Define a qual tabela o `related_id` se refere. |
| `data_interacao` | `DATETIME` | Não | Data e hora exatas da interação. |
| `tipo_interacao` | `ENUM('email', 'telefone', 'reuniao_online', 'reuniao_presencial', 'whatsapp', 'outro')` | Não | Canal utilizado para o contato. |
| `resumo` | `TEXT` | Não | Descrição detalhada do que foi discutido na interação. |
| `created_at` | `TIMESTAMP` | Não | Data de criação do registro. |

## 4. Fluxo de Telas e Funcionalidades

### 4.1. Leads

-   **Tela de Listagem (`/crm/leads`)**: Uma grade (tabela) com todos os leads, com filtros por status, origem e data. Ações rápidas para editar, visualizar ou converter em oportunidade.
-   **Tela de Cadastro/Edição (`/crm/leads/create`, `/crm/leads/edit/{id}`)**: Formulário com abas, seguindo o padrão `form-enterprise.php`:
    -   **Aba 1: Dados do Lead**: Campos da tabela `crm_leads`.
        -   **Importação de CNPJ**: Assim como na tela de Clientes, haverá um campo CNPJ com um botão de busca que, ao ser acionado, utilizará o `CnpjService` para preencher automaticamente os campos de nome, e-mail, telefone e endereço, se disponíveis.
    -   **Aba 2: Interações**: Uma área para registrar e visualizar o histórico de interações (tabela `crm_interacoes`). Um formulário permitirá adicionar uma nova interação rapidamente (data/hora, tipo, resumo).
-   **Conversão de Lead**: Ao mudar o `status_lead` para "Qualificado", o sistema oferecerá a opção de "Converter em Oportunidade". Isso criará um novo registro em `crm_oportunidades`, vinculando-o ao lead original.

### 4.2. Oportunidades

-   **Tela de Listagem (`/crm/oportunidades`)**: Grade com todas as oportunidades, filtros por etapa do funil, status e data de fechamento prevista.
-   **Tela de Edição (`/crm/oportunidades/edit/{id}`)**: Formulário com abas:
    -   **Aba 1: Detalhes da Oportunidade**: Campos da tabela `crm_oportunidades`.
    -   **Aba 2: Interações**: Histórico de interações, herdado do lead (se aplicável) e com possibilidade de adicionar novas.
-   **Conversão de Oportunidade**: Ao mudar o `status_oportunidade` para "Ganha", o sistema irá:
    1.  Abrir um formulário de **pré-cadastro de Cliente**, já preenchido com os dados do Lead/Oportunidade.
    2.  Permitir que o usuário revise e complemente as informações.
    3.  Ao salvar, um novo registro será criado na tabela `clientes`.

### 4.3. Funil de Vendas (`/crm/funil`)

-   Esta será a tela principal do módulo, com uma interface visual no estilo Kanban.
-   Haverá colunas representando cada `etapa_funil` da tabela `crm_oportunidades`: **Qualificação**, **Proposta**, **Negociação**, **Fechamento**.
-   Cada oportunidade será um "card" dentro de uma coluna.
-   Os cards serão **arrastáveis (drag-and-drop)** entre as colunas, atualizando automaticamente a `etapa_funil` no banco de dados.
-   Cada card exibirá informações chave: título da oportunidade, nome do lead/cliente, valor estimado e data de fechamento prevista.

## 5. Permissões de Acesso

Novas permissões serão adicionadas ao `app/Core/Permission.php` para controlar o acesso ao módulo CRM. Propomos a seguinte estrutura:

-   `view_crm`: Permite visualizar o menu CRM e todas as suas seções.
-   `manage_leads`: Permite criar, editar e excluir leads.
-   `manage_oportunidades`: Permite criar, editar e excluir oportunidades.

Essas permissões serão atribuídas aos perfis (`superadmin`, `admin`, etc.) conforme a necessidade do negócio.

## 6. Próximos Passos e Validação

Solicito a sua análise e validação sobre este documento. Por favor, verifique se o fluxo proposto, os campos definidos e as funcionalidades atendem às suas expectativas. Estou à disposição para ajustar qualquer ponto antes de iniciar a implementação técnica.

**Pontos para validação:**

1.  Os campos sugeridos para Leads (focados em radiologia) são suficientes?
2.  Os status de Leads e as etapas do Funil de Oportunidades estão adequados?
3.  Os tipos de interação cobrem todas as formas de contato comuns?
4.  O fluxo de conversão (Lead -> Oportunidade -> Cliente) está claro e correto?

Após sua aprovação, o próximo passo será a criação das migrations do banco de dados e o desenvolvimento dos controllers, models e views. Aguardo seu feedback.
