<?php
/**
 * CnesImportService — Importação da base CNES 100% via browser
 * =============================================================
 * Processa o ZIP da base CNES (DATASUS) sem LOAD DATA INFILE.
 * Usa INSERT em lotes de 200 registros, compatível com MariaDB Hostinger.
 * Grava progresso em arquivo JSON para polling do browser.
 */
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;
use ZipArchive;

class CnesImportService
{
    private PDO    $pdo;
    private string $progressFile;
    private string $storageDir;
    private int    $batchSize = 200;

    // Mapeamento de equipamentos de Diagnóstico por Imagem (código CNES → nome)
    private const EQUIP_IMAGEM = [
        '01' => 'Raio-X',
        '02' => 'Mamógrafo',
        '03' => 'Tomógrafo Computadorizado',
        '04' => 'Ressonância Magnética',
        '05' => 'Ultrassom',
        '06' => 'Densitômetro Ósseo',
        '07' => 'Cintilógrafo/Gama Câmara',
        '08' => 'PET-CT',
        '09' => 'Angiógrafo',
        '10' => 'Arco Cirúrgico',
        '11' => 'Intensificador de Imagem',
        '12' => 'Litotriptor',
        '13' => 'Câmara Hiperbárica',
        '14' => 'Acelerador Linear',
        '15' => 'Bomba de Cobalto',
        '16' => 'Braquiterapia',
        '17' => 'Raio-X Odontológico',
        '18' => 'Tomógrafo Feixe de Elétrons',
        '19' => 'Raio-X Portátil',
        '20' => 'Fluoroscópio',
        '21' => 'Ecocardiografo',
        '22' => 'Eletroencefalógrafo',
        '23' => 'Eletrocardiógrafo',
        '24' => 'Monitor Cardíaco',
        '25' => 'Hemodinâmica',
        '26' => 'Endoscópio',
        '27' => 'Laparoscópio',
        '28' => 'Broncoscópio',
        '29' => 'Colposcópio',
        '30' => 'Cistoscópio',
    ];

