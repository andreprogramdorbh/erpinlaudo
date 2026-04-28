<?php
/**
 * ERP InLaudo - Configuração E-mail (Enterprise Layout)
 * Abas: Configuração SMTP | Alertas
 */

$config          = $config ?? [];
$cryptoOk        = $crypto_configured ?? false;
$alertas         = $alertas ?? ['financeiro' => [], 'faturamento' => [], 'crm' => [], 'corpo_clinico' => []];

// Labels dos módulos
$moduloLabels = [
    'financeiro'    => ['label' => 'Financeiro',    'icon' => 'fa-coins',        'cor' => 'success'],
    'faturamento'   => ['label' => 'Faturamento',   'icon' => 'fa-file-invoice', 'cor' => 'primary'],
    'crm'           => ['label' => 'CRM',           'icon' => 'fa-users',        'cor' => 'warning'],
    'corpo_clinico' => ['label' => 'Corpo Clínico', 'icon' => 'fa-user-md',      'cor' => 'info'],
];
?>

<?php if (!$cryptoOk): ?>
<!-- ALERTA: Criptografia não configurada -->
<div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-start gap-3" id="alertaCriptografia" role="alert">
    <div class="flex-shrink-0 mt-1">
        <i class="fas fa-shield-alt fa-lg text-danger"></i>
    </div>
    <div class="flex-grow-1">
        <h6 class="alert-heading fw-bold mb-1">Criptografia não configurada — envio de e-mail bloqueado</h6>
        <p class="mb-2 small">
            A variável <code>APP_KEY</code> não está definida no arquivo <code>.env</code> do servidor.
            Sem ela, a senha SMTP <strong>não pode ser salva com segurança</strong> e o envio de e-mails ficará indisponível.
        </p>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" id="btnGerarChave" class="btn btn-sm btn-danger">
                <i class="fas fa-key me-1"></i> Gerar nova APP_KEY
            </button>
            <div id="chaveGeradaWrapper" class="d-none input-group" style="max-width: 520px;">
                <input type="text" id="chaveGerada" class="form-control form-control-sm font-monospace" readonly>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnCopiarChave" title="Copiar chave">
                    <i class="fas fa-copy" id="iconCopiar"></i>
                </button>
            </div>
            <span id="msgCopiado" class="text-success small d-none fw-semibold">
                <i class="fas fa-check me-1"></i>Copiado!
            </span>
        </div>
        <p class="mb-0 mt-2 small text-muted">
            Após adicionar a chave ao <code>.env</code>, reinicie o servidor e recarregue esta página.
        </p>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center gap-2 py-2" role="alert">
    <i class="fas fa-shield-alt text-success"></i>
    <span class="small fw-semibold">Criptografia ativa — a senha SMTP será armazenada com segurança (AES-256-GCM).</span>
</div>
<?php endif; ?>

<!-- ============================================================
     ABAS: Configuração SMTP | Alertas
     ============================================================ -->
