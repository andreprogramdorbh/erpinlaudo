-- Migration: 2026-06-19 — adiciona asaas_xml_url e asaas_error_desc à tabela notas_fiscais
-- Execute: mysql -u root -p erp < database/2026-06-19_nf_asaas_campos.sql

ALTER TABLE notas_fiscais
    ADD COLUMN asaas_xml_url    VARCHAR(500)  DEFAULT NULL AFTER asaas_pdf_url,
    ADD COLUMN asaas_error_desc TEXT          DEFAULT NULL AFTER asaas_xml_url;
