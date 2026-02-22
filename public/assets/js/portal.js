/**
 * Portal do Cliente — INLAUDO
 * JavaScript mobile-first com UX aprimorada
 */

// ============================================================
// Toggle visibilidade de senha
// ============================================================
function portalToggleSenha(fieldId) {
    const input = document.getElementById(fieldId);
    const icon  = document.getElementById('icon-' + fieldId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ============================================================
// Indicador de força da senha
// ============================================================
function portalCheckSenha() {
    const senhaField = document.getElementById('senha') || document.getElementById('nova_senha');
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    if (!senhaField || !fill || !label) return;

    const v = senhaField.value;
    let score = 0;
    if (v.length >= 8)  score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const levels = [
        { pct: '20%', color: '#e02424', text: 'Muito fraca' },
        { pct: '40%', color: '#d97706', text: 'Fraca' },
        { pct: '60%', color: '#f59e0b', text: 'Razoável' },
        { pct: '80%', color: '#0e9f6e', text: 'Forte' },
        { pct: '100%', color: '#065f46', text: 'Muito forte' },
    ];

    const lvl = levels[Math.max(0, score - 1)] || levels[0];
    fill.style.width    = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent   = v.length > 0 ? lvl.text : '';
    label.style.color   = lvl.color;
}

// ============================================================
// Verificação de confirmação de senha
// ============================================================
function portalCheckConfirmacao() {
    const senhaField = document.getElementById('senha') || document.getElementById('nova_senha');
    const confirmField = document.getElementById('senha_confirmacao') || document.getElementById('nova_senha_confirm');
    const msg = document.getElementById('matchMsg');
    if (!senhaField || !confirmField || !msg) return;

    if (confirmField.value.length === 0) {
        msg.classList.add('d-none');
        return;
    }

    msg.classList.remove('d-none');
    if (senhaField.value === confirmField.value) {
        msg.textContent = '✓ As senhas coincidem';
        msg.className = 'portal-match-msg match';
    } else {
        msg.textContent = '✗ As senhas não coincidem';
        msg.className = 'portal-match-msg no-match';
    }
}

// ============================================================
// Submissão com loading state
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    // Loading em botões de submit
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (!btn) return;
            const textEl    = btn.querySelector('.btn-text');
            const loadingEl = btn.querySelector('.btn-loading');
            if (textEl && loadingEl) {
                textEl.classList.add('d-none');
                loadingEl.classList.remove('d-none');
                btn.disabled = true;
            }
        });
    });

    // Submenu toggle na sidebar
    document.querySelectorAll('.portal-nav-group-label').forEach(function (label) {
        label.addEventListener('click', function () {
            const submenu = label.nextElementSibling;
            if (!submenu) return;
            submenu.classList.toggle('open');
            const arrow = label.querySelector('.portal-nav-arrow');
            if (arrow) arrow.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : '';
        });
    });

    // Validação do formulário de primeiro acesso / alterar senha
    const formPrimeiroAcesso = document.getElementById('formPrimeiroAcesso');
    const formAlterarSenha   = document.getElementById('formAlterarSenha');

    [formPrimeiroAcesso, formAlterarSenha].forEach(function (form) {
        if (!form) return;
        form.addEventListener('submit', function (e) {
            const senhaField   = form.querySelector('#senha, #nova_senha');
            const confirmField = form.querySelector('#senha_confirmacao, #nova_senha_confirm');
            if (!senhaField || !confirmField) return;

            if (senhaField.value.length < 8) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 8 caracteres.');
                senhaField.focus();
                return;
            }

            if (senhaField.value !== confirmField.value) {
                e.preventDefault();
                alert('As senhas não coincidem. Verifique e tente novamente.');
                confirmField.focus();
            }
        });
    });

    // Auto-hide de alertas de sucesso após 5 segundos
    document.querySelectorAll('.portal-alert-success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });
});
