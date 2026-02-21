-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 03/02/2026 às 01:21
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `inlaud99_saas_inlaudo`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL COMMENT 'ID único do cliente',
  `tipo` enum('PF','PJ') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PJ' COMMENT 'Tipo de cliente: Pessoa Física ou Jurídica',
  `cpf_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CPF (PF) ou CNPJ (PJ) - sem formatação',
  `razao_social` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Razão Social (PJ) ou Nome Completo (PF)',
  `nome_fantasia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome Fantasia (PJ) ou Apelido (PF)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'E-mail principal do cliente',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Website/URL do cliente',
  `cnae_principal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CNAE Principal (PJ)',
  `descricao_cnae` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição do CNAE',
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rua/Avenida',
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número',
  `complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Complemento (apto, sala, etc)',
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bairro',
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cidade',
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Estado (UF)',
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CEP',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telefone comercial',
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Celular principal',
  `instagram` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário Instagram',
  `tiktok` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário TikTok',
  `facebook` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário/Página Facebook',
  `status` enum('ativo','inativo','suspenso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo' COMMENT 'Status do cliente',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de cadastro',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização',
  `usuario_id` int(11) NOT NULL COMMENT 'ID do usuário que cadastrou'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de Clientes - Armazena informações de PF e PJ';

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_contatos`
--

CREATE TABLE `clientes_contatos` (
  `id` int(11) NOT NULL COMMENT 'ID único do contato',
  `cliente_id` int(11) NOT NULL COMMENT 'ID do cliente (FK)',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do contato',
  `departamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Departamento (ex: Financeiro, RH, etc)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'E-mail do contato',
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Celular do contato',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telefone do contato',
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cargo/Função',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações adicionais',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo' COMMENT 'Status do contato',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de cadastro',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de Contatos - Relacionamento 1:N com Clientes';

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Usuário Teste', 'admin@inlaudo.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-29 18:12:31', '2026-01-31 05:15:00'),
(3, 'Admin Teste', 'teste@email.com', '$2y$10$pLiV/abHhwwTl1KtO1.5n.wVrsEVlHRGAUbsp3c9toPzHamfcS/NC', '2026-01-31 08:07:37', '2026-01-31 08:07:37');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_clientes_data_cadastro` (`data_cadastro`),
  ADD KEY `idx_clientes_razao_social` (`razao_social`);

--
-- Índices de tabela `clientes_contatos`
--
ALTER TABLE `clientes_contatos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_clientes_contatos_cliente_status` (`cliente_id`,`status`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID único do cliente';

--
-- AUTO_INCREMENT de tabela `clientes_contatos`
--
ALTER TABLE `clientes_contatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID único do contato';

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `clientes_contatos`
--
ALTER TABLE `clientes_contatos`
  ADD CONSTRAINT `fk_clientes_contatos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
