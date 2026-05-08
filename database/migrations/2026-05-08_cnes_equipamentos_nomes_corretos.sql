-- Migration: Corrigir nomes de equipamentos CNES na tabela cnes_equipamentos
-- Problema: O dicionário EQUIP_IMAGEM no CnesImportService estava com mapeamento
--           completamente errado. Os nomes não correspondiam à tabela oficial do CNES.
--           Fonte oficial: wiki.saude.gov.br/cnes/index.php/Equipamentos
--
-- Exemplos de erros encontrados para a unidade 9174648 (INOVA DIAGNOSTICO PARAISOPOLIS):
--   Código 05: ERP mostrava 'Ultrassom'             | Correto: 'Raio X de 100 A 500 Ma'
--   Código 11: ERP mostrava 'Intensificador Imagem' | Correto: 'Tomógrafo Computadorizado'
--   Código 13: ERP mostrava 'Câmara Hiperbárica'    | Correto: 'Ultrassom Doppler Colorido'
--   Código 17: ERP mostrava 'Raio-X Odontológico'   | Correto: 'Mamógrafo Computadorizado'
--
-- Esta migration atualiza TODOS os registros já importados com os nomes corretos.
-- Após executar esta migration, reimporte os CSVs para que novos registros
-- também sejam importados com os nomes corretos.

UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Gama Câmara'                                              WHERE `co_equipamento` = '01';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Mamógrafo com Comando Simples'                            WHERE `co_equipamento` = '02';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Mamógrafo com Estereotaxia'                               WHERE `co_equipamento` = '03';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X Até 100 Ma'                                        WHERE `co_equipamento` = '04';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X de 100 A 500 Ma'                                   WHERE `co_equipamento` = '05';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X Mais de 500ma'                                     WHERE `co_equipamento` = '06';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X Dentário'                                          WHERE `co_equipamento` = '07';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X com Fluoroscopia'                                  WHERE `co_equipamento` = '08';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X para Densitometria Óssea'                          WHERE `co_equipamento` = '09';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Raio X para Hemodinâmica'                                 WHERE `co_equipamento` = '10';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Tomógrafo Computadorizado'                                WHERE `co_equipamento` = '11';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ressonância Magnética'                                    WHERE `co_equipamento` = '12';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ultrassom Doppler Colorido'                               WHERE `co_equipamento` = '13';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ultrassom Ecógrafo'                                       WHERE `co_equipamento` = '14';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ultrassom Convencional'                                   WHERE `co_equipamento` = '15';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Processadora de Filme Exclusiva Para Mamografia'          WHERE `co_equipamento` = '16';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Mamógrafo Computadorizado'                                WHERE `co_equipamento` = '17';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'PET/CT'                                                   WHERE `co_equipamento` = '18';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ar Condicionado'                                          WHERE `co_equipamento` = '19';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Câmara Frigorífica'                                       WHERE `co_equipamento` = '20';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Controle Ambiental/Ar-condicionado Central'               WHERE `co_equipamento` = '21';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Grupo Gerador'                                            WHERE `co_equipamento` = '22';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Usina de Oxigênio'                                        WHERE `co_equipamento` = '23';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Câmara para Conservação de Hemoderivados/Imuno/Termolábeis' WHERE `co_equipamento` = '24';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Câmara para Conservação de Imunobiológicos'               WHERE `co_equipamento` = '25';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Condensador'                                              WHERE `co_equipamento` = '26';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Freezer Científico'                                       WHERE `co_equipamento` = '27';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Grupo Gerador (101 A 300 KVA)'                            WHERE `co_equipamento` = '28';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Grupo Gerador (8 A 100 KVA)'                              WHERE `co_equipamento` = '29';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Grupo Gerador (Acima de 300 KVA)'                         WHERE `co_equipamento` = '30';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Endoscópio das Vias Respiratórias'                        WHERE `co_equipamento` = '31';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Endoscópio das Vias Urinárias'                            WHERE `co_equipamento` = '32';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Endoscópio Digestivo'                                     WHERE `co_equipamento` = '33';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Equipamentos para Optometria'                             WHERE `co_equipamento` = '34';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Laparoscópico/Vídeo'                                      WHERE `co_equipamento` = '35';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Microscópio Cirúrgico'                                    WHERE `co_equipamento` = '36';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Cadeira Oftalmológica'                                    WHERE `co_equipamento` = '37';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Coluna Oftalmológica'                                     WHERE `co_equipamento` = '38';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Refrator'                                                 WHERE `co_equipamento` = '39';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Lensômetro'                                               WHERE `co_equipamento` = '40';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Eletrocardiógrafo'                                        WHERE `co_equipamento` = '41';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Eletroencefalógrafo'                                      WHERE `co_equipamento` = '42';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Grupo Gerador de 1.500 (mínimo)'                          WHERE `co_equipamento` = '43';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Projetor ou Tabela de Optotipos'                          WHERE `co_equipamento` = '44';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Retinoscópio'                                             WHERE `co_equipamento` = '45';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Oftalmoscópio'                                            WHERE `co_equipamento` = '46';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ceratômetro'                                              WHERE `co_equipamento` = '47';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Tonômetro de Aplanação'                                   WHERE `co_equipamento` = '48';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Biomicroscópio (Lâmpada de Fenda)'                        WHERE `co_equipamento` = '49';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Campímetro'                                               WHERE `co_equipamento` = '50';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Bomba/Balão Intra-aórtico'                                WHERE `co_equipamento` = '51';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Bomba de Infusão'                                         WHERE `co_equipamento` = '52';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Berço Aquecido'                                           WHERE `co_equipamento` = '53';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Bilirrubinômetro'                                         WHERE `co_equipamento` = '54';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Debitômetro'                                              WHERE `co_equipamento` = '55';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Desfibrilador'                                            WHERE `co_equipamento` = '56';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Equipamento de Fototerapia'                               WHERE `co_equipamento` = '57';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Incubadora'                                               WHERE `co_equipamento` = '58';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Marca-passo Temporário'                                   WHERE `co_equipamento` = '59';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Monitor de ECG'                                           WHERE `co_equipamento` = '60';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Monitor de Pressão Invasivo'                              WHERE `co_equipamento` = '61';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Monitor de Pressão Não Invasivo'                          WHERE `co_equipamento` = '62';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Reanimador Pulmonar/Ambu'                                 WHERE `co_equipamento` = '63';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Respirador/Ventilador'                                    WHERE `co_equipamento` = '64';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Grupo Gerador Portátil (até 7 KVA)'                       WHERE `co_equipamento` = '65';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Refrigerador'                                             WHERE `co_equipamento` = '66';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Caminhão Baú Refrigerado'                                 WHERE `co_equipamento` = '67';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Embarcação para Transporte com Motor Popa (até 12 pessoas)' WHERE `co_equipamento` = '68';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Empilhadeira'                                             WHERE `co_equipamento` = '69';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Veículo Utilitário (Tipo Furgão)'                         WHERE `co_equipamento` = '70';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Aparelho de Diatermia por Ultrassom/Ondas Curtas'         WHERE `co_equipamento` = '71';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Aparelho de Eletroestimulação'                            WHERE `co_equipamento` = '72';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Bomba de Infusão de Hemoderivados'                        WHERE `co_equipamento` = '73';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Equipamentos de Aférese'                                  WHERE `co_equipamento` = '74';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Equipamento de Circulação Extracorpórea'                  WHERE `co_equipamento` = '76';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Equipamento Para Hemodiálise'                             WHERE `co_equipamento` = '77';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Forno de Bier'                                            WHERE `co_equipamento` = '78';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Veículo Pick-up Cabine Dupla 4x4 (Diesel)'               WHERE `co_equipamento` = '79';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Equipo Odontológico Completo'                             WHERE `co_equipamento` = '80';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Compressor Odontológico'                                  WHERE `co_equipamento` = '81';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Fotopolimerizador'                                        WHERE `co_equipamento` = '82';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Caneta de Alta Rotação'                                   WHERE `co_equipamento` = '83';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Caneta de Baixa Rotação'                                  WHERE `co_equipamento` = '84';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Amalgamador'                                              WHERE `co_equipamento` = '85';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Aparelho de Profilaxia C/Jato de Bicarbonato'             WHERE `co_equipamento` = '86';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Emissões Otoacústicas Evocadas Transientes'               WHERE `co_equipamento` = '87';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Emissões Otoacústicas Evocadas por Produto de Distorção'  WHERE `co_equipamento` = '88';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Potencial Evocado Auditivo de Tronco Encefálico Automático' WHERE `co_equipamento` = '89';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Pot. Evocado Aud. Tronco Encef. de Curta, Média e Longa Latência' WHERE `co_equipamento` = '90';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Audiômetro de Um Canal'                                   WHERE `co_equipamento` = '91';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Audiômetro de Dois Canais'                                WHERE `co_equipamento` = '92';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Imitanciômetro'                                           WHERE `co_equipamento` = '93';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Imitanciômetro Multifrequencial'                          WHERE `co_equipamento` = '94';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Cabine Acústica'                                          WHERE `co_equipamento` = '95';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Sistema de Campo Livre'                                   WHERE `co_equipamento` = '96';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Sistema Completo de Reforço Visual (VRA)'                 WHERE `co_equipamento` = '97';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Ganho de Inserção'                                        WHERE `co_equipamento` = '98';
UPDATE `cnes_equipamentos` SET `no_equipamento` = 'Hi-Pro'                                                   WHERE `co_equipamento` = '99';
