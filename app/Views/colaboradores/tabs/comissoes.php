<?php
/**
 * Aba: Comissões do Colaborador
 */
use App\Core\UI;
$colaboradorId = $colaborador->id ?? null;
$comissoes     = $comissoes ?? [];
?>
<!-- Modal: Nova Regra de Comissão -->
<div class="modal fade" id="modalComissao" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-percent text-primary me-2"></i><span id="modalComissaoTitulo">Nova Regra de Comissão</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="comissaoId" value="">
                <input type="hidden" id="comissaoColaboradorId" value="<?php echo (int)$colaboradorId; ?>">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Descrição <span class="text-danger">*</span></label>
                        <input type="text" id="comissaoDescricao" class="form-control"
                            placeholder="Ex: Comissão sobre faturamento mensal">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo</label>
                        <select id="comissaoTipo" class="form-select" onchange="toggleValorLabel()">
                            <option value="percentual">Percentual (%)</option>
                            <option value="valor_fixo">Valor Fixo (R$)</option>
                            <option value="por_exame">Por Exame (R$)</option>
                            <option value="por_contrato">Por Contrato (R$)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" id="labelValorComissao">Valor (%)</label>
                        <input type="number" id="comissaoValor" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Base de Cálculo</label>
                        <select id="comissaoBase" class="form-select">
                            <option value="faturamento_bruto">Faturamento Bruto</option>
                            <option value="faturamento_liquido">Faturamento Líquido</option>
                            <option value="valor_exame">Valor do Exame</option>
                            <option value="valor_contrato">Valor do Contrato</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Vigência Início</label>
                        <input type="date" id="comissaoVigenciaInicio" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Vigência Fim</label>
                        <input type="date" id="comissaoVigenciaFim" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Ativo</label>
                        <select id="comissaoAtivo" class="form-select">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Observações</label>
                        <textarea id="comissaoObs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="salvarComissao()">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Botão Nova Regra -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-percent text-primary me-2"></i>Regras de Comissão</h5>
    <button type="button" class="btn btn-primary btn-sm fw-bold" onclick="abrirModalComissao()">
        <i class="fas fa-plus me-1"></i> Nova Regra
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $tiposComissao = [
            'percentual'    => 'Percentual',
            'valor_fixo'    => 'Valor Fixo',
            'por_exame'     => 'Por Exame',
            'por_contrato'  => 'Por Contrato',
        ];
        $basesCalculo = [
            'faturamento_bruto'   => 'Fat. Bruto',
            'faturamento_liquido' => 'Fat. Líquido',
            'valor_exame'         => 'Valor Exame',
            'valor_contrato'      => 'Valor Contrato',
        ];
        $headers     = ['Descrição', 'Tipo', 'Valor', 'Base', 'Vigência', 'Ativo', 'Ações'];
        $rowRenderer = function ($com) use ($tiposComissao, $basesCalculo) {
            $desc  = htmlspecialchars($com->descricao ?? '');
            $tipo  = $tiposComissao[$com->tipo] ?? $com->tipo;
            $valor = $com->tipo === 'percentual'
                ? number_format((float)$com->valor, 2, ',', '.') . '%'
                : 'R$ ' . number_format((float)$com->valor, 2, ',', '.');
            $base  = $basesCalculo[$com->base_calculo] ?? $com->base_calculo;
            $vig   = '';
            if ($com->vigencia_inicio) $vig .= date('d/m/Y', strtotime($com->vigencia_inicio));
            if ($com->vigencia_fim)    $vig .= ' — ' . date('d/m/Y', strtotime($com->vigencia_fim));
            $ativo = $com->ativo ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>';
            $json  = htmlspecialchars(json_encode([
                'id'              => $com->id,
                'descricao'       => $com->descricao,
                'tipo'            => $com->tipo,
                'valor'           => $com->valor,
                'base_calculo'    => $com->base_calculo,
                'vigencia_inicio' => $com->vigencia_inicio ?? '',
                'vigencia_fim'    => $com->vigencia_fim ?? '',
                'ativo'           => $com->ativo,
                'observacoes'     => $com->observacoes ?? '',
            ]), ENT_QUOTES);
            $edit = '<button class="btn btn-sm btn-outline-primary me-2" onclick=\'editarComissao(' . $json . ')\' title="Editar"><i class="fas fa-edit"></i></button>';
            $del  = '<button class="btn btn-sm btn-outline-danger" onclick="deletarComissao(' . (int)$com->id . ')" title="Excluir"><i class="fas fa-trash"></i></button>';
            return '<tr>'
                . '<td><strong>' . $desc . '</strong></td>'
                . '<td>' . $tipo . '</td>'
                . '<td>' . $valor . '</td>'
                . '<td>' . $base . '</td>'
                . '<td class="small">' . $vig . '</td>'
                . '<td>' . $ativo . '</td>'
                . '<td class="text-end">' . $edit . $del . '</td>'
                . '</tr>';
        };
        UI::render('table', [
            'headers'      => $headers,
            'items'        => $comissoes,
            'rowRenderer'  => $rowRenderer,
            'emptyMessage' => 'Nenhuma regra de comissão cadastrada.',
        ]);
        ?>
    </div>
