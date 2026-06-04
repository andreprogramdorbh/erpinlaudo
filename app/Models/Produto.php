<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Produto extends Model
{
    protected string $table = 'produtos';

    // ─── Logger interno ──────────────────────────────────────────────────────
    private function log(string $level, string $msg, array $ctx = []): void
    {
        $dir  = defined('BASE_PATH') ? BASE_PATH . '/storage/logs' : sys_get_temp_dir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/estoque_' . date('Y-m') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $msg;
        if (!empty($ctx)) {
            $line .= ' | ctx=' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log($line);
    }

    // ─── Converte valor BR (1.234,56) ou numérico (1234.56) para float ─────────
    private function toFloat(mixed $v): float
    {
        if ($v === null || $v === '') return 0.0;
        $s = trim((string) $v);
        // Formato brasileiro: contém vírgula como separador decimal (ex: "1.650,00")
        if (preg_match('/^-?[\d.]+,[\d]{1,2}$/', $s)) {
            return (float) str_replace(['.', ','], ['', '.'], $s);
        }
        // Formato numérico do banco ou JS (ex: "1650.00" ou "1650")
        // Remove apenas vírgulas caso existam como separador de milhar (ex: "1,650.00")
        $s = str_replace(',', '', $s);
        return (float) $s;
    }

    // ─── Geração de código incremental por tenant ────────────────────────────
    public function gerarCodigo(int $usuarioId): string
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO produto_codigo_seq (usuario_id, ultimo_seq) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE ultimo_seq = ultimo_seq + 1"
            );
            $stmt->execute([$usuarioId]);
            $stmt = $this->pdo->prepare(
                "SELECT ultimo_seq FROM produto_codigo_seq WHERE usuario_id = ?"
            );
            $stmt->execute([$usuarioId]);
            $seq = (int) $stmt->fetchColumn();
            $this->pdo->commit();
            return 'PRD-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[gerarCodigo] ' . $e->getMessage());
            return 'PRD-' . date('YmdHis');
        }
    }

    // ─── Listagem com filtros ────────────────────────────────────────────────
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ["p.usuario_id = :uid"];
        $params = [':uid' => $usuarioId];

        if (!empty($filtros['q'])) {
            $where[] = "(p.nome LIKE :q OR p.codigo LIKE :q OR p.marca LIKE :q OR p.modelo LIKE :q OR p.fabricante_nome LIKE :q)";
            $params[':q'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['tipo'])) {
            $where[] = "p.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['categoria'])) {
            $where[] = "p.categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }
        if (isset($filtros['status']) && $filtros['status'] !== '') {
            $where[] = "p.status = :status";
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['fabricante_id'])) {
            $where[] = "p.fabricante_id = :fab";
            $params[':fab'] = (int) $filtros['fabricante_id'];
        }
        if (isset($filtros['estoque_baixo']) && $filtros['estoque_baixo']) {
            $where[] = "p.controla_estoque = 1 AND p.estoque_atual <= p.estoque_minimo";
        }
        if (isset($filtros['vencendo']) && $filtros['vencendo']) {
            $where[] = "p.controla_validade = 1";
        }

        $sql = "SELECT p.*,
                       f.nome AS fornecedor_nome_rel
                FROM {$this->table} p
                LEFT JOIN fornecedores f ON f.id = p.fabricante_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ─── Busca para seleção em propostas/componentes ─────────────────────────
    public function buscar(int $usuarioId, string $q, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, codigo, nome, tipo, categoria, unidade_medida,
                    preco_venda, preco_custo, markup_percentual, status,
                    imagem_principal, marca, modelo
             FROM {$this->table}
             WHERE usuario_id = ?
               AND status = 'ativo'
               AND visivel_proposta = 1
               AND (nome LIKE ? OR codigo LIKE ? OR marca LIKE ? OR modelo LIKE ?)
             ORDER BY nome ASC
             LIMIT ?"
        );
        $like = '%' . $q . '%';
        $stmt->execute([$usuarioId, $like, $like, $like, $like, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ─── Busca por ID ────────────────────────────────────────────────────────
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*,
                    f.nome AS fornecedor_nome_rel,
                    f.email AS fornecedor_email,
                    f.telefone AS fornecedor_telefone
             FROM {$this->table} p
             LEFT JOIN fornecedores f ON f.id = p.fabricante_id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ─── Criar produto ───────────────────────────────────────────────────────
    public function create(array $d): int|false
    {
        $this->log('info', '[create] Iniciando criação', [
            'usuario_id' => $d['usuario_id'] ?? null,
            'nome'       => $d['nome'] ?? '',
            'codigo'     => $d['codigo'] ?? '',
        ]);

        $sql = "INSERT INTO {$this->table} (
            usuario_id, codigo, tipo, categoria, nome, nome_tecnico,
            descricao_curta, descricao_completa, modelo, marca,
            fabricante_id, fabricante_nome, pais_origem, ncm,
            anvisa_registro, anvisa_classe, anvisa_validade,
            unidade_medida, unidade_compra, fator_conversao,
            preco_custo, preco_custo_medio, despesas_acessorias,
            markup_percentual, preco_venda, preco_minimo_venda, preco_sugerido,
            margem_lucro_liquida, impostos_percentual, moeda,
            controla_estoque, estoque_atual, estoque_minimo, estoque_maximo,
            ponto_reposicao, lead_time_dias, localizacao_estoque,
            controla_validade, alerta_validade_dias, lote_obrigatorio,
            controla_depreciacao, vida_util_meses, valor_residual,
            metodo_depreciacao, depreciacao_mensal, alerta_substituicao_meses,
            peso_kg, altura_cm, largura_cm, profundidade_cm,
            voltagem, potencia_w,
            garantia_meses, garantia_estendida_meses,
            assistencia_tecnica, manual_url, ficha_tecnica_url,
            palavras_chave, publico_alvo, indicacoes_uso, contraindicacoes,
            diferenciais, concorrentes, ciclo_venda_dias,
            imagem_principal, video_url, catalogo_pdf_url,
            status, visivel_proposta, visivel_catalogo,
            requer_instalacao, requer_treinamento, requer_anvisa,
            observacoes_internas
        ) VALUES (
            :usuario_id, :codigo, :tipo, :categoria, :nome, :nome_tecnico,
            :descricao_curta, :descricao_completa, :modelo, :marca,
            :fabricante_id, :fabricante_nome, :pais_origem, :ncm,
            :anvisa_registro, :anvisa_classe, :anvisa_validade,
            :unidade_medida, :unidade_compra, :fator_conversao,
            :preco_custo, :preco_custo_medio, :despesas_acessorias,
            :markup_percentual, :preco_venda, :preco_minimo_venda, :preco_sugerido,
            :margem_lucro_liquida, :impostos_percentual, :moeda,
            :controla_estoque, :estoque_atual, :estoque_minimo, :estoque_maximo,
            :ponto_reposicao, :lead_time_dias, :localizacao_estoque,
            :controla_validade, :alerta_validade_dias, :lote_obrigatorio,
            :controla_depreciacao, :vida_util_meses, :valor_residual,
            :metodo_depreciacao, :depreciacao_mensal, :alerta_substituicao_meses,
            :peso_kg, :altura_cm, :largura_cm, :profundidade_cm,
            :voltagem, :potencia_w,
            :garantia_meses, :garantia_estendida_meses,
            :assistencia_tecnica, :manual_url, :ficha_tecnica_url,
            :palavras_chave, :publico_alvo, :indicacoes_uso, :contraindicacoes,
            :diferenciais, :concorrentes, :ciclo_venda_dias,
            :imagem_principal, :video_url, :catalogo_pdf_url,
            :status, :visivel_proposta, :visivel_catalogo,
            :requer_instalacao, :requer_treinamento, :requer_anvisa,
            :observacoes_internas
        )";
        try {
            $params = $this->_bindInsertParams($d);
            $this->log('debug', '[create] Params preparados', array_keys($params));
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $id = (int) $this->pdo->lastInsertId();
            if ($id > 0) {
                $this->log('info', '[create] Produto criado com sucesso', ['id' => $id]);
                $this->registrarHistoricoPreco($id, $d);
            } else {
                $this->log('warning', '[create] lastInsertId retornou 0');
            }
            return $id ?: false;
        } catch (\PDOException $e) {
            $this->log('error', '[create] PDOException: ' . $e->getMessage(), [
                'code'  => $e->getCode(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            return false;
        }
    }

    // ─── Atualizar produto ───────────────────────────────────────────────────
    public function update(int $id, array $d): bool
    {
        $this->log('info', '[update] Iniciando atualização', [
            'produto_id' => $id,
            'usuario_id' => $d['usuario_id'] ?? null,
        ]);

        $antes = $this->findById($id);

        $sql = "UPDATE {$this->table} SET
            tipo=:tipo, categoria=:categoria, nome=:nome, nome_tecnico=:nome_tecnico,
            descricao_curta=:descricao_curta, descricao_completa=:descricao_completa,
            modelo=:modelo, marca=:marca,
            fabricante_id=:fabricante_id, fabricante_nome=:fabricante_nome,
            pais_origem=:pais_origem, ncm=:ncm,
            anvisa_registro=:anvisa_registro, anvisa_classe=:anvisa_classe, anvisa_validade=:anvisa_validade,
            unidade_medida=:unidade_medida, unidade_compra=:unidade_compra, fator_conversao=:fator_conversao,
            preco_custo=:preco_custo, preco_custo_medio=:preco_custo_medio, despesas_acessorias=:despesas_acessorias,
            markup_percentual=:markup_percentual, preco_venda=:preco_venda,
            preco_minimo_venda=:preco_minimo_venda, preco_sugerido=:preco_sugerido,
            margem_lucro_liquida=:margem_lucro_liquida, impostos_percentual=:impostos_percentual, moeda=:moeda,
            controla_estoque=:controla_estoque, estoque_atual=:estoque_atual,
            estoque_minimo=:estoque_minimo, estoque_maximo=:estoque_maximo,
            ponto_reposicao=:ponto_reposicao, lead_time_dias=:lead_time_dias, localizacao_estoque=:localizacao_estoque,
            controla_validade=:controla_validade, alerta_validade_dias=:alerta_validade_dias, lote_obrigatorio=:lote_obrigatorio,
            controla_depreciacao=:controla_depreciacao, vida_util_meses=:vida_util_meses, valor_residual=:valor_residual,
            metodo_depreciacao=:metodo_depreciacao, depreciacao_mensal=:depreciacao_mensal,
            alerta_substituicao_meses=:alerta_substituicao_meses,
            peso_kg=:peso_kg, altura_cm=:altura_cm, largura_cm=:largura_cm, profundidade_cm=:profundidade_cm,
            voltagem=:voltagem, potencia_w=:potencia_w,
            garantia_meses=:garantia_meses, garantia_estendida_meses=:garantia_estendida_meses,
            assistencia_tecnica=:assistencia_tecnica, manual_url=:manual_url, ficha_tecnica_url=:ficha_tecnica_url,
            palavras_chave=:palavras_chave, publico_alvo=:publico_alvo,
            indicacoes_uso=:indicacoes_uso, contraindicacoes=:contraindicacoes,
            diferenciais=:diferenciais, concorrentes=:concorrentes, ciclo_venda_dias=:ciclo_venda_dias,
            video_url=:video_url, catalogo_pdf_url=:catalogo_pdf_url,
            status=:status, visivel_proposta=:visivel_proposta, visivel_catalogo=:visivel_catalogo,
            requer_instalacao=:requer_instalacao, requer_treinamento=:requer_treinamento, requer_anvisa=:requer_anvisa,
            observacoes_internas=:observacoes_internas
        WHERE id = :id AND usuario_id = :usuario_id";
        try {
            $params = $this->_bindUpdateParams($d);
            $params[':id']         = $id;
            $params[':usuario_id'] = $d['usuario_id'];
            $this->log('debug', '[update] Params preparados', array_keys($params));
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            $this->log('info', '[update] Resultado', [
                'ok'        => $ok,
                'rowCount'  => $rowCount,
                'produto_id'=> $id,
            ]);
            if ($ok && $antes) {
                $precoCustoNovo = $this->toFloat($d['preco_custo'] ?? 0);
                $precoVendaNovo = $this->toFloat($d['preco_venda'] ?? 0);
                if ((float)$antes->preco_custo !== $precoCustoNovo || (float)$antes->preco_venda !== $precoVendaNovo) {
                    $this->registrarHistoricoPreco($id, $d);
                }
            }
            return $ok;
        } catch (\PDOException $e) {
            $this->log('error', '[update] PDOException: ' . $e->getMessage(), [
                'code'       => $e->getCode(),
                'produto_id' => $id,
                'trace'      => substr($e->getTraceAsString(), 0, 500),
            ]);
            return false;
        }
    }

    // ─── Excluir produto ─────────────────────────────────────────────────────
    public function delete(int $id, int $usuarioId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?"
            );
            $ok = $stmt->execute([$id, $usuarioId]);
            $this->log('info', '[delete] Produto excluído', ['id' => $id, 'ok' => $ok]);
            return $ok;
        } catch (\PDOException $e) {
            $this->log('error', '[delete] PDOException: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    // ─── Atualizar estoque (entrada/saída) ───────────────────────────────────
    public function atualizarEstoque(int $id, float $quantidade, string $tipo): bool
    {
        // tipo: 'entrada' soma, 'saida' subtrai
        $op = $tipo === 'entrada' ? '+' : '-';
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET estoque_atual = estoque_atual {$op} ? WHERE id = ?"
            );
            $ok = $stmt->execute([$quantidade, $id]);
            $this->log('info', '[atualizarEstoque] Estoque atualizado', [
                'produto_id' => $id, 'quantidade' => $quantidade, 'tipo' => $tipo, 'ok' => $ok,
            ]);
            return $ok;
        } catch (\PDOException $e) {
            $this->log('error', '[atualizarEstoque] PDOException: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    // ─── Atualizar imagem principal ──────────────────────────────────────────
    public function updateImagem(int $id, int $usuarioId, string $path): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET imagem_principal = ? WHERE id = ? AND usuario_id = ?"
        );
        return $stmt->execute([$path, $id, $usuarioId]);
    }

    // ─── KPIs para o dashboard do módulo ────────────────────────────────────
    public function kpis(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                                    AS total,
               SUM(tipo = 'produto')                                       AS total_produtos,
               SUM(tipo = 'servico')                                       AS total_servicos,
               SUM(status = 'ativo')                                       AS total_ativos,
               SUM(status = 'inativo')                                     AS total_inativos,
               SUM(status = 'descontinuado')                               AS total_descontinuados,
               SUM(controla_estoque = 1 AND estoque_atual <= estoque_minimo AND estoque_minimo > 0) AS estoque_critico,
               SUM(controla_validade = 1)                                  AS com_validade,
               SUM(controla_depreciacao = 1)                               AS com_depreciacao,
               SUM(preco_venda * estoque_atual)                            AS valor_estoque_venda,
               SUM(preco_custo * estoque_atual)                            AS valor_estoque_custo,
               AVG(markup_percentual)                                      AS markup_medio,
               AVG(margem_lucro_liquida)                                   AS margem_media
             FROM {$this->table}
             WHERE usuario_id = ?"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: (object)[];
    }

    // ─── Histórico de preços ─────────────────────────────────────────────────
    public function getHistoricoPrecos(int $produtoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT h.*, u.name AS usuario_nome
             FROM produto_historico_precos h
             LEFT JOIN users u ON u.id = h.usuario_responsavel
             WHERE h.produto_id = ?
             ORDER BY h.created_at DESC
             LIMIT 20"
        );
        $stmt->execute([$produtoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function registrarHistoricoPreco(int $id, array $d): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO produto_historico_precos
                    (produto_id, usuario_id, preco_custo, preco_venda, markup_percentual, motivo, usuario_responsavel)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $id,
                $d['usuario_id'],
                $this->toFloat($d['preco_custo'] ?? 0),
                $this->toFloat($d['preco_venda'] ?? 0),
                $this->toFloat($d['markup_percentual'] ?? 0),
                $d['motivo_preco'] ?? null,
                $d['usuario_id'],
            ]);
        } catch (\Throwable $e) {
            $this->log('error', '[registrarHistoricoPreco] ' . $e->getMessage(), ['produto_id' => $id]);
        }
    }

    // ─── Bind para INSERT (inclui :codigo) ───────────────────────────────────
    private function _bindInsertParams(array $d): array
    {
        $p = $this->_bindCommonParams($d);
        $p[':codigo']        = $d['codigo'] ?? '';
        $p[':estoque_atual'] = $this->toFloat($d['estoque_atual'] ?? 0);
        return $p;
    }

    // ─── Bind para UPDATE (inclui :estoque_atual para ajuste manual) ───────────
    private function _bindUpdateParams(array $d): array
    {
        $p = $this->_bindCommonParams($d);
        // Permite ajuste manual de estoque_atual na edição
        // O campo é readonly na view, mas pode ser desbloqueado via JS se necessário
        $p[':estoque_atual'] = $this->toFloat($d['estoque_atual'] ?? 0);
        return $p;
    }

    // ─── Parâmetros comuns a INSERT e UPDATE ─────────────────────────────────
    private function _bindCommonParams(array $d): array
    {
        $n = fn($k, $def = null) => isset($d[$k]) && $d[$k] !== '' ? $d[$k] : $def;
        $f = fn($k) => $this->toFloat($d[$k] ?? '');
        $i = fn($k, $def = 0) => isset($d[$k]) && $d[$k] !== '' ? (int) $d[$k] : $def;
        $b = fn($k) => isset($d[$k]) ? (int)(bool)$d[$k] : 0;

        return [
            ':usuario_id'               => $d['usuario_id'],
            ':tipo'                     => $d['tipo'] ?? 'produto',
            ':categoria'                => $d['categoria'] ?? 'equipamento_medico',
            ':nome'                     => $d['nome'] ?? '',
            ':nome_tecnico'             => $n('nome_tecnico'),
            ':descricao_curta'          => $n('descricao_curta'),
            ':descricao_completa'       => $n('descricao_completa'),
            ':modelo'                   => $n('modelo'),
            ':marca'                    => $n('marca'),
            ':fabricante_id'            => $n('fabricante_id') ? (int)$d['fabricante_id'] : null,
            ':fabricante_nome'          => $n('fabricante_nome'),
            ':pais_origem'              => $n('pais_origem'),
            ':ncm'                      => $n('ncm'),
            ':anvisa_registro'          => $n('anvisa_registro'),
            ':anvisa_classe'            => $n('anvisa_classe'),
            ':anvisa_validade'          => $n('anvisa_validade'),
            ':unidade_medida'           => $d['unidade_medida'] ?? 'UN',
            ':unidade_compra'           => $n('unidade_compra'),
            ':fator_conversao'          => $f('fator_conversao') ?: 1.0,
            ':preco_custo'              => $f('preco_custo'),
            ':preco_custo_medio'        => $f('preco_custo_medio') ?: $f('preco_custo'),
            ':despesas_acessorias'      => $f('despesas_acessorias'),
            ':markup_percentual'        => $f('markup_percentual'),
            ':preco_venda'              => $f('preco_venda'),
            ':preco_minimo_venda'       => $f('preco_minimo_venda'),
            ':preco_sugerido'           => $f('preco_sugerido'),
            ':margem_lucro_liquida'     => $f('margem_lucro_liquida'),
            ':impostos_percentual'      => $f('impostos_percentual'),
            ':moeda'                    => $d['moeda'] ?? 'BRL',
            ':controla_estoque'         => $b('controla_estoque'),
            ':estoque_minimo'           => $f('estoque_minimo'),
            ':estoque_maximo'           => $f('estoque_maximo'),
            ':ponto_reposicao'          => $f('ponto_reposicao'),
            ':lead_time_dias'           => $i('lead_time_dias'),
            ':localizacao_estoque'      => $n('localizacao_estoque'),
            ':controla_validade'        => $b('controla_validade'),
            ':alerta_validade_dias'     => $i('alerta_validade_dias', 90),
            ':lote_obrigatorio'         => $b('lote_obrigatorio'),
            ':controla_depreciacao'     => $b('controla_depreciacao'),
            ':vida_util_meses'          => $n('vida_util_meses') ? (int)$d['vida_util_meses'] : null,
            ':valor_residual'           => $f('valor_residual'),
            ':metodo_depreciacao'       => $d['metodo_depreciacao'] ?? 'linear',
            ':depreciacao_mensal'       => $f('depreciacao_mensal'),
            ':alerta_substituicao_meses'=> $n('alerta_substituicao_meses') ? (int)$d['alerta_substituicao_meses'] : null,
            ':peso_kg'                  => $n('peso_kg') ? $f('peso_kg') : null,
            ':altura_cm'                => $n('altura_cm') ? $f('altura_cm') : null,
            ':largura_cm'               => $n('largura_cm') ? $f('largura_cm') : null,
            ':profundidade_cm'          => $n('profundidade_cm') ? $f('profundidade_cm') : null,
            ':voltagem'                 => $n('voltagem'),
            ':potencia_w'               => $n('potencia_w') ? $f('potencia_w') : null,
            ':garantia_meses'           => $i('garantia_meses'),
            ':garantia_estendida_meses' => $i('garantia_estendida_meses'),
            ':assistencia_tecnica'      => $n('assistencia_tecnica'),
            ':manual_url'               => $n('manual_url'),
            ':ficha_tecnica_url'        => $n('ficha_tecnica_url'),
            ':palavras_chave'           => $n('palavras_chave'),
            ':publico_alvo'             => $n('publico_alvo'),
            ':indicacoes_uso'           => $n('indicacoes_uso'),
            ':contraindicacoes'         => $n('contraindicacoes'),
            ':diferenciais'             => $n('diferenciais'),
            ':concorrentes'             => $n('concorrentes'),
            ':ciclo_venda_dias'         => $n('ciclo_venda_dias') ? (int)$d['ciclo_venda_dias'] : null,
            ':imagem_principal'         => $n('imagem_principal'),
            ':video_url'                => $n('video_url'),
            ':catalogo_pdf_url'         => $n('catalogo_pdf_url'),
            ':status'                   => $d['status'] ?? 'ativo',
            ':visivel_proposta'         => $b('visivel_proposta'),
            ':visivel_catalogo'         => $b('visivel_catalogo'),
            ':requer_instalacao'        => $b('requer_instalacao'),
            ':requer_treinamento'       => $b('requer_treinamento'),
            ':requer_anvisa'            => $b('requer_anvisa'),
            ':observacoes_internas'     => $n('observacoes_internas'),
        ];
    }
}
