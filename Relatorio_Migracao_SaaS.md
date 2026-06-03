# Relatório Técnico: Transformação do ERP InLaudo em SaaS Multi-Tenant com DNS Personalizado

**Data:** 26 de Março de 2026  
**Autor:** Manus AI  
**Projeto:** ERP InLaudo  

---

## 1. Resumo Executivo

O ERP InLaudo já possui uma base sólida para operação multi-tenant (múltiplos inquilinos/clientes), utilizando a coluna `usuario_id` para isolar dados em 21 tabelas principais (Clientes, Financeiro, Notas Fiscais, Integrações, etc.). No entanto, a arquitetura atual acopla a entidade "Inquilino" (a clínica/empresa contratante) à entidade "Usuário" (a pessoa que faz login). 

Para transformar o sistema em um verdadeiro **SaaS Multi-Tenant White-Label** (com suporte a domínios/DNS personalizados e múltiplos usuários por clínica), é necessária uma reestruturação na camada de banco de dados, no roteamento (identificação por domínio) e na infraestrutura do servidor web para emissão dinâmica de certificados SSL.

---

## 2. Diagnóstico da Arquitetura Atual

Após análise profunda do código-fonte, identificamos o seguinte cenário:

*   **Isolamento de Dados:** O sistema já utiliza a abordagem de *Single Database, Shared Schema* (Banco único, esquema compartilhado). As queries filtram os dados usando `WHERE usuario_id = ?`.
*   **Modelo de Usuários:** A tabela `users` atua simultaneamente como "Conta da Empresa" e "Credencial de Acesso". Não há suporte nativo para que uma mesma clínica tenha múltiplos usuários (ex: um perfil "Médico", um "Recepcionista" e um "Administrador") compartilhando os mesmos dados.
*   **Armazenamento (Storage):** Os uploads já estão inteligentemente separados por pastas baseadas no ID do usuário (ex: `storage/uploads/contas_pagar/{usuario_id}/`).
*   **Integrações:** Asaas, NFS-e e WhatsApp já suportam múltiplas credenciais, vinculadas ao `usuario_id`.
*   **Aparência (UI):** O logotipo e as cores estão fixos no código (`/assets/logo-inlaudo.png` e variáveis CSS no `erp_header.php`), com uma tentativa inicial de ler um logo global em `/public/uploads/logo`.

---

## 3. Plano de Migração Passo a Passo

Para atingir o objetivo de um SaaS com DNS personalizado por cliente, as seguintes alterações devem ser implementadas:

### Passo 1: Reestruturação do Banco de Dados (Separação Tenant vs User)

É fundamental separar a "Empresa" da "Pessoa que acessa".

1.  **Criar a tabela `tenants` (ou `empresas`):**
    *   `id` (INT PK)
    *   `nome_fantasia`, `razao_social`, `cnpj`
    *   `dominio_personalizado` (ex: `sistema.clinicax.com.br`)
    *   `subdominio` (ex: `clinicax.inlaudo.com.br`)
    *   `logo_path`, `cor_primaria`
    *   `status` (ativo, inadimplente, cancelado)
2.  **Modificar a tabela `users`:**
    *   Adicionar a coluna `tenant_id` (FK apontando para `tenants.id`).
    *   Isso permitirá que a Clínica X (`tenant_id = 1`) tenha 5 usuários diferentes na tabela `users`.
3.  **Migração de Dados Existentes:**
    *   Criar um script que lê cada registro atual da tabela `users`, cria um registro correspondente na tabela `tenants`, e atualiza o `tenant_id` nas 21 tabelas do sistema (substituindo a lógica do `usuario_id` pelo `tenant_id`).

### Passo 2: Identificação de Tenant por DNS (Roteamento Dinâmico)

Para que cada cliente tenha seu próprio link de login personalizado, o sistema precisa saber "quem" está sendo acessado antes mesmo do usuário digitar e-mail e senha.

1.  **Middleware de Identificação (TenantMiddleware):**
    *   Criar um middleware global que roda em todas as requisições.
    *   Ele deve ler a variável `$_SERVER['HTTP_HOST']` (ex: `laudos.hospitaly.com.br`).
    *   Fazer uma busca no banco: `SELECT * FROM tenants WHERE dominio_personalizado = ? OR subdominio = ? LIMIT 1`.
    *   Se encontrar, injeta os dados do Tenant em uma constante global ou na sessão (ex: `App\Core\TenantContext::set($tenant)`).
    *   Se não encontrar e for o domínio principal (`erp.inlaudo.com.br`), exibe a tela de login padrão ou uma landing page.
