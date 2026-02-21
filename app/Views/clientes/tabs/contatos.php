<div class="card border-0 shadow-sm">
    <div class="card-body p-4 p-lg-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-primary fw-bold mb-0"><i class="fas fa-users me-2"></i> Pessoas de Contato</h5>
            <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalContato">
                <i class="fas fa-plus me-1"></i> Adicionar Contato
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border-top" id="tabelaContatos">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3">Tipo</th>
                        <th class="py-3">Contato (Valor)</th>
                        <th class="py-3">Observações</th>
                        <th class="py-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contatos)): ?>
                        <tr class="empty-row text-center">
                            <td colspan="4" class="py-5 text-muted small">Nenhum contato cadastrado para este cliente.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contatos as $contato): ?>
                            <tr id="contato-<?php echo $contato->id; ?>">
                                <td><span
                                        class="badge bg-soft-primary text-primary"><?php echo htmlspecialchars($contato->departamento ?: 'Geral'); ?></span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($contato->nome); ?></strong></td>
                                <td><small
                                        class="text-muted"><?php echo htmlspecialchars($contato->observacoes ?: '-'); ?></small>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger border-0"
                                        onclick="removerContato(<?php echo $contato->id; ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-5 pt-4 border-top">
            <button class="btn btn-lg btn-light px-4 fw-bold" onclick="document.getElementById('geral-tab').click()">
                <i class="fas fa-arrow-left me-2"></i> Voltar para Geral
            </button>
            <a href="/clientes" class="btn btn-success btn-lg px-5 fw-bold">
                Concluir Cadastro <i class="fas fa-check-circle ms-2"></i>
            </a>
        </div>
    </div>
</div>

<!-- Modal Adicionar Contato -->
<div class="modal fade" id="modalContato" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Novo Contato</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formAddContato">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente->id; ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tipo de Contato</label>
                        <select name="tipo_contato" id="tipo_contato_sel" class="form-select" required>
                            <option value="Celular">Celular</option>
                            <option value="Email">Email</option>
                            <option value="Comercial">Comercial (Fixo)</option>
                            <option value="Residencial">Residencial</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Valor (Número ou E-mail)</label>
                        <input type="text" name="valor_contato" id="valor_contato" class="form-control" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnSalvarContato">Salvar Contato</button>
            </div>
        </div>
    </div>
</div>