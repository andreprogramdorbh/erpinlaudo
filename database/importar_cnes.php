#!/usr/bin/env php
<?php
/**
 * Script de Importação da Base CNES para o ERP InLaudo
 * =====================================================
 * Uso: php importar_cnes.php [opcoes]
 *
 * Opções:
 *   --dir=/caminho/para/csvs   Diretório com os CSVs extraídos do ZIP (padrão: ./cnes_csvs)
 *   --uf=SP                    Filtrar apenas estabelecimentos de uma UF (opcional)
 *   --municipio=355030         Filtrar por código IBGE do município (opcional)
 *   --apenas-imagem            Importar apenas equipamentos de diagnóstico por imagem
 *   --step=estab               Executar apenas um passo: estab|equip|prof
 *   --limit=1000               Limitar número de registros (para teste)
 *   --help                     Exibir esta ajuda
 *
 * Exemplo:
 *   php importar_cnes.php --dir=/home/ubuntu/cnes_base --uf=MG
 *   php importar_cnes.php --dir=/home/ubuntu/cnes_base --step=estab --limit=500
 */

declare(strict_types=1);

// ── Configuração ────────────────────────────────────────────────────────────────────────────────
// Carregar .env antes do config
$rootDir = dirname(__DIR__);
$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v, " \t\n\r\0\x0B\"'");
        putenv("$k=$v"); $_ENV[$k] = $v;
    }
}
$configFile = $rootDir . '/config/database.php';
if (!file_exists($configFile)) {
    // Fallback para app/Config/database.php
    $configFile = $rootDir . '/app/Config/database.php';
}
if (!file_exists($configFile)) {
    die("ERRO: Arquivo de configuração não encontrado. Verifique o .env\n");
}
$dbConfig = require $configFile;

// ── Argumentos CLI ────────────────────────────────────────────────────────────
$opts = getopt('', ['dir:', 'uf:', 'municipio:', 'apenas-imagem', 'step:', 'limit:', 'help']);

if (isset($opts['help'])) {
    echo file_get_contents(__FILE__);
    exit(0);
}

$csvDir     = $opts['dir']       ?? __DIR__ . '/cnes_csvs';
$filtroUf   = strtoupper($opts['uf'] ?? '');
$filtroMun  = $opts['municipio'] ?? '';
$apenasImg  = isset($opts['apenas-imagem']);
$step       = $opts['step']      ?? 'all';
$limit      = isset($opts['limit']) ? (int)$opts['limit'] : 0;

if (!is_dir($csvDir)) {
    die("ERRO: Diretório de CSVs não encontrado: {$csvDir}\n");
}

// ── Conexão PDO ───────────────────────────────────────────────────────────────
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
    echo "[OK] Conexão com o banco estabelecida.\n";
} catch (PDOException $e) {
    die("ERRO de conexão: " . $e->getMessage() . "\n");
}

// ── Funções auxiliares ────────────────────────────────────────────────────────

/**
 * Lê um CSV com separador ; e encoding latin1→utf8, retornando iterador de arrays.
 */
function lerCsv(string $arquivo): Generator
{
    if (!file_exists($arquivo)) {
        echo "  [AVISO] Arquivo não encontrado: {$arquivo}\n";
        return;
    }
    $handle = fopen($arquivo, 'r');
    if (!$handle) {
        echo "  [ERRO] Não foi possível abrir: {$arquivo}\n";
        return;
    }
    // Detectar BOM UTF-8
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    // Ler cabeçalho
    $header = fgetcsv($handle, 4096, ';');
    if (!$header) {
        fclose($handle);
        return;
    }
    // Normalizar nomes das colunas (remover aspas, converter para lowercase)
    $header = array_map(fn($h) => strtolower(trim(trim($h, '"\''))), $header);
    while (($row = fgetcsv($handle, 4096, ';')) !== false) {
        if (count($row) < count($header)) continue;
        // Converter encoding latin1 → utf8 se necessário
        $row = array_map(function($v) {
            $v = trim(trim($v, '"\''));
            if (!mb_check_encoding($v, 'UTF-8')) {
                $v = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
            }
            return $v === '' ? null : $v;
        }, $row);
        yield array_combine($header, array_slice($row, 0, count($header)));
    }
    fclose($handle);
}

