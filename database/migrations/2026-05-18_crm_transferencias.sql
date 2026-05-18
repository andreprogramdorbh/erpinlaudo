-- =============================================================================
-- Migration: CRM Transferências de Lead e Oportunidade
-- Data: 2026-05-18
-- Descrição: Cria a tabela crm_transferencias para registrar histórico completo
--            de transferências de leads e oportunidades entre usuários.
--            Também adiciona o tipo_interacao 'transferencia' na tabela
--            crm_interacoes para que o evento apareça na timeline.
-- =============================================================================

-- Tabela de transferências
CREATE TABLE IF NOT EXISTS crm_transferencias (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT NOT NULL                    COMMENT 'Quem executou a transferência (users.id)',
  related_id        INT NOT NULL                    COMMENT 'ID do lead ou oportunidade',
  related_type      ENUM('lead','oportunidade') NOT NULL,
  de_usuario_id     INT NOT NULL                    COMMENT 'Usuário de origem (users.id)',
  para_usuario_id   INT NOT NULL                    COMMENT 'Usuário de destino (users.id)',
  motivo            ENUM(
                      'sdr_qualificacao',
                      'conta_chave',
                      'colaborador_desligado',
                      'rodizio_por_inatividade'
                    ) NOT NULL                      COMMENT 'Motivo da transferência',
  observacao        TEXT NULL                       COMMENT 'Observação adicional opcional',
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_crm_transf_related  (related_type, related_id),
  INDEX idx_crm_transf_usuario  (usuario_id),
  INDEX idx_crm_transf_de       (de_usuario_id),
  INDEX idx_crm_transf_para     (para_usuario_id),

  CONSTRAINT fk_crm_transf_executor FOREIGN KEY (usuario_id)      REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_crm_transf_de       FOREIGN KEY (de_usuario_id)   REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_crm_transf_para     FOREIGN KEY (para_usuario_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico de transferências de leads e oportunidades entre usuários';

-- Adiciona o tipo 'transferencia' no ENUM de crm_interacoes (se ainda não existir)
-- MySQL não permite ALTER ENUM de forma condicional, então usamos MODIFY seguro:
ALTER TABLE crm_interacoes
  MODIFY COLUMN tipo_interacao ENUM(
    'email',
    'telefone',
    'whatsapp',
    'reuniao_presencial',
    'reuniao_online',
    'visita_tecnica',
    'proposta_enviada',
    'contrato_enviado',
    'transferencia',
    'outro'
  ) NOT NULL;
