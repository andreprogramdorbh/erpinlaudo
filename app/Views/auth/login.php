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

    <h1>Acesso ao ERP</h1>

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
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            E-mail ou senha incorretos.
        </div>
    <?php endif; ?>

    <?php Form::start('loginForm', '/login'); ?>
    <div class="mb-3">
        <label class="form-label">E-mail <span class="text-danger">*</span></label>
        <?php Form::input('email', '', 'email', '', [
            'placeholder' => 'exemplo@inlaudo.com.br',
            'required' => true,
            'class' => 'form-control',
            'autofocus' => true
        ]); ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Senha <span class="text-danger">*</span></label>
        <?php Form::input('password', '', 'password', '', [
            'placeholder' => '••••••••',
            'required' => true,
            'class' => 'form-control'
        ]); ?>
    </div>

    <?php Form::button('Entrar na Conta', 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <a href="/forgot-password" class="forgot-password">Esqueceu sua senha?</a>

    <p class="login-footer">© 2026 InLaudo. Todos os direitos reservados.</p>
</div>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
