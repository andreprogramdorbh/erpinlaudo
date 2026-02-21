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

    <h1>Esqueci minha senha</h1>

    <?php if (!empty($sent)): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Se o e-mail existir, enviaremos instruções.
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Informe um e-mail válido.
        </div>
    <?php endif; ?>

    <?php Form::start('forgotForm', '/forgot-password'); ?>
    <div class="mb-4">
        <label class="form-label">E-mail <span class="text-danger">*</span></label>
        <?php Form::input('email', '', 'email', '', [
            'placeholder' => 'exemplo@inlaudo.com.br',
            'required' => true,
            'class' => 'form-control',
            'autofocus' => true
        ]); ?>
    </div>

    <?php Form::button('Enviar instruções', 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <a href="/login" class="forgot-password">Voltar ao login</a>

    <p class="login-footer">© 2026 InLaudo. Todos os direitos reservados.</p>
</div>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