function col(array $row, string ...$keys): ?string
{
    foreach ($keys as $key) {
        $k = strtolower(trim($key, '"\''));
        if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return null;
}

function progresso(int $atual, int $total, string $label = ''): void
{
    if ($total > 0) {
        $pct = round($atual / $total * 100);
        echo "\r  {$label} {$atual}/{$total} ({$pct}%)   ";
    } else {
        echo "\r  {$label} {$atual}...   ";
    }
}

// ── PASSO 1: Importar Estabelecimentos ────────────────────────────────────────
if ($step === 'all' || $step === 'estab') {
    echo "\n[PASSO 1] Importando estabelecimentos...\n";

    $arquivo = $csvDir . '/tbEstabelecimento202602.csv';
    if (!file_exists($arquivo)) {
        // Tentar nome sem ano
        $arquivo = glob($csvDir . '/tbEstabelecimento*.csv')[0] ?? null;
    }

    if (!$arquivo || !file_exists($arquivo)) {
        echo "  [AVISO] tbEstabelecimento*.csv não encontrado. Pulando.\n";
    } else {
        // Detectar competência pelo nome do arquivo
        preg_match('/(\d{6})\.csv$/', basename($arquivo), $mComp);
        $competencia = $mComp[1] ?? date('Ym');

        $sql = "INSERT INTO cnes_estabelecimentos
                (co_unidade, co_cnes, nu_cnpj, nu_cnpj_mantenedora,
                 no_razao_social, no_fantasia, no_fantasia_abrev, tp_unidade,
                 co_tipo_unidade, co_tipo_estabelecimento,
                 co_atividade, co_clientela, tp_gestao,
                 no_logradouro, nu_endereco, no_complemento, no_bairro, co_cep,
                 co_municipio_gestor, co_estado_gestor, nu_telefone, nu_fax,
                 no_email, no_url, nu_latitude, nu_longitude,
                 co_natureza_juridica, st_conexao_internet,
                 co_cpf_diretor_clinico, reg_diretor_clinico,
                 co_motivo_desabilitacao, dt_atualizacao, competencia)
                VALUES
                (:co_unidade, :co_cnes, :nu_cnpj, :nu_cnpj_mantenedora,
                 :no_razao_social, :no_fantasia, :no_fantasia_abrev, :tp_unidade,
                 :co_tipo_unidade, :co_tipo_estabelecimento,
                 :co_atividade, :co_clientela, :tp_gestao,
                 :no_logradouro, :nu_endereco, :no_complemento, :no_bairro, :co_cep,
                 :co_municipio_gestor, :co_estado_gestor, :nu_telefone, :nu_fax,
                 :no_email, :no_url, :nu_latitude, :nu_longitude,
                 :co_natureza_juridica, :st_conexao_internet,
                 :co_cpf_diretor_clinico, :reg_diretor_clinico,
                 :co_motivo_desabilitacao, :dt_atualizacao, :competencia)
                ON DUPLICATE KEY UPDATE
                  no_razao_social         = VALUES(no_razao_social),
                  no_fantasia             = VALUES(no_fantasia),
                  co_tipo_unidade         = VALUES(co_tipo_unidade),
                  co_tipo_estabelecimento = VALUES(co_tipo_estabelecimento),
                  no_logradouro           = VALUES(no_logradouro),
                  co_estado_gestor        = VALUES(co_estado_gestor),
                  co_municipio_gestor     = VALUES(co_municipio_gestor),
                  nu_telefone             = VALUES(nu_telefone),
                  no_email                = VALUES(no_email),
                  co_motivo_desabilitacao = VALUES(co_motivo_desabilitacao),
                  dt_atualizacao          = VALUES(dt_atualizacao),
                  competencia             = VALUES(competencia),
                  updated_at              = NOW()";

        $stmt    = $pdo->prepare($sql);
        $count   = 0;
        $skipped = 0;

        $pdo->beginTransaction();
        foreach (lerCsv($arquivo) as $row) {
            $uf = col($row, 'co_estado_gestor');
            if ($filtroUf && strtoupper($uf ?? '') !== $filtroUf) { $skipped++; continue; }
            $mun = col($row, 'co_municipio_gestor');
            if ($filtroMun && $mun !== $filtroMun) { $skipped++; continue; }

            $stmt->execute([
                ':co_unidade'             => col($row, 'co_unidade'),
                ':co_cnes'                => col($row, 'co_cnes'),
                ':nu_cnpj'                => col($row, 'nu_cnpj'),
                ':nu_cnpj_mantenedora'    => col($row, 'nu_cnpj_mantenedora'),
                ':no_razao_social'        => col($row, 'no_razao_social') ?? 'SEM NOME',
                ':no_fantasia'            => col($row, 'no_fantasia'),
                ':no_fantasia_abrev'      => col($row, 'no_fantasia_abrev'),
                ':tp_unidade'             => col($row, 'tp_unidade'),
                ':co_tipo_unidade'        => col($row, 'co_tipo_unidade'),
                ':co_tipo_estabelecimento'=> col($row, 'co_tipo_estabelecimento'),
                ':co_atividade'           => col($row, 'co_atividade'),
                ':co_clientela'           => col($row, 'co_clientela'),
                ':tp_gestao'              => col($row, 'tp_gestao'),
                ':no_logradouro'          => col($row, 'no_logradouro'),
                ':nu_endereco'            => col($row, 'nu_endereco'),
                ':no_complemento'         => col($row, 'no_complemento'),
                ':no_bairro'              => col($row, 'no_bairro'),
                ':co_cep'                 => col($row, 'co_cep'),
                ':co_municipio_gestor'    => col($row, 'co_municipio_gestor'),
                ':co_estado_gestor'       => col($row, 'co_estado_gestor'),
                ':nu_telefone'            => col($row, 'nu_telefone'),
                ':nu_fax'                 => col($row, 'nu_fax'),
                ':no_email'               => col($row, 'no_email'),
                ':no_url'                 => col($row, 'no_url'),
                ':nu_latitude'            => col($row, 'nu_latitude'),
                ':nu_longitude'           => col($row, 'nu_longitude'),
                ':co_natureza_juridica'   => col($row, 'co_natureza_jur'),
                ':st_conexao_internet'    => col($row, 'st_conexao_internet'),
                ':co_cpf_diretor_clinico' => col($row, 'co_cpfdiretorcln'),
                ':reg_diretor_clinico'    => col($row, 'reg_diretorcln'),
                ':co_motivo_desabilitacao'=> col($row, 'co_motivo_desab'),
                ':dt_atualizacao'         => col($row, "to_char(dt_atualizacao,'dd/mm/yyyy')"),
                ':competencia'            => $competencia,
            ]);
            $count++;
            if ($count % 500 === 0) {
                $pdo->commit();
                $pdo->beginTransaction();
                progresso($count, 0, 'Estabelecimentos:');
            }
            if ($limit > 0 && $count >= $limit) break;
        }
        $pdo->commit();
        echo "\n  [OK] {$count} estabelecimentos importados. {$skipped} ignorados (filtro UF/município).\n";
    }
}

// ── PASSO 2: Importar Equipamentos ───────────────────────────────────────────
if ($step === 'all' || $step === 'equip') {
    echo "\n[PASSO 2] Importando equipamentos...\n";

    $arquivo = glob($csvDir . '/rlEstabEquipamento*.csv')[0] ?? null;

    if (!$arquivo) {
        echo "  [AVISO] rlEstabEquipamento*.csv não encontrado. Pulando.\n";
    } else {
        // Carregar lookup de equipamentos
        $lookup = [];
        foreach ($pdo->query("SELECT co_equipamento, no_equipamento, co_tipo FROM cnes_dom_equipamentos") as $r) {
            $lookup[trim($r->co_equipamento)] = $r;
        }
        $tipoLookup = [];
        foreach ($pdo->query("SELECT co_tipo, no_tipo FROM cnes_dom_tipo_equipamento") as $r) {
            $tipoLookup[trim($r->co_tipo)] = $r->no_tipo;
        }

        // Carregar co_unidades importados (para filtrar)
        $unidadesImportadas = [];
        if ($filtroUf || $filtroMun) {
            $sqlFiltro = "SELECT co_unidade FROM cnes_estabelecimentos WHERE 1=1";
            $paramsFiltro = [];
            if ($filtroUf) { $sqlFiltro .= " AND co_estado_gestor = ?"; $paramsFiltro[] = $filtroUf; }
            if ($filtroMun) { $sqlFiltro .= " AND co_municipio_gestor = ?"; $paramsFiltro[] = $filtroMun; }
            $stmtFiltro = $pdo->prepare($sqlFiltro);
            $stmtFiltro->execute($paramsFiltro);
            foreach ($stmtFiltro->fetchAll(PDO::FETCH_COLUMN) as $u) {
                $unidadesImportadas[$u] = true;
            }
            echo "  Filtrando por " . count($unidadesImportadas) . " unidades da UF/município.\n";
        }

        $sql = "INSERT INTO cnes_equipamentos
                (co_unidade, co_equipamento, no_equipamento, co_tipo_equipamento,
                 no_tipo_equipamento, qt_existente, qt_uso, tp_sus, qt_sus, dt_atualizacao)
                VALUES
                (:co_unidade, :co_equipamento, :no_equipamento, :co_tipo,
                 :no_tipo, :qt_existente, :qt_uso, :tp_sus, :qt_sus, :dt_atualizacao)
                ON DUPLICATE KEY UPDATE
                  qt_existente = VALUES(qt_existente),
                  qt_uso       = VALUES(qt_uso)";

        // Adicionar índice único se não existir
        try {
            $pdo->exec("ALTER TABLE cnes_equipamentos ADD UNIQUE KEY uk_unidade_equip (co_unidade, co_equipamento)");
        } catch (PDOException $e) { /* já existe */ }

        $stmt  = $pdo->prepare($sql);
        $count = 0;
        $skip  = 0;

        $pdo->beginTransaction();
        foreach (lerCsv($arquivo) as $row) {
            $coUnidade = col($row, 'co_unidade');
            if (!$coUnidade) { $skip++; continue; }
            if (!empty($unidadesImportadas) && !isset($unidadesImportadas[$coUnidade])) { $skip++; continue; }

            $coEquip = trim(col($row, 'co_equipamento') ?? '');
            $coTipo  = trim(col($row, 'co_tipo_equipamento') ?? '');

            if ($apenasImg && $coTipo !== '1') { $skip++; continue; }

            $equipInfo = $lookup[$coEquip] ?? null;
            $noEquip   = $equipInfo ? $equipInfo->no_equipamento : "Equipamento {$coEquip}";
            $noTipo    = $tipoLookup[$coTipo] ?? "Tipo {$coTipo}";

            $stmt->execute([
                ':co_unidade'    => $coUnidade,
                ':co_equipamento'=> $coEquip,
                ':no_equipamento'=> $noEquip,
                ':co_tipo'       => $coTipo,
                ':no_tipo'       => $noTipo,
                ':qt_existente'  => (int)(col($row, 'qt_existente') ?? 0),
                ':qt_uso'        => (int)(col($row, 'qt_uso') ?? 0),
                ':tp_sus'        => col($row, 'tp_sus'),
                ':qt_sus'        => (int)(col($row, 'qt_sus') ?? 0),
                ':dt_atualizacao'=> col($row, "to_char(dt_atualizacao,'dd/mm/yyyy')"),
            ]);
            $count++;
            if ($count % 1000 === 0) {
                $pdo->commit();
                $pdo->beginTransaction();
                progresso($count, 0, 'Equipamentos:');
            }
            if ($limit > 0 && $count >= $limit) break;
        }
        $pdo->commit();
        echo "\n  [OK] {$count} equipamentos importados. {$skip} ignorados.\n";
    }
}

// ── PASSO 3: Importar Profissionais ──────────────────────────────────────────
if ($step === 'all' || $step === 'prof') {
    echo "\n[PASSO 3] Importando profissionais (carga horária por estabelecimento)...\n";

    $arquivoProf = glob($csvDir . '/tbDadosProfissionalSus*.csv')[0] ?? null;
    $arquivoCH   = glob($csvDir . '/tbCargaHorariaSus*.csv')[0] ?? null;

    if (!$arquivoCH) {
        echo "  [AVISO] tbCargaHorariaSus*.csv não encontrado. Pulando.\n";
    } else {
        // Carregar lookup de nomes de profissionais (do tbDadosProfissionalSus)
        $nomesProf = [];
        if ($arquivoProf && file_exists($arquivoProf)) {
            echo "  Carregando nomes de profissionais...\n";
            $n = 0;
            foreach (lerCsv($arquivoProf) as $row) {
                $co = col($row, 'co_profissional_sus');
                if ($co) {
                    $nomesProf[$co] = col($row, 'no_profissional') ?? 'Profissional';
                }
                $n++;
                if ($n % 100000 === 0) progresso($n, 0, 'Nomes carregados:');
                if ($limit > 0 && $n >= $limit * 10) break;
            }
            echo "\n  {$n} nomes carregados.\n";
        }

        // Carregar lookup CBO
        $cbos = [];
        foreach ($pdo->query("SELECT co_cbo, no_cbo FROM cnes_dom_cbo") as $r) {
            $cbos[$r->co_cbo] = $r->no_cbo;
        }
        // Carregar lookup conselhos
        $conselhos = [];
        foreach ($pdo->query("SELECT co_conselho, no_conselho FROM cnes_dom_conselho") as $r) {
            $conselhos[$r->co_conselho] = $r->no_conselho;
        }

        // Carregar unidades importadas
        $unidadesImportadas = [];
        if ($filtroUf || $filtroMun) {
            $sqlFiltro = "SELECT co_unidade FROM cnes_estabelecimentos WHERE 1=1";
            $paramsFiltro = [];
            if ($filtroUf) { $sqlFiltro .= " AND co_estado_gestor = ?"; $paramsFiltro[] = $filtroUf; }
            if ($filtroMun) { $sqlFiltro .= " AND co_municipio_gestor = ?"; $paramsFiltro[] = $filtroMun; }
            $stmtFiltro = $pdo->prepare($sqlFiltro);
            $stmtFiltro->execute($paramsFiltro);
            foreach ($stmtFiltro->fetchAll(PDO::FETCH_COLUMN) as $u) {
                $unidadesImportadas[$u] = true;
            }
        }

        $sql = "INSERT INTO cnes_profissionais
                (co_unidade, co_profissional_sus, no_profissional, co_cbo, no_cbo,
                 co_conselho_classe, no_conselho_classe, nu_registro, sg_uf_crm,
                 tp_sus_nao_sus, ind_vinculacao, qt_carga_horaria_amb, qt_carga_horaria_outros)
                VALUES
                (:co_unidade, :co_prof, :no_prof, :co_cbo, :no_cbo,
                 :co_conselho, :no_conselho, :nu_registro, :sg_uf,
                 :tp_sus, :ind_vinc, :ch_amb, :ch_outros)
                ON DUPLICATE KEY UPDATE
                  no_profissional = VALUES(no_profissional),
                  co_cbo          = VALUES(co_cbo),
                  no_cbo          = VALUES(no_cbo)";

        // Adicionar índice único
        try {
            $pdo->exec("ALTER TABLE cnes_profissionais ADD UNIQUE KEY uk_unidade_prof (co_unidade, co_profissional_sus, co_cbo(10))");
        } catch (PDOException $e) { /* já existe */ }

        $stmt  = $pdo->prepare($sql);
        $count = 0;
        $skip  = 0;

        $pdo->beginTransaction();
        foreach (lerCsv($arquivoCH) as $row) {
            $coUnidade = col($row, 'co_unidade');
            if (!$coUnidade) { $skip++; continue; }
            if (!empty($unidadesImportadas) && !isset($unidadesImportadas[$coUnidade])) { $skip++; continue; }

            $coProf    = col($row, 'co_profissional_sus');
            $coCbo     = col($row, 'co_cbo');
            $coConselho= col($row, 'co_conselho_classe');

            $stmt->execute([
                ':co_unidade'  => $coUnidade,
                ':co_prof'     => $coProf,
                ':no_prof'     => $nomesProf[$coProf ?? ''] ?? 'Profissional',
                ':co_cbo'      => $coCbo,
                ':no_cbo'      => $cbos[$coCbo ?? ''] ?? null,
                ':co_conselho' => $coConselho,
                ':no_conselho' => $conselhos[$coConselho ?? ''] ?? null,
                ':nu_registro' => col($row, 'nu_registro'),
                ':sg_uf'       => col($row, 'sg_uf_crm'),
                ':tp_sus'      => col($row, 'tp_sus_nao_sus'),
                ':ind_vinc'    => col($row, 'ind_vinculacao'),
                ':ch_amb'      => (int)(col($row, 'qt_carga_horaria_ambulatorial') ?? 0),
                ':ch_outros'   => (int)(col($row, 'qt_carga_horaria_outros') ?? 0),
            ]);
            $count++;
            if ($count % 1000 === 0) {
                $pdo->commit();
                $pdo->beginTransaction();
                progresso($count, 0, 'Profissionais:');
            }
            if ($limit > 0 && $count >= $limit) break;
        }
        $pdo->commit();
        echo "\n  [OK] {$count} vínculos profissional-estabelecimento importados. {$skip} ignorados.\n";
    }
}

echo "\n[CONCLUÍDO] Importação da base CNES finalizada.\n";
echo "  Acesse o ERP em: /cnes para visualizar os dados.\n\n";
