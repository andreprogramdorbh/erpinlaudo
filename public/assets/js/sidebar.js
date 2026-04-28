(function () {
    if (window.__erpSidebarInitialized) return;
    window.__erpSidebarInitialized = true;

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function isMobile() {
        return window.innerWidth < 992;
    }

    function getSidebarEls() {
        return {
            sidebar:       document.getElementById('mainSidebar'),
            layoutWrapper: document.querySelector('.layout-wrapper'),
            overlay:       document.getElementById('sidebarOverlay'),
        };
    }

    // ─── Aplicar estado salvo no localStorage (apenas desktop) ────────────────
    function applySidebarState() {
        if (isMobile()) return; // em mobile sempre começa fechado

        var els = getSidebarEls();
        if (!els.sidebar || !els.layoutWrapper) return;

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            els.sidebar.classList.add('collapsed');
            els.layoutWrapper.classList.add('sidebar-collapsed');
        }
    }

    // ─── Toggle principal ─────────────────────────────────────────────────────
    function toggleSidebar() {
        var els = getSidebarEls();
        if (!els.sidebar) return;

        if (isMobile()) {
            // MOBILE: alterna entre show/hidden + overlay
            if (els.sidebar.classList.contains('show')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        } else {
            // DESKTOP: alterna entre collapsed/expanded
            els.sidebar.classList.toggle('collapsed');
            if (els.layoutWrapper) {
                els.layoutWrapper.classList.toggle('sidebar-collapsed');
            }
            var isCollapsed = els.sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            // Salvar como cookie para o PHP ler server-side
            document.cookie = 'sidebarCollapsed=' + isCollapsed + '; path=/; max-age=31536000';
        }
    }

    function openMobileSidebar() {
        var els = getSidebarEls();
        if (!els.sidebar) return;
        els.sidebar.classList.add('show');
        if (els.overlay) els.overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // evita scroll do fundo
    }

    function closeMobileSidebar() {
        var els = getSidebarEls();
        if (!els.sidebar) return;
        els.sidebar.classList.remove('show');
        if (els.overlay) els.overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ─── Fechar sidebar mobile ao clicar no overlay ───────────────────────────
    function initOverlay() {
        var els = getSidebarEls();
        if (!els.overlay) return;
        els.overlay.addEventListener('click', function () {
            closeMobileSidebar();
        });
        els.overlay.addEventListener('touchstart', function () {
            closeMobileSidebar();
        }, { passive: true });
    }

    // ─── Fechar sidebar mobile ao clicar em link de navegação (não submenu) ──
    function initMobileNavLinks() {
        document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (!isMobile()) return;
                // Não fechar se for o toggle de um submenu
                var parentItem = this.closest('.nav-item');
                if (parentItem && parentItem.classList.contains('has-submenu')) return;
                closeMobileSidebar();
            });
        });
    }

    // ─── Submenus ─────────────────────────────────────────────────────────────
    function initSubmenuBehavior() {
        document.querySelectorAll('.nav-item.has-submenu > .nav-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var els = getSidebarEls();
                // Desktop colapsado: não abre submenu
                if (!isMobile() && els.sidebar && els.sidebar.classList.contains('collapsed')) return;

                e.preventDefault();
                var navItem = this.closest('.nav-item');
                if (navItem) {
                    navItem.classList.toggle('open');
                }
            });
        });
    }

    // ─── Tooltips Bootstrap ───────────────────────────────────────────────────
    function initTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

    // ─── Fechar sidebar mobile ao redimensionar para desktop ─────────────────
    function initResizeHandler() {
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (!isMobile()) {
                    closeMobileSidebar(); // limpa estado mobile ao voltar para desktop
                }
            }, 150);
        });
    }

    // ─── Confirmação de logout ────────────────────────────────────────────────
    function confirmLogout() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sair do Sistema?',
                text: 'Você precisará se autenticar novamente para acessar o ERP.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00529B',
                cancelButtonColor: '#a0aec0',
                confirmButtonText: 'Sim, Sair!',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/logout';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
            return;
        }
        if (confirm('Deseja realmente sair do sistema?')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // ─── Expor funções globais ────────────────────────────────────────────────
    if (typeof window.toggleSidebar === 'undefined') {
        window.toggleSidebar = toggleSidebar;
    }
    if (typeof window.confirmLogout === 'undefined') {
        window.confirmLogout = confirmLogout;
    }

    // ─── Inicialização ────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        applySidebarState();
        initOverlay();
        initSubmenuBehavior();
        initMobileNavLinks();
        initResizeHandler();
        // Tooltips após Bootstrap carregar (está no footer)
        setTimeout(initTooltips, 100);
    });

})();
