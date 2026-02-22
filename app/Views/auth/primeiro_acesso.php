<?php
require_once dirname(__DIR__) . '/layout/public_header.php';
$logoPath = '/assets/logo-inlaudo.png';
$uploadLogoDir = BASE_PATH . '/public/uploads/logo';
if (is_dir($uploadLogoDir)) {
    $files = array_diff(scandir($uploadLogoDir), ['.', '..']);
    if (!empty($files)) {
        $logoFile = reset($files);
        $logoPath = '/uploads/logo/' . $logoFile;
    }
}
?>
<div class="login-card">
    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="InLaudo" class="logo">
    <h1>Primeiro Acesso</h1>
    <p style="color:#6b7280;font-size:.9rem;margin-bottom:1.5rem;">
        Informe o e-mail cadastrado como <strong>E-mail Principal</strong> do seu cadastro para criar sua senha de acesso à Área do Cliente.
    </p>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($erro); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <?php $erros = [
            'email_nao_encontrado' => 'E-mail não encontrado. Verifique se é o mesmo cadastrado no seu contrato.',
            'token_invalido'       => 'Link inválido ou expirado. Solicite um novo primeiro acesso.',
            'senhas_diferentes'    => 'As senhas não coincidem. Tente novamente.',
            'senha_curta'          => 'A senha deve ter pelo menos 8 caracteres.',
        ]; ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'Ocorreu um erro. Tente novamente.'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($token)): ?>
        <!-- ETAPA 1: Informar e-mail -->
        <form method="POST" action="/primeiro-acesso">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div class="mb-4">
                <label class="form-label">E-mail Principal <span class="text-danger">*</span></label>
                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="seu@email.com.br"
                       required
                       autofocus
                       autocomplete="email"
                       value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Continuar</button>
        </form>

    <?php else: ?>
        <!-- ETAPA 2: Criar senha (token válido) -->
        <p style="color:#374151;font-size:.85rem;margin-bottom:1.25rem;">
            Olá, <strong><?php echo htmlspecialchars($nomeCliente ?? 'Cliente'); ?></strong>!
            Crie sua senha para acessar a Área do Cliente.
        </p>
        <form method="POST" action="/primeiro-acesso/salvar" id="formCriarSenha">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password"
                           id="novaSenha"
                           name="senha"
                           class="form-control pe-5"
                           placeholder="Mínimo 8 caracteres"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           oninput="paCheckSenha()">
                    <button type="button" onclick="paToggle('novaSenha','paEye1')" tabindex="-1"
                            style="position:absolute;top:50%;right:.75rem;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;">
                        <i class="fa fa-eye" id="paEye1"></i>
                    </button>
                </div>
                <!-- Indicador de força -->
                <div style="margin-top:.4rem;">
                    <div style="height:4px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                        <div id="paStrengthFill" style="height:100%;width:0;background:#e02424;transition:width .3s,background .3s;border-radius:4px;"></div>
                    </div>
                    <span id="paStrengthLabel" style="font-size:.75rem;color:#6b7280;"></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password"
                           id="confirmarSenha"
                           name="senha_confirmacao"
                           class="form-control pe-5"
                           placeholder="Repita a senha"
                           required
                           autocomplete="new-password"
                           oninput="paCheckConfirm()">
                    <button type="button" onclick="paToggle('confirmarSenha','paEye2')" tabindex="-1"
                            style="position:absolute;top:50%;right:.75rem;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;">
                        <i class="fa fa-eye" id="paEye2"></i>
                    </button>
                </div>
                <span id="paMatchMsg" style="font-size:.78rem;display:none;"></span>
            </div>
            <button type="submit" class="btn btn-primary" id="paBtnSalvar">Criar Senha e Entrar</button>
        </form>
    <?php endif; ?>

    <div class="login-links-group" style="margin-top:1.25rem;">
        <a href="/login" class="forgot-password">
            <i class="fa fa-arrow-left" style="margin-right:.3rem;"></i>Voltar ao login
        </a>
    </div>

    <p class="login-footer">© <?php echo date('Y'); ?> InLaudo. Todos os direitos reservados.</p>
</div>

<script>
function paToggle(fieldId, iconId) {
    var input = document.getElementById(fieldId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}

function paCheckSenha() {
    var v = document.getElementById('novaSenha').value;
    var fill  = document.getElementById('paStrengthFill');
    var label = document.getElementById('paStrengthLabel');
    if (!fill) return;
    var score = 0;
    if (v.length >= 8)  score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var levels = [
        {pct:'20%',color:'#e02424',text:'Muito fraca'},
        {pct:'40%',color:'#d97706',text:'Fraca'},
        {pct:'60%',color:'#f59e0b',text:'Razoável'},
        {pct:'80%',color:'#0e9f6e',text:'Forte'},
        {pct:'100%',color:'#065f46',text:'Muito forte'},
    ];
    var lvl = levels[Math.max(0, score - 1)];
    fill.style.width = v.length > 0 ? lvl.pct : '0';
    fill.style.background = lvl.color;
    label.textContent = v.length > 0 ? lvl.text : '';
    label.style.color = lvl.color;
}

function paCheckConfirm() {
    var s = document.getElementById('novaSenha').value;
    var c = document.getElementById('confirmarSenha').value;
    var msg = document.getElementById('paMatchMsg');
    if (!msg) return;
    if (c.length === 0) { msg.style.display = 'none'; return; }
    msg.style.display = 'inline';
    if (s === c) {
        msg.textContent = '✓ As senhas coincidem';
        msg.style.color = '#0e9f6e';
    } else {
        msg.textContent = '✗ As senhas não coincidem';
        msg.style.color = '#e02424';
    }
}

var formCriarSenha = document.getElementById('formCriarSenha');
if (formCriarSenha) {
    formCriarSenha.addEventListener('submit', function(e) {
        var s = document.getElementById('novaSenha').value;
        var c = document.getElementById('confirmarSenha').value;
        if (s.length < 8) {
            e.preventDefault();
            alert('A senha deve ter pelo menos 8 caracteres.');
            return;
        }
        if (s !== c) {
            e.preventDefault();
            alert('As senhas não coincidem. Verifique e tente novamente.');
            return;
        }
        var btn = document.getElementById('paBtnSalvar');
        if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