2.  **Personalização da Tela de Login (`app/Views/auth/login.php`):**
    *   A view de login deve ler o contexto do Tenant.
    *   Se acessado via `laudos.hospitaly.com.br`, a view troca dinamicamente a tag `<img>` para o logo do Hospital Y e altera as variáveis CSS (`--primary`) para as cores da marca do cliente.

### Passo 3: Refatoração da Autenticação e Queries

1.  **Login Scoped:**
    *   No `AuthController::login`, a validação deve ser: "Encontre o usuário com este e-mail **QUE PERTENÇA** ao Tenant identificado pela URL atual".
    *   Isso impede que um usuário da Clínica A faça login pelo link da Clínica B.
2.  **Atualização do Core Model:**
    *   Para evitar falhas de segurança (vazamento de dados entre clientes), o ideal é implementar um *Global Scope* no `App\Core\Model`.
    *   Toda query `SELECT`, `UPDATE` ou `DELETE` deve automaticamente concatenar `AND tenant_id = ?` usando o ID do Tenant logado na sessão. Atualmente, isso é feito manualmente em cada controller/model, o que é propenso a erros humanos.

### Passo 4: Infraestrutura de Servidor Web e SSL (O Desafio do DNS)

Esta é a parte mais complexa de um SaaS com domínio personalizado. Quando o cliente aponta o DNS dele (CNAME) para o seu servidor, o seu servidor precisa aceitar a requisição e gerar um certificado SSL (HTTPS) válido para o domínio dele automaticamente.

**Opções de Arquitetura:**

*   **Opção A: Caddy Web Server (Recomendado)**
    *   Substituir o Nginx/Apache pelo Caddy. O Caddy possui recurso nativo de *On-Demand TLS*. Quando uma requisição chega de um domínio desconhecido, ele consulta uma API interna do seu ERP (ex: `/api/check-domain?domain=sistema.cliente.com`). Se o ERP retornar HTTP 200 (confirmando que o domínio existe na tabela `tenants`), o Caddy gera o SSL via Let's Encrypt na hora, de forma 100% transparente.
*   **Opção B: Nginx + Lua / OpenResty**
    *   Usar Nginx com módulos dinâmicos para ler certificados de um banco de dados Redis ou gerar via script. É mais complexo de manter.
*   **Opção C: Serviços de Terceiros (Cloudflare for SaaS / AWS SSL)**
    *   Utilizar o "Cloudflare for SaaS" (Custom Hostnames). O cliente aponta o CNAME para a Cloudflare, a Cloudflare gera o SSL automaticamente e repassa a requisição para o seu servidor principal. É a solução mais robusta e escalável, delegando a complexidade do SSL para a Cloudflare.

---

## 4. Resumo das Tarefas de Desenvolvimento

| Componente | Tarefa | Complexidade |
| :--- | :--- | :--- |
| **Banco de Dados** | Criar tabela `tenants`, adicionar `tenant_id` em `users`, renomear `usuario_id` nas 21 tabelas de negócio. | Média |
| **Core / Router** | Criar `TenantMiddleware` para identificar o cliente via `$_SERVER['HTTP_HOST']`. | Baixa |
| **Views** | Refatorar `login.php` e `erp_header.php` para carregar logo e cores dinamicamente do banco. | Baixa |
| **Controllers** | Atualizar CRUDs de configurações (Asaas, NFS-e) para salvar no nível do Tenant, não do Usuário. | Média |
| **Infraestrutura** | Configurar Cloudflare for SaaS ou Caddy Server para emissão automática de SSL para domínios de clientes. | Alta |
| **Painel Admin** | Criar um painel "Super Admin" (Master) para a InLaudo gerenciar as assinaturas, domínios e status dos Tenants. | Média |

## 5. Conclusão

O sistema ERP InLaudo foi muito bem construído e já possui a lógica de isolamento de dados (através do `usuario_id`). A transição para um SaaS Multi-Tenant real é um passo natural e totalmente viável. 

O foco principal do esforço de desenvolvimento será a **separação do conceito de Empresa vs Usuário** e a **configuração da infraestrutura de rede (Web Server/Cloudflare)** para suportar a resolução dinâmica de DNS e emissão de certificados SSL em massa.