</div>

<script>
function toggleValorLabel() {
    const tipo = document.getElementById('comissaoTipo').value;
    document.getElementById('labelValorComissao').textContent = tipo === 'percentual' ? 'Valor (%)' : 'Valor (R$)';
}

function abrirModalComissao() {
    document.getElementById('comissaoId').value = '';
    document.getElementById('comissaoDescricao').value = '';
    document.getElementById('comissaoTipo').value = 'percentual';
    document.getElementById('comissaoValor').value = '0';
    document.getElementById('comissaoBase').value = 'faturamento_bruto';
    document.getElementById('comissaoVigenciaInicio').value = '';
    document.getElementById('comissaoVigenciaFim').value = '';
    document.getElementById('comissaoAtivo').value = '1';
    document.getElementById('comissaoObs').value = '';
    document.getElementById('modalComissaoTitulo').textContent = 'Nova Regra de Comissão';
    toggleValorLabel();
    new bootstrap.Modal(document.getElementById('modalComissao')).show();
}

function editarComissao(data) {
    document.getElementById('comissaoId').value = data.id;
    document.getElementById('comissaoDescricao').value = data.descricao;
    document.getElementById('comissaoTipo').value = data.tipo;
    document.getElementById('comissaoValor').value = data.valor;
    document.getElementById('comissaoBase').value = data.base_calculo;
    document.getElementById('comissaoVigenciaInicio').value = data.vigencia_inicio;
    document.getElementById('comissaoVigenciaFim').value = data.vigencia_fim;
    document.getElementById('comissaoAtivo').value = data.ativo;
    document.getElementById('comissaoObs').value = data.observacoes;
    document.getElementById('modalComissaoTitulo').textContent = 'Editar Regra de Comissão';
    toggleValorLabel();
    new bootstrap.Modal(document.getElementById('modalComissao')).show();
}

function salvarComissao() {
    const id   = document.getElementById('comissaoId').value;
    const colId = document.getElementById('comissaoColaboradorId').value;
    const body = new URLSearchParams({
        colaborador_id:  colId,
        descricao:       document.getElementById('comissaoDescricao').value,
        tipo:            document.getElementById('comissaoTipo').value,
        valor:           document.getElementById('comissaoValor').value,
        base_calculo:    document.getElementById('comissaoBase').value,
        vigencia_inicio: document.getElementById('comissaoVigenciaInicio').value,
        vigencia_fim:    document.getElementById('comissaoVigenciaFim').value,
        ativo:           document.getElementById('comissaoAtivo').value,
        observacoes:     document.getElementById('comissaoObs').value,
    });
    const url = id ? '/colaboradores/comissoes/update/' + id : '/colaboradores/comissoes/store';
    fetch(url, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.success) { window.location.reload(); }
            else { alert('Erro: ' + (data.error || 'Erro desconhecido')); }
        })
        .catch(() => alert('Erro ao salvar.'));
}

function deletarComissao(id) {
    if (!confirm('Excluir esta regra de comissão?')) return;
    fetch('/colaboradores/comissoes/delete/' + id, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) { window.location.reload(); }
            else { alert('Erro: ' + (data.error || 'Erro desconhecido')); }
        })
        .catch(() => alert('Erro ao excluir.'));
}
</script>
