-- Migration: Corrigir nomes de tipos de equipamento na tabela cnes_equipamentos
-- Problema: O dicionário TIPO_EQUIP estava incompleto e com mapeamento incorreto.
-- Os tipos 3-10 estavam errados ou ausentes, causando "Tipo 7" em vez do nome real.
--
-- Mapeamento oficial CNES/DATASUS:
--   1  = Diagnóstico por Imagem
--   2  = Infraestrutura
--   3  = Equipamentos por Métodos Ópticos
--   4  = Equipamentos por Métodos Gráficos
--   5  = Manutenção da Vida
--   6  = Laboratorial
--   7  = Odontológico
--   8  = Audiologia
--   9  = Telessaúde
--   10 = Diálise

UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Diagnóstico por Imagem'          WHERE co_tipo_equipamento = '1';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Infraestrutura'                   WHERE co_tipo_equipamento = '2';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Equipamentos por Métodos Ópticos' WHERE co_tipo_equipamento = '3';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Equipamentos por Métodos Gráficos' WHERE co_tipo_equipamento = '4';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Manutenção da Vida'               WHERE co_tipo_equipamento = '5';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Laboratorial'                     WHERE co_tipo_equipamento = '6';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Odontológico'                     WHERE co_tipo_equipamento = '7';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Audiologia'                       WHERE co_tipo_equipamento = '8';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Telessaúde'                       WHERE co_tipo_equipamento = '9';
UPDATE cnes_equipamentos SET no_tipo_equipamento = 'Diálise'                          WHERE co_tipo_equipamento = '10';
