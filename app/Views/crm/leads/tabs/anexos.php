<?php
/**
 * ERP InLaudo - Aba Anexos do Lead
 */
?>
<style>
.anx-form-card{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.5rem;padding:1.25rem;margin-bottom:1.5rem}
.anx-form-card h3{font-size:.9rem;font-weight:600;color:#16a34a;margin-bottom:1rem}
.anx-table{width:100%;border-collapse:collapse;font-size:.875rem}
.anx-table th{background:#f8fafc;font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em;padding:.6rem .75rem;border-bottom:2px solid #e2e8f0;text-align:left}
.anx-table td{padding:.7rem .75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.anx-table tr:last-child td{border-bottom:none}
.anx-table tr:hover td{background:#f8fafc}
.anx-tipo-badge{font-size:.7rem;font-weight:600;padding:.2em .6em;border-radius:10px;white-space:nowrap}
.anx-tipo-contrato{background:#dbeafe;color:#1d4ed8}
.anx-tipo-termo_aceite{background:#dcfce7;color:#15803d}
.anx-tipo-proposta_comercial{background:#fef9c3;color:#854d0e}
.anx-tipo-edital{background:#fee2e2;color:#b91c1c}
.anx-tipo-outro{background:#f1f5f9;color:#475569}
.anx-size{font-size:.75rem;color:#94a3b8}
</style>

<?php if (!empty($_GET['success']) && $_GET['success'] === 'anexo_salvo'): ?>
<div class="alert alert-success alert-dismissible fade show mb-3">
  <i class="fas fa-check-circle me-2"></i> Documento anexado com sucesso!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
<?php $erros = [
  'nome_obrigatorio'  => 'O nome do documento é obrigatório.',
  'upload_failed'     => 'Falha ao enviar o arquivo. Tente novamente.',
  'file_too_large'    => 'Arquivo muito grande. Limite: 10 MB.',
  'invalid_file_type' => 'Tipo de arquivo não permitido. Use PDF, Word, Excel ou imagem.',
  'db_failure'        => 'Erro ao salvar no banco de dados.',
]; ?>
<div class="alert alert-danger alert-dismissible fade show mb-3">
  <i class="fas fa-exclamation-circle me-2"></i>
  <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'Erro ao processar o anexo.'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Formulário de novo anexo -->
<div class="anx-form-card">
  <h3><i class="fas fa-upload me-1"></i> Anexar Novo Documento</h3>
  <form method="POST" action="/crm/leads/anexo/upload" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <input type="hidden" name="related_id" value="<?php echo (int)($lead->id ?? 0); ?>">
    <div class="form-grid form-grid-3">
      <div class="form-group">
        <label class="form-label required">Nome do Documento</label>
        <input type="text" name="nome_documento" class="form-control"
               placeholder="Ex: Proposta Comercial v2" required maxlength="255">
      </div>
      <div class="form-group">
        <label class="form-label required">Tipo</label>
        <select name="tipo_documento" class="form-select" required>
          <?php foreach ($tiposAnexo as $k => $v): ?>
          <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label required">Arquivo</label>
        <input type="file" name="arquivo" class="form-control" required
               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
        <div class="form-text">PDF, Word, Excel ou imagem. Máx: 10 MB.</div>
      </div>
    </div>
    <div class="d-flex justify-content-end mt-2">
      <button type="submit" class="btn btn-success">
        <i class="fas fa-upload me-1"></i> Salvar Documento
      </button>
    </div>
  </form>
</div>

<!-- Lista de anexos -->
<?php if (empty($anexos)): ?>
<div class="text-center py-4 text-muted">
  <i class="fas fa-paperclip fa-2x mb-2 d-block"></i>
  Nenhum documento anexado ainda. Use o formulário acima para adicionar.
</div>
<?php else: ?>
<div class="table-responsive">
  <table class="anx-table">
    <thead>
      <tr>
        <th>Documento</th>
        <th>Tipo</th>
        <th>Arquivo</th>
        <th>Tamanho</th>
        <th>Salvo em</th>
        <th>Por</th>
        <th style="width:80px">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($anexos as $anx): ?>
      <?php
        $tipoKey   = $anx->tipo_documento ?? 'outro';
        $tipoLabel = $tiposAnexo[$tipoKey] ?? 'Outro';
        $iconeAnx  = $iconesAnexo[$tipoKey] ?? 'fa-paperclip text-muted';
        $sizeHuman = '';
        if ($anx->file_size) {
          $sizeHuman = $anx->file_size >= 1048576
            ? round($anx->file_size / 1048576, 1) . ' MB'
            : round($anx->file_size / 1024, 0) . ' KB';
        }
      ?>
      <tr id="anx-<?php echo $anx->id; ?>">
        <td>
          <i class="fas <?php echo explode(' ', $iconeAnx)[0]; ?> me-2 <?php echo explode(' ', $iconeAnx)[1] ?? ''; ?>"></i>
          <strong><?php echo htmlspecialchars($anx->nome_documento); ?></strong>
        </td>
        <td>
          <span class="anx-tipo-badge anx-tipo-<?php echo htmlspecialchars($tipoKey); ?>">
            <?php echo htmlspecialchars($tipoLabel); ?>
          </span>
        </td>
        <td><span class="text-muted" style="font-size:.8rem"><?php echo htmlspecialchars($anx->original_name); ?></span></td>
        <td><span class="anx-size"><?php echo $sizeHuman ?: '—'; ?></span></td>
        <td style="white-space:nowrap;font-size:.8rem;color:#64748b">
          <i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($anx->created_at)); ?>
        </td>
        <td style="font-size:.8rem;color:#64748b"><?php echo htmlspecialchars($anx->usuario_nome ?? 'Sistema'); ?></td>
        <td>
          <div class="d-flex gap-1">
            <a href="/crm/leads/anexo/download/<?php echo $anx->id; ?>" class="btn btn-sm btn-outline-primary" title="Baixar">
              <i class="fas fa-download"></i>
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir"
                    onclick="deletarAnexo(<?php echo $anx->id; ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
function deletarAnexo(id) {
  if (!confirm('Excluir este documento? Esta ação não pode ser desfeita.')) return;
  const form = new FormData();
  form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
    || '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>');
  fetch('/crm/leads/anexo/delete/' + id, { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const el = document.getElementById('anx-' + id);
        if (el) el.remove();
      } else {
        alert(res.error || 'Erro ao excluir o documento.');
      }
    })
    .catch(() => alert('Erro de conexão.'));
}
</script>
