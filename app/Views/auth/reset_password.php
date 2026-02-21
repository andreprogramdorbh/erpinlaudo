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

$tokenValue = $tokenValue ?? '';
$errorMsg = '';
if (!empty($_GET['error'])) {
    if ($_GET['error'] === 'short') {
        $errorMsg = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif ($_GET['error'] === 'mismatch') {
        $errorMsg = 'As senhas não coincidem.';
    } else {
        $errorMsg = 'Não foi possível redefinir a senha. Tente novamente.';
    }
}
?>

<div class="login-card">
    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="InLaudo" class="logo">

    <h1>Redefinir senha</h1>

    <?php if ($errorMsg): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <?php Form::start('resetForm', '/reset-password/' . htmlspecialchars($tokenValue)); ?>
    <div class="mb-3">
        <label class="form-label">Nova senha <span class="text-danger">*</span></label>
        <?php Form::input('password', '', 'password', '', [
            'placeholder' => 'Mínimo 8 caracteres',
            'required' => true,
            'class' => 'form-control',
            'autofocus' => true,
            'minlength' => 8
        ]); ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Confirmar senha <span class="text-danger">*</span></label>
        <?php Form::input('password_confirm', '', 'password', '', [
            'placeholder' => 'Repita a senha',
            'required' => true,
            'class' => 'form-control',
            'minlength' => 8
        ]); ?>
    </div>

    <?php Form::button('Redefinir senha', 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <a href="/login" class="forgot-password">Voltar ao login</a>

    <p class="login-footer">© 2026 InLaudo. Todos os direitos reservados.</p>
</div>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
