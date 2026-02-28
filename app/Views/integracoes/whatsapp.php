<?php
/**
 * ERP InLaudo - Configuração Integração Bot WhatsApp
 */
$config   = $config ?? null;
$logs     = $logs ?? [];
$apiKey   = $apiKey ?? null;
$apiUrl   = $apiUrl ?? '';
?>

<div class="row">
    <div class="col-12">

        <!-- Card: Configuração do Token -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 fw-bold">
                            <i class="fab fa-whatsapp text-success me-2"></i>
                            Integração Bot WhatsApp
                        </h5>
                        <p class="text-muted small mb-0">
                            Configure o token de acesso para o chatbot WhatsApp (Baileys) se comunicar com a API do ERP.
                        </p>
                    </div>
                    <div class="col-auto">
                        <?php if (!empty($config)): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                <i class="fas fa-circle me-1" style="font-size:.5rem"></i> Integração Ativa
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
                                <i class="fas fa-circle me-1" style="font-size:.5rem"></i> Não Configurada
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">

                <!-- Alerta de nova chave gerada -->
                <?php if (!empty($apiKey)): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-key text-success me-3 mt-1" style="font-size:1.2rem"></i>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">Nova API Key gerada com sucesso!</h6>
                                <p class="mb-2 small text-muted">
                                    <strong>Copie agora</strong> — esta chave não será exibida novamente por segurança.
                                </p>
                                <div class="input-group">
                                    <input type="text"
                                           id="newApiKeyDisplay"
                                           class="form-control font-monospace"
                                           value="<?php echo htmlspecialchars($apiKey); ?>"
                                           readonly>
                                    <button class="btn btn-success" type="button" onclick="copyApiKey()">
                                        <i class="fas fa-copy me-1"></i> Copiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Informações da integração atual -->
                <?php if (!empty($config)): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">API Key Atual</label>
                            <div class="input-group">
                                <input type="password"
                                       id="currentApiKey"
                                       class="form-control font-monospace"
                                       value="<?php echo str_repeat('•', 40); ?>"
                                       readonly>
                                <span class="input-group-text bg-light text-muted">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                            <div class="form-text">Chave configurada em <?php echo date('d/m/Y', strtotime($config->updated_at ?? $config->created_at ?? 'now')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">URL Base da API</label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control font-monospace"
                                       value="<?php echo htmlspecialchars($apiUrl); ?>"
                                       readonly>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($apiUrl); ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="form-text">Use esta URL no arquivo <code>.env</code> do chatbot.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Endpoints disponíveis -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary mb-3">
                        <i class="fas fa-route me-2"></i>Endpoints Disponíveis
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Método</th>
                                    <th>Endpoint</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-primary">POST</span></td>
                                    <td><code>/api/v1/whatsapp/identificar</code></td>
                                    <td>Identifica o cliente pelo número de telefone</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">POST</span></td>
                                    <td><code>/api/v1/whatsapp/resumo</code></td>
                                    <td>Retorna resumo financeiro do cliente</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">POST</span></td>
                                    <td><code>/api/v1/whatsapp/faturas</code></td>
                                    <td>Lista faturas em aberto, vencidas ou pagas</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">POST</span></td>
                                    <td><code>/api/v1/whatsapp/notas-fiscais</code></td>
                                    <td>Lista notas fiscais emitidas</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info border-0 small py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Todas as requisições devem incluir o cabeçalho <code>X-API-Key: SUA_CHAVE</code>.
                    </div>
                </div>

                <!-- Botões de ação -->
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" id="btnGerarToken" class="btn btn-success">
                        <i class="fas fa-key me-2"></i>
                        <?php echo !empty($config) ? 'Regenerar API Key' : 'Gerar API Key'; ?>
                    </button>
                    <?php if (!empty($config)): ?>
                        <button type="button" id="btnRevogarToken" class="btn btn-outline-danger">
                            <i class="fas fa-ban me-2"></i> Revogar Integração
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card: Logs de Acesso -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-list-alt text-primary me-2"></i>
                            Logs de Acesso do Bot
                        </h5>
                        <p class="text-muted small mb-0">Últimas 50 consultas realizadas pelo chatbot</p>
                    </div>
                    <div class="col-auto">
                        <a href="/integracao/whatsapp/logs/export" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i> Exportar CSV
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <p class="mb-0">Nenhum log registrado ainda.</p>
                        <small>Os logs aparecerão aqui após o chatbot realizar as primeiras consultas.</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Telefone</th>
                                    <th>Endpoint</th>
                                    <th>Intenção</th>
                                    <th>Status</th>
                                    <th>Resumo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-nowrap small">
                                            <?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?>
                                        </td>
                                        <td class="font-monospace small">
                                            <?php echo htmlspecialchars($log->telefone_hash); ?>
                                        </td>
                                        <td class="small">
                                            <code><?php echo htmlspecialchars($log->endpoint); ?></code>
                                        </td>
                                        <td class="small">
                                            <?php echo htmlspecialchars($log->intent); ?>
                                        </td>
                                        <td>
                                            <?php if ($log->status === 'success'): ?>
                                                <span class="badge bg-success-subtle text-success">Sucesso</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger">Erro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo htmlspecialchars($log->summary); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function copyApiKey() {
    const input = document.getElementById('newApiKeyDisplay');
    if (input) {
        navigator.clipboard.writeText(input.value).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copiado!',
                text: 'A API Key foi copiada para a área de transferência.',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }
}

document.getElementById('btnGerarToken')?.addEventListener('click', function () {
    Swal.fire({
        title: 'Gerar nova API Key?',
        html: '<?php echo !empty($config) ? '<strong>Atenção:</strong> A chave atual será invalidada imediatamente. O chatbot precisará ser atualizado com a nova chave.' : 'Será gerada uma chave secreta para autenticar o chatbot WhatsApp.'; ?>',
        icon: '<?php echo !empty($config) ? 'warning' : 'question'; ?>',
        showCancelButton: true,
        confirmButtonText: 'Sim, gerar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/integracao/whatsapp/gerar-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    Swal.fire('Erro', data.message || 'Falha ao gerar token.', 'error');
                }
            })
            .catch(() => Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error'));
        }
    });
});

document.getElementById('btnRevogarToken')?.addEventListener('click', function () {
    Swal.fire({
        title: 'Revogar integração?',
        html: '<strong>Atenção:</strong> O chatbot perderá acesso imediatamente. Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, revogar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/integracao/whatsapp/revogar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Revogado!', 'A integração foi desativada.', 'success')
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Erro', data.message || 'Falha ao revogar.', 'error');
                }
            })
            .catch(() => Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error'));
        }
    });
});
</script>