    private const TIPO_EQUIP = [
        '1' => 'Diagnóstico por Imagem',
        '2' => 'Infraestrutura',
        '3' => 'Manutenção da Vida',
        '4' => 'Odontológico',
        '5' => 'Óptico',
        '6' => 'Outro',
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->storageDir   = dirname(__DIR__, 2) . '/storage';
        $this->progressFile = $this->storageDir . '/cnes_import_progress.json';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API pública
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Inicia a importação a partir de um arquivo ZIP já salvo no servidor.
     *
     * @param string $zipPath  Caminho absoluto do ZIP
     * @param array  $opcoes   ['uf' => 'MG', 'apenas_imagem' => false, 'competencia' => '202602']
     */
    public function importarZip(string $zipPath, array $opcoes = []): void
    {
        $uf           = strtoupper(trim($opcoes['uf'] ?? ''));
        $apenasImagem = (bool)($opcoes['apenas_imagem'] ?? false);
        $competencia  = $opcoes['competencia'] ?? date('Ym');

        // Criar diretório temporário para extração
        $tmpDir = $this->storageDir . '/cnes_tmp_' . time();
        @mkdir($tmpDir, 0755, true);

        try {
            $this->gravaProgresso([
                'status'      => 'extraindo',
                'etapa'       => 'Extraindo ZIP...',
                'pct'         => 2,
                'estab'       => 0,
                'equip'       => 0,
                'prof'        => 0,
                'erros'       => [],
                'iniciado_em' => date('Y-m-d H:i:s'),
            ]);

            // Extrair ZIP
            $this->extrairZip($zipPath, $tmpDir);

            // Registrar importação no banco
            $importId = $this->registrarImportacao($competencia);

            // Etapa 1: Estabelecimentos
            $this->gravaProgresso(['status' => 'importando', 'etapa' => 'Importando estabelecimentos...', 'pct' => 5]);
            $totalEstab = $this->importarEstabelecimentos($tmpDir, $uf, $competencia, $importId);

            // Etapa 2: Equipamentos
            $this->gravaProgresso(['etapa' => 'Importando equipamentos...', 'pct' => 60, 'estab' => $totalEstab]);
            $totalEquip = $this->importarEquipamentos($tmpDir, $uf, $apenasImagem, $competencia, $importId);

            // Etapa 3: Profissionais
            $this->gravaProgresso(['etapa' => 'Importando profissionais...', 'pct' => 75, 'equip' => $totalEquip]);
            $totalProf = $this->importarProfissionais($tmpDir, $uf, $competencia, $importId);

            // Finalizar
            $this->finalizarImportacao($importId, $totalEstab, $totalEquip, $totalProf);
            $this->gravaProgresso([
                'status' => 'concluido',
                'etapa'  => 'Importação concluída!',
                'pct'    => 100,
                'estab'  => $totalEstab,
                'equip'  => $totalEquip,
                'prof'   => $totalProf,
                'concluido_em' => date('Y-m-d H:i:s'),
            ]);

        } catch (\Throwable $e) {
            $this->gravaProgresso([
                'status' => 'erro',
                'etapa'  => 'Erro: ' . $e->getMessage(),
                'pct'    => 0,
                'erros'  => [$e->getMessage()],
            ]);
            throw $e;
        } finally {
            // Limpar diretório temporário
            $this->limparDiretorio($tmpDir);
        }
    }

    /**
     * Lê o progresso atual da importação.
     */
    public function lerProgresso(): array
    {
        if (!file_exists($this->progressFile)) {
            return ['status' => 'idle', 'pct' => 0, 'etapa' => ''];
        }
        $data = json_decode(file_get_contents($this->progressFile), true);
        return $data ?: ['status' => 'idle', 'pct' => 0, 'etapa' => ''];
    }

    /**
     * Retorna o histórico de importações.
     */
    public function historico(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT * FROM cnes_importacoes ORDER BY iniciado_em DESC LIMIT 20"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Extração do ZIP
    // ─────────────────────────────────────────────────────────────────────────

    private function extrairZip(string $zipPath, string $destDir): void
    {
        if (!class_exists('ZipArchive')) {
            // Fallback: usar unzip via shell
            $cmd = sprintf('unzip -o %s -d %s 2>&1', escapeshellarg($zipPath), escapeshellarg($destDir));
            exec($cmd, $output, $code);
            if ($code !== 0) {
                throw new \RuntimeException('Falha ao extrair ZIP: ' . implode("\n", $output));
            }
            return;
        }

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);
        if ($result !== true) {
            throw new \RuntimeException("Não foi possível abrir o ZIP (código: {$result})");
        }
        $zip->extractTo($destDir);
        $zip->close();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Importação de Estabelecimentos
    // ─────────────────────────────────────────────────────────────────────────

    private function importarEstabelecimentos(string $dir, string $uf, string $competencia, int $importId): int
    {
        $arquivo = $this->encontrarArquivo($dir, 'tbEstabelecimento');
        if (!$arquivo) {
            $this->gravaProgresso(['etapa' => '[AVISO] tbEstabelecimento*.csv não encontrado. Pulando.']);
            return 0;
        }

        $sql = "INSERT INTO cnes_estabelecimentos
                (co_cnes, co_unidade, nu_cnpj, nu_cnpj_mantenedora, tp_pfpj,
                 no_razao_social, no_fantasia, no_fantasia_abrev,
                 tp_unidade, co_tipo_unidade, co_tipo_estabelecimento,
                 co_atividade, co_clientela, tp_gestao, co_turno_atendimento,
                 tp_estab_sempre_aberto, no_logradouro, nu_endereco,
                 no_complemento, no_bairro, co_cep,
                 co_estado_gestor, co_municipio_gestor,
                 nu_telefone, nu_fax, no_email, no_url,
                 nu_latitude, nu_longitude,
                 co_natureza_jur, co_natureza_juridica,
                 st_conexao_internet,
                 nu_cpf_diretor, co_cpf_diretor_clinico,
                 reg_diretor, reg_diretor_clinico,
                 co_motivo_desabilitacao, dt_atualizacao, competencia,
                 created_at, updated_at)
                VALUES
                (:co_cnes, :co_unidade, :nu_cnpj, :nu_cnpj_manten, :tp_pfpj,
                 :no_razao, :no_fantasia, :no_fantasia_abrev,
                 :tp_unidade, :co_tipo_unidade, :co_tipo_estab,
                 :co_atividade, :co_clientela, :tp_gestao, :co_turno,
                 :tp_sempre_aberto, :no_logradouro, :nu_endereco,
                 :no_complemento, :no_bairro, :co_cep,
                 :co_estado, :co_municipio,
                 :nu_telefone, :nu_fax, :no_email, :no_url,
                 :nu_lat, :nu_lon,
                 :co_nat_jur, :co_nat_jur2,
                 :st_conexao,
                 :nu_cpf_dir, :co_cpf_dir_cln,
                 :reg_dir, :reg_dir_cln,
                 :co_motivo, :dt_atu, :competencia,
                 NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  co_unidade              = VALUES(co_unidade),
                  nu_cnpj                 = VALUES(nu_cnpj),
                  nu_cnpj_mantenedora     = VALUES(nu_cnpj_mantenedora),
                  no_razao_social         = VALUES(no_razao_social),
                  no_fantasia             = VALUES(no_fantasia),
                  no_fantasia_abrev       = VALUES(no_fantasia_abrev),
                  tp_unidade              = VALUES(tp_unidade),
                  co_tipo_unidade         = VALUES(co_tipo_unidade),
                  co_tipo_estabelecimento = VALUES(co_tipo_estabelecimento),
                  co_atividade            = VALUES(co_atividade),
                  co_clientela            = VALUES(co_clientela),
                  tp_gestao               = VALUES(tp_gestao),
                  no_logradouro           = VALUES(no_logradouro),
                  nu_endereco             = VALUES(nu_endereco),
                  no_complemento          = VALUES(no_complemento),
                  no_bairro               = VALUES(no_bairro),
                  co_cep                  = VALUES(co_cep),
                  co_estado_gestor        = VALUES(co_estado_gestor),
                  co_municipio_gestor     = VALUES(co_municipio_gestor),
                  nu_telefone             = VALUES(nu_telefone),
                  nu_fax                  = VALUES(nu_fax),
                  no_email                = VALUES(no_email),
                  no_url                  = VALUES(no_url),
                  nu_latitude             = VALUES(nu_latitude),
                  nu_longitude            = VALUES(nu_longitude),
                  co_natureza_jur         = VALUES(co_natureza_jur),
                  co_natureza_juridica    = VALUES(co_natureza_juridica),
                  st_conexao_internet     = VALUES(st_conexao_internet),
                  nu_cpf_diretor          = VALUES(nu_cpf_diretor),
                  co_cpf_diretor_clinico  = VALUES(co_cpf_diretor_clinico),
                  reg_diretor             = VALUES(reg_diretor),
                  reg_diretor_clinico     = VALUES(reg_diretor_clinico),
                  co_motivo_desabilitacao = VALUES(co_motivo_desabilitacao),
                  dt_atualizacao          = VALUES(dt_atualizacao),
                  competencia             = VALUES(competencia),
                  updated_at              = NOW()";

        $stmt  = $this->pdo->prepare($sql);
        $count = 0;
        $skip  = 0;

        $this->pdo->beginTransaction();

        foreach ($this->lerCsv($arquivo) as $row) {
            // Filtro por UF
            if ($uf && strtoupper($this->col($row, 'CO_ESTADO_GESTOR')) !== $uf) {
                $skip++;
                continue;
            }

            $coUnidade = $this->col($row, 'CO_UNIDADE');
            $coCnes    = $this->col($row, 'CO_CNES');
            if (!$coUnidade || !$coCnes) { $skip++; continue; }

            // Converter data DD/MM/YYYY → YYYY-MM-DD
            $dtAtu = $this->converterData($this->col($row, "TO_CHAR(DT_ATUALIZACAO,'DD/MM/YYYY')"));

            $stmt->execute([
                ':co_cnes'          => $coCnes,
                ':co_unidade'       => $coUnidade,
                ':nu_cnpj'          => $this->col($row, 'NU_CNPJ'),
                ':nu_cnpj_manten'   => $this->col($row, 'NU_CNPJ_MANTENEDORA'),
                ':tp_pfpj'          => $this->col($row, 'TP_PFPJ'),
                ':no_razao'         => $this->col($row, 'NO_RAZAO_SOCIAL'),
                ':no_fantasia'      => $this->col($row, 'NO_FANTASIA'),
                ':no_fantasia_abrev'=> $this->col($row, 'NO_FANTASIA_ABREV'),
                ':tp_unidade'       => $this->col($row, 'TP_UNIDADE'),
                ':co_tipo_unidade'  => $this->col($row, 'CO_TIPO_UNIDADE'),
                ':co_tipo_estab'    => $this->col($row, 'CO_TIPO_ESTABELECIMENTO'),
                ':co_atividade'     => $this->col($row, 'CO_ATIVIDADE'),
                ':co_clientela'     => $this->col($row, 'CO_CLIENTELA'),
                ':tp_gestao'        => $this->col($row, 'TP_GESTAO'),
                ':co_turno'         => $this->col($row, 'CO_TURNO_ATENDIMENTO'),
                ':tp_sempre_aberto' => $this->col($row, 'TP_ESTAB_SEMPRE_ABERTO'),
                ':no_logradouro'    => $this->col($row, 'NO_LOGRADOURO'),
                ':nu_endereco'      => $this->col($row, 'NU_ENDERECO'),
                ':no_complemento'   => $this->col($row, 'NO_COMPLEMENTO'),
                ':no_bairro'        => $this->col($row, 'NO_BAIRRO'),
                ':co_cep'           => $this->col($row, 'CO_CEP'),
                ':co_estado'        => $this->col($row, 'CO_ESTADO_GESTOR'),
                ':co_municipio'     => $this->col($row, 'CO_MUNICIPIO_GESTOR'),
                ':nu_telefone'      => $this->col($row, 'NU_TELEFONE'),
                ':nu_fax'           => $this->col($row, 'NU_FAX'),
                ':no_email'         => strtolower((string)$this->col($row, 'NO_EMAIL')),
                ':no_url'           => $this->col($row, 'NO_URL'),
                ':nu_lat'           => $this->col($row, 'NU_LATITUDE') ?: null,
                ':nu_lon'           => $this->col($row, 'NU_LONGITUDE') ?: null,
                ':co_nat_jur'       => $this->col($row, 'CO_NATUREZA_JUR'),
                ':co_nat_jur2'      => $this->col($row, 'CO_NATUREZA_JUR'),
                ':st_conexao'       => $this->col($row, 'ST_CONEXAO_INTERNET'),
                ':nu_cpf_dir'       => $this->col($row, 'CO_CPFDIRETORCLN'),
                ':co_cpf_dir_cln'   => $this->col($row, 'CO_CPFDIRETORCLN'),
                ':reg_dir'          => $this->col($row, 'REG_DIRETORCLN'),
                ':reg_dir_cln'      => $this->col($row, 'REG_DIRETORCLN'),
                ':co_motivo'        => $this->col($row, 'CO_MOTIVO_DESAB') ?: null,
                ':dt_atu'           => $dtAtu,
                ':competencia'      => $competencia,
            ]);

            $count++;

            if ($count % $this->batchSize === 0) {
                $this->pdo->commit();
                $this->pdo->beginTransaction();
                $pct = min(58, 5 + intval($count / 10000));
                $this->gravaProgresso([
                    'etapa' => "Estabelecimentos: {$count} importados...",
                    'pct'   => $pct,
                    'estab' => $count,
                ]);
            }
        }

        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Importação de Equipamentos
    // ─────────────────────────────────────────────────────────────────────────

    private function importarEquipamentos(string $dir, string $uf, bool $apenasImagem, string $competencia, int $importId): int
    {
        $arquivo = $this->encontrarArquivo($dir, 'rlEstabEquipamento');
        if (!$arquivo) {
            $this->gravaProgresso(['etapa' => '[AVISO] rlEstabEquipamento*.csv não encontrado. Pulando.']);
            return 0;
        }

        // Carregar lookup de unidades por UF (se filtro aplicado)
        $unidadesPorUf = [];
        if ($uf) {
            $stmt = $this->pdo->prepare(
                "SELECT co_unidade FROM cnes_estabelecimentos WHERE co_estado_gestor = ?"
            );
            $stmt->execute([$uf]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $u) {
                $unidadesPorUf[$u] = true;
            }
        }

        $sql = "INSERT INTO cnes_equipamentos
                (co_cnes, co_unidade, co_equipamento, no_equipamento,
                 co_tipo_equipamento, no_tipo_equipamento,
                 qt_existente, qt_uso, tp_sus, qt_sus,
                 dt_atualizacao, competencia, created_at, updated_at)
                VALUES
                (:co_cnes, :co_unidade, :co_equip, :no_equip,
                 :co_tipo, :no_tipo,
                 :qt_exist, :qt_uso, :tp_sus, :qt_sus,
                 :dt_atu, :competencia, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  qt_existente        = VALUES(qt_existente),
                  qt_uso              = VALUES(qt_uso),
                  tp_sus              = VALUES(tp_sus),
                  qt_sus              = VALUES(qt_sus),
                  no_equipamento      = VALUES(no_equipamento),
                  co_tipo_equipamento = VALUES(co_tipo_equipamento),
                  no_tipo_equipamento = VALUES(no_tipo_equipamento),
                  dt_atualizacao      = VALUES(dt_atualizacao),
                  competencia         = VALUES(competencia),
                  updated_at          = NOW()";

        // Garantir UNIQUE KEY para o ON DUPLICATE KEY UPDATE funcionar
        try {
            $this->pdo->exec(
                "ALTER TABLE cnes_equipamentos ADD UNIQUE KEY uk_unidade_equip (co_unidade, co_equipamento)"
            );
        } catch (\Throwable $e) { /* já existe */ }

        $stmt  = $this->pdo->prepare($sql);
        $count = 0;
        $skip  = 0;

        $this->pdo->beginTransaction();

        foreach ($this->lerCsv($arquivo) as $row) {
            $coUnidade = $this->col($row, 'CO_UNIDADE');
            if (!$coUnidade) { $skip++; continue; }

            // Filtro por UF
            if ($uf && !isset($unidadesPorUf[$coUnidade])) { $skip++; continue; }

            $coEquip = trim((string)$this->col($row, 'CO_EQUIPAMENTO'));
            $coTipo  = trim((string)$this->col($row, 'CO_TIPO_EQUIPAMENTO'));

            // Filtro apenas imagem
            if ($apenasImagem && $coTipo !== '1') { $skip++; continue; }

            $noEquip = self::EQUIP_IMAGEM[$coEquip] ?? "Equipamento {$coEquip}";
            $noTipo  = self::TIPO_EQUIP[$coTipo]   ?? "Tipo {$coTipo}";

            // Buscar co_cnes da unidade
            $coCnes = null;
            try {
                $s = $this->pdo->prepare("SELECT co_cnes FROM cnes_estabelecimentos WHERE co_unidade = ? LIMIT 1");
                $s->execute([$coUnidade]);
                $r = $s->fetch(PDO::FETCH_OBJ);
                $coCnes = $r ? $r->co_cnes : null;
            } catch (\Throwable $e) {}

            $dtAtu = $this->converterData($this->col($row, "TO_CHAR(DT_ATUALIZACAO,'DD/MM/YYYY')"));

            $stmt->execute([
                ':co_cnes'    => $coCnes,
                ':co_unidade' => $coUnidade,
                ':co_equip'   => $coEquip,
                ':no_equip'   => $noEquip,
                ':co_tipo'    => $coTipo,
                ':no_tipo'    => $noTipo,
                ':qt_exist'   => (int)($this->col($row, 'QT_EXISTENTE') ?? 0),
                ':qt_uso'     => (int)($this->col($row, 'QT_USO') ?? 0),
                ':tp_sus'     => $this->col($row, 'TP_SUS'),
                ':qt_sus'     => (int)($this->col($row, 'QT_SUS') ?? 0),
                ':dt_atu'     => $dtAtu,
                ':competencia'=> $competencia,
            ]);

            $count++;

            if ($count % $this->batchSize === 0) {
                $this->pdo->commit();
                $this->pdo->beginTransaction();
                $pct = min(73, 60 + intval($count / 50000));
                $this->gravaProgresso([
                    'etapa' => "Equipamentos: {$count} importados...",
                    'pct'   => $pct,
                    'equip' => $count,
                ]);
            }
        }

        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Importação de Profissionais
    // ─────────────────────────────────────────────────────────────────────────

    private function importarProfissionais(string $dir, string $uf, string $competencia, int $importId): int
    {
        $arquivoCH   = $this->encontrarArquivo($dir, 'tbCargaHorariaSus');
        $arquivoProf = $this->encontrarArquivo($dir, 'tbDadosProfissionalSus');

        if (!$arquivoCH) {
            $this->gravaProgresso(['etapa' => '[AVISO] tbCargaHorariaSus*.csv não encontrado. Pulando.']);
            return 0;
        }

        // Carregar lookup de nomes de profissionais em memória (limitado a 500k)
        $nomesProf = [];
        if ($arquivoProf) {
            $this->gravaProgresso(['etapa' => 'Carregando nomes dos profissionais...', 'pct' => 76]);
            $n = 0;
            foreach ($this->lerCsv($arquivoProf) as $row) {
                $co = $this->col($row, 'CO_PROFISSIONAL_SUS');
                if ($co) {
                    $nomesProf[$co] = $this->col($row, 'NO_PROFISSIONAL') ?? 'Profissional';
                }
                $n++;
                if ($n >= 500000) break; // Limite de memória
            }
        }

        // Carregar lookup de unidades por UF
        $unidadesPorUf = [];
        if ($uf) {
            $stmt = $this->pdo->prepare(
                "SELECT co_unidade FROM cnes_estabelecimentos WHERE co_estado_gestor = ?"
            );
            $stmt->execute([$uf]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $u) {
                $unidadesPorUf[$u] = true;
            }
        }

        // Garantir UNIQUE KEY
        try {
            $this->pdo->exec(
                "ALTER TABLE cnes_profissionais ADD UNIQUE KEY uk_unidade_prof_cbo (co_unidade(30), co_profissional_sus(32), co_cbo(6))"
            );
        } catch (\Throwable $e) { /* já existe */ }

        $sql = "INSERT INTO cnes_profissionais
                (co_cnes, co_unidade, co_profissional_sus, no_profissional,
                 co_cbo, co_conselho_classe, nu_registro, nu_registro_conselho,
                 sg_uf_crm, sg_uf_conselho,
                 tp_sus_nao_sus, ind_vinculacao,
                 qt_carga_horaria_amb, qt_carga_horaria_outros,
                 dt_atualizacao, competencia, created_at, updated_at)
                VALUES
                (:co_cnes, :co_unidade, :co_prof, :no_prof,
                 :co_cbo, :co_conselho, :nu_registro, :nu_registro2,
                 :sg_uf, :sg_uf2,
                 :tp_sus, :ind_vinc,
                 :ch_amb, :ch_outros,
                 :dt_atu, :competencia, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  no_profissional        = VALUES(no_profissional),
                  co_cbo                 = VALUES(co_cbo),
                  co_conselho_classe     = VALUES(co_conselho_classe),
                  nu_registro            = VALUES(nu_registro),
                  nu_registro_conselho   = VALUES(nu_registro_conselho),
                  sg_uf_crm              = VALUES(sg_uf_crm),
                  tp_sus_nao_sus         = VALUES(tp_sus_nao_sus),
                  ind_vinculacao         = VALUES(ind_vinculacao),
                  qt_carga_horaria_amb   = VALUES(qt_carga_horaria_amb),
                  qt_carga_horaria_outros= VALUES(qt_carga_horaria_outros),
                  dt_atualizacao         = VALUES(dt_atualizacao),
                  competencia            = VALUES(competencia),
                  updated_at             = NOW()";

        $stmt  = $this->pdo->prepare($sql);
        $count = 0;
        $skip  = 0;

        $this->pdo->beginTransaction();

        foreach ($this->lerCsv($arquivoCH) as $row) {
            $coUnidade = $this->col($row, 'CO_UNIDADE');
            if (!$coUnidade) { $skip++; continue; }

            // Filtro por UF
            if ($uf && !isset($unidadesPorUf[$coUnidade])) { $skip++; continue; }

            $coProf = $this->col($row, 'CO_PROFISSIONAL_SUS');

            // Buscar co_cnes
            $coCnes = null;
            try {
                $s = $this->pdo->prepare("SELECT co_cnes FROM cnes_estabelecimentos WHERE co_unidade = ? LIMIT 1");
                $s->execute([$coUnidade]);
                $r = $s->fetch(PDO::FETCH_OBJ);
                $coCnes = $r ? $r->co_cnes : null;
            } catch (\Throwable $e) {}

            $dtAtu = $this->converterData($this->col($row, "TO_CHAR(A.DT_ATUALIZACAO,'DD/MM/YYYY')"));

            $stmt->execute([
                ':co_cnes'    => $coCnes,
                ':co_unidade' => $coUnidade,
                ':co_prof'    => $coProf,
                ':no_prof'    => $nomesProf[$coProf ?? ''] ?? 'Profissional',
                ':co_cbo'     => $this->col($row, 'CO_CBO'),
                ':co_conselho'=> $this->col($row, 'CO_CONSELHO_CLASSE'),
                ':nu_registro'=> $this->col($row, 'NU_REGISTRO'),
                ':nu_registro2'=> $this->col($row, 'NU_REGISTRO'),
                ':sg_uf'      => $this->col($row, 'SG_UF_CRM'),
                ':sg_uf2'     => $this->col($row, 'SG_UF_CRM'),
                ':tp_sus'     => $this->col($row, 'TP_SUS_NAO_SUS'),
                ':ind_vinc'   => $this->col($row, 'IND_VINCULACAO'),
                ':ch_amb'     => (int)($this->col($row, 'QT_CARGA_HORARIA_AMBULATORIAL') ?? 0),
                ':ch_outros'  => (int)($this->col($row, 'QT_CARGA_HORARIA_OUTROS') ?? 0),
                ':dt_atu'     => $dtAtu,
                ':competencia'=> $competencia,
            ]);

            $count++;

            if ($count % $this->batchSize === 0) {
                $this->pdo->commit();
                $this->pdo->beginTransaction();
                $pct = min(98, 75 + intval($count / 100000));
                $this->gravaProgresso([
                    'etapa' => "Profissionais: {$count} importados...",
                    'pct'   => $pct,
                    'prof'  => $count,
                ]);
            }
        }

        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function encontrarArquivo(string $dir, string $prefixo): ?string
    {
        // Busca recursiva no diretório extraído
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && stripos($file->getFilename(), $prefixo) !== false
                && strtolower($file->getExtension()) === 'csv') {
                return $file->getPathname();
            }
        }
        return null;
    }

    /**
     * Lê um CSV ISO-8859-1 linha por linha, retornando arrays associativos.
     * Usa cabeçalho da primeira linha como chaves.
     */
    private function lerCsv(string $arquivo): \Generator
    {
        $fh = fopen($arquivo, 'r');
        if (!$fh) {
            throw new \RuntimeException("Não foi possível abrir: {$arquivo}");
        }

        // Ler cabeçalho
        $cabecalho = fgetcsv($fh, 0, ';');
        if (!$cabecalho) {
            fclose($fh);
            return;
        }

        // Normalizar cabeçalho: remover aspas, converter para maiúsculas
        $cabecalho = array_map(function ($c) {
            return strtoupper(trim($c, " \t\n\r\0\x0B\""));
        }, $cabecalho);

        while (($linha = fgetcsv($fh, 0, ';')) !== false) {
            if (count($linha) < 2) continue;

            // Converter encoding ISO-8859-1 → UTF-8
            $linha = array_map(function ($v) {
                if ($v === null) return null;
                $v = trim($v, " \t\n\r\0\x0B");
                // Tentar detectar se já é UTF-8
                if (!mb_check_encoding($v, 'UTF-8')) {
                    $v = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
                }
                return $v === '' ? null : $v;
            }, $linha);

            // Combinar cabeçalho com valores
            $row = array_combine(
                $cabecalho,
                array_pad($linha, count($cabecalho), null)
            );

            yield $row;
        }

        fclose($fh);
    }

    private function col(array $row, string $chave): ?string
    {
        $chave = strtoupper($chave);
        return isset($row[$chave]) && $row[$chave] !== '' ? (string)$row[$chave] : null;
    }

    private function converterData(?string $data): ?string
    {
        if (!$data) return null;
        // DD/MM/YYYY → YYYY-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // YYYY-MM-DD já está correto
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $data;
        }
        return null;
    }

    private function registrarImportacao(string $competencia): int
    {
        try {
            $this->pdo->prepare(
                "INSERT INTO cnes_importacoes (competencia, status, log, iniciado_em)
                 VALUES (?, 'em_andamento', 'Iniciado via browser', NOW())
                 ON DUPLICATE KEY UPDATE status = 'em_andamento', iniciado_em = NOW()"
            )->execute([$competencia]);
            return (int)$this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function finalizarImportacao(int $id, int $estab, int $equip, int $prof): void
    {
        if (!$id) return;
        try {
            $this->pdo->prepare(
                "UPDATE cnes_importacoes
                 SET status = 'concluido', total_estab = ?, total_equip = ?,
                     total_prof = ?, concluido_em = NOW(),
                     log = CONCAT(log, ' | Concluído: ', NOW())
                 WHERE id = ?"
            )->execute([$estab, $equip, $prof, $id]);
        } catch (\Throwable $e) {}
    }

    private function gravaProgresso(array $dados): void
    {
        $atual = [];
        if (file_exists($this->progressFile)) {
            $atual = json_decode(file_get_contents($this->progressFile), true) ?? [];
        }
        $novo = array_merge($atual, $dados);
        file_put_contents($this->progressFile, json_encode($novo, JSON_UNESCAPED_UNICODE));
    }

    private function limparDiretorio(string $dir): void
    {
        if (!is_dir($dir)) return;
        $cmd = sprintf('rm -rf %s', escapeshellarg($dir));
        exec($cmd);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Importação a partir de diretório com CSVs já extraídos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Importa a base CNES a partir de um diretório com CSVs já extraídos.
     * Ideal para quando os arquivos já estão no servidor (ex: /tmp/cnes_base/).
     *
     * @param string $dir     Caminho absoluto do diretório com os CSVs
     * @param array  $opcoes  ['uf' => 'MG', 'apenas_imagem' => false, 'competencia' => '202602']
     */
    public function importarDiretorio(string $dir, array $opcoes = []): void
    {
        $uf           = strtoupper(trim($opcoes['uf'] ?? ''));
        $apenasImagem = (bool)($opcoes['apenas_imagem'] ?? false);
        $competencia  = $opcoes['competencia'] ?? date('Ym');

        if (!is_dir($dir)) {
            throw new \RuntimeException("Diretório não encontrado: {$dir}");
        }

        $csvs = glob($dir . '/*.csv');
        if (empty($csvs)) {
            throw new \RuntimeException("Nenhum arquivo CSV encontrado em: {$dir}");
        }

        try {
            $this->gravaProgresso([
                'status'      => 'importando',
                'etapa'       => 'Iniciando importação dos CSVs do servidor...',
                'pct'         => 2,
                'estab'       => 0,
                'equip'       => 0,
                'prof'        => 0,
                'erros'       => [],
                'iniciado_em' => date('Y-m-d H:i:s'),
                'dir'         => $dir,
                'total_csvs'  => count($csvs),
            ]);

            $importId = $this->registrarImportacao($competencia);

            // Etapa 1: Estabelecimentos
            $this->gravaProgresso(['etapa' => 'Importando estabelecimentos...', 'pct' => 5]);
            $totalEstab = $this->importarEstabelecimentos($dir, $uf, $competencia, $importId);

            // Etapa 2: Equipamentos
            $this->gravaProgresso(['etapa' => 'Importando equipamentos...', 'pct' => 60, 'estab' => $totalEstab]);
            $totalEquip = $this->importarEquipamentos($dir, $uf, $apenasImagem, $competencia, $importId);

            // Etapa 3: Profissionais
            $this->gravaProgresso(['etapa' => 'Importando profissionais...', 'pct' => 75, 'equip' => $totalEquip]);
            $totalProf = $this->importarProfissionais($dir, $uf, $competencia, $importId);

            // Finalizar
            $this->finalizarImportacao($importId, $totalEstab, $totalEquip, $totalProf);
            $this->gravaProgresso([
                'status'       => 'concluido',
                'etapa'        => 'Importação concluída com sucesso!',
                'pct'          => 100,
                'estab'        => $totalEstab,
                'equip'        => $totalEquip,
                'prof'         => $totalProf,
                'concluido_em' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->gravaProgresso([
                'status' => 'erro',
                'etapa'  => 'Erro: ' . $e->getMessage(),
                'pct'    => 0,
                'erros'  => [$e->getMessage()],
            ]);
            throw $e;
        }
        // Não limpar o diretório — os arquivos pertencem ao servidor
    }
}
