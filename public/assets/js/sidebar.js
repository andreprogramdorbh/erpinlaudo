(function () {
    if (window.__erpSidebarInitialized) return;
    window.__erpSidebarInitialized = true;

    function getSidebarEls() {
        return {
            sidebar: document.getElementById('mainSidebar'),
            layoutWrapper: document.querySelector('.layout-wrapper'),
        };
    }

    function applySidebarState() {
        const { sidebar, layoutWrapper } = getSidebarEls();
        if (!sidebar || !layoutWrapper) return;

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            layoutWrapper.classList.add('sidebar-collapsed');
        }
    }

    function toggleSidebar() {
        const { sidebar, layoutWrapper } = getSidebarEls();
        if (!sidebar || !layoutWrapper) return;

        sidebar.classList.toggle('collapsed');
        layoutWrapper.classList.toggle('sidebar-collapsed');

        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    function initTooltips() {
        if (typeof bootstrap === 'undefined') return;

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function initSubmenuBehavior() {
        if (typeof window.jQuery === 'undefined') return;

        window.jQuery('.nav-item.has-submenu > .nav-link').on('click', function (e) {
            const { sidebar } = getSidebarEls();
            if (sidebar && sidebar.classList.contains('collapsed')) return;

            e.preventDefault();
            window.jQuery(this).parent().toggleClass('open');
        });
    }

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
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/logout';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
            return;
        }

        if (confirm('Deseja realmente sair do sistema?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            document.body.appendChild(form);
            form.submit();
        }
    }

    if (typeof window.toggleSidebar === 'undefined') {
        window.toggleSidebar = toggleSidebar;
    }

    if (typeof window.confirmLogout === 'undefined') {
        window.confirmLogout = confirmLogout;
    }

    document.addEventListener('DOMContentLoaded', function () {
        applySidebarState();
        initTooltips();
        initSubmenuBehavior();
    });
})();
