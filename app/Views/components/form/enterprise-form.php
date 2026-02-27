<?php
/**
 * ERP InLaudo - Componente de Formulário Enterprise Padrão
 * Layout reutilizável para formulários com abas
 * Uso: require_once __DIR__ . '/enterprise-form.php';
 * 
 * @param array $data Array com configurações do formulário
 */

// Include header if not already included
if (!defined('ERP_VIEW_RENDERING') && !defined('ERP_HEADER_INCLUDED')) {
    require_once dirname(dirname(__DIR__)) . '/layout/erp_header.php';
    define('ERP_HEADER_INCLUDED', true);
}

// Validação de dados obrigatórios
if (!isset($data['title'])) {
    throw new Exception('Enterprise Form: "title" é obrigatório');
}

if (!isset($data['tabs']) || !is_array($data['tabs'])) {
    throw new Exception('Enterprise Form: "tabs" é obrigatório e deve ser um array');
}

// Configurações padrão
$config = array_merge([
    'title' => '',
    'subtitle' => '',
    'is_edit' => false,
    'record_id' => null,
    'active_tab' => 0,
    'tabs' => [],
    'actions' => [],
    'class' => '',
    'show_breadcrumb' => true,
    'breadcrumb' => []
], $data);

// Determina aba ativa
$activeTabIndex = $config['active_tab'];
if (is_string($activeTabIndex)) {
    // Busca por nome
    foreach ($config['tabs'] as $index => $tab) {
        if ($tab['id'] === $activeTabIndex) {
            $activeTabIndex = $index;
            break;
        }
    }
} else {
    $activeTabIndex = (int)$activeTabIndex;
}

// Classes CSS
$containerClasses = 'form-container ' . $config['class'];
if ($config['is_edit']) {
    $containerClasses .= ' form-edit-mode';
}
?>

<!-- Container Principal do Formulário Enterprise -->
<div class="<?php echo $containerClasses; ?>" 
     data-is-edit="<?php echo $config['is_edit'] ? 'true' : 'false'; ?>"
     data-client-id="<?php echo $config['record_id'] ?? ''; ?>"
     data-active-tab="<?php echo $config['tabs'][$activeTabIndex]['id'] ?? ''; ?>">

    <!-- Header do Formulário -->
    <header class="form-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="form-title">
                    <?php echo htmlspecialchars($config['title']); ?>
                    <?php if ($config['is_edit']): ?>
                        <small class="form-edit-badge">Editando</small>
                    <?php endif; ?>
                </h1>
                <?php if (!empty($config['subtitle'])): ?>
                    <p class="form-subtitle"><?php echo htmlspecialchars($config['subtitle']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($config['actions'])): ?>
                <div class="form-header-actions">
                    <?php foreach ($config['actions'] as $action): ?>
                        <?php if (isset($action['type']) && $action['type'] === 'dropdown'): ?>
                            <div class="dropdown">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="<?php echo $action['icon'] ?? 'fas fa-ellipsis-v'; ?>"></i>
                                    <?php echo $action['label'] ?? 'Ações'; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php foreach ($action['items'] as $item): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo $item['url'] ?? '#'; ?>">
                                                <i class="<?php echo $item['icon'] ?? 'fas fa-link'; ?> me-2"></i>
                                                <?php echo $item['label']; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo $action['url'] ?? '#'; ?>" 
                               class="btn btn-<?php echo $action['color'] ?? 'secondary'; ?> btn-sm">
                                <i class="<?php echo $action['icon'] ?? 'fas fa-arrow-left'; ?>"></i>
                                <?php echo $action['label']; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Sistema de Abas -->
    <div class="form-tabs-container" data-active-tab="<?php echo $activeTabIndex; ?>">
    <nav class="form-tabs">
        <div class="form-tabs-list">
                <?php foreach ($config['tabs'] as $index => $tab): ?>
                    <?php
                    $isActive = $index === $activeTabIndex;
                    $isLocked = isset($tab['locked']) && $tab['locked'];
                    $tabId = $tab['id'] ?? 'tab-' . $index;
                    ?>
                    <div class="form-tab">
                        <button class="form-tab-button <?php echo $isActive ? 'active' : ''; ?> <?php echo $isLocked ? 'locked' : ''; ?>"
                                type="button"
                                data-tab="<?php echo $tabId; ?>"
                                data-tab-index="<?php echo $index; ?>"
                                <?php echo $isLocked ? 'disabled data-locked="true" data-locked-message="' . htmlspecialchars($tab['locked_message'] ?? 'Complete as etapas anteriores') . '"' : ''; ?>>
                            
                            <span class="tab-number"><?php echo $index + 1; ?></span>
                            <i class="<?php echo $tab['icon'] ?? 'fas fa-file'; ?> tab-icon"></i>
                            <span class="tab-text"><?php echo htmlspecialchars($tab['title']); ?></span>
                            
                            <?php if ($isLocked): ?>
                                <i class="fas fa-lock lock-icon"></i>
                            <?php endif; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
        </div>
    </nav>

    <!-- Conteúdo do Formulário -->
    <main class="form-content">
        <?php foreach ($config['tabs'] as $index => $tab): ?>
            <?php
            $isActive = $index === $activeTabIndex;
            $tabId = $tab['id'] ?? 'tab-' . $index;
            ?>
            
            <section class="form-panel <?php echo $isActive ? 'active' : ''; ?>"
                     id="<?php echo $tabId; ?>"
                     data-tab-index="<?php echo $index; ?>">
                
                <?php if (isset($tab['content']) && is_callable($tab['content'])): ?>
                    <?php echo $tab['content'](); ?>
                <?php elseif (isset($tab['view']) && !empty($tab['view'])): ?>
                    <?php 
                    $viewPath = dirname(__DIR__, 2) . '/' . str_replace('.', '/', $tab['view']) . '.php';
                    if (file_exists($viewPath)) {
                        include $viewPath;
                    } else {
                        echo '<div class="alert alert-warning">View não encontrada: ' . htmlspecialchars($tab['view']) . '</div>';
                    }
                    ?>
                <?php elseif (isset($tab['html'])): ?>
                    <?php echo $tab['html']; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p>Conteúdo não definido para esta aba.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </main>
    </div><!-- /.form-tabs-container -->

    <!-- Ações do Formulário (Rodapé) -->
    <?php if (!empty($config['footer_actions'])): ?>
        <footer class="form-actions">
            <?php foreach ($config['footer_actions'] as $index => $action): ?>
                <?php
                $buttonType = $action['type'] ?? 'button';
                $buttonClass = 'btn btn-' . ($action['color'] ?? 'primary');
                $buttonClass .= isset($action['class']) ? ' ' . $action['class'] : '';
                $buttonClass .= $action['large'] ? ' btn-lg' : '';
                
                if (isset($action['primary']) && $action['primary']) {
                    $buttonClass .= ' btn-primary';
                }
                ?>
                
                <?php if ($buttonType === 'submit'): ?>
                    <button type="submit" 
                            class="<?php echo $buttonClass; ?>"
                            form="<?php echo $action['form'] ?? 'mainForm'; ?>"
                            <?php echo isset($action['disabled']) && $action['disabled'] ? 'disabled' : ''; ?>>
                        <?php if (!empty($action['icon'])): ?>
                            <i class="<?php echo $action['icon']; ?> me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($action['label']); ?>
                    </button>
                <?php elseif ($buttonType === 'button'): ?>
                    <button type="button" 
                            class="<?php echo $buttonClass; ?>"
                            onclick="<?php echo $action['onclick'] ?? ''; ?>"
                            <?php echo isset($action['disabled']) && $action['disabled'] ? 'disabled' : ''; ?>>
                        <?php if (!empty($action['icon'])): ?>
                            <i class="<?php echo $action['icon']; ?> me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($action['label']); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo $action['url'] ?? '#'; ?>" 
                       class="<?php echo $buttonClass; ?>"
                       <?php echo isset($action['target']) ? 'target="' . $action['target'] . '"' : ''; ?>>
                        <?php if (!empty($action['icon'])): ?>
                            <i class="<?php echo $action['icon']; ?> me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($action['label']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </footer>
    <?php endif; ?>