<ul class="nav nav-tabs mb-4" id="emailTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="tab-smtp" data-bs-toggle="tab"
                data-bs-target="#pane-smtp" type="button" role="tab">
            <i class="fas fa-server me-2"></i>Configuração SMTP
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-alertas" data-bs-toggle="tab"
                data-bs-target="#pane-alertas" type="button" role="tab">
            <i class="fas fa-bell me-2"></i>Alertas
            <?php
            $totalAtivos = 0;
            foreach ($alertas as $mod) {
                foreach ($mod as $a) {
                    if ($a->ativo) $totalAtivos++;
                }
            }
            if ($totalAtivos > 0):
            ?>
            <span class="badge bg-primary ms-1"><?php echo $totalAtivos; ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="emailTabsContent">

    <!-- ============================================================
         ABA 1: CONFIGURAÇÃO SMTP
         ============================================================ -->
    <div class="tab-pane fade show active" id="pane-smtp" role="tabpanel">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0 fw-bold">Configuração de E-mail (SMTP)</h5>
                                <p class="text-muted small mb-0">Configure o envio transacional do sistema (alertas, tokens e testes)</p>
                            </div>
                            <div class="col-auto">
                                <button type="button" id="btnTestarEmail" class="btn btn-outline-primary btn-sm"
                                    <?php echo !$cryptoOk ? 'disabled title="Configure a criptografia antes de testar"' : ''; ?>>
                                    <i class="fas fa-paper-plane me-1"></i> Enviar E-mail de Teste
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <form id="formEmailConfig" novalidate>
                            <div class="row g-4">
                                <div class="col-12">
                                    <h6 class="fw-bold text-primary mb-3">
                                        <i class="fas fa-server me-2"></i>Servidor SMTP
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="host" class="form-label">Host <span class="text-danger">*</span></label>
                                            <input type="text" name="host" id="host" class="form-control" required
                                                   value="<?php echo htmlspecialchars((string)($config['host'] ?? '')); ?>"
                                                   placeholder="smtp.seudominio.com">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="port" class="form-label">Porta <span class="text-danger">*</span></label>
                                            <input type="number" name="port" id="port" class="form-control" required
                                                   value="<?php echo htmlspecialchars((string)($config['port'] ?? 587)); ?>"
                                                   min="1" max="65535">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="protocol" class="form-label">Protocolo <span class="text-danger">*</span></label>
                                            <select name="protocol" id="protocol" class="form-select" required>
                                                <option value="tls" <?php echo (($config['protocol'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>>TLS (STARTTLS)</option>
                                                <option value="ssl" <?php echo (($config['protocol'] ?? '') === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 text-light">

                                <div class="col-12">
                                    <h6 class="fw-bold text-primary mb-3">
                                        <i class="fas fa-key me-2"></i>Autenticação
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Usuário <span class="text-danger">*</span></label>
                                            <input type="text" name="username" id="username" class="form-control" required
                                                   value="<?php echo htmlspecialchars((string)($config['username'] ?? '')); ?>"
                                                   placeholder="usuario@seudominio.com">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">
                                                Senha
                                                <?php if (!empty($config['password_enc'])): ?>
                                                    <span class="badge bg-success ms-1" style="font-size:0.7rem;">
                                                        <i class="fas fa-lock me-1"></i>Configurada
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                            <div class="input-group">
                                                <input type="password" name="password" id="password" class="form-control"
                                                       value="<?php echo !empty($config['password_enc']) ? '********' : ''; ?>"
                                                       placeholder="<?php echo !empty($config['password_enc']) ? '(mantém a atual se não alterar)' : 'Senha de App Google (16 caracteres)'; ?>"
                                                       <?php echo !$cryptoOk ? 'disabled' : ''; ?>>
                                                <button class="btn btn-outline-secondary" type="button" id="btnToggleSenha"
                                                        title="Mostrar/ocultar senha" <?php echo !$cryptoOk ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-eye" id="iconSenha"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">
                                                <?php if (!$cryptoOk): ?>
                                                    <span class="text-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Configure a <code>APP_KEY</code> no <code>.env</code> para habilitar o campo de senha.
                                                    </span>
                                                <?php else: ?>
                                                    A senha é armazenada criptografada no banco (AES-256-GCM).
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 text-light">

                                <div class="col-12">
                                    <h6 class="fw-bold text-primary mb-3">
                                        <i class="fas fa-at me-2"></i>Remetente
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="from_email" class="form-label">From (E-mail)</label>
                                            <input type="email" name="from_email" id="from_email" class="form-control"
                                                   value="<?php echo htmlspecialchars((string)($config['from_email'] ?? '')); ?>"
                                                   placeholder="noreply@seudominio.com">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="from_name" class="form-label">From (Nome)</label>
                                            <input type="text" name="from_name" id="from_name" class="form-control"
                                                   value="<?php echo htmlspecialchars((string)($config['from_name'] ?? 'ERP InLaudo')); ?>"
                                                   placeholder="ERP InLaudo">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="status" class="form-label">Status</label>
                                            <select name="status" id="status" class="form-select">
                                                <option value="active" <?php echo (($config['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Ativo</option>
                                                <option value="inactive" <?php echo (($config['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-5">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="/configuracoes" class="btn btn-light px-4">Voltar</a>
                                        <button type="submit" class="btn btn-primary px-5"
                                                <?php echo !$cryptoOk ? 'disabled title="Configure a criptografia antes de salvar"' : ''; ?>>
                                            <i class="fas fa-save me-2"></i>Salvar Alterações
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /pane-smtp -->


    <!-- ============================================================
         ABA 2: ALERTAS
         ============================================================ -->
    <div class="tab-pane fade" id="pane-alertas" role="tabpanel">

        <?php if (!$cryptoOk): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Configure a criptografia (aba SMTP) antes de ativar alertas.</span>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Alertas por E-mail</h5>
                <p class="text-muted small mb-0">Gerencie os disparos automáticos por módulo do sistema</p>
            </div>
        </div>

        <?php foreach ($moduloLabels as $moduloKey => $moduloInfo): ?>
        <?php $listaAlertas = $alertas[$moduloKey] ?? []; ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?php echo $moduloInfo['cor']; ?> rounded-pill px-3 py-2">
                        <i class="fas <?php echo $moduloInfo['icon']; ?> me-1"></i>
                        <?php echo $moduloInfo['label']; ?>
                    </span>
                    <span class="text-muted small">
                        <?php
                        $ativos = array_filter($listaAlertas, fn($a) => $a->ativo);
                        echo count($ativos) . ' de ' . count($listaAlertas) . ' alertas ativos';
                        ?>
                    </span>
                </div>
            </div>

            <?php if (empty($listaAlertas)): ?>
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-bell-slash fa-2x mb-2 opacity-25"></i>
                <p class="mb-0 small">Nenhum alerta configurado para este módulo.</p>
                <p class="small">Execute a migration <code>2026-03-15_email_alertas.sql</code> para carregar os padrões.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">Status</th>
                            <th>Alerta</th>
                            <th style="width:130px">Antecedência</th>
                            <th style="width:110px">Frequência</th>
                            <th style="width:90px">Hora</th>
                            <th style="width:160px">Destinatários</th>
                            <th style="width:120px">Último Disparo</th>
                            <th style="width:100px" class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($listaAlertas as $alerta): ?>
                    <tr id="row-alerta-<?php echo $alerta->id; ?>" class="<?php echo !$alerta->ativo ? 'opacity-50' : ''; ?>">
                        <td>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input toggle-alerta" type="checkbox"
                                       data-id="<?php echo $alerta->id; ?>"
                                       <?php echo $alerta->ativo ? 'checked' : ''; ?>
                                       <?php echo !$cryptoOk ? 'disabled' : ''; ?>
                                       title="<?php echo $alerta->ativo ? 'Desativar' : 'Ativar'; ?> alerta">
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold small"><?php echo htmlspecialchars($alerta->nome); ?></div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                <?php echo htmlspecialchars($alerta->descricao ?? ''); ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $dias = (int) $alerta->antecedencia_dias;
                            if ($dias > 0) {
                                echo "<span class='badge bg-info text-dark'>{$dias} dia(s) antes</span>";
                            } elseif ($dias === 0) {
                                echo "<span class='badge bg-secondary'>No dia</span>";
                            } else {
                                echo "<span class='badge bg-danger'>" . abs($dias) . " dia(s) após</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $freqLabels = ['unico' => 'Único', 'diario' => 'Diário', 'semanal' => 'Semanal'];
                            $freqCores  = ['unico' => 'secondary', 'diario' => 'primary', 'semanal' => 'warning'];
                            $freq = $alerta->frequencia ?? 'unico';
                            echo "<span class='badge bg-{$freqCores[$freq]}'>" . ($freqLabels[$freq] ?? $freq) . "</span>";
                            ?>
                        </td>
                        <td>
                            <span class="font-monospace small"><?php echo htmlspecialchars(substr($alerta->hora_disparo ?? '08:00:00', 0, 5)); ?></span>
                        </td>
                        <td>
                            <?php
                            $dests = json_decode($alerta->destinatarios ?? '[]', true) ?: [];
                            $destLabels = [
                                'admin'      => '<span class="badge bg-dark me-1">Admin</span>',
                                'financeiro' => '<span class="badge bg-success me-1">Financeiro</span>',
                                'vendedor'   => '<span class="badge bg-warning text-dark me-1">Vendedor</span>',
                                'cliente'    => '<span class="badge bg-info text-dark me-1">Cliente</span>',
                            ];
                            foreach ($dests as $d) {
                                echo $destLabels[$d] ?? "<span class='badge bg-secondary me-1'>" . htmlspecialchars($d) . "</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <span class="small text-muted">
                                <?php
                                if (!empty($alerta->ultimo_disparo)) {
                                    echo date('d/m/Y H:i', strtotime($alerta->ultimo_disparo));
                                } else {
                                    echo '<em>Nunca</em>';
                                }
                                ?>
                            </span>
                            <?php if ($alerta->total_disparos > 0): ?>
                            <div class="text-muted" style="font-size:0.72rem;"><?php echo $alerta->total_disparos; ?> disparo(s)</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-editar-alerta me-1"
                                    data-id="<?php echo $alerta->id; ?>"
                                    data-alerta='<?php echo htmlspecialchars(json_encode($alerta), ENT_QUOTES); ?>'
                                    title="Editar configurações">
                                <i class="fas fa-cog"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-disparar-alerta"
                                    data-id="<?php echo $alerta->id; ?>"
                                    data-nome="<?php echo htmlspecialchars($alerta->nome); ?>"
                                    title="Disparar manualmente"
                                    <?php echo !$cryptoOk ? 'disabled' : ''; ?>>
                                <i class="fas fa-play"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div><!-- /pane-alertas -->

</div><!-- /tab-content -->


<!-- ============================================================
     MODAL: Editar Alerta
     ============================================================ -->
<div class="modal fade" id="modalEditarAlerta" tabindex="-1" aria-labelledby="modalEditarAlertaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalEditarAlertaLabel">
                    <i class="fas fa-bell me-2 text-primary"></i>Configurar Alerta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarAlerta" novalidate>
                    <input type="hidden" name="codigo" id="ea_codigo">
                    <input type="hidden" name="modulo" id="ea_modulo">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nome do Alerta</label>
                            <input type="text" name="nome" id="ea_nome" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea name="descricao" id="ea_descricao" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Antecedência (dias)</label>
                            <input type="number" name="antecedencia_dias" id="ea_antecedencia" class="form-control" min="-365" max="365">
                            <div class="form-text">Positivo = antes do vencimento. Negativo = após vencimento.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frequência</label>
                            <select name="frequencia" id="ea_frequencia" class="form-select">
                                <option value="unico">Único (dispara uma vez)</option>
                                <option value="diario">Diário (enquanto condição persistir)</option>
                                <option value="semanal">Semanal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Hora do Disparo</label>
                            <input type="time" name="hora_disparo" id="ea_hora" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Destinatários</label>
                            <div class="d-flex flex-wrap gap-2 mb-2" id="ea_dest_checkboxes">
                                <?php
                                $destOpcoes = [
                                    'admin'      => ['label' => 'Admin / Gestão',    'cor' => 'dark'],
                                    'financeiro' => ['label' => 'Equipe Financeiro', 'cor' => 'success'],
                                    'vendedor'   => ['label' => 'Vendedor Responsável', 'cor' => 'warning'],
                                    'cliente'    => ['label' => 'Cliente',           'cor' => 'info'],
                                ];
                                foreach ($destOpcoes as $val => $opt):
                                ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input dest-check" type="checkbox"
                                           id="dest_<?php echo $val; ?>" value="<?php echo $val; ?>">
                                    <label class="form-check-label" for="dest_<?php echo $val; ?>">
                                        <span class="badge bg-<?php echo $opt['cor']; ?>"><?php echo $opt['label']; ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="destinatarios" id="ea_destinatarios">
                            <div class="form-text">Adicione e-mails extras separados por vírgula:</div>
                            <input type="text" id="ea_dest_extra" class="form-control form-control-sm mt-1"
                                   placeholder="extra@email.com, outro@email.com">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Cópia (CC)</label>
                            <input type="text" name="cc" id="ea_cc" class="form-control"
                                   placeholder="copia@email.com, outro@email.com">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Assunto do E-mail</label>
                            <input type="text" name="assunto_template" id="ea_assunto" class="form-control" required>
                            <div class="form-text">
                                Variáveis: <code>{cliente}</code> <code>{fornecedor}</code> <code>{valor}</code>
                                <code>{dias}</code> <code>{vencimento}</code> <code>{lead}</code>
                                <code>{oportunidade}</code> <code>{vendedor}</code>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Corpo do E-mail (HTML)</label>
                            <textarea name="corpo_template" id="ea_corpo" class="form-control font-monospace"
                                      rows="8" style="font-size:0.82rem;"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ea_ativo" value="1">
                                <label class="form-check-label fw-semibold" for="ea_ativo">Alerta Ativo</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarAlerta">
                    <i class="fas fa-save me-2"></i>Salvar Alerta
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Toggle visibilidade da senha ─────────────────────────── */
    const btnToggleSenha = document.getElementById('btnToggleSenha');
    if (btnToggleSenha) {
        btnToggleSenha.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon  = document.getElementById('iconSenha');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    }

    /* ── Gerador de APP_KEY ───────────────────────────────────── */
    const btnGerar  = document.getElementById('btnGerarChave');
    const wrapper   = document.getElementById('chaveGeradaWrapper');
    const inputKey  = document.getElementById('chaveGerada');
    const btnCopiar = document.getElementById('btnCopiarChave');
    const msgCop    = document.getElementById('msgCopiado');

    if (btnGerar) {
        btnGerar.addEventListener('click', function () {
            btnGerar.disabled = true;
            btnGerar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Gerando...';
            fetch('/integracao/email/gerar-chave', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        inputKey.value = data.chave;
                        wrapper.classList.remove('d-none');
                        btnGerar.innerHTML = '<i class="fas fa-sync me-1"></i> Gerar outra';
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Falha ao gerar chave.' });
                        btnGerar.innerHTML = '<i class="fas fa-key me-1"></i> Gerar nova APP_KEY';
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na requisição.' });
                    btnGerar.innerHTML = '<i class="fas fa-key me-1"></i> Gerar nova APP_KEY';
                })
                .finally(() => { btnGerar.disabled = false; });
        });
    }

    if (btnCopiar) {
        btnCopiar.addEventListener('click', function () {
            navigator.clipboard.writeText(inputKey.value).then(() => {
                document.getElementById('iconCopiar').classList.replace('fa-copy', 'fa-check');
                msgCop.classList.remove('d-none');
                setTimeout(() => {
                    document.getElementById('iconCopiar').classList.replace('fa-check', 'fa-copy');
                    msgCop.classList.add('d-none');
                }, 2500);
            }).catch(() => { inputKey.select(); document.execCommand('copy'); });
        });
    }

    /* ── Alerta de senha incompatível ────────────────────────── */
    function alertaSenhaIncompativel() {
        const campoSenha = document.getElementById('password');
        Swal.fire({
            icon: 'warning',
            title: 'Senha desatualizada',
            html: '<p>A senha foi salva com uma <strong>chave de criptografia diferente</strong> da atual.</p>' +
                  '<p>Por segurança, <strong>digite a Senha de App do Google novamente</strong> no campo Senha e clique em <em>Salvar Alterações</em>.</p>',
            confirmButtonText: 'Entendido, vou redigitar',
            confirmButtonColor: '#0d6efd'
        }).then(() => {
            campoSenha.value = '';
            campoSenha.placeholder = 'Digite a Senha de App do Google (16 caracteres)';
            campoSenha.focus();
            campoSenha.classList.add('border-warning');
            campoSenha.addEventListener('input', function () {
                campoSenha.classList.remove('border-warning');
            }, { once: true });
        });
    }

    /* ── Salvar configuração SMTP ────────────────────────────── */
    const form = document.getElementById('formEmailConfig');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData  = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
        formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION["csrf_token"] ?? ""); ?>');
        fetch('/integracao/email/save', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 2000, showConfirmButton: false });
                } else if (data.error_type === 'password_key_mismatch') {
                    alertaSenhaIncompativel();
                } else {
                    throw new Error(data.error || 'Erro ao salvar configuração');
                }
            })
            .catch(error => { Swal.fire({ icon: 'error', title: 'Erro', text: error.message }); })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
            });
    });

    /* ── Enviar e-mail de teste ───────────────────────────────── */
    const btnTestar = document.getElementById('btnTestarEmail');
    if (btnTestar) {
        btnTestar.addEventListener('click', function () {
            this.disabled = true;
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
            const csrfToken = '<?php echo htmlspecialchars($_SESSION["csrf_token"] ?? ""); ?>';
            const fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fetch('/integracao/email/test', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Enviado!', text: data.message });
                    } else if (data.error_type === 'password_key_mismatch') {
                        alertaSenhaIncompativel();
                    } else {
                        Swal.fire({
                            icon: 'error', title: 'Falha no envio',
                            html: '<p>' + (data.error || 'Falha ao enviar e-mail') + '</p>' +
                                  (data.error_type ? '<p class="text-muted small mb-0">Código: ' + data.error_type + '</p>' : '')
                        });
                    }
                })
                .catch(error => { Swal.fire({ icon: 'error', title: 'Falha', text: error.message }); })
                .finally(() => { this.disabled = false; this.innerHTML = originalHtml; });
        });
    }

    /* ══════════════════════════════════════════════════════════
       ALERTAS
       ══════════════════════════════════════════════════════════ */

    /* ── Toggle ativo/inativo ─────────────────────────────────── */
    document.querySelectorAll('.toggle-alerta').forEach(function (chk) {
        chk.addEventListener('change', function () {
            const id    = this.dataset.id;
            const ativo = this.checked ? 1 : 0;
            const row   = document.getElementById('row-alerta-' + id);

            const fd = new FormData();
            fd.append('id', id);
            fd.append('ativo', ativo);

            const self = this;
            fetch('/integracao/email/alertas/toggle', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        row.classList.toggle('opacity-50', !ativo);
                        const badge = document.querySelector('#tab-alertas .badge.bg-primary');
                        // Atualiza badge de ativos na aba (recarrega contagem)
                        let total = 0;
                        document.querySelectorAll('.toggle-alerta:checked').forEach(() => total++);
                        if (badge) { badge.textContent = total; badge.style.display = total > 0 ? '' : 'none'; }
                    } else {
                        self.checked = !self.checked; // reverte
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível alterar o status.' });
                    }
                })
                .catch(() => {
                    self.checked = !self.checked;
                    Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na requisição.' });
                });
        });
    });

    /* ── Abrir modal de edição ────────────────────────────────── */
    document.querySelectorAll('.btn-editar-alerta').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const alerta = JSON.parse(this.dataset.alerta);

            document.getElementById('ea_codigo').value      = alerta.codigo;
            document.getElementById('ea_modulo').value      = alerta.modulo;
            document.getElementById('ea_nome').value        = alerta.nome;
            document.getElementById('ea_descricao').value   = alerta.descricao || '';
            document.getElementById('ea_antecedencia').value = alerta.antecedencia_dias;
            document.getElementById('ea_frequencia').value  = alerta.frequencia;
            document.getElementById('ea_hora').value        = (alerta.hora_disparo || '08:00').substring(0, 5);
            document.getElementById('ea_assunto').value     = alerta.assunto_template;
            document.getElementById('ea_corpo').value       = alerta.corpo_template;
            document.getElementById('ea_ativo').checked     = !!alerta.ativo;

            // Destinatários
            const dests = JSON.parse(alerta.destinatarios || '[]');
            const extrasEmails = [];
            document.querySelectorAll('.dest-check').forEach(c => {
                c.checked = dests.includes(c.value);
            });
            dests.forEach(d => {
                if (!['admin','financeiro','vendedor','cliente'].includes(d)) {
                    extrasEmails.push(d);
                }
            });
            document.getElementById('ea_dest_extra').value = extrasEmails.join(', ');

            // CC
            const cc = JSON.parse(alerta.cc || '[]');
            document.getElementById('ea_cc').value = Array.isArray(cc) ? cc.join(', ') : (cc || '');

            const modal = new bootstrap.Modal(document.getElementById('modalEditarAlerta'));
            modal.show();
        });
    });

    /* ── Salvar alerta (modal) ────────────────────────────────── */
    document.getElementById('btnSalvarAlerta').addEventListener('click', function () {
        const form = document.getElementById('formEditarAlerta');

        // Monta array de destinatários
        const dests = [];
        document.querySelectorAll('.dest-check:checked').forEach(c => dests.push(c.value));
        const extras = document.getElementById('ea_dest_extra').value
            .split(',').map(e => e.trim()).filter(e => e.includes('@'));
        extras.forEach(e => dests.push(e));
        document.getElementById('ea_destinatarios').value = JSON.stringify(dests);

        const fd = new FormData(form);
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        fetch('/integracao/email/alertas/salvar', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Alerta salvo!', timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Falha ao salvar.' });
                }
            })
            .catch(() => { Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na requisição.' }); })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alerta';
            });
    });

    /* ── Disparar alerta manualmente ─────────────────────────── */
    document.querySelectorAll('.btn-disparar-alerta').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const nome = this.dataset.nome;

            Swal.fire({
                icon: 'question',
                title: 'Disparar alerta?',
                html: '<p>Deseja disparar manualmente o alerta:</p><p class="fw-bold">' + nome + '</p>' +
                      '<p class="text-muted small">O sistema irá buscar os registros elegíveis e enviar os e-mails agora.</p>',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-play me-1"></i> Disparar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd'
            }).then(result => {
                if (!result.isConfirmed) return;

                const fd = new FormData();
                fd.append('alerta_id', id);

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                fetch('/integracao/email/alertas/disparar', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const r = data.resultado;
                            Swal.fire({
                                icon: 'success',
                                title: 'Alerta disparado!',
                                html: '<p>Resultado do disparo:</p>' +
                                      '<ul class="text-start">' +
                                      '<li>Enviados: <strong>' + r.enviados + '</strong></li>' +
                                      '<li>Falhas: <strong>' + r.falhas + '</strong></li>' +
                                      '<li>Ignorados: <strong>' + r.ignorados + '</strong></li>' +
                                      '</ul>'
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Falha no disparo.' });
                        }
                    })
                    .catch(() => { Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na requisição.' }); })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-play"></i>';
                    });
            });
        });
    });

    /* ── Ativa aba Alertas se hash na URL ─────────────────────── */
    if (window.location.hash === '#alertas') {
        const tabAlertas = document.getElementById('tab-alertas');
        if (tabAlertas) { new bootstrap.Tab(tabAlertas).show(); }
    }

});
</script>
