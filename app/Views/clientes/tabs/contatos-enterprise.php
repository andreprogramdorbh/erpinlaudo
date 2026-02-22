<?php
/**
 * ERP InLaudo - Aba de Contatos do Formulário de Clientes (Enterprise Layout)
 * Conteúdo da aba de gestão de contatos
 */

// Verifica se há contatos para exibir
$hasContatos = !empty($contatos) && is_array($contatos);
?>

<!-- Cabeçalho da Seção -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="form-section-title mb-0">
            <i class="fas fa-users section-icon"></i>
            Pessoas de Contato
        </h3>
        <p class="form-help mb-0">Gerencie as pessoas de contato deste cliente</p>
    </div>

    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalContato">
        <i class="fas fa-plus me-1"></i>
        Adicionar Contato
    </button>
</div>

<!-- Tabela de Contatos -->
<div class="form-table-container">
    <table class="form-table" id="tabelaContatos">
        <thead>
            <tr>
                <th width="20%">Tipo</th>
                <th width="30%">Contato</th>
                <th width="35%">Observações</th>
                <th width="15%" class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$hasContatos): ?>
                <tr class="empty-row">
                    <td colspan="4" class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Nenhum contato cadastrado para este cliente.</p>
                            <small class="text-muted">Clique em "Adicionar Contato" para começar.</small>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($contatos as $contato): ?>
                    <tr id="contato-<?php echo $contato->id; ?>">
                        <td>
                            <span class="form-badge primary">
                                <?php echo htmlspecialchars($contato->departamento ?: 'Geral'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="contact-info">
                                <strong><?php echo htmlspecialchars($contato->nome); ?></strong>
                                <?php if (!empty($contato->email)): ?>
                                    <div class="small text-muted">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($contato->email); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($contato->telefone)): ?>
                                    <div class="small text-muted">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($contato->telefone); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($contato->celular)): ?>
                                    <div class="small text-muted">
                                        <i class="fas fa-mobile-alt me-1"></i>
                                        <?php echo htmlspecialchars($contato->celular); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted small">
                                <?php echo htmlspecialchars($contato->observacoes ?: '-'); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" title="Editar contato"
                                    onclick="editContact(<?php echo $contato->id; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0" title="Remover contato"
                                    onclick="removerContato(<?php echo $contato->id; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Adicionar/Editar Contato -->
<div class="modal fade" id="modalContato" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-plus me-2"></i>
                    <span id="modalTitle">Novo Contato</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="formAddContato" novalidate>
                    <input type="hidden" name="cliente_id" value="<?php echo (int) ($cliente->id ?? 0); ?>">
                    <input type="hidden" name="contato_id" id="contato_id" value="">

                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="nome_contato" class="form-label required">Nome do Contato</label>
                            <input type="text" name="nome" id="nome_contato" class="form-control"
                                placeholder="Nome completo" required>
                        </div>

                        <div class="form-group">
                            <label for="departamento_contato" class="form-label">Departamento</label>
                            <input type="text" name="departamento" id="departamento_contato" class="form-control"
                                placeholder="Ex: Financeiro, Compras...">
                        </div>
                    </div>

                    <div class="form-grid form-grid-3 mt-3">
                        <div class="form-group">
                            <label for="email_contato" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email_contato" class="form-control"
                                placeholder="email@empresa.com">
                        </div>

                        <div class="form-group">
                            <label for="celular_contato" class="form-label">Celular</label>
                            <input type="text" name="celular" id="celular_contato" class="form-control"
                                placeholder="(00) 00000-0000">
                        </div>

                        <div class="form-group">
                            <label for="telefone_contato" class="form-label">Telefone Fixo</label>
                            <input type="text" name="telefone" id="telefone_contato" class="form-control"
                                placeholder="(00) 0000-0000">
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label for="observacoes_contato" class="form-label">Observações</label>
                        <textarea name="observacoes" id="observacoes_contato" class="form-control" rows="3"
                            placeholder="Informações adicionais..."></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnSalvarContato">
                    <i class="fas fa-save me-1"></i> Salvar Contato
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos desta aba -->
<style>
    .contact-info {
        line-height: 1.4;
    }

    .contact-info .small {
        display: block;
        margin-top: 0.25rem;
    }

    .empty-state {
        padding: 2rem 1rem;
    }

    .form-table-container {
        background: white;
        border-radius: var(--form-radius);
        border: 1px solid var(--form-gray-200);
        overflow: hidden;
    }

    .btn-group .btn {
        border-radius: 0;
    }

    .btn-group .btn:first-child {
        border-top-left-radius: var(--form-radius);
        border-bottom-left-radius: var(--form-radius);
    }

    .btn-group .btn:last-child {
        border-top-right-radius: var(--form-radius);
        border-bottom-right-radius: var(--form-radius);
    }

    @media (max-width: 768px) {
        .form-table {
            font-size: 0.875rem;
        }

        .btn-group {
            flex-direction: column;
            width: 100%;
        }

        .btn-group .btn {
            border-radius: var(--form-radius);
            margin-bottom: 0.25rem;
        }
    }
</style>

<!-- Script específico para gestão de contatos -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Reset do modal ao fechar
        const modal = document.getElementById('modalContato');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('formAddContato').reset();
                document.getElementById('modalTitle').textContent = 'Novo Contato';
                document.getElementById('contato_id').value = '';

                // Remove classes de validação
                const inputs = modal.querySelectorAll('.form-control, .form-select');
                inputs.forEach(input => {
                    input.classList.remove('is-valid', 'is-invalid');
                });
            });
        }

        // Validação do formulário de contato ao submeter via Enter
        const formContato = document.getElementById('formAddContato');
        if (formContato) {
            formContato.addEventListener('submit', function (e) {
                e.preventDefault();
                // Delega ao botão principal que já detecta se é novo ou edição
                document.getElementById('btnSalvarContato')?.click();
            });
        }

        // Funções auxiliares
        function showFieldError(input, message) {
            hideFieldError(input);
            input.classList.add('is-invalid');

            const feedback = document.createElement('div');
            feedback.className = 'form-feedback invalid';
            feedback.textContent = message;

            input.parentNode.insertBefore(feedback, input.nextSibling);
        }

        function hideFieldError(input) {
            input.classList.remove('is-invalid');
            const feedback = input.parentNode.querySelector('.form-feedback.invalid');
            if (feedback) {
                feedback.remove();
            }
        }
    });

    // editContact é exposto globalmente pelo ClientesForm (clientes-form.js)
    // A função abaixo serve como fallback caso o JS principal ainda não tenha carregado
    if (typeof window.editContact === 'undefined') {
        window.editContact = function (contactId) {
            console.warn('ClientesForm ainda não inicializado. Tentando novamente...');
            setTimeout(() => {
                if (window.clientesFormInstance) {
                    window.clientesFormInstance.loadContactForEdit(contactId);
                } else {
                    alert('Erro: o módulo de contatos não foi carregado. Recarregue a página.');
                }
            }, 500);
        };
    }
</script>