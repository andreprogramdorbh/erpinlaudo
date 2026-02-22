<?php
use App\Core\Form;
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
    <h1>Acesso ao Sistema</h1>

    <?php if (!empty($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Senha redefinida com sucesso. Faça login com a nova senha.
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['reset']) && $_GET['reset'] === 'invalid'): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Link inválido ou expirado. Solicite uma nova redefinição de senha.
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <?php $erros = [
            '1'               => 'E-mail ou senha incorretos.',
            'credenciais'     => 'E-mail ou senha incorretos.',
            'conta_inativa'   => 'Sua conta está inativa. Entre em contato com o suporte.',
            'sessao_expirada' => 'Sua sessão expirou. Faça login novamente.',
        ]; ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'E-mail ou senha incorretos.'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['logout'])): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Você saiu com segurança.
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['primeiro_acesso']) && $_GET['primeiro_acesso'] === 'ok'): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Senha criada com sucesso! Faça login para acessar a Área do Cliente.
        </div>
    <?php endif; ?>

    <?php Form::start('loginForm', '/login'); ?>
    <div class="mb-3">
        <label class="form-label">E-mail <span class="text-danger">*</span></label>
        <?php Form::input('email', '', 'email', '', [
            'placeholder'  => 'seu@email.com.br',
            'required'     => true,
            'class'        => 'form-control',
            'autofocus'    => true,
            'autocomplete' => 'email',
        ]); ?>
    </div>
    <div class="mb-4">
        <label class="form-label">Senha <span class="text-danger">*</span></label>
        <div class="position-relative">
            <?php Form::input('password', '', 'password', '', [
                'id'           => 'loginPassword',
                'placeholder'  => '••••••••',
                'required'     => true,
                'class'        => 'form-control pe-5',
                'autocomplete' => 'current-password',
            ]); ?>
            <button type="button"
                    onclick="toggleLoginSenha()"
                    tabindex="-1"
                    style="position:absolute;top:50%;right:.75rem;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;">
                <i class="fa fa-eye" id="loginEyeIcon"></i>
            </button>
        </div>
    </div>
    <?php Form::button('Entrar', 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <!-- Links de suporte abaixo do botão -->
    <div class="login-links-group">
        <a href="/forgot-password" class="forgot-password">Esqueceu sua senha?</a>
        <a href="/primeiro-acesso" class="forgot-password primeiro-acesso-link">
            <i class="fa fa-user-plus" style="margin-right:.3rem;"></i>Primeiro acesso
        </a>
    </div>

    <p class="login-footer">© <?php echo date('Y'); ?> InLaudo. Todos os direitos reservados.</p>
</div>

<script>
function toggleLoginSenha() {
    var input = document.getElementById('loginPassword');
    var icon  = document.getElementById('loginEyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