</div>

<!-- Estilos Complementares (se necessário) -->
<style>
.form-edit-badge {
    display: inline-block;
    background: rgba(16, 185, 129, 0.1);
    color: var(--form-success);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-left: 1rem;
}

.form-header-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

@media (max-width: 768px) {
    .form-header {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }
    
    .form-header-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .form-tabs-list {
        flex-direction: column;
    }
    
    .form-tab {
        max-width: none;
    }
}
</style>

<!-- Scripts de Inicialização -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o sistema de abas se não foi inicializado automaticamente
    const tabsContainer = document.querySelector('.form-tabs-container:not(.form-tabs-initialized)');
    if (tabsContainer) {
        // O form-tabs.js já inicializa automaticamente, mas garantimos aqui
        if (window.FormTabs && !tabsContainer.formTabs) {
            tabsContainer.formTabs = new window.FormTabs(tabsContainer, {
                saveState: true
            });
        }
    }
    
    // Adiciona feedback visual em campos de formulário
    const formInputs = document.querySelectorAll('.form-control, .form-select');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-group')?.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.closest('.form-group')?.classList.remove('focused');
        });
    });

    // Processa alertas vindos via URL (PHP Redirects)
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');

    if (error) {
        const errorMessages = {
            'missing_fields': 'Preencha todos os campos obrigatórios.',
            'invalid_cpf': 'O CPF informado é inválido.',
            'invalid_cnpj': 'O CNPJ informado é inválido.',
            'db_failure': 'Erro ao salvar dados no banco de dados.',
            'unauthorized': 'Você não tem permissão para esta ação.',
            'fatal': 'Ocorreu um erro interno inesperado.'
        };
        Swal.fire({
            icon: 'error',
            title: 'Ops!',
            text: errorMessages[error] || 'Ocorreu um erro ao processar sua solicitação.',
            confirmButtonColor: '#00529B'
        });
    }

    if (success) {
        const successMessages = {
            'created': 'Cliente cadastrado com sucesso!',
            'updated': 'Alterações salvas com sucesso!',
            'deleted': 'Cliente removido com sucesso.'
        };
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: successMessages[success] || 'Operação realizada com sucesso.',
            timer: 3000,
            showConfirmButton: false
        });
    }
});
</script>

<?php 
// Include footer if header was included
if (!defined('ERP_VIEW_RENDERING') && defined('ERP_HEADER_INCLUDED')) {
    require_once dirname(dirname(__DIR__)) . '/layout/erp_footer.php';
}
?>
